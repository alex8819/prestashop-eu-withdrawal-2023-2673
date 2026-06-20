<?php
/**
 * Upgrade to 0.3.0 — add verification_code column to the requests table.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_3_0($module)
{
    $table = _DB_PREFIX_ . 'euwithdrawal_request';

    $hasColumn = (bool) Db::getInstance()->getValue(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = '" . pSQL($table) . "'
           AND COLUMN_NAME = 'verification_code'"
    );

    if (!$hasColumn) {
        Db::getInstance()->execute(
            'ALTER TABLE `' . $table . '`
             ADD `verification_code` VARCHAR(32) NOT NULL DEFAULT "" AFTER `declaration`,
             ADD KEY `verification_code` (`verification_code`)'
        );
    }

    return true;
}
