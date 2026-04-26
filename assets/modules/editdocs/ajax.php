<?php
/**
 * AJAX контроллер для модуля editDocs
 */

define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);
define('NO_TRACY', true);

include_once(__DIR__ . "/../../../index.php");

global $modx;
$modx->db->connect();
if (empty($modx->config)) {
    $modx->getSettings();
}

spl_autoload_register(function ($class) {
    $prefix = 'EditDocs\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once(MODX_BASE_PATH . "assets/modules/editdocs/editdocs.class.php");

use EditDocs\Http\EditDocsAjaxHandler;

function validateEnvironment($modx): void
{
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        $modx->sendRedirect($modx->getConfig('site_url'));
        exit;
    }

    if (IN_MANAGER_MODE != "true" || empty($modx) || !($modx instanceof DocumentParser)) {
        echo "<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.";
        exit;
    }

    if (!$modx->hasPermission('exec_module')) {
        header("location: " . $modx->getManagerPath() . "?a=106");
        exit;
    }

    if (!isset($_SESSION['mgrValidated'])) {
        exit;
    }
}

validateEnvironment($modx);
$legacyEditDocs = new editDocs($modx);
$handler = new EditDocsAjaxHandler($modx, $legacyEditDocs);
$handler->handleRequest();
