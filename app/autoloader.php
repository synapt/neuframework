<?php
/**
 * neuFramework v5 - Autoloader Class
 */

namespace neufw\app;

/**
 * autoloader for the framework
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class autoloader {
    /**
     * @var         string          $base               Base directory to path from (__DIR__)
     */
    public static string $base = __DIR__;
    /**
     * @var         string          $extensions         Prefix of the file type to include (.php)
     */
    public static string $extension = '.php';

    /**
     * autoloader::autoload()
     *
     * Autoloader function to turn namespace references into the file inclusion
     *
     * @access      public
     
     * @param       string         $namespace          Array key name of the setting to get
     *
     * @return      void           Value of the setting if it exists, else NULL
     */
    public static function autoload(string $namespace): void {
        // Is this the full app reference?
        if (str_contains($namespace, 'neufw\app')) {
            $full_path = str_replace(['neufw\app\\', '\\'], '/', $namespace);
        }
        else {
            // Is a localized reference
            $full_path = '/' . $namespace;
        }
        
        // Make the full path
        $full_path = self::$base . $full_path . self::$extension;
        
        // Check the file exists and can be read
        if (is_readable($full_path) === true) {
            require_once($full_path);
        }
        else {
            // Namespaces are required so we'll throw a subtle fatal
            trigger_error(sprintf('Unable to load the %s namespace', $namespace), E_USER_ERROR);
        }
    }
}

// And now register the autoloading
spl_autoload_extensions(autoloader::$extension);
spl_autoload_register('neufw\app\autoloader::autoload');

// Use this to catch most fatal errors
register_shutdown_function('neufw\app\core\error::catchFatal');

// And we'll use our own error handlers
set_error_handler('neufw\app\core\error::handleErrors', E_ALL);
set_exception_handler('neufw\app\core\error::handleExceptions');