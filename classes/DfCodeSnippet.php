<?php
/**
 * Header/Footer Code Injector
 *
 * @author    DataFirefly
 * @copyright DataFirefly Limited
 * @license   Commercial
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DfCodeSnippet extends ObjectModel
{
    public const LOCATION_HEAD = 'head';
    public const LOCATION_BODY_OPEN = 'body_open';
    public const LOCATION_BODY_CLOSE = 'body_close';

    public const MODE_ALL = 'all';
    public const MODE_INCLUDE = 'include';
    public const MODE_EXCLUDE = 'exclude';

    /** @var int */
    public $id_df_code_snippet;

    /** @var string */
    public $name;

    /** @var string Raw code injected as is (CSS/JS/HTML) */
    public $code;

    /** @var string head | body_open | body_close */
    public $location = self::LOCATION_HEAD;

    /** @var string JSON list of page names (FrontController::getPageName()) */
    public $pages;

    /** @var string all | include | exclude */
    public $pages_mode = self::MODE_ALL;

    /** @var bool */
    public $active = true;

    /** @var bool Only injected for the maintenance IP addresses */
    public $test_mode = false;

    /** @var int Injection order inside a location (ascending) */
    public $position = 0;

    /** @var int 0 = all stores */
    public $id_shop = 0;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = [
        'table' => 'df_code_snippet',
        'primary' => 'id_df_code_snippet',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            // TYPE_HTML + isString: no HTMLPurifier pass, the code is stored untouched.
            'code' => ['type' => self::TYPE_HTML, 'validate' => 'isString'],
            'location' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'pages' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'pages_mode' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'test_mode' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * @return string[]
     */
    public static function getLocations()
    {
        return [self::LOCATION_HEAD, self::LOCATION_BODY_OPEN, self::LOCATION_BODY_CLOSE];
    }

    /**
     * @return string[]
     */
    public static function getModes()
    {
        return [self::MODE_ALL, self::MODE_INCLUDE, self::MODE_EXCLUDE];
    }

    /* ----------------------------------------------------------------------
     *  SCHEMA (used by install() and by every upgrade script)
     * -------------------------------------------------------------------- */

    /**
     * Creates the table if needed and adds the columns introduced after 1.0.0,
     * so fresh installs and upgraded installs always end up with the same schema.
     *
     * @return bool
     */
    public static function ensureSchema()
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . self::$definition['table'];

        $created = $db->execute('CREATE TABLE IF NOT EXISTS `' . $table . '` (
            `id_df_code_snippet` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `code` LONGTEXT NULL,
            `location` VARCHAR(32) NOT NULL DEFAULT \'head\',
            `pages` TEXT NULL,
            `pages_mode` VARCHAR(16) NOT NULL DEFAULT \'all\',
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            `test_mode` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_df_code_snippet`),
            KEY `idx_active_shop` (`active`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;');

        if (!$created) {
            return false;
        }

        $rows = $db->executeS('SHOW COLUMNS FROM `' . $table . '`');
        if (!is_array($rows)) {
            return false;
        }
        $columns = [];
        foreach ($rows as $row) {
            $columns[$row['Field']] = true;
        }

        if (!isset($columns['pages_mode'])) {
            if (!$db->execute('ALTER TABLE `' . $table . '` ADD `pages_mode` VARCHAR(16) NOT NULL DEFAULT \'all\' AFTER `pages`')) {
                return false;
            }
            // 1.0.0 semantics: a non-empty page list meant "only these pages".
            $db->execute('UPDATE `' . $table . '` SET `pages_mode` = \'include\'
                WHERE `pages` IS NOT NULL AND `pages` NOT IN (\'\', \'[]\')');
        }

        if (!isset($columns['test_mode'])) {
            if (!$db->execute('ALTER TABLE `' . $table . '` ADD `test_mode` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `active`')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function dropSchema()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::$definition['table'] . '`');
    }

    /* ----------------------------------------------------------------------
     *  QUERIES
     * -------------------------------------------------------------------- */

    /**
     * Every active snippet of a store (and the "all stores" ones), in injection order.
     * One query per page view, whatever the number of locations.
     *
     * @param int $idShop
     *
     * @return array
     */
    public static function getActiveForShop($idShop)
    {
        // SELECT * on purpose: the front keeps working between a file update
        // and the execution of the upgrade script.
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
            WHERE `active` = 1
            AND (`id_shop` = 0 OR `id_shop` = ' . (int) $idShop . ')
            ORDER BY `position` ASC, `id_df_code_snippet` ASC'
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param string $location
     *
     * @return int
     */
    public static function getMaxPosition($location)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
            WHERE `location` = \'' . pSQL($location) . '\''
        );
    }

    /* ----------------------------------------------------------------------
     *  PAGE TARGETING
     * -------------------------------------------------------------------- */

    /**
     * @param string|null $json
     *
     * @return string[]
     */
    public static function decodePages($json)
    {
        if (!is_string($json) || $json === '') {
            return [];
        }
        $pages = json_decode($json, true);
        if (!is_array($pages)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $pages), function ($page) {
            return $page !== '';
        }));
    }

    /**
     * Valid page names: core php_self values (order-confirmation, my-account...)
     * and module pages (module-<module>-<controller>).
     *
     * @param string $page
     *
     * @return bool
     */
    public static function isValidPageName($page)
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]{1,128}$/', $page);
    }

    /**
     * @param array $row Raw database row
     * @param string $pageName Current page name
     *
     * @return bool
     */
    public static function rowMatchesPage(array $row, $pageName)
    {
        $pages = self::decodePages(isset($row['pages']) ? $row['pages'] : null);

        if (isset($row['pages_mode']) && in_array($row['pages_mode'], self::getModes(), true)) {
            $mode = $row['pages_mode'];
        } else {
            // Row written by 1.0.0 before the upgrade script ran.
            $mode = $pages ? self::MODE_INCLUDE : self::MODE_ALL;
        }

        if ($mode === self::MODE_INCLUDE) {
            return $pageName !== '' && in_array($pageName, $pages, true);
        }
        if ($mode === self::MODE_EXCLUDE) {
            return !in_array($pageName, $pages, true);
        }

        return true;
    }
}
