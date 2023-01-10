<?php
/**
 * neuFramework v5 - Logfile Object
 */

namespace neufw\app\core;

use DateTime;
use DateTimeZone;
use RuntimeException;

/**
 * Logging object for general logfile operations
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class logger {
    /**
     * logger::writeToLog()
     *
     * Get a specific configuration setting
     *
     * @access      public
     *
     * @param       string         $message            Message string to write to log
     * @param       string         $file               Log type reference (internally handled) or specific file name
     *
     * @return      bool           Boolean true if wrote to file, false if file access error
     */
    public static function writeToLog(string $message, string $file = 'debug'): bool {
        // Get the config value for directory and double check for proper trailing /
        $directory = config::getSetting('logs_directory');
        if (str_ends_with($directory, '/') === false) {
            $directory .= '/';
        }

        // Overall path and file
        $logFile = $directory . $file . '.log';

        // Check if we can actually write to log or try to create file if it does not exist
        if (is_writable($logFile) === false && touch($logFile) === false) {
            // Odd predicament, we can't write to the log file, let's fallback to PHP error_log
            $phpErrorLog = ini_get('error_log');

            // Better check we can write to this one also
            // Still nope?  Okay let's try to create this one
            if ((is_writable($phpErrorLog) === false) && touch($phpErrorLog) === false) {
                // Okay log issues, so let's subtly echo a message
                throw new RuntimeException('Notice: Check log permissions');
            }

            // Write to PHP error log
            file_put_contents($phpErrorLog, sprintf('NOTICE: Was unable to write to %s, fell back to PHP error_log' . PHP_EOL, $logFile), FILE_APPEND);
            file_put_contents($phpErrorLog, $message . PHP_EOL, FILE_APPEND);
            return true;
        }

        // Write to our error log
        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);

        return true;
    }
    
    /**
     * logger::writeToLogDated()
     *
     * Wrapper to writeToLog() to prefix a timestamp before the message
     *
     * @access      public
     *
     * @param       string         $message            Message string to write to log
     * @param       string         $file               Filename to log to
     * @param       string         $format             DateTime format string for the timestamp style
     *
     * @return      bool           A boolean returned from writeToLog()
     */
    public static function writeToLogDated(string $message, string $file = 'debug', string $format = 'M/d/Y H:i:s'): bool {
        $time = new DateTime('now', new DateTimeZone('America/New_York'));

        // Format the message with a timestamp prefix
        $formattedMessage = sprintf('%s | %s', $time->format($format), $message);

        return self::writeToLog($formattedMessage, $file);
    }
}