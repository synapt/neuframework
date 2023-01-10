<?php
/** neuFramework v5 - Generic template auto-loader
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
use neufw\app\core\utility;
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

// Get requested page info
$section = utility::checkgetValue('section');  // Section name, A sub folder name under templates
$page = utility::checkgetValue('page');        // Page (file) name

// Validate this appears to be a valid page request
if (($routeName = utility::validateRoute($page, $section)) === false) {
    utility::httpRedirect(sprintf('%s://%s/404', config::getSetting('protocol'), config::getSetting('domain')));
}

// Initialize Twig
$twig = new Environment(new FilesystemLoader(config::getSetting('twig_directory')), [
    'cache' => false,
]);
$twig->addExtension(new IntlExtension());
$twig->addGlobal('config', ['protocol' => config::getSetting('protocol'), 'domain' => config::getSetting('domain')]);
$twig->addGlobal('errors', error::getErrors(true));

// Output Template
try {
    $twig->display($routeName);
}
catch (LoaderError|SyntaxError|RuntimeError $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
}