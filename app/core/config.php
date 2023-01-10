<?php
/**
 * neuFramework v5 - Config Object
 */

namespace neufw\app\core;

use Dotenv\Dotenv;
use Dflydev\DotAccessData;
use Exception;
use RuntimeException;

/**
 * Object for handling configuration settings
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class config {
    /**
     * @var         array          $settings           Array of configuration settings
     */
    static protected array $settings;
    
    /**
     * config::verifyRequired()
     *
     * Method for loaders to check required settings against the loaded settings
     *
     * @access      private
     *
     * @param       array          $required           An array of required options
     *
     * @return      bool
     */
    private static function verifyRequired(array $required): bool {
        return (count($required) === count(array_intersect($required, array_keys(self::$settings), true)));
    }
    
    /**
     * config::dotEnvLoader()
     *
     * Load config variables from a .env file variables
     *
     * @access      private
     *
     * @param       array          $options            An array of required options
     *
     * @return      void
     */
    private static function dotEnvLoader(array $options = []): void {
        /*  Available $options keys and values;
         *  directory: A string value of the exact directory to look in
         *  filename: A string value of the config filename to look for
         *  required: An array value of required/expected configuration values
         *  lowercase_keys: boolean, true to strtolower() all key names (default)
         *  multidimensional: boolean, true to convert dot notated strings to a multidimensional array
         */
        
        // If a directory was provided use it, otherwise default to /app directory (DIRECTORY_ROOT . '/app')
        $directory = (array_key_exists('directory', $options) === true) ? $options['directory'] : __DIR__ . '/..';
        
        // If a filename was provided use it, otherwise default to .env
        $filename = (array_key_exists('filename', $options) === true) ? $options['filename'] : '.env';
        
        // Load the .env file formatted as an array for easy processing
        if (($dotenv = Dotenv::createArrayBacked($directory, $filename)->safeLoad()) === null) {
            logger::writeToLogDated(sprintf('Was unable to find and/or load %s under the %s directory', $filename, $directory), 'error');
            trigger_error('Unable to load the requested configuration file', E_USER_ERROR);
        }
        
        // phpdotenv has its own required checker, we'll use it instead of ours
        if (array_key_exists('required', $options) === true) {
            try {
                $dotenv->required($options['required']);
            }
            catch (RuntimeException $e) {
                trigger_error('Required configuration values were not found during config loading', E_USER_ERROR);
            }
        }
        
        // Now run through all the options and apply them into our local settings array
        foreach ($dotenv as $key => $value) {
            if (array_key_exists('lowercase_keys', $options) === false || (array_key_exists('lowercase_keys', $options) === true && $options['lowercase_keys'] === true)) {
                $key = strtolower($key);
            }
            
            // phpdotenv doesn't support a multidimensional perspective, so we can do some trickery here
            if (array_key_exists('multidimensional', $options) === true && $options['multidimensional'] === true && str_contains($key, '.') === true) {
                $data = new DotAccessData\Data;
                $data->set($key, $value);
                self::$settings = array_merge_recursive(self::$settings, $data->export());
            }
            else {
                self::$settings[$key] = $value;
            }
            
        }
    }
    
    /**
     * config::jsonLoader()
     *
     * Load config variables from ENV variables
     *
     * @access      private
     *
     * @param       array          $options            An array of required options
     *
     * @return      void
     */
    private static function jsonLoader(array $options): void {
        /*  Available $options keys and values;
         *  directory: A string value of the exact directory to look in
         *  filename: A string value of the config filename to look for
         *  required: An array value of required/expected configuration values
         *  lowercase_keys: boolean, true to strtolower() all key names (default)
         */
        
        // If a directory was provided use it, otherwise default to /app directory (DIRECTORY_ROOT . '/app')
        $directory = (array_key_exists('directory', $options) === true) ? $options['directory'] : __DIR__ . '/..';
        
        // If a filename was provided use it, otherwise default to .env
        $filename = (array_key_exists('filename', $options) === true) ? $options['filename'] : 'config.json';
        
        // Check that file exists
        $file = $directory . '/' . $filename;
        if (is_readable($file) !== true || ($data = file_get_contents($file)) === false) {
            logger::writeToLogDated(sprintf('Was unable to find and/or load %s under the %s directory', $filename, $directory), 'error');
            trigger_error('Unable to load the requested configuration file', E_USER_ERROR);
        }
        
        // Decode the JSON associatively
        try {
            $json = json_decode($data, true, 2, JSON_THROW_ON_ERROR|JSON_BIGINT_AS_STRING);
        }
        catch (Exception $e) {
            logger::writeToLogDated(sprintf('The following error occurred during JSON decoding; %s', $e->getMessage()), 'error');
            trigger_error('Unable to load the requested configuration file', E_USER_ERROR);
        }
        
        foreach ($json as $key => $value) {
            if (array_key_exists('lowercase_keys', $options) === false || (array_key_exists('lowercase_keys', $options) === true && $options['lowercase_keys'] === true)) {
                $key = strtolower($key);
            }
            self::$settings[$key] = $value;
        }
        
        // Making sure we should have all the requested configuration options if defined
        if ((array_key_exists('required', $options) === true) && self::verifyRequired($options['required']) !== true) {
            trigger_error('Required configuration values were not found during config loading', E_USER_ERROR);
        }
    }
    
    /**
     * config::envLoader()
     *
     * Load config variables from ENV variables
     *
     * @access      private
     *
     * @param       array          $options            An array of required options
     *
     * @return      void
     */
    private static function envLoader(array $options): void {
        /*  Available $options keys and values;
         *  required: An array of explicit config/env names to require/fetch for
         *  lowercase_keys: boolean, true to strtolower() all key names (default)
         */
        
        /*  This is ultimately the least recommended model of loading, getenv() is not exactly
         *  performance-efficient at all, but more notable getenv() is not thread-safe.
         *
         *  We also use getenv() for 'best speed' and cause of $_ENV potential issues from variables_order
         */
        foreach (getenv() as $key => $value) {
            // Design assumes everything is /prefixed/ with CONFIG_ just to avoid normal env variables
            if (str_starts_with($key, 'CONFIG_',) === true) {
                $key = str_replace('CONFIG_', '', $key);
                if (array_key_exists('lowercase_keys', $options) === false || (array_key_exists('lowercase_keys', $options) === true && $options['lowercase_keys'] === true)) {
                    $key = strtolower($key);
                }
                self::$settings[$key] = $value;
            }
        }
        
        // Making sure we should have all the requested configuration options if defined
        if ((array_key_exists('required', $options) === true) && self::verifyRequired($options['required']) !== true) {
            trigger_error('Required configuration values were not found during config loading', E_USER_ERROR);
        }
    }
    
    /**
     * config::initialize()
     *
     * Create and populate the settings array
     *
     * @access      public
     *
     * @param       string         $mechanism          Type of mechanism to load config values from
     * @param       array          $options            An array of relevant option variables for given mechanism
     *
     * @return      void
     */
    public static function initialize(string $mechanism, array $options): void {
        // Some framework defaults that need to always exist, can be overrode via config load
        self::$settings = [
            // Environment (dev/staging/production)
            'environment' => 'dev',
            
            // Protocol (http, https, etc)
            'protocol' => 'https',
            
            // Default session name
            'session_name' => 'neusession',
            
            // Log files location (prevent access via httpd if in app)
            'logs_directory' => $_SERVER['DOCUMENT_ROOT'] . '/app/logs/',
            
            // Template directory for twig templates and cache
            'twig_directory' => $_SERVER['DOCUMENT_ROOT'] . '/app/templates/',
            'twig_cache_directory' => $_SERVER['DOCUMENT_ROOT'] . '/app/cache/templates/'
        ];
        
        // Could do this through some dynamic trickery but just doing it this way for now
        $mechanism = strtolower($mechanism);
        if ($mechanism === 'dotenv') {
            self::dotEnvLoader($options);
        }
        if ($mechanism === 'json') {
            self::jsonLoader($options);
        }
        if ($mechanism === 'env') {
            self::envLoader($options);
        }
        
        // We're going through all this we're at least expecting one record right?
        if (count(self::$settings) === 0) {
            trigger_error('No configuration records were found during load, expected at least one.', E_USER_ERROR);
        }
    }
    
    /**
     * config::getSetting()
     *
     * Get a specific configuration setting
     *
     * @access      public
     *
     * @param       string         $key                Array key name of the setting to get
     *
     * @return      mixed          Value of the setting if it exists, else NULL
     */
    public static function getSetting(string $key): mixed {
        if (isset(self::$settings[$key]) === true) {
            return self::$settings[$key];
        }
        return null;
    }
    
    /**
     * config::getAllSettings()
     *
     * Get all configuration settings
     *
     * @access      public
     *
     * @return      mixed          A return of the settings array
     */
    public static function getAllSettings(): array {
        return self::$settings;
    }
    
    /**
     * config::setSetting()
     *
     * Set existing or Add a new configuration setting
     *
     * @access      public
     *
     * @param       string         $key                Array key name of to add or update
     * @param       mixed          $value              Value to add with or update to
     *
     * @return      void
     */
    public static function setSetting(string $key, mixed $value): void {
        self::$settings[$key] = $value;
    }
    
    /**
     * config::deleteSetting()
     *
     * Remove an existing configuration setting
     *
     * @access      public
     *
     * @param       string         $key                Array key name to remove (unset)
     *
     * @return      void
     */
    public static function deleteSetting(string $key): void {
        unset(self::$settings[$key]);
    }
}
