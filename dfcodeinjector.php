<?php
/**
 * Header/Footer Code Injector (CSS/JS/HTML)
 *
 * @author    DataFirefly
 * @copyright DataFirefly Limited
 * @license   Commercial
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/DfCodeSnippet.php';

class DfCodeInjector extends Module
{
    /** @var array|null Snippets of the current request, grouped by location */
    protected static $requestSnippets;

    /** @var array Body locations whose theme hook ran during the current request */
    protected static $renderedByTheme = [];

    public function __construct()
    {
        $this->name = 'dfcodeinjector';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'DataFirefly';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Header/Footer Code Injector');
        $this->description = $this->l('Inject custom CSS, JS or HTML code into the head tag, right after the opening body tag or right before the closing body tag. Page targeting, ordering, test mode and multistore.');
        $this->confirmUninstall = $this->l('All saved code snippets will be permanently deleted. Continue?');
    }

    public function install()
    {
        return parent::install()
            && DfCodeSnippet::ensureSchema()
            && $this->installTab()
            && $this->registerHook([
                'displayHeader',
                'displayAfterBodyOpeningTag',
                'displayBeforeBodyClosingTag',
                'actionOutputHTMLBefore',
            ]);
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && DfCodeSnippet::dropSchema()
            && parent::uninstall();
    }

    protected function installTab()
    {
        if (Tab::getIdFromClassName('AdminDfCodeInjector')) {
            return true;
        }

        $names = [
            'en' => 'Code Injector',
            'fr' => 'Injecteur de code',
            'es' => 'Inyector de código',
            'de' => 'Code-Injektor',
            'it' => 'Iniettore di codice',
            'nl' => 'Code-injector',
            'pl' => 'Wstrzykiwanie kodu',
            'pt' => 'Injetor de código',
        ];

        $tab = new Tab();
        $tab->class_name = 'AdminDfCodeInjector';
        $tab->module = $this->name;
        $tab->id_parent = -1; // hidden tab, opened through the module "Configure" button
        $tab->active = true;
        $tab->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $iso = Tools::strtolower($lang['iso_code']);
            $tab->name[(int) $lang['id_lang']] = isset($names[$iso]) ? $names[$iso] : $names['en'];
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminDfCodeInjector');
        if (!$idTab) {
            return true;
        }

        return (bool) (new Tab($idTab))->delete();
    }

    /**
     * The "Configure" button opens the snippet manager.
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminDfCodeInjector'));
    }

    /* ----------------------------------------------------------------------
     *  INJECTION HOOKS
     * -------------------------------------------------------------------- */

    public function hookDisplayHeader($params)
    {
        return $this->renderLocation(DfCodeSnippet::LOCATION_HEAD);
    }

    public function hookDisplayAfterBodyOpeningTag($params)
    {
        self::$renderedByTheme[DfCodeSnippet::LOCATION_BODY_OPEN] = true;

        return $this->renderLocation(DfCodeSnippet::LOCATION_BODY_OPEN);
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        self::$renderedByTheme[DfCodeSnippet::LOCATION_BODY_CLOSE] = true;

        return $this->renderLocation(DfCodeSnippet::LOCATION_BODY_CLOSE);
    }

    /**
     * Safety net for themes that do not call the body hooks: once the page HTML
     * is built, the code is spliced right after <body ...> and right before </body>.
     * $params['html'] is passed by reference by FrontController::smartyOutputContent().
     */
    public function hookActionOutputHTMLBefore($params)
    {
        if (!isset($params['html']) || !is_string($params['html']) || $params['html'] === '') {
            return;
        }

        $html = $params['html'];
        $changed = false;

        if (empty(self::$renderedByTheme[DfCodeSnippet::LOCATION_BODY_OPEN])) {
            $code = $this->renderLocation(DfCodeSnippet::LOCATION_BODY_OPEN);
            if ($code !== '') {
                $html = self::insertAfterBodyOpening($html, $code, $changed);
            }
        }

        if (empty(self::$renderedByTheme[DfCodeSnippet::LOCATION_BODY_CLOSE])) {
            $code = $this->renderLocation(DfCodeSnippet::LOCATION_BODY_CLOSE);
            if ($code !== '') {
                $html = self::insertBeforeBodyClosing($html, $code, $changed);
            }
        }

        if ($changed) {
            $params['html'] = $html;
        }
    }

    /**
     * @param string $html
     * @param string $code
     * @param bool $changed
     *
     * @return string
     */
    public static function insertAfterBodyOpening($html, $code, &$changed)
    {
        // Start after </head> so a "<body" string inside a head script is never matched.
        $offset = stripos($html, '</head>');
        $offset = ($offset === false) ? 0 : $offset;

        if (!preg_match('/<body\b[^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
            return $html;
        }

        $position = $match[0][1] + strlen($match[0][0]);
        $changed = true;

        return substr($html, 0, $position) . "\n" . $code . substr($html, $position);
    }

    /**
     * @param string $html
     * @param string $code
     * @param bool $changed
     *
     * @return string
     */
    public static function insertBeforeBodyClosing($html, $code, &$changed)
    {
        $position = strripos($html, '</body>');
        if ($position === false) {
            return $html;
        }

        $changed = true;

        return substr($html, 0, $position) . $code . substr($html, $position);
    }

    /* ----------------------------------------------------------------------
     *  RENDERING
     * -------------------------------------------------------------------- */

    /**
     * @param string $location
     *
     * @return string
     */
    protected function renderLocation($location)
    {
        $snippets = $this->getRequestSnippets();
        if (empty($snippets[$location])) {
            return '';
        }

        return implode("\n", $snippets[$location]) . "\n";
    }

    /**
     * Loads, filters and groups the snippets once per request.
     *
     * @return array
     */
    protected function getRequestSnippets()
    {
        if (self::$requestSnippets !== null) {
            return self::$requestSnippets;
        }

        self::$requestSnippets = [];

        $rows = DfCodeSnippet::getActiveForShop((int) $this->context->shop->id);
        if (!$rows) {
            return self::$requestSnippets;
        }

        $pageName = $this->getCurrentPageName();
        $isTestVisitor = null;

        foreach ($rows as $row) {
            if (!empty($row['test_mode'])) {
                if ($isTestVisitor === null) {
                    $isTestVisitor = $this->isTestVisitor();
                }
                if (!$isTestVisitor) {
                    continue;
                }
            }

            if (!DfCodeSnippet::rowMatchesPage($row, $pageName)) {
                continue;
            }

            $code = trim((string) $row['code']);
            if ($code === '') {
                continue;
            }

            self::$requestSnippets[(string) $row['location']][] = $code;
        }

        return self::$requestSnippets;
    }

    /**
     * Same page name as the theme's {$page.page_name}: php_self for core pages
     * (index, product, order-confirmation...), module-<module>-<controller> for modules.
     *
     * @return string
     */
    protected function getCurrentPageName()
    {
        $controller = $this->context->controller;
        if (!$controller) {
            return '';
        }

        try {
            if (method_exists($controller, 'getPageName')) {
                return (string) $controller->getPageName();
            }
        } catch (Throwable $e) {
            // Fall through to php_self.
        }

        return isset($controller->php_self) ? (string) $controller->php_self : '';
    }

    /**
     * Test mode: the visitor IP must be listed in Shop Parameters > General >
     * Maintenance (PS_MAINTENANCE_IP). Ranges in CIDR notation are accepted.
     *
     * @return bool
     */
    protected function isTestVisitor()
    {
        $ip = (string) Tools::getRemoteAddr();
        $allowed = array_values(array_filter(array_map('trim', explode(',', (string) Configuration::get('PS_MAINTENANCE_IP')))));

        if ($ip === '' || !$allowed) {
            return false;
        }

        if (in_array($ip, $allowed, true)) {
            return true;
        }

        if (class_exists('Symfony\Component\HttpFoundation\IpUtils')) {
            try {
                return Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $allowed);
            } catch (Throwable $e) {
                return false;
            }
        }

        return false;
    }
}
