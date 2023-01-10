<?php
/**
 * neuFramework v5 - Database Object
 */

namespace neufw\app\core;

use PDO;
use PDOException;

/**
 * A database object, oriented around ease of OO access to multiple PDO connections without use of globals
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class database {
    /**
     * @var         array          $connections        An array of stored database connections for use
     */
    protected static array $connections = [];
    
    /**
     * new database()
     *
     * Create a database connection and save it
     *
     * @access      public
     *
     * @param       string         $user               User to login as
     * @param       string         $pass               Password of the account
     * @param       string         $database           Name of the database to open
     * @param       array|string   $connection         An array with connection info or a string to a socket file
     *
     * @return      void
     */
    public function __construct(string $user, string $pass, string $database, array|string $connection) {
        /*  Available $connection keys and values;
         *  socket: An accessible socket file to access the database over rather than TCP settings below
         *  host: A hostname or IP address to connect to
         *  port: The TCP port to connect to
         *  tls: If provided, an array of TLS details in the form of cert, key and ca key names.
         */

        try {
            // Are we using a socket?
            if (is_string($connection) === true && $connection !== '' && is_readable($connection) === true) {
                $pdo = new PDO(
                    sprintf("mysql:dbname=%s;unix_socket=%s;charset=utf8mb4",
                        $database,
                        $connection
                    ),
                    $user,
                    $pass
                );
            }
            // Nope, do a normal TCP connection
            else {
                $pdo = new PDO(
                    sprintf("mysql:dbname=%s;host=%s;port=%s;charset=utf8mb4",
                        $database,
                        $connection['host'],
                        $connection['port']
                    ),
                    $user,
                    $pass
                );

                // Check if we need to define certificates for a TLS connection
                if (array_key_exists('tls', $connection) === true && is_array($connection['tls']) === true) {
                    $pdo->setAttribute(PDO::MYSQL_ATTR_SSL_KEY, $connection['tls']['key']);
                    $pdo->setAttribute(PDO::MYSQL_ATTR_SSL_CERT, $connection['tls']['cert']);
                    $pdo->setAttribute(PDO::MYSQL_ATTR_SSL_CA, $connection['tls']['ca']);
                }
            }

            // Disable emulated prepares as a common security measure
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Handling errors as exceptions tends to be cleaner
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            // Something went wrong
            trigger_error('Attempt to connect to database failed.\nDB returned; ' . $e->getMessage(), E_USER_ERROR);
        }

        // Set the connection in our array of resources
        self::$connections[$database] = $pdo;
    }
    
    /**
     * database::getInstance()
     *
     * Get the requested PDO instance by database name
     *
     * @access      public
     *
     * @param       string         $dbName             Database name reference to pull the instance of
     *
     * @return      PDO|null       The PDO database resource/connection of this instance or null if it doesn't exist
     */
    public static function getInstance(string $dbName): ?PDO {
        if (array_key_exists($dbName, self::$connections) === true) {
            return self::$connections[$dbName];
        }
        return null;
    }
    
    /**
     * database::closeInstance()
     *
     * Close and unset a stored/active database reference
     *
     * @access      public
     *
     * @param       string         $dbName             Database name reference to close the instance of
     *
     * @return      bool           Boolean true if instance existed and was unset, false if it did not exist
     */
    public static function closeInstance(string $dbName): bool {
        if (array_key_exists($dbName, self::$connections) === true) {
            self::$connections[$dbName] = null;
            unset(self::$connections[$dbName]);
            
            return true;
        }
        return false;
    }
}
