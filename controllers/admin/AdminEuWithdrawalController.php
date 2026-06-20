<?php
/**
 * Back-office controller: manage withdrawal requests.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'euwithdrawal/classes/WithdrawalRequest.php';

class AdminEuWithdrawalController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'euwithdrawal_request';
        $this->className = 'WithdrawalRequest';
        $this->identifier = 'id_withdrawal';
        $this->lang = false;
        $this->explicitSelect = true;
        $this->allow_export = true;

        parent::__construct();

        $this->_select = 'CONCAT(a.customer_firstname, " ", a.customer_lastname) AS customer';

        $this->fields_list = [
            'id_withdrawal' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'verification_code' => ['title' => $this->l('Codice verifica'), 'class' => 'fixed-width-sm'],
            'order_reference' => ['title' => $this->l('Ordine')],
            'customer' => ['title' => $this->l('Cliente'), 'havingFilter' => true],
            'customer_email' => ['title' => $this->l('Email')],
            'type' => ['title' => $this->l('Tipo'), 'align' => 'center', 'callback' => 'renderType'],
            'source' => ['title' => $this->l('Origine'), 'align' => 'center'],
            'status' => ['title' => $this->l('Stato'), 'align' => 'center', 'callback' => 'renderStatus'],
            'date_add' => ['title' => $this->l('Data'), 'type' => 'datetime', 'align' => 'center'],
        ];

        $this->fields_list['type']['type'] = 'select';
        $this->fields_list['type']['filter_key'] = 'a!type';
        $this->fields_list['type']['list'] = ['full' => $this->l('Totale'), 'partial' => $this->l('Parziale')];

        $this->fields_list['status']['type'] = 'select';
        $this->fields_list['status']['filter_key'] = 'a!status';
        $this->fields_list['status']['list'] = [
            'pending' => $this->l('In attesa'),
            'processed' => $this->l('Elaborato'),
            'rejected' => $this->l('Rifiutato'),
            'refunded' => $this->l('Rimborsato'),
        ];

        $this->_defaultOrderBy = 'id_withdrawal';
        $this->_defaultOrderWay = 'DESC';
    }

    public function renderType($value)
    {
        return $value === 'partial' ? $this->l('Parziale') : $this->l('Totale');
    }

    public function renderStatus($value)
    {
        $map = [
            'pending' => ['#8a6d3b', '#fcf8e3', $this->l('In attesa')],
            'processed' => ['#31708f', '#d9edf7', $this->l('Elaborato')],
            'rejected' => ['#a94442', '#f2dede', $this->l('Rifiutato')],
            'refunded' => ['#3c763d', '#dff0d8', $this->l('Rimborsato')],
        ];
        $s = isset($map[$value]) ? $map[$value] : ['#555', '#eee', $value];

        return '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-weight:700;color:' . $s[0] . ';background:' . $s[1] . ';">' . $s[2] . '</span>';
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']); // le richieste nascono dal front-office
    }

    public function postProcess()
    {
        if (Tools::isSubmit('updateStatus') && ($id = (int) Tools::getValue('id_withdrawal'))) {
            $status = Tools::getValue('new_status');
            $allowed = [
                WithdrawalRequest::STATUS_PENDING,
                WithdrawalRequest::STATUS_PROCESSED,
                WithdrawalRequest::STATUS_REJECTED,
                WithdrawalRequest::STATUS_REFUNDED,
            ];
            if (in_array($status, $allowed, true)) {
                $wr = new WithdrawalRequest($id);
                if (Validate::isLoadedObject($wr)) {
                    $wr->status = $status;
                    $wr->update();
                    $this->confirmations[] = $this->l('Stato aggiornato.');
                }
            }
            Tools::redirectAdmin(self::$currentIndex . '&id_withdrawal=' . $id . '&vieweuwithdrawal_request&token=' . $this->token);
        }

        if (Tools::isSubmit('resendReceipt') && ($id = (int) Tools::getValue('id_withdrawal'))) {
            $this->resendReceipt($id);
            Tools::redirectAdmin(self::$currentIndex . '&id_withdrawal=' . $id . '&vieweuwithdrawal_request&token=' . $this->token);
        }

        return parent::postProcess();
    }

    protected function resendReceipt($id)
    {
        $wr = new WithdrawalRequest((int) $id);
        if (!Validate::isLoadedObject($wr) || !$wr->customer_email) {
            $this->errors[] = $this->l('Impossibile inviare la ricevuta.');

            return;
        }
        $order = new Order((int) $wr->id_order);

        $itemsText = '';
        foreach ($wr->getItems() as $it) {
            $itemsText .= '• ' . (int) $it['quantity'] . '× ' . $it['product_name'] . "\n";
        }

        $sent = Mail::Send(
            (int) $wr->id_lang,
            'withdrawal_confirmation',
            $this->l('Conferma di recesso — ordine') . ' ' . $wr->order_reference,
            [
                '{firstname}' => $wr->customer_firstname,
                '{lastname}' => $wr->customer_lastname,
                '{order_reference}' => $wr->order_reference,
                '{order_date}' => Validate::isLoadedObject($order) ? Tools::displayDate($order->date_add) : '',
                '{request_date}' => Tools::displayDate($wr->date_add),
                '{type}' => $wr->type,
                '{items}' => nl2br(Tools::htmlentitiesUTF8($itemsText)),
                '{declaration}' => nl2br(Tools::htmlentitiesUTF8($wr->declaration)),
                '{verification_code}' => $wr->verification_code,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            ],
            $wr->customer_email,
            trim($wr->customer_firstname . ' ' . $wr->customer_lastname),
            null, null, null, null,
            _PS_MODULE_DIR_ . 'euwithdrawal/mails/',
            false,
            Validate::isLoadedObject($order) ? (int) $order->id_shop : null
        );

        if ($sent) {
            $this->confirmations[] = $this->l('Ricevuta reinviata al cliente.');
        } else {
            $this->errors[] = $this->l('Invio email non riuscito.');
        }
    }

    public function renderView()
    {
        $id = (int) Tools::getValue('id_withdrawal');
        $wr = new WithdrawalRequest($id);
        if (!Validate::isLoadedObject($wr)) {
            return $this->l('Richiesta non trovata.');
        }

        $items = $wr->getItems();
        $orderUrl = $this->context->link->getAdminLink('AdminOrders') . '&id_order=' . (int) $wr->id_order . '&vieworder';
        $statusUrl = self::$currentIndex . '&token=' . $this->token . '&id_withdrawal=' . $id . '&updateStatus';
        $resendUrl = self::$currentIndex . '&token=' . $this->token . '&id_withdrawal=' . $id . '&resendReceipt';

        $rows = '';
        foreach ($items as $it) {
            $rows .= '<tr><td>' . htmlspecialchars($it['product_name']) . '</td><td>' . htmlspecialchars($it['product_reference']) . '</td><td style="text-align:center">' . (int) $it['quantity'] . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="3" class="text-muted">' . $this->l('Intero ordine') . '</td></tr>';
        }

        $options = '';
        foreach (['pending' => 'In attesa', 'processed' => 'Elaborato', 'rejected' => 'Rifiutato', 'refunded' => 'Rimborsato'] as $k => $lbl) {
            $options .= '<option value="' . $k . '"' . ($wr->status === $k ? ' selected' : '') . '>' . $this->l($lbl) . '</option>';
        }

        $h = '<div class="panel">';
        $h .= '<div class="panel-heading"><i class="icon-gavel"></i> ' . $this->l('Richiesta di recesso') . ' #' . (int) $wr->id . ' — ' . $this->renderStatus($wr->status) . '</div>';
        $h .= '<div class="row"><div class="col-lg-6">';
        $h .= '<table class="table"><tbody>';
        $h .= '<tr><th>' . $this->l('Codice verifica') . '</th><td><code>' . htmlspecialchars($wr->verification_code) . '</code></td></tr>';
        $h .= '<tr><th>' . $this->l('Ordine') . '</th><td><a href="' . htmlspecialchars($orderUrl) . '">' . htmlspecialchars($wr->order_reference) . '</a></td></tr>';
        $h .= '<tr><th>' . $this->l('Cliente') . '</th><td>' . htmlspecialchars(trim($wr->customer_firstname . ' ' . $wr->customer_lastname)) . '</td></tr>';
        $h .= '<tr><th>' . $this->l('Email') . '</th><td>' . htmlspecialchars($wr->customer_email) . '</td></tr>';
        $h .= '<tr><th>' . $this->l('Tipo') . '</th><td>' . $this->renderType($wr->type) . '</td></tr>';
        $h .= '<tr><th>' . $this->l('Origine') . '</th><td>' . htmlspecialchars($wr->source) . '</td></tr>';
        $h .= '<tr><th>' . $this->l('Data richiesta') . '</th><td>' . Tools::displayDate($wr->date_add) . '</td></tr>';
        $h .= '<tr><th>IP</th><td>' . htmlspecialchars($wr->ip) . '</td></tr>';
        $h .= '</tbody></table>';
        $h .= '</div><div class="col-lg-6">';
        $h .= '<h4>' . $this->l('Prodotti oggetto di recesso') . '</h4>';
        $h .= '<table class="table"><thead><tr><th>' . $this->l('Prodotto') . '</th><th>' . $this->l('Rif.') . '</th><th>' . $this->l('Q.tà') . '</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        $h .= '</div></div>';

        $h .= '<h4>' . $this->l('Dichiarazione di recesso (supporto durevole)') . '</h4>';
        $h .= '<pre style="white-space:pre-wrap;background:#f7f7f7;padding:12px;border-radius:6px;">' . htmlspecialchars($wr->declaration) . '</pre>';

        $h .= '<form method="post" action="' . htmlspecialchars($statusUrl) . '" class="form-inline" style="margin:14px 0;">';
        $h .= '<input type="hidden" name="id_withdrawal" value="' . (int) $wr->id . '">';
        $h .= '<label style="margin-right:8px;">' . $this->l('Cambia stato') . '</label>';
        $h .= '<select name="new_status" class="form-control" style="margin-right:8px;">' . $options . '</select>';
        $h .= '<button type="submit" name="updateStatus" value="1" class="btn btn-primary">' . $this->l('Aggiorna') . '</button>';
        $h .= '</form>';

        $h .= '<a href="' . htmlspecialchars($resendUrl) . '" class="btn btn-default"><i class="icon-envelope"></i> ' . $this->l('Reinvia ricevuta al cliente') . '</a> ';
        $h .= '<a href="' . htmlspecialchars($orderUrl) . '" class="btn btn-default"><i class="icon-shopping-cart"></i> ' . $this->l('Apri ordine') . '</a>';

        $h .= '</div>';

        return $h;
    }
}
