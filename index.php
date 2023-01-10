<?php
/** neuFramework v5 - Example Page Script
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */

// Autoloader
require_once('app/vendor/autoload.php');

// Namespaces
use neufw\app\core\config;
use neufw\app\core\error;
use neufw\app\core\sessions;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;

// Just in case DOCUMENT_ROOT for some reason does not exist
if (array_key_exists('DOCUMENT_ROOT', $_SERVER) !== false) {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
}

// Load Settings
config::initialize('dotenv', ['directory' => $_SERVER['DOCUMENT_ROOT'] . '/app', 'filename' => '.env']);

// Initialize Sessions
$sessions = new sessions(config::getSetting('session_name'), config::getSetting('session_path'));

// Initialize Twig
$twig = new Environment(new FilesystemLoader(config::getSetting('twig_directory')), [
    'cache' => false,
]);
$twig->addExtension(new IntlExtension());
$twig->addGlobal('config', ['protocol' => config::getSetting('protocol'), 'domain' => config::getSetting('domain')]);
$twig->addGlobal('errors', error::getErrors(true));

// Page Info
$pageInfo = [
    'page' => "index",
    'title' => "Example Individual Page Script",
    'description' => "Example Individual Page Script"
];

// Output Template
try {
    $twig->display('index.twig', [
        'pageInfo' => $pageInfo
    ]);
}
catch (LoaderError|SyntaxError|RuntimeError $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
}