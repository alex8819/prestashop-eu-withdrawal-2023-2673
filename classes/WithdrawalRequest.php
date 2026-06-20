<?php
/**
 * Withdrawal request ObjectModel + helpers.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WithdrawalRequest extends ObjectModel
{
    public $id_order;
    public $id_customer;
    public $order_reference;
    public $customer_firstname;
    public $customer_lastname;
    public $customer_email;
    public $type;          // full | partial
    public $status;        // pending | processed | rejected | refunded
    public $source;        // account | guest
    public $id_lang;
    public $declaration;   // testo dichiarazione (supporto durevole)
    public $ip;
    public $date_add;
    public $date_upd;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REFUNDED = 'refunded';

    public static $definition = [
        'table' => 'euwithdrawal_request',
        'primary' => 'id_withdrawal',
        'fields' => [
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'order_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32],
            'customer_firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 255],
            'customer_lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 255],
            'customer_email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16],
            'source' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'declaration' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 4000],
            'ip' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /* ------------------------------------------------------------------ */

    public static function createTables()
    {
        $sql1 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'euwithdrawal_request` (
            `id_withdrawal` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT UNSIGNED NOT NULL,
            `id_customer` INT UNSIGNED NOT NULL DEFAULT 0,
            `order_reference` VARCHAR(32) NOT NULL DEFAULT "",
            `customer_firstname` VARCHAR(255) NOT NULL DEFAULT "",
            `customer_lastname` VARCHAR(255) NOT NULL DEFAULT "",
            `customer_email` VARCHAR(255) NOT NULL DEFAULT "",
            `type` VARCHAR(16) NOT NULL DEFAULT "full",
            `status` VARCHAR(16) NOT NULL DEFAULT "pending",
            `source` VARCHAR(16) NOT NULL DEFAULT "account",
            `id_lang` INT UNSIGNED NOT NULL DEFAULT 0,
            `declaration` TEXT,
            `ip` VARCHAR(64) NOT NULL DEFAULT "",
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_withdrawal`),
            KEY `id_order` (`id_order`),
            KEY `id_customer` (`id_customer`),
            KEY `status` (`status`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $sql2 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'euwithdrawal_request_item` (
            `id_withdrawal_item` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_withdrawal` INT UNSIGNED NOT NULL,
            `id_order_detail` INT UNSIGNED NOT NULL DEFAULT 0,
            `product_name` VARCHAR(255) NOT NULL DEFAULT "",
            `product_reference` VARCHAR(64) NOT NULL DEFAULT "",
            `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_withdrawal_item`),
            KEY `id_withdrawal` (`id_withdrawal`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql1) && Db::getInstance()->execute($sql2);
    }

    public static function dropTables()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'euwithdrawal_request_item`')
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'euwithdrawal_request`');
    }

    /** True if a non-rejected FULL withdrawal already exists for the order. */
    public static function hasActiveFullRequest($idOrder)
    {
        return (bool) Db::getInstance()->getValue('
            SELECT 1 FROM `' . _DB_PREFIX_ . 'euwithdrawal_request`
            WHERE id_order = ' . (int) $idOrder . '
              AND type = "full"
              AND status <> "' . pSQL(self::STATUS_REJECTED) . '"');
    }

    /** Persist the selected order items for a withdrawal. */
    public function saveItems(array $items)
    {
        foreach ($items as $it) {
            Db::getInstance()->insert('euwithdrawal_request_item', [
                'id_withdrawal' => (int) $this->id,
                'id_order_detail' => (int) ($it['id_order_detail'] ?? 0),
                'product_name' => pSQL($it['product_name'] ?? ''),
                'product_reference' => pSQL($it['product_reference'] ?? ''),
                'quantity' => (int) ($it['quantity'] ?? 1),
            ]);
        }

        return true;
    }

    public function getItems()
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'euwithdrawal_request_item`
            WHERE id_withdrawal = ' . (int) $this->id);
    }

    public static function getItemsByWithdrawalId($id)
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'euwithdrawal_request_item`
            WHERE id_withdrawal = ' . (int) $id);
    }

    public function delete()
    {
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'euwithdrawal_request_item` WHERE id_withdrawal = ' . (int) $this->id);

        return parent::delete();
    }
}
