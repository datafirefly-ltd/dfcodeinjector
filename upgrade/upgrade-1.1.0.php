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

/**
 * 1.1.0: targeting mode (all / include / exclude), test mode,
 * automatic body fallback through actionOutputHTMLBefore.
 *
 * @param DfCodeInjector $module
 *
 * @return bool
 */
function upgrade_module_1_1_0($module)
{
    require_once __DIR__ . '/../classes/DfCodeSnippet.php';

    $ok = DfCodeSnippet::ensureSchema();

    if ($ok && !$module->isRegisteredInHook('actionOutputHTMLBefore')) {
        $ok = (bool) $module->registerHook('actionOutputHTMLBefore');
    }

    // Make sure PHP stops serving the 1.0.0 files from opcache.
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    return $ok;
}
