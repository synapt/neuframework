<?php
/**
 * neuFramework v5 - Logfile Object
 */

namespace neufw\app\core;

use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Error\RuntimeError;
use Twig\Error\LoaderError;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Throwable;

/**
 * Error handling
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class error {
    /**
     * @var         array          $errors             An array of stored errors to display on nice output in dev envs
     */
    protected static array $errors;

    /**
     * error::handleErrors()
     *
     * Custom PHP error_handler callback
     *
     * @access      public
     *
     * @param       int            $errno              PHP Error constant value (ie; E_NOTICE)
     * @param       string         $message            The error message from PHP
     * @param       string         $file               What file the error happened in
     * @param       int            $line               What line the error happened at
     * @param       array          $context            Context array of variables (deprecated in 7.2, gone in 8.0)
     *
     * @return      void
     */
    public static function handleErrors(int $errno = 0, string $message = '', string $file = '', int $line = 0, array $context = []): void {
        // Timestamp and join file and line
        $fileInfo = $file . ':' . $line;
        
        // Build a little cleaner message for log
        $formattedMessage = sprintf(
            '%-20s | %s',
            $fileInfo,
            $message
        );

        // If we're logging deprecated for some reason, save that to a dedicated log for sanity sake
        if ($errno === E_DEPRECATED) {
            logger::writeToLogDated($formattedMessage, 'deprecated');
        }
        else {
            logger::writeToLogDated($formattedMessage, 'error');
        }

        // If this is a dev environment, preserve simple errors in an array for detailed output on-page if invoked
        if (config::getSetting('environment') !== 'production') {
            // Save error to the log and sanitize paths
            self::$errors[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $message);
        }

        // And for handling critical/fatal errors with a templated display
        if ($errno === E_ERROR || $errno === E_USER_ERROR) {
            // We can ignore this step though if we're in a CLI call
            if (PHP_SAPI !== 'cli') {
                self::displayNiceError();
            }

            exit(-1);
        }
    }
    
    /**
     * error::handleExceptions()
     *
     * Custom PHP exception_handler callback
     *
     * @access      public
     *
     * @param       Throwable      $exception          An exception object
     *
     * @return      never
     */
    public static function handleExceptions(Throwable $exception): never {
        // Timestamp and join file and line
        $fileInfo = $exception->getFile() . ':' . $exception->getLine();
    
        // Build a little cleaner message for log
        $formattedMessage = sprintf(
            '%-20s | %s',
            $fileInfo,
            $exception->getMessage()
        );
        logger::writeToLogDated($formattedMessage, 'error');
    
        // If not a CLI mode, display a templated error output
        if (PHP_SAPI !== 'cli') {
            self::displayNiceError();
        }

        // Exceptions that reach this point are always fatal, terminate
        exit(-1);
    }
    
    /**
     * error::catchFatal()
     *
     * Custom PHP error_handler callback
     *
     * @access      public
     *
     * @return      void
     */
    public static function catchFatal(): void {
        // Snag the last error data
        $lastError = error_get_last();
        
        // Check if it was a fatal
        if ($lastError !== null && ($lastError['type'] === E_ERROR || $lastError['type'] === E_USER_ERROR)) {
            // Log the error before anything else
            logger::writeToLogDated($lastError['message'], 'error');
            
            // And try to show a clean error to the browser
            if (PHP_SAPI !== 'cli') {
                self::displayNiceError();
            }
        }
    }
    
    /**
     * error::getErrors()
     *
     * Return all errors for debug box output
     *
     * @access      public
     *
     * @param       bool           $ignoreEnv          Ignore if we're in production and allow the full error array return
     *
     * @return      array|null     null or the array of items from $errors property
     */
    public static function getErrors(bool $ignoreEnv = false): ?array {
        if ($ignoreEnv !== true && config::getSetting('environment') === 'production') {
            return null;
        }
        return self::$errors ?? null;
    }
    
    /**
     * error::displayNiceError()
     *
     * Method to output a styled error if not in CLI mode
     *
     * @access      private
     *
     * @return      void
     */
    private static function displayNiceError(): void {
        http_response_code(500);
        
        $twig = new Environment(new FilesystemLoader(config::getSetting('twig_directory')), [
            'cache' => false,
        ]);
        $twig->addExtension(new IntlExtension());

        try {
            $twig->display('_internal/errors/fatal.twig', [
                'config' => ['protocol' => config::getSetting('protocol'), 'domain' => config::getSetting('domain')],
                'errors' => self::getErrors()
            ]);
        }
        catch (LoaderError|SyntaxError|RuntimeError $e) {
            logger::writeToLogDated($e->getMessage(), 'error');
            // Pretty attempt failed, so let's just echo a generic technical message
            echo '<br>A technical error occurred and engineers notified, please try again shortly.';
        }
        exit(-1);
    }
}