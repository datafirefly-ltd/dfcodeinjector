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

require_once __DIR__ . '/../../classes/DfCodeSnippet.php';

class AdminDfCodeInjectorController extends ModuleAdminController
{
    /** Source used for the legacy translation keys of this file. */
    public const TRANSLATION_SOURCE = 'admindfcodeinjectorcontroller';

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'df_code_snippet';
        $this->className = 'DfCodeSnippet';
        $this->identifier = 'id_df_code_snippet';
        $this->lang = false;
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->allow_export = true;

        parent::__construct();

        $this->fields_list = [
            'id_df_code_snippet' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->t('Name'),
                'filter_key' => 'a!name',
            ],
            'location' => [
                'title' => $this->t('Location'),
                'type' => 'select',
                'list' => $this->getLocationLabels(),
                'filter_key' => 'a!location',
                'callback' => 'displayLocationLabel',
            ],
            'pages_mode' => [
                'title' => $this->t('Targeting'),
                'type' => 'select',
                'list' => $this->getModeLabels(),
                'filter_key' => 'a!pages_mode',
                'callback' => 'displayTargetingLabel',
            ],
            'test_mode' => [
                'title' => $this->t('Test mode'),
                'type' => 'select',
                'list' => [1 => $this->t('Yes'), 0 => $this->t('No')],
                'filter_key' => 'a!test_mode',
                'callback' => 'displayTestBadge',
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ],
            'position' => [
                'title' => $this->t('Order'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!position',
            ],
            'active' => [
                'title' => $this->t('Active'),
                'type' => 'bool',
                'active' => 'status',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!active',
            ],
            'date_upd' => [
                'title' => $this->t('Updated'),
                'type' => 'datetime',
                'align' => 'right',
                'filter_key' => 'a!date_upd',
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_list = array_slice($this->fields_list, 0, 2, true)
                + ['id_shop' => [
                    'title' => $this->t('Store'),
                    'callback' => 'displayShopLabel',
                    'search' => false,
                    'orderby' => false,
                ]]
                + array_slice($this->fields_list, 2, null, true);
        }

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->t('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->t('Delete the selected snippets?'),
            ],
        ];
    }

    /**
     * Legacy module translation (PS 8 and 9). Output is HTML-escaped.
     *
     * @param string $string
     *
     * @return string
     */
    protected function t($string)
    {
        return $this->module->l($string, self::TRANSLATION_SOURCE);
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js');
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_snippet'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->t('Add a snippet'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /* ----------------------------------------------------------------------
     *  LIST
     * -------------------------------------------------------------------- */

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('duplicateSnippet');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * Row action "Duplicate" (called by HelperList).
     */
    public function displayDuplicateSnippetLink($token = null, $id = 0, $name = null)
    {
        $href = self::$currentIndex
            . '&' . $this->identifier . '=' . (int) $id
            . '&duplicatesnippet'
            . '&token=' . ($token ? $token : $this->token);
        $label = $this->t('Duplicate');

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" title="' . $label . '">'
            . '<i class="icon-copy"></i> ' . $label . '</a>';
    }

    public function initProcess()
    {
        parent::initProcess();

        if (Tools::getIsset('duplicatesnippet') && (int) Tools::getValue($this->identifier) > 0) {
            if ($this->access('add')) {
                $this->action = 'duplicate_snippet';
            } else {
                $this->errors[] = $this->t('You do not have permission to add snippets.');
            }
        }
    }

    /**
     * Manual copy: ObjectModel::duplicateObject() runs HTMLPurifier on HTML
     * fields and would strip the script tags from the code.
     */
    public function processDuplicateSnippet()
    {
        $source = new DfCodeSnippet((int) Tools::getValue($this->identifier));
        if (!Validate::isLoadedObject($source)) {
            $this->errors[] = $this->t('The snippet could not be found.');

            return false;
        }

        $copy = new DfCodeSnippet();
        $copy->name = Tools::substr(html_entity_decode($this->t('Copy of'), ENT_QUOTES, 'UTF-8') . ' ' . $source->name, 0, 255);
        $copy->code = $source->code;
        $copy->location = $source->location;
        $copy->pages = $source->pages;
        $copy->pages_mode = $source->pages_mode;
        $copy->test_mode = $source->test_mode;
        $copy->id_shop = $source->id_shop;
        $copy->position = DfCodeSnippet::getMaxPosition($source->location) + 1;
        $copy->active = false; // never inject a copy before it has been reviewed

        if (!$copy->add()) {
            $this->errors[] = $this->t('The snippet could not be duplicated.');

            return false;
        }

        $this->redirect_after = self::$currentIndex . '&conf=3&token=' . $this->token;

        return true;
    }

    /* ----------------------------------------------------------------------
     *  FORM
     * -------------------------------------------------------------------- */

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->t('Code snippet'),
                'icon' => 'icon-code',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->t('Name'),
                    'name' => 'name',
                    'required' => true,
                    'col' => 5,
                    'desc' => $this->t('Internal name, for example Google Tag Manager or Promo banner CSS.'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->t('Location'),
                    'name' => 'location',
                    'required' => true,
                    'col' => 5,
                    'options' => [
                        'query' => $this->toOptions($this->getLocationLabels()),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->t('Body locations work with every theme: when the theme does not call the matching hook, the code is inserted directly into the page HTML.'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->t('Code'),
                    'name' => 'code',
                    'rows' => 16,
                    'class' => 'dfci-code',
                    'desc' => [
                        $this->t('Paste the complete code, tags included: <style>, <script>, <link>, <meta>, <noscript>. It is injected as is, without any change.'),
                        $this->t('In this field, Tab inserts 4 spaces. Press Escape then Tab to move to the next field.'),
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->t('Targeting'),
                    'name' => 'pages_mode',
                    'col' => 5,
                    'options' => [
                        'query' => $this->toOptions($this->getModeLabels()),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->t('Pages'),
                    'name' => 'pages[]',
                    'id' => 'dfci_pages',
                    'multiple' => true,
                    'class' => 'chosen',
                    'options' => [
                        'query' => $this->getPageOptions(),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'form_group_class' => 'dfci-pages-group',
                ],
                [
                    'type' => 'text',
                    'label' => $this->t('Other pages'),
                    'name' => 'pages_custom',
                    'col' => 6,
                    'form_group_class' => 'dfci-pages-group',
                    'desc' => $this->t('Technical page names separated by commas. Module pages use module-modulename-controller, as in the page_name variable of the theme.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->t('Order'),
                    'name' => 'position',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->t('Snippets of the same location are injected in ascending order. Leave empty to place this snippet last.'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->t('Test mode'),
                    'name' => 'test_mode',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'test_mode_on', 'value' => 1, 'label' => $this->t('Yes')],
                        ['id' => 'test_mode_off', 'value' => 0, 'label' => $this->t('No')],
                    ],
                    'desc' => $this->t('Only inject the code for the maintenance IP addresses set in Shop Parameters > General > Maintenance. Use it to check a snippet on the live store before opening it to every visitor.'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->t('Active'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->t('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->t('No')],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->t('Save'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $shops = [['id' => 0, 'name' => $this->t('All stores')]];
            foreach (Shop::getShops(false) as $shop) {
                $shops[] = ['id' => (int) $shop['id_shop'], 'name' => htmlspecialchars($shop['name'], ENT_QUOTES, 'UTF-8')];
            }

            $this->fields_form['input'][] = [
                'type' => 'select',
                'label' => $this->t('Store'),
                'name' => 'id_shop',
                'col' => 5,
                'options' => [
                    'query' => $shops,
                    'id' => 'id',
                    'name' => 'name',
                ],
                'desc' => $this->t('Store on which the snippet is injected.'),
            ];
        }

        return parent::renderForm();
    }

    public function getFieldsValue($obj)
    {
        $knownPages = array_keys($this->getPageLabels());

        if (Tools::getIsset('pages_mode')) {
            // Form re-displayed after a validation error: keep what was typed.
            $selected = Tools::getValue('pages');
            $this->fields_value['pages[]'] = is_array($selected) ? array_map('strval', $selected) : [];
            $this->fields_value['pages_custom'] = (string) Tools::getValue('pages_custom');
        } else {
            $isSnippet = ($obj instanceof DfCodeSnippet) && Validate::isLoadedObject($obj);
            $pages = $isSnippet ? DfCodeSnippet::decodePages($obj->pages) : [];
            $this->fields_value['pages[]'] = array_values(array_intersect($pages, $knownPages));
            $this->fields_value['pages_custom'] = implode(', ', array_diff($pages, $knownPages));

            if (!$isSnippet) {
                $this->fields_value['location'] = DfCodeSnippet::LOCATION_HEAD;
                $this->fields_value['pages_mode'] = DfCodeSnippet::MODE_ALL;
                $this->fields_value['position'] = '';
                $this->fields_value['test_mode'] = 0;
                $this->fields_value['active'] = 1;
            }
        }

        return parent::getFieldsValue($obj);
    }

    /* ----------------------------------------------------------------------
     *  SAVE
     * -------------------------------------------------------------------- */

    public function processAdd()
    {
        if (!$this->normalizeSubmittedFields()) {
            return false;
        }

        return parent::processAdd();
    }

    public function processUpdate()
    {
        if (!$this->normalizeSubmittedFields()) {
            return false;
        }

        return parent::processUpdate();
    }

    /**
     * Validates the custom fields and rewrites $_POST in the shape expected
     * by the ObjectModel before the native save runs.
     *
     * @return bool
     */
    protected function normalizeSubmittedFields()
    {
        $location = (string) Tools::getValue('location');
        if (!in_array($location, DfCodeSnippet::getLocations(), true)) {
            $this->errors[] = $this->t('Invalid location.');
        }

        $mode = (string) Tools::getValue('pages_mode', DfCodeSnippet::MODE_ALL);
        if (!in_array($mode, DfCodeSnippet::getModes(), true)) {
            $this->errors[] = $this->t('Invalid targeting mode.');
            $mode = DfCodeSnippet::MODE_ALL;
        }

        $selected = Tools::getValue('pages');
        $selected = is_array($selected) ? array_map('strval', $selected) : [];
        $selected = array_values(array_intersect($selected, array_keys($this->getPageLabels())));

        $custom = preg_split('/[\s,;]+/', (string) Tools::getValue('pages_custom'), -1, PREG_SPLIT_NO_EMPTY);
        $invalid = [];
        foreach ($custom as $page) {
            if (!DfCodeSnippet::isValidPageName($page)) {
                $invalid[] = $page;
            }
        }
        if ($invalid) {
            $this->errors[] = sprintf(
                $this->t('Invalid page names: %s'),
                htmlspecialchars(implode(', ', $invalid), ENT_QUOTES, 'UTF-8')
            );
        }

        $pages = array_values(array_unique(array_merge($selected, array_diff($custom, $invalid))));

        if ($mode !== DfCodeSnippet::MODE_ALL && !$pages) {
            $this->errors[] = $this->t('Select at least one page for this targeting mode.');
        }

        if ($this->errors) {
            return false;
        }

        $_POST['pages'] = json_encode($mode === DfCodeSnippet::MODE_ALL ? [] : $pages);
        $_POST['pages_mode'] = $mode;

        if (!Shop::isFeatureActive()) {
            $_POST['id_shop'] = 0;
        }

        $position = trim((string) Tools::getValue('position'));
        if ($position === '') {
            $_POST['position'] = DfCodeSnippet::getMaxPosition($location) + 1;
        } else {
            $_POST['position'] = max(0, (int) $position);
        }

        return true;
    }

    /* ----------------------------------------------------------------------
     *  LIST CALLBACKS (output is printed raw by HelperList)
     * -------------------------------------------------------------------- */

    public function displayLocationLabel($value, $row)
    {
        $labels = $this->getLocationLabels();

        return isset($labels[$value]) ? $labels[$value] : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function displayTargetingLabel($value, $row)
    {
        $pages = DfCodeSnippet::decodePages(isset($row['pages']) ? $row['pages'] : null);
        $labels = $this->getModeLabels();
        $mode = isset($labels[$value]) ? $value : ($pages ? DfCodeSnippet::MODE_INCLUDE : DfCodeSnippet::MODE_ALL);

        if ($mode === DfCodeSnippet::MODE_ALL) {
            return $labels[$mode];
        }

        return '<span title="' . htmlspecialchars(implode(', ', $pages), ENT_QUOTES, 'UTF-8') . '">'
            . $labels[$mode] . ' (' . count($pages) . ')</span>';
    }

    public function displayTestBadge($value, $row)
    {
        return (int) $value ? '<span class="badge badge-warning">' . $this->t('Test mode') . '</span>' : '';
    }

    public function displayShopLabel($value, $row)
    {
        if ((int) $value === 0) {
            return $this->t('All stores');
        }
        $shop = new Shop((int) $value);

        return htmlspecialchars(Validate::isLoadedObject($shop) ? $shop->name : (string) $value, ENT_QUOTES, 'UTF-8');
    }

    /* ----------------------------------------------------------------------
     *  STATIC DATA
     * -------------------------------------------------------------------- */

    protected function getLocationLabels()
    {
        return [
            DfCodeSnippet::LOCATION_HEAD => $this->t('Header (head tag)'),
            DfCodeSnippet::LOCATION_BODY_OPEN => $this->t('Body start (after the opening body tag)'),
            DfCodeSnippet::LOCATION_BODY_CLOSE => $this->t('Body end (before the closing body tag)'),
        ];
    }

    protected function getModeLabels()
    {
        return [
            DfCodeSnippet::MODE_ALL => $this->t('All pages'),
            DfCodeSnippet::MODE_INCLUDE => $this->t('Only the selected pages'),
            DfCodeSnippet::MODE_EXCLUDE => $this->t('All pages except the selected ones'),
        ];
    }

    /**
     * Front pages, keyed by the page name returned by FrontController::getPageName().
     */
    protected function getPageLabels()
    {
        return [
            'index' => $this->t('Home page'),
            'category' => $this->t('Category page'),
            'product' => $this->t('Product page'),
            'cms' => $this->t('CMS page'),
            'manufacturer' => $this->t('Brand page'),
            'supplier' => $this->t('Supplier page'),
            'search' => $this->t('Search results'),
            'best-sales' => $this->t('Best sellers'),
            'new-products' => $this->t('New products'),
            'prices-drop' => $this->t('Price drops'),
            'cart' => $this->t('Cart'),
            'order' => $this->t('Checkout'),
            'order-confirmation' => $this->t('Order confirmation'),
            'authentication' => $this->t('Login and registration'),
            'registration' => $this->t('Registration'),
            'password' => $this->t('Forgotten password'),
            'my-account' => $this->t('My account'),
            'identity' => $this->t('Personal information'),
            'addresses' => $this->t('Addresses'),
            'address' => $this->t('Address form'),
            'history' => $this->t('Order history'),
            'order-detail' => $this->t('Order details'),
            'order-follow' => $this->t('Merchandise returns'),
            'order-slip' => $this->t('Credit slips'),
            'discount' => $this->t('Vouchers'),
            'guest-tracking' => $this->t('Guest order tracking'),
            'contact' => $this->t('Contact'),
            'stores' => $this->t('Stores'),
            'sitemap' => $this->t('Sitemap'),
            'pagenotfound' => $this->t('Page not found (404)'),
        ];
    }

    protected function getPageOptions()
    {
        $options = [];
        foreach ($this->getPageLabels() as $id => $label) {
            $options[] = ['id' => $id, 'name' => $label . ' (' . $id . ')'];
        }

        return $options;
    }

    protected function toOptions(array $labels)
    {
        $options = [];
        foreach ($labels as $id => $label) {
            $options[] = ['id' => $id, 'name' => $label];
        }

        return $options;
    }
}
