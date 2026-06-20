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
 * @version   0.1.0-beta
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

    const ADMIN_CONTROLLER = 'AdminEuWithdrawal';

    public function __construct()
    {
        $this->name = 'euwithdrawal';
        $this->tab = 'front_office_features';
        $this->version = '0.1.0';
        $this->author = 'alex8819';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EU Withdrawal Button (Directive 2023/2673) — Pulsante di Recesso');
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
            && Configuration::updateValue('EUW_ELIGIBLE_STATES', '');
    }

    protected function deleteConfig()
    {
        foreach (['EUW_ENABLED', 'EUW_PERIOD_DAYS', 'EUW_DATE_SOURCE', 'EUW_ALLOW_GUEST', 'EUW_MERCHANT_EMAIL', 'EUW_ELIGIBLE_STATES'] as $k) {
            Configuration::deleteByName($k);
        }

        return true;
    }

    protected function installTab()
    {
        if (Tab::getIdFromClassName(self::ADMIN_CONTROLLER)) {
            return true;
        }

        $parentId = (int) Tab::getIdFromClassName('AdminParentOrders');

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

    /** Statutory label for the given language id, with safe fallback. */
    public function getStatutoryLabel($idLang = null)
    {
        $iso = strtolower(Language::getIsoById((int) ($idLang ?: $this->context->language->id)) ?: 'en');

        return isset(self::$LABELS[$iso]) ? self::$LABELS[$iso] : self::$LABELS['en'];
    }

    /** Statutory "confirm withdrawal" label (Art. 11a §3), with safe fallback. */
    public function getConfirmLabel($idLang = null)
    {
        $iso = strtolower(Language::getIsoById((int) ($idLang ?: $this->context->language->id)) ?: 'en');

        return isset(self::$CONFIRM_LABELS[$iso]) ? self::$CONFIRM_LABELS[$iso] : self::$CONFIRM_LABELS['en'];
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

        // already a non-rejected FULL withdrawal -> not eligible
        if (WithdrawalRequest::hasActiveFullRequest((int) $order->id)) {
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
        $alreadyRequested = WithdrawalRequest::hasActiveFullRequest((int) $order->id);

        $this->context->smarty->assign([
            'euw_eligible' => $eligible,
            'euw_already' => $alreadyRequested,
            'euw_expired' => (time() > $deadlineTs),
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
            $states = Tools::getValue('EUW_ELIGIBLE_STATES');
            $states = is_array($states) ? implode(',', array_map('intval', $states)) : '';
            Configuration::updateValue('EUW_ELIGIBLE_STATES', $states);

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
        $helper->default_form_language = (int) $this->context->language->id;

        $values = [
            'EUW_ENABLED' => (int) Configuration::get('EUW_ENABLED'),
            'EUW_PERIOD_DAYS' => (int) Configuration::get('EUW_PERIOD_DAYS'),
            'EUW_DATE_SOURCE' => Configuration::get('EUW_DATE_SOURCE'),
            'EUW_ALLOW_GUEST' => (int) Configuration::get('EUW_ALLOW_GUEST'),
            'EUW_MERCHANT_EMAIL' => Configuration::get('EUW_MERCHANT_EMAIL'),
        ];
        foreach ($selectedStates as $sid) {
            $values['EUW_ELIGIBLE_STATES_' . $sid] = 1;
        }
        $helper->tpl_vars = ['fields_value' => $values];

        return $helper->generateForm([$fields_form]);
    }
}
