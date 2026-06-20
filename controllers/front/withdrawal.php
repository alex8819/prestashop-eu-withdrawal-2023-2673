<?php
/**
 * Front controller: withdrawal flow (customer + guest), two-step, durable receipt.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/** Control-flow stop used to render an error page and halt processing. */
class EuwStop extends Exception
{
}

class EuWithdrawalWithdrawalModuleFrontController extends ModuleFrontController
{
    /** @var EuWithdrawal */
    public $module;

    public function init()
    {
        parent::init();
        // SEO + cache safety: queste pagine non devono MAI essere indicizzate né cachate.
        header('X-Robots-Tag: noindex, nofollow, noarchive', true);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
        header('Pragma: no-cache', true);
    }

    public function initContent()
    {
        parent::initContent();

        try {
            if (!$this->module->featureEnabled()) {
                $this->fail($this->module->l('La funzione di recesso non è attiva.', 'withdrawal'));
            }

            switch (Tools::getValue('action', 'form')) {
                case 'lookup':
                    $this->processLookup();
                    break;
                case 'review':
                    $this->processReview();
                    break;
                case 'submit':
                    $this->processSubmit();
                    break;
                case 'success':
                    $this->setTemplate('module:euwithdrawal/views/templates/front/success.tpl');
                    break;
                case 'form':
                default:
                    $this->processForm();
                    break;
            }
        } catch (EuwStop $e) {
            // L'errore è già stato assegnato e il template impostato in fail().
        }
    }

    /* ------------------------------------------------------------------ *
     *  Guest lookup (order reference + email)                            *
     * ------------------------------------------------------------------ */

    protected function processLookup()
    {
        if (!Configuration::get('EUW_ALLOW_GUEST')) {
            $this->fail($this->module->l('Il recesso per ospiti non è abilitato. Accedi al tuo account.', 'withdrawal'));
        }

        $error = '';
        if (Tools::isSubmit('euw_lookup')) {
            $reference = trim(Tools::getValue('reference'));
            $email = trim(Tools::getValue('email'));

            $order = $this->findOrderByReference($reference);
            if ($order && $this->orderEmailMatches($order, $email)) {
                $this->rememberGuest((int) $order->id, $email);
                Tools::redirect($this->module->getWithdrawalUrl((int) $order->id, ['g' => $this->guestToken((int) $order->id, $email)]));
            } else {
                $error = $this->module->l('Ordine non trovato o email non corrispondente.', 'withdrawal');
            }
        }

        $this->context->smarty->assign([
            'euw_action_url' => $this->context->link->getModuleLink('euwithdrawal', 'withdrawal', ['action' => 'lookup']),
            'euw_error' => $error,
            'euw_reference' => Tools::getValue('reference', ''),
        ]);
        $this->setTemplate('module:euwithdrawal/views/templates/front/guest-lookup.tpl');
    }

    /* ------------------------------------------------------------------ *
     *  Step 1 — declaration form                                          *
     * ------------------------------------------------------------------ */

    protected function processForm()
    {
        list($order, $source) = $this->resolveOrder();

        if (!$this->module->isOrderEligible($order)) {
            $this->fail($this->module->l('Questo ordine non è (più) idoneo al recesso.', 'withdrawal'));
        }

        $this->assignOrderProducts($order);
        $this->context->smarty->assign([
            'euw_order_reference' => $order->reference,
            'euw_label' => $this->module->getStatutoryLabel(),
            'euw_deadline' => date('d/m/Y', $this->module->getDeadlineTs($order)),
            // Step 1 invia alla pagina di revisione (conferma a 2 step reale)
            'euw_action_url' => $this->context->link->getModuleLink('euwithdrawal', 'withdrawal', [
                'action' => 'review',
                'id_order' => (int) $order->id,
                'token' => Tools::getValue('token'),
                'g' => Tools::getValue('g'),
            ]),
            'euw_source' => $source,
        ]);
        $this->setTemplate('module:euwithdrawal/views/templates/front/form.tpl');
    }

    /* ------------------------------------------------------------------ *
     *  Step 2 — submit                                                    *
     * ------------------------------------------------------------------ */

    protected function processSubmit()
    {
        if (!Tools::isSubmit('euw_confirm')) {
            Tools::redirect($this->module->getWithdrawalUrl((int) Tools::getValue('id_order'), ['g' => Tools::getValue('g')]));
        }

        list($order, $source) = $this->resolveOrder();

        if (!$this->module->isOrderEligible($order)) {
            $this->fail($this->module->l('Questo ordine non è (più) idoneo al recesso.', 'withdrawal'));
        }

        // Conferma esplicita obbligatoria (doppia conferma: step dedicato + checkbox)
        if (!Tools::getValue('euw_acknowledge')) {
            $this->fail($this->module->l('Devi confermare la richiesta di recesso.', 'withdrawal'));
        }

        list($type, $items) = $this->getSelectedItems($order);

        $customer = new Customer((int) $order->id_customer);
        $email = Validate::isLoadedObject($customer) ? $customer->email : '';
        $firstname = Validate::isLoadedObject($customer) ? $customer->firstname : '';
        $lastname = Validate::isLoadedObject($customer) ? $customer->lastname : '';

        $declaration = $this->buildDeclaration($order, $firstname, $lastname, $items, $type);

        $wr = new WithdrawalRequest();
        $wr->id_order = (int) $order->id;
        $wr->id_customer = (int) $order->id_customer;
        $wr->order_reference = $order->reference;
        $wr->customer_firstname = $firstname;
        $wr->customer_lastname = $lastname;
        $wr->customer_email = $email;
        $wr->type = $type;
        $wr->status = WithdrawalRequest::STATUS_PENDING;
        $wr->source = $source;
        $wr->id_lang = (int) $this->context->language->id;
        $wr->declaration = $declaration;
        $wr->ip = $this->anonymizeIp(Tools::getRemoteAddr());

        if (!$wr->add()) {
            $this->fail($this->module->l('Errore durante il salvataggio della richiesta. Riprova.', 'withdrawal'));
        }
        $wr->saveItems($items);

        $this->addOrderMessage($order, $declaration);
        $this->sendEmails($order, $wr, $items);

        Tools::redirect($this->context->link->getModuleLink('euwithdrawal', 'withdrawal', ['action' => 'success']));
    }

    /* ------------------------------------------------------------------ *
     *  Step 1.5 — review & confirm (genuine two-step, Art. 11a §3)        *
     * ------------------------------------------------------------------ */

    protected function processReview()
    {
        list($order, $source) = $this->resolveOrder();

        if (!$this->module->isOrderEligible($order)) {
            $this->fail($this->module->l('Questo ordine non è (più) idoneo al recesso.', 'withdrawal'));
        }

        list($type, $items) = $this->getSelectedItems($order);

        $customer = new Customer((int) $order->id_customer);
        $firstname = Validate::isLoadedObject($customer) ? $customer->firstname : '';
        $lastname = Validate::isLoadedObject($customer) ? $customer->lastname : '';

        $this->context->smarty->assign([
            'euw_order_reference' => $order->reference,
            'euw_confirm_label' => $this->module->getConfirmLabel(),
            'euw_deadline' => date('d/m/Y', $this->module->getDeadlineTs($order)),
            'euw_type' => $type,
            'euw_items' => $items,
            'euw_declaration' => $this->buildDeclaration($order, $firstname, $lastname, $items, $type),
            'euw_submit_url' => $this->context->link->getModuleLink('euwithdrawal', 'withdrawal', [
                'action' => 'submit',
                'id_order' => (int) $order->id,
                'token' => Tools::getValue('token'),
                'g' => Tools::getValue('g'),
            ]),
            'euw_back_url' => $this->module->getWithdrawalUrl((int) $order->id, ['g' => Tools::getValue('g')]),
        ]);
        $this->setTemplate('module:euwithdrawal/views/templates/front/review.tpl');
    }

    /** Parse euw_type + items from the request into [type, items[]]. */
    protected function getSelectedItems(Order $order)
    {
        $type = Tools::getValue('euw_type') === 'partial' ? 'partial' : 'full';
        $products = $order->getProducts();
        $items = [];

        if ($type === 'partial') {
            $selected = array_map('strval', (array) Tools::getValue('items'));
            foreach ($products as $p) {
                $idod = (int) $p['id_order_detail'];
                if (in_array((string) $idod, $selected, true)) {
                    $qty = (int) Tools::getValue('qty_' . $idod, $p['product_quantity']);
                    $qty = max(1, min($qty, (int) $p['product_quantity']));
                    $items[] = [
                        'id_order_detail' => $idod,
                        'product_name' => $p['product_name'],
                        'product_reference' => $p['product_reference'],
                        'quantity' => $qty,
                    ];
                }
            }
            if (!$items) {
                $this->fail($this->module->l('Seleziona almeno un prodotto per il recesso parziale.', 'withdrawal'));
            }
        } else {
            foreach ($products as $p) {
                $items[] = [
                    'id_order_detail' => (int) $p['id_order_detail'],
                    'product_name' => $p['product_name'],
                    'product_reference' => $p['product_reference'],
                    'quantity' => (int) $p['product_quantity'],
                ];
            }
        }

        return [$type, $items];
    }

    /* ------------------------------------------------------------------ *
     *  Helpers                                                            *
     * ------------------------------------------------------------------ */

    /** Resolve the order from context (logged customer or verified guest). */
    protected function resolveOrder()
    {
        $idOrder = (int) Tools::getValue('id_order');
        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            $this->fail($this->module->l('Ordine non trovato.', 'withdrawal'));
        }

        // Cliente loggato proprietario
        if ($this->context->customer->isLogged()
            && (int) $order->id_customer === (int) $this->context->customer->id
            && Tools::getValue('token') === $this->module->getOrderToken($idOrder)) {
            return [$order, 'account'];
        }

        // Ospite verificato (lookup precedente in sessione)
        if (Configuration::get('EUW_ALLOW_GUEST')) {
            $email = $this->getRememberedGuestEmail($idOrder);
            if ($email
                && Tools::getValue('g') === $this->guestToken($idOrder, $email)
                && $this->orderEmailMatches($order, $email)) {
                return [$order, 'guest'];
            }
        }

        $this->fail($this->module->l('Accesso non autorizzato a questo ordine. Usa il link corretto o accedi al tuo account.', 'withdrawal'));
    }

    protected function assignOrderProducts(Order $order)
    {
        $rows = [];
        foreach ($order->getProducts() as $p) {
            $rows[] = [
                'id_order_detail' => (int) $p['id_order_detail'],
                'name' => $p['product_name'],
                'reference' => $p['product_reference'],
                'quantity' => (int) $p['product_quantity'],
            ];
        }
        $this->context->smarty->assign('euw_products', $rows);
    }

    protected function buildDeclaration(Order $order, $firstname, $lastname, array $items, $type)
    {
        $lines = [];
        foreach ($items as $it) {
            $lines[] = '- ' . $it['quantity'] . '× ' . $it['product_name']
                . ($it['product_reference'] ? ' (' . $it['product_reference'] . ')' : '');
        }
        $tpl = $this->module->l('Con la presente notifico il recesso dal mio contratto di vendita relativo ai seguenti beni (ordine %ref% del %date%):', 'withdrawal');
        $head = str_replace(['%ref%', '%date%'], [$order->reference, Tools::displayDate($order->date_add)], $tpl);

        $statement = $head . "\n" . implode("\n", $lines)
            . "\n\n" . $this->module->l('Tipo di recesso', 'withdrawal') . ': '
            . ($type === 'partial' ? $this->module->l('parziale', 'withdrawal') : $this->module->l('totale', 'withdrawal'))
            . "\n" . $this->module->l('Cliente', 'withdrawal') . ': ' . trim($firstname . ' ' . $lastname)
            . "\n" . $this->module->l('Data della richiesta', 'withdrawal') . ': ' . date('d/m/Y H:i');

        // Allegato I-B (modulo di recesso tipo) — incluso per conformità Direttiva 2011/83/UE
        $modelForm = $this->module->getModelForm((int) $this->context->language->id, [
            'items' => implode("\n", $lines),
            'ordered' => Tools::displayDate($order->date_add),
            'name' => trim($firstname . ' ' . $lastname),
            'date' => date('d/m/Y'),
        ]);

        return $statement . "\n\n— — — — — — — — — —\n" . $modelForm;
    }

    protected function addOrderMessage(Order $order, $declaration)
    {
        try {
            $msg = new Message();
            $msg->id_order = (int) $order->id;
            $msg->id_customer = (int) $order->id_customer;
            $msg->private = 1;
            $msg->message = Tools::substr('[RECESSO UE 2023/2673]' . "\n" . $declaration, 0, 1600);
            $msg->add();
        } catch (Exception $e) {
            // non bloccare il flusso utente per un messaggio ordine
        }
    }

    protected function sendEmails(Order $order, WithdrawalRequest $wr, array $items)
    {
        $itemsText = '';
        foreach ($items as $it) {
            $itemsText .= '• ' . $it['quantity'] . '× ' . $it['product_name'] . "\n";
        }

        $tplVars = [
            '{firstname}' => $wr->customer_firstname,
            '{lastname}' => $wr->customer_lastname,
            '{order_reference}' => $order->reference,
            '{order_date}' => Tools::displayDate($order->date_add),
            '{request_date}' => date('d/m/Y H:i'),
            '{type}' => $wr->type,
            '{items}' => nl2br(Tools::htmlentitiesUTF8($itemsText)),
            '{declaration}' => nl2br(Tools::htmlentitiesUTF8($wr->declaration)),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
        ];

        $mailDir = _PS_MODULE_DIR_ . $this->module->name . '/mails/';

        // Ricevuta al consumatore (supporto durevole)
        if ($wr->customer_email) {
            Mail::Send(
                (int) $this->context->language->id,
                'withdrawal_confirmation',
                $this->module->l('Conferma di recesso — ordine', 'withdrawal') . ' ' . $order->reference,
                $tplVars,
                $wr->customer_email,
                trim($wr->customer_firstname . ' ' . $wr->customer_lastname),
                null, null, null, null,
                $mailDir, false, (int) $order->id_shop
            );
        }

        // Notifica al negozio
        $merchant = Configuration::get('EUW_MERCHANT_EMAIL');
        if (!$merchant || !Validate::isEmail($merchant)) {
            $merchant = Configuration::get('PS_SHOP_EMAIL');
        }
        if ($merchant && Validate::isEmail($merchant)) {
            Mail::Send(
                (int) $this->context->language->id,
                'withdrawal_notification',
                $this->module->l('Nuova richiesta di recesso — ordine', 'withdrawal') . ' ' . $order->reference,
                $tplVars,
                $merchant,
                Configuration::get('PS_SHOP_NAME'),
                null, null, null, null,
                $mailDir, false, (int) $order->id_shop
            );
        }
    }

    /* --------------------------- guest utils --------------------------- */

    protected function findOrderByReference($reference)
    {
        $reference = pSQL(trim($reference));
        if ($reference === '') {
            return null;
        }
        $id = (int) Db::getInstance()->getValue('
            SELECT id_order FROM `' . _DB_PREFIX_ . 'orders`
            WHERE reference = "' . $reference . '" ORDER BY id_order DESC');
        if (!$id) {
            return null;
        }
        $order = new Order($id);

        return Validate::isLoadedObject($order) ? $order : null;
    }

    protected function orderEmailMatches(Order $order, $email)
    {
        $customer = new Customer((int) $order->id_customer);

        return Validate::isLoadedObject($customer)
            && Validate::isEmail($email)
            && strtolower(trim($email)) === strtolower(trim($customer->email));
    }

    protected function guestToken($idOrder, $email)
    {
        return substr(Tools::hash('euwg' . (int) $idOrder . strtolower(trim($email)) . _COOKIE_KEY_), 0, 24);
    }

    protected function rememberGuest($idOrder, $email)
    {
        $store = json_decode($this->context->cookie->euw_guest ?: '{}', true) ?: [];
        $store[(string) $idOrder] = strtolower(trim($email));
        $this->context->cookie->euw_guest = json_encode($store);
        $this->context->cookie->write();
    }

    protected function getRememberedGuestEmail($idOrder)
    {
        $store = json_decode($this->context->cookie->euw_guest ?: '{}', true) ?: [];

        return isset($store[(string) $idOrder]) ? $store[(string) $idOrder] : null;
    }

    protected function anonymizeIp($ip)
    {
        if (strpos($ip, ':') !== false) { // IPv6
            $parts = explode(':', $ip);

            return implode(':', array_slice($parts, 0, 3)) . '::';
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';

            return implode('.', $parts);
        }

        return '0.0.0.0';
    }

    protected function fail($message)
    {
        $this->context->smarty->assign('euw_error', $message);
        $this->setTemplate('module:euwithdrawal/views/templates/front/error.tpl');

        throw new EuwStop();
    }
}
