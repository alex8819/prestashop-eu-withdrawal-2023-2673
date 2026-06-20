<?php
/**
 * EU Withdrawal Button (Directive 2023/2673) — Pulsante di Recesso
 *
 * Free, open-source PrestaShop 8.x module that adds the EU statutory
 * "right of withdrawal" function required by Directive (EU) 2023/2673
 * (Art. 11a Consumer Rights Directive 2011/83/EU; Italy: Art. 54-bis
 * Codice del Consumo, D.Lgs. 209/2025), applicable from 19 June 2026.
 *
 * @author    alex8819 and contributors
 * @license   GPL-3.0-or-later
 * @version   0.3.1-beta
 * @link      https://github.com/alex8819/prestashop-eu-withdrawal-2023-2673
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/WithdrawalRequest.php';

class EuWithdrawal extends Module
{
    /** Statutory withdrawal button labels per ISO language (Art. 11a §1). */
    public static $LABELS = [
        'it' => 'Recedi dal contratto',
        'en' => 'Withdraw from the contract',
        'fr' => 'Se rétracter du contrat',
        'de' => 'Vertrag widerrufen',
        'es' => 'Desistir del contrato',
    ];

    /** Statutory "confirm withdrawal" function labels per ISO language (Art. 11a §3). */
    public static $CONFIRM_LABELS = [
        'it' => 'Conferma il recesso',
        'en' => 'Confirm withdrawal',
        'fr' => 'Confirmer la rétractation',
        'de' => 'Widerruf bestätigen',
        'es' => 'Confirmar el desistimiento',
    ];

    /**
     * Model withdrawal form per ISO language (Annex I-B, Directive 2011/83/EU).
     * Placeholders: %shop% %email% %items% %ordered% %name% %date%
     */
    public static $MODEL_FORM = [
        'it' => "Modulo di recesso tipo (Allegato I-B)\n(compilare e restituire il presente modulo solo se si desidera recedere dal contratto)\n— Destinatario: %shop% (%email%)\n— Con la presente io/noi (*) notifico/notifichiamo (*) il recesso dal mio/nostro (*) contratto di vendita dei seguenti beni (*):\n%items%\n— Ordinato il (*): %ordered%\n— Nome del/dei consumatore(i): %name%\n— Data: %date%\n(*) Cancellare la dicitura inutile.",
        'en' => "Model withdrawal form (Annex I-B)\n(complete and return this form only if you wish to withdraw from the contract)\n— To: %shop% (%email%)\n— I/We (*) hereby give notice that I/We (*) withdraw from my/our (*) contract of sale of the following goods (*):\n%items%\n— Ordered on (*): %ordered%\n— Name of consumer(s): %name%\n— Date: %date%\n(*) Delete as appropriate.",
        'fr' => "Formulaire type de rétractation (Annexe I-B)\n(veuillez compléter et renvoyer le présent formulaire uniquement si vous souhaitez vous rétracter du contrat)\n— À l'attention de : %shop% (%email%)\n— Je/Nous (*) vous notifie/notifions (*) par la présente ma/notre (*) rétractation du contrat portant sur la vente des biens suivants (*) :\n%items%\n— Commandé le (*) : %ordered%\n— Nom du/des consommateur(s) : %name%\n— Date : %date%\n(*) Rayez la mention inutile.",
        'de' => "Muster-Widerrufsformular (Anhang I-B)\n(Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus und senden Sie es zurück)\n— An: %shop% (%email%)\n— Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über den Kauf der folgenden Waren (*):\n%items%\n— Bestellt am (*): %ordered%\n— Name des/der Verbraucher(s): %name%\n— Datum: %date%\n(*) Unzutreffendes streichen.",
        'es' => "Modelo de formulario de desistimiento (Anexo I-B)\n(sólo debe cumplimentar y enviar el presente formulario si desea desistir del contrato)\n— A la atención de: %shop% (%email%)\n— Por la presente le comunico/comunicamos (*) que desisto/desistimos (*) de mi/nuestro (*) contrato de venta de los siguientes bienes (*):\n%items%\n— Pedido el (*): %ordered%\n— Nombre del consumidor(es): %name%\n— Fecha: %date%\n(*) Táchese lo que no proceda.",
    ];

    const ADMIN_CONTROLLER = 'AdminEuWithdrawal';

    /**
     * Statutory withdrawal exemptions (Art. 16 CRD 2011/83/EU / Art. 59 Codice del Consumo)
     * keyed by reason, then ISO language. Shown when withdrawal does not apply.
     */
    public static $EXEMPTIONS = [
        'custom' => [
            'it' => 'Beni confezionati su misura o chiaramente personalizzati.',
            'en' => 'Goods made to the consumer\'s specifications or clearly personalised.',
            'fr' => 'Biens confectionnés selon les spécifications du consommateur ou nettement personnalisés.',
            'de' => 'Waren, die nach Kundenspezifikation angefertigt oder eindeutig personalisiert wurden.',
            'es' => 'Bienes confeccionados conforme a las especificaciones del consumidor o claramente personalizados.',
        ],
        'hygiene' => [
            'it' => 'Beni sigillati non idonei alla restituzione per motivi igienici o di salute, aperti dopo la consegna.',
            'en' => 'Sealed goods unsuitable for return for health/hygiene reasons, unsealed after delivery.',
            'fr' => 'Biens scellés ne pouvant être renvoyés pour des raisons d\'hygiène ou de santé, descellés après la livraison.',
            'de' => 'Versiegelte Waren, die aus Gründen des Gesundheitsschutzes/der Hygiene nicht zur Rückgabe geeignet sind und nach der Lieferung entsiegelt wurden.',
            'es' => 'Bienes precintados no aptos para devolución por razones de higiene o salud, desprecintados tras la entrega.',
        ],
        'perishable' => [
            'it' => 'Beni che rischiano di deteriorarsi o scadere rapidamente.',
            'en' => 'Goods liable to deteriorate or expire rapidly.',
            'fr' => 'Biens susceptibles de se détériorer ou de se périmer rapidement.',
            'de' => 'Waren, die schnell verderben können oder deren Verfallsdatum schnell überschritten würde.',
            'es' => 'Bienes que puedan deteriorarse o caducar con rapidez.',
        ],
        'sealed_media' => [
            'it' => 'Registrazioni audio/video o software informatici sigillati e aperti dopo la consegna.',
            'en' => 'Sealed audio/video recordings or computer software, unsealed after delivery.',
            'fr' => 'Enregistrements audio/vidéo ou logiciels informatiques scellés, descellés après la livraison.',
            'de' => 'Versiegelte Ton-/Videoaufnahmen oder Computersoftware, die nach der Lieferung entsiegelt wurden.',
            'es' => 'Grabaciones de audio/vídeo o programas informáticos precintados, desprecintados tras la entrega.',
        ],
        'digital' => [
            'it' => 'Contenuto digitale non su supporto materiale, la cui esecuzione è iniziata con il consenso e la rinuncia al recesso.',
            'en' => 'Digital content not on a tangible medium, where performance began with consent and waiver of withdrawal.',
            'fr' => 'Contenu numérique non fourni sur support matériel, dont l\'exécution a commencé avec accord et renonciation à la rétractation.',
            'de' => 'Digitale Inhalte nicht auf einem körperlichen Datenträger, deren Ausführung mit Zustimmung und Verzicht auf das Widerrufsrecht begonnen hat.',
            'es' => 'Contenido digital no prestado en soporte material, cuya ejecución comenzó con consentimiento y renuncia al desistimiento.',
        ],
    ];

    public function __construct()
    {
        $this->name = 'euwithdrawal';
        $this->tab = 'front_office_features';
        $this->version = '0.3.1';
        $this->author = 'alex8819';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PsRecessoFacile EU — Pulsante di Recesso (Direttiva 2023/2673)');
        $this->description = $this->l('Aggiunge la funzione di recesso digitale conforme alla Direttiva UE 2023/2673 (Art. 54-bis Codice del Consumo / Art. 11a CRD): pulsante in area cliente e per ospiti, flusso a due step, ricevuta su supporto durevole, gestione in backoffice.');
        $this->confirmUninstall = $this->l('Sicuro di voler disinstallare? Le richieste di recesso registrate verranno eliminate.');
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && WithdrawalRequest::createTables()
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayOrderDetail')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->installTab()
            && $this->installDefaultConfig();
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->deleteConfig()
            && WithdrawalRequest::dropTables()
            && parent::uninstall();
    }

    protected function installDefaultConfig()
    {
        return Configuration::updateValue('EUW_ENABLED', 1)
            && Configuration::updateValue('EUW_PERIOD_DAYS', 14)
            && Configuration::updateValue('EUW_DATE_SOURCE', 'delivery')
            && Configuration::updateValue('EUW_ALLOW_GUEST', 1)
            && Configuration::updateValue('EUW_MERCHANT_EMAIL', Configuration::get('PS_SHOP_EMAIL'))
            && Configuration::updateValue('EUW_ELIGIBLE_STATES', '')
            && Configuration::updateValue('EUW_EXEMPT_CATEGORIES', '')
            && Configuration::updateValue('EUW_EXEMPT_REASON', 'custom');
    }

    protected function deleteConfig()
    {
        foreach (['EUW_ENABLED', 'EUW_PERIOD_DAYS', 'EUW_DATE_SOURCE', 'EUW_ALLOW_GUEST', 'EUW_MERCHANT_EMAIL', 'EUW_ELIGIBLE_STATES', 'EUW_BUTTON_LABEL', 'EUW_EXEMPT_CATEGORIES', 'EUW_EXEMPT_REASON'] as $k) {
            Configuration::deleteByName($k);
        }

        return true;
    }

    protected function installTab()
    {
        if (Tab::getIdFromClassName(self::ADMIN_CONTROLLER)) {
            return true;
        }

        // Sotto "Servizio clienti" (dove stanno i Resi); fallback Ordini, poi root.
        $parentId = (int) Tab::getIdFromClassName('AdminParentCustomerThreads');
        if (!$parentId) {
            $parentId = (int) Tab::getIdFromClassName('AdminParentOrders');
        }

        $tab = new Tab();
        $tab->class_name = self::ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->id_parent = $parentId ?: 0;
        $tab->icon = 'assignment_return';
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Recesso (UE 2023/2673)';
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab()
    {
        $id = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);
        if ($id) {
            $tab = new Tab($id);

            return (bool) $tab->delete();
        }

        return true;
    }

    /* ------------------------------------------------------------------ *
     *  Helpers (eligibility / dates / labels)                            *
     * ------------------------------------------------------------------ */

    public function featureEnabled()
    {
        return (bool) Configuration::get('EUW_ENABLED');
    }

    public function getPeriodDays()
    {
        return max(1, (int) Configuration::get('EUW_PERIOD_DAYS'));
    }

    /** Statutory label for the given language id; admin override wins if set. */
    public function getStatutoryLabel($idLang = null)
    {
        $idLang = (int) ($idLang ?: $this->context->language->id);

        $override = Configuration::get('EUW_BUTTON_LABEL', $idLang);
        if ($override !== false && trim((string) $override) !== '') {
            return $override;
        }

        $iso = strtolower(Language::getIsoById($idLang) ?: 'en');

        return isset(self::$LABELS[$iso]) ? self::$LABELS[$iso] : self::$LABELS['en'];
    }

    /** Filled Annex I-B model withdrawal form for the given language. */
    public function getModelForm($idLang, array $params)
    {
        $iso = strtolower(Language::getIsoById((int) $idLang) ?: 'en');
        $tpl = isset(self::$MODEL_FORM[$iso]) ? self::$MODEL_FORM[$iso] : self::$MODEL_FORM['en'];

        return str_replace(
            ['%shop%', '%email%', '%items%', '%ordered%', '%name%', '%date%'],
            [
                $params['shop'] ?? Configuration::get('PS_SHOP_NAME'),
                $params['email'] ?? Configuration::get('PS_SHOP_EMAIL'),
                $params['items'] ?? '',
                $params['ordered'] ?? '',
                $params['name'] ?? '',
                $params['date'] ?? date('d/m/Y'),
            ],
            $tpl
        );
    }

    /** Statutory "confirm withdrawal" label (Art. 11a §3), with safe fallback. */
    public function getConfirmLabel($idLang = null)
    {
        $iso = strtolower(Language::getIsoById((int) ($idLang ?: $this->context->language->id)) ?: 'en');

        return isset(self::$CONFIRM_LABELS[$iso]) ? self::$CONFIRM_LABELS[$iso] : self::$CONFIRM_LABELS['en'];
    }

    /* --------------------- exemptions (Art. 16 / 59) --------------------- */

    /** Configured exempt category ids. */
    public function getExemptCategoryIds()
    {
        return array_filter(array_map('intval', explode(',', (string) Configuration::get('EUW_EXEMPT_CATEGORIES'))));
    }

    /** True if a product belongs to a configured exempt category. */
    public function isProductExempt($idProduct)
    {
        $exempt = $this->getExemptCategoryIds();
        if (!$exempt) {
            return false;
        }
        $cats = Product::getProductCategories((int) $idProduct);

        return (bool) array_intersect($exempt, array_map('intval', $cats));
    }

    /** Localised text of the configured exemption reason. */
    public function getExemptionText($idLang = null)
    {
        $reason = (string) Configuration::get('EUW_EXEMPT_REASON') ?: 'custom';
        if (!isset(self::$EXEMPTIONS[$reason])) {
            return '';
        }
        $iso = strtolower(Language::getIsoById((int) ($idLang ?: $this->context->language->id)) ?: 'en');
        $set = self::$EXEMPTIONS[$reason];

        return isset($set[$iso]) ? $set[$iso] : $set['en'];
    }

    /** Whether the order contains at least one product NOT exempt from withdrawal. */
    public function hasNonExemptProducts(Order $order)
    {
        if (!$this->getExemptCategoryIds()) {
            return true;
        }
        foreach ($order->getProducts() as $p) {
            if (!$this->isProductExempt((int) $p['product_id'])) {
                return true;
            }
        }

        return false;
    }

    /** Order products still withdrawable: not exempt AND not already withdrawn. */
    public function getWithdrawableProducts(Order $order)
    {
        $withdrawn = WithdrawalRequest::getWithdrawnOrderDetailIds((int) $order->id);
        $out = [];
        foreach ($order->getProducts() as $p) {
            if ($this->isProductExempt((int) $p['product_id'])) {
                continue;
            }
            if (in_array((int) $p['id_order_detail'], $withdrawn, true)) {
                continue;
            }
            $out[] = $p;
        }

        return $out;
    }

    /**
     * Start date of the withdrawal period for an order.
     * 'delivery' = date the order reached a delivered/shipped state (fallback to order date).
     */
    public function getPeriodStartDate(Order $order)
    {
        if (Configuration::get('EUW_DATE_SOURCE') === 'delivery') {
            $date = Db::getInstance()->getValue('
                SELECT oh.date_add
                FROM ' . _DB_PREFIX_ . 'order_history oh
                JOIN ' . _DB_PREFIX_ . 'order_state os ON os.id_order_state = oh.id_order_state
                WHERE oh.id_order = ' . (int) $order->id . '
                  AND (os.delivery = 1 OR os.shipped = 1)
                ORDER BY os.delivery DESC, oh.date_add DESC');
            if ($date) {
                return $date;
            }
        }

        return $order->date_add;
    }

    /** UNIX timestamp until which withdrawal is allowed. */
    public function getDeadlineTs(Order $order)
    {
        return strtotime($this->getPeriodStartDate($order)) + ($this->getPeriodDays() * 86400);
    }

    /** Whether the order is eligible for a (new) withdrawal request right now. */
    public function isOrderEligible(Order $order)
    {
        if (!$this->featureEnabled() || !Validate::isLoadedObject($order)) {
            return false;
        }
        if (!$order->valid) {
            return false;
        }

        $states = trim((string) Configuration::get('EUW_ELIGIBLE_STATES'));
        if ($states !== '') {
            $allowed = array_filter(array_map('intval', explode(',', $states)));
            if ($allowed && !in_array((int) $order->getCurrentState(), $allowed, true)) {
                return false;
            }
        }

        if (time() > $this->getDeadlineTs($order)) {
            return false;
        }

        // Idoneo solo se resta almeno un prodotto recedibile
        // (non esente Art. 16/59 e non già oggetto di un recesso non rifiutato).
        if (!count($this->getWithdrawableProducts($order))) {
            return false;
        }

        return true;
    }

    /** Per-order security token for the front controller links. */
    public function getOrderToken($idOrder)
    {
        return substr(Tools::hash('euw' . (int) $idOrder . _COOKIE_KEY_), 0, 24);
    }

    public function getWithdrawalUrl($idOrder, array $extra = [])
    {
        return $this->context->link->getModuleLink($this->name, 'withdrawal', array_merge([
            'action' => 'form',
            'id_order' => (int) $idOrder,
            'token' => $this->getOrderToken((int) $idOrder),
        ], $extra));
    }

    public function getGuestLookupUrl()
    {
        return $this->context->link->getModuleLink($this->name, 'withdrawal', ['action' => 'lookup']);
    }

    /* ------------------------------------------------------------------ *
     *  Hooks                                                             *
     * ------------------------------------------------------------------ */

    public function hookActionFrontControllerSetMedia()
    {
        // Carica gli asset SOLO nelle pagine pertinenti (account/ordini e controller del modulo).
        // Mai sulle pagine pubbliche cachate (home, categorie, prodotti) -> nessun impatto SEO/performance.
        $controller = Tools::getValue('controller');
        $isModulePage = ($controller === 'withdrawal')
            || (isset($this->context->controller->module) && $this->context->controller->module && $this->context->controller->module->name === $this->name);

        if ($isModulePage || in_array($controller, ['order-detail', 'history', 'my-account', 'identity'], true)) {
            $this->context->controller->registerStylesheet(
                'euwithdrawal-css',
                'modules/' . $this->name . '/views/css/withdrawal.css',
                ['media' => 'all', 'priority' => 200]
            );
            $this->context->controller->registerJavascript(
                'euwithdrawal-js',
                'modules/' . $this->name . '/views/js/withdrawal.js',
                ['position' => 'bottom', 'priority' => 200]
            );
        }
    }

    public function hookDisplayCustomerAccount($params)
    {
        if (!$this->featureEnabled()) {
            return '';
        }

        $this->context->smarty->assign([
            'euw_lookup_url' => $this->getGuestLookupUrl(),
            'euw_label' => $this->getStatutoryLabel(),
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/customer-account.tpl');
    }

    public function hookDisplayOrderDetail($params)
    {
        if (!$this->featureEnabled() || empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $eligible = $this->isOrderEligible($order);
        $deadlineTs = $this->getDeadlineTs($order);
        $allExempt = !$this->hasNonExemptProducts($order);
        // "Già richiesto" = esiste una richiesta non rifiutata e non resta nulla da recedere.
        $alreadyRequested = WithdrawalRequest::hasActiveRequest((int) $order->id)
            && !count($this->getWithdrawableProducts($order))
            && !$allExempt;

        $this->context->smarty->assign([
            'euw_eligible' => $eligible,
            'euw_already' => $alreadyRequested,
            'euw_expired' => (time() > $deadlineTs),
            'euw_exempt' => $allExempt,
            'euw_exempt_text' => $allExempt ? $this->getExemptionText() : '',
            'euw_deadline' => date('d/m/Y', $deadlineTs),
            'euw_label' => $this->getStatutoryLabel(),
            'euw_url' => $this->getWithdrawalUrl((int) $order->id),
            'euw_reference' => $order->reference,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/order-detail.tpl');
    }

    /* ------------------------------------------------------------------ *
     *  Back-office configuration                                          *
     * ------------------------------------------------------------------ */

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitEuw')) {
            Configuration::updateValue('EUW_ENABLED', (int) Tools::getValue('EUW_ENABLED'));
            Configuration::updateValue('EUW_PERIOD_DAYS', max(1, (int) Tools::getValue('EUW_PERIOD_DAYS')));
            Configuration::updateValue('EUW_DATE_SOURCE', in_array(Tools::getValue('EUW_DATE_SOURCE'), ['delivery', 'order'], true) ? Tools::getValue('EUW_DATE_SOURCE') : 'delivery');
            Configuration::updateValue('EUW_ALLOW_GUEST', (int) Tools::getValue('EUW_ALLOW_GUEST'));
            $email = trim((string) Tools::getValue('EUW_MERCHANT_EMAIL'));
            if ($email === '' || Validate::isEmail($email)) {
                Configuration::updateValue('EUW_MERCHANT_EMAIL', $email);
            }
            // HelperForm posta i checkbox come EUW_ELIGIBLE_STATES_<id>: li raccogliamo singolarmente.
            $states = [];
            foreach (OrderState::getOrderStates((int) $this->context->language->id) as $s) {
                if ((int) Tools::getValue('EUW_ELIGIBLE_STATES_' . (int) $s['id_order_state'])) {
                    $states[] = (int) $s['id_order_state'];
                }
            }
            Configuration::updateValue('EUW_ELIGIBLE_STATES', implode(',', $states));

            // Etichetta pulsante personalizzabile (multilingua, override facoltativo)
            $labels = [];
            foreach (Language::getLanguages(false) as $lang) {
                $labels[(int) $lang['id_lang']] = Tools::getValue('EUW_BUTTON_LABEL_' . (int) $lang['id_lang'], '');
            }
            Configuration::updateValue('EUW_BUTTON_LABEL', $labels);

            // Esenzioni (Art. 16/59)
            $exempt = Tools::getValue('EUW_EXEMPT_CATEGORIES');
            $exempt = is_array($exempt)
                ? implode(',', array_map('intval', $exempt))
                : implode(',', array_filter(array_map('intval', explode(',', (string) $exempt))));
            Configuration::updateValue('EUW_EXEMPT_CATEGORIES', $exempt);
            $reason = Tools::getValue('EUW_EXEMPT_REASON');
            Configuration::updateValue('EUW_EXEMPT_REASON', array_key_exists($reason, self::$EXEMPTIONS) ? $reason : 'custom');

            $output .= $this->displayConfirmation($this->l('Impostazioni salvate.'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $stateOptions = [];
        foreach (OrderState::getOrderStates((int) $this->context->language->id) as $s) {
            $stateOptions[] = ['id_state' => (int) $s['id_order_state'], 'name' => $s['name']];
        }
        $selectedStates = array_filter(array_map('intval', explode(',', (string) Configuration::get('EUW_ELIGIBLE_STATES'))));

        $iso = strtolower(Language::getIsoById((int) $this->context->language->id) ?: 'en');
        $reasonOptions = [];
        foreach (self::$EXEMPTIONS as $key => $set) {
            $reasonOptions[] = ['id' => $key, 'name' => isset($set[$iso]) ? $set[$iso] : $set['en']];
        }

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->l('Impostazioni recesso'), 'icon' => 'icon-gavel'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => $this->l('Attivo'), 'name' => 'EUW_ENABLED', 'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Sì')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'text', 'label' => $this->l('Giorni periodo di recesso'), 'name' => 'EUW_PERIOD_DAYS',
                        'class' => 'fixed-width-sm', 'desc' => $this->l('Predefinito di legge: 14 giorni.'),
                    ],
                    [
                        'type' => 'select', 'label' => $this->l('Decorrenza periodo'), 'name' => 'EUW_DATE_SOURCE',
                        'options' => ['query' => [
                            ['id' => 'delivery', 'name' => $this->l('Dalla consegna (consigliato per beni)')],
                            ['id' => 'order', 'name' => $this->l('Dalla data ordine')],
                        ], 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type' => 'switch', 'label' => $this->l('Consenti recesso agli ospiti'), 'name' => 'EUW_ALLOW_GUEST', 'is_bool' => true,
                        'desc' => $this->l('Abilita la pagina di lookup (n° ordine + email) per i clienti non registrati.'),
                        'values' => [
                            ['id' => 'gon', 'value' => 1, 'label' => $this->l('Sì')],
                            ['id' => 'goff', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'text', 'label' => $this->l('Email negozio per notifiche'), 'name' => 'EUW_MERCHANT_EMAIL',
                        'desc' => $this->l('Lascia vuoto per usare l\'email del negozio.'),
                    ],
                    [
                        'type' => 'text', 'label' => $this->l('Etichetta pulsante (override)'), 'name' => 'EUW_BUTTON_LABEL',
                        'lang' => true,
                        'desc' => $this->l('Lascia vuoto per usare la dicitura statutaria per lingua (es. "Recedi dal contratto").'),
                    ],
                    [
                        'type' => 'text', 'label' => $this->l('Categorie esenti dal recesso (ID)'), 'name' => 'EUW_EXEMPT_CATEGORIES',
                        'desc' => $this->l('ID delle categorie (foglia) a cui i prodotti sono direttamente associati e che sono escluse dal recesso (Art. 16/59), separati da virgola. Vuoto = nessuna esenzione.'),
                    ],
                    [
                        'type' => 'select', 'label' => $this->l('Motivo esenzione'), 'name' => 'EUW_EXEMPT_REASON',
                        'options' => ['query' => $reasonOptions, 'id' => 'id', 'name' => 'name'],
                        'desc' => $this->l('Testo legale mostrato al cliente quando il recesso non si applica.'),
                    ],
                    [
                        'type' => 'checkbox', 'label' => $this->l('Stati ordine idonei'), 'name' => 'EUW_ELIGIBLE_STATES',
                        'desc' => $this->l('Se non selezioni nulla, vale qualsiasi ordine valido (pagato).'),
                        'values' => [
                            'query' => array_map(function ($o) use ($selectedStates) {
                                return ['id' => $o['id_state'], 'name' => $o['name'], 'val' => $o['id_state']];
                            }, $stateOptions),
                            'id' => 'id', 'name' => 'name',
                        ],
                    ],
                ],
                'submit' => ['title' => $this->l('Salva')],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitEuw';
        $helper->languages = Language::getLanguages(false);
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');

        $values = [
            'EUW_ENABLED' => (int) Configuration::get('EUW_ENABLED'),
            'EUW_PERIOD_DAYS' => (int) Configuration::get('EUW_PERIOD_DAYS'),
            'EUW_DATE_SOURCE' => Configuration::get('EUW_DATE_SOURCE'),
            'EUW_ALLOW_GUEST' => (int) Configuration::get('EUW_ALLOW_GUEST'),
            'EUW_MERCHANT_EMAIL' => Configuration::get('EUW_MERCHANT_EMAIL'),
            'EUW_EXEMPT_CATEGORIES' => Configuration::get('EUW_EXEMPT_CATEGORIES'),
            'EUW_EXEMPT_REASON' => Configuration::get('EUW_EXEMPT_REASON') ?: 'custom',
        ];
        foreach ($selectedStates as $sid) {
            $values['EUW_ELIGIBLE_STATES_' . $sid] = 1;
        }
        $values['EUW_BUTTON_LABEL'] = [];
        foreach (Language::getLanguages(false) as $lang) {
            $values['EUW_BUTTON_LABEL'][(int) $lang['id_lang']] = Configuration::get('EUW_BUTTON_LABEL', (int) $lang['id_lang']) ?: '';
        }
        $helper->tpl_vars = ['fields_value' => $values];

        return $helper->generateForm([$fields_form]);
    }
}
