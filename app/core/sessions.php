<?php
/**
 * neuFramework v5 - Sessions Class
 */

namespace neufw\app\core;

use PDO;
use PDOException;

/**
 * Session class w/ some basic level user auth definitions for getting off the ground
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class sessions {
    /**
     * @var         int            Value of an account with an enabled/active status
     */
    public const STATUS_ENABLED = 1;
    /**
     * @var         int            Value of an account with a disabled/inactive status
     */
    public const STATUS_DISABLED = 0;
    /**
     * @var         int            Value of an account with an admin level role
     */
    public const ROLE_ADMIN = 1;
    /**
     * @var         int            Value of an account with a normal level role
     */
    public const ROLE_NORMAL = 10;
    
    /**
     * @var         PDO            $database           Database instance to utilize for sessions
     */
    protected PDO $database;
    /**
     * @var         array          $userdata           Userdata of logged in/authenticated user
     */
    public array $userdata;
    
    /**
     * $sessions = new sessions()
     *
     * Define the main session settings and attempt to initialize sessions
     *
     * @access      public
     *
     * @param       string         $sessionName        Name of the session
     * @param       string|null    $sessionPath        Path to session file directory
     * @param       int            $sidLength          Session/SID hash length (22 to 256)
     * @param       int            $sidBits            Bits per character (4, 5 or 6)
     * @param       string|null    $dbName             Name of the database (instance) for sessions
     */
    public function __construct(string $sessionName, ?string $sessionPath = null, int $sidLength = 48, int $sidBits = 5, string $dbName = null) {
        if ($dbName !== null) {
            // Get the database instance
            $this->database = Database::getInstance($dbName);
        }
        
        // Set session configuration
        if ($sessionPath !== NULL) {
            if (is_writable($sessionPath) === true) {
                session_save_path($sessionPath);
            }
            else {
                trigger_error('Unable to access session path(s)', E_USER_WARNING);
            }
        }
        
        // Session options
        $options = [
            'use_trans_sid' => 0,
            'use_only_cookies' => 1,
            'gc_probability' => 1,
            'gc_maxlifetime' => 86400,
            'sid_length' => $sidLength,
            'sid_bits_per_character' => $sidBits,
        ];
        
        // Try to start the session
        $this->init($sessionName, $options);
    }
    
    /**
     * $sessions->init()
     *
     * Get a specific configuration setting
     *
     * @access      private
     *
     * @param       string         $sessionName        Name of the session
     * @param       array          $options            Session configuration options
     *
     * @return      bool           Boolean based on if session_start() properly initialized
     */
    private function init(string $sessionName, array $options): bool {
        // Try to start the session
        session_name($sessionName);
        session_set_cookie_params([
            'domain' => config::getSetting('domain'),
            'path' => '/',
            'lifetime' => 86400,
            'httponly' => true,
            'secure' => true,
            'samesite' => 'Strict',
        ]);
        if (session_start($options) === false) {
            trigger_error('[$auth->init] Could not start the session.', E_USER_WARNING);
            return false;
        }
        return true;
    }
    
    /**
     * $sessions->login()
     *
     * Handle account login
     *
     * @access      public
     *
     * @param       string         $user               Given username/email identity
     * @param       string         $pass               Provided password to compare to
     *
     * @return      bool           Boolean based on a valid login
     */
    public function login(string $user, string $pass): bool {
        // Pull the user data to compare to
        try {
            // Fetching just the password_hash() string
            $result = $this->database->prepare("SELECT `password` FROM `users` WHERE `username`=:username AND `status`=1");
            $result->bindParam(":username", $user, PDO::PARAM_STR);
            $result->execute();
            
            // Check for row/data
            if ($result->rowCount() === 0 || ($data = $result->fetch(PDO::FETCH_OBJ)) === false) {
                // No user found
                return false;
            }
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
        
        // Check password
        if (password_verify($pass, $data->password) === false) {
            // Invalid password
            return false;
        }
        
        // Valid password check, set session user for validate use
        $_SESSION['username'] = $user;
        
        // Return success
        return true;
    }
    
    /**
     * $sessions->validate()
     *
     * Check if existing session is valid and populate $auth->userdata with relevant data
     *
     * @access      public
     *
     * @return      bool           Boolean based on a valid existing session
     */
    public function validate(): bool {
        // Check if user is currently logged in
        if (isset($_SESSION['username']) === false || $_SESSION['username'] === "") {
            // No existing session, redirect
            header(sprintf("Location: %s/auth/login", Config::getSetting('domain')));
            exit(-1);
        }
        // Reference-friendly for prepared bind
        $user = $_SESSION['username'];
        
        // Pull relevant userdata
        try {
            $result = $this->database->prepare("SELECT `id`,`username`,`full_name`,`email`,`status`,`role` FROM `users` WHERE `username`=:username AND `status`=1");
            $result->bindParam(":username", $user, PDO::PARAM_STR);
            $result->execute();
            
            // Check for row/data
            if ($result->rowCount() === 0 || ($data = $result->fetch(PDO::FETCH_OBJ)) === false) {
                // No data (suspended, deleted, etc user)
                header(sprintf("Location: %s/auth/login", Config::getSetting('domain')));
                exit(-1);
            }
        }
        catch (PDOException $e) {
            trigger_error(sprintf("Database query failure during auth validation; %s", $e->getMessage()), E_USER_ERROR);
        }
        
        // Build the userdata array for $auth->userdata[] accessibility
        foreach ($data as $key => $value) {
            $this->userdata[$key] = $value;
        }
        
        // Validity confirmed
        return true;
    }
    
    /**
     * $sessions->logout()
     *
     * Session logout and flush
     *
     * @access      public
     *
     * @return      void
     */
    public function logout(): void {
        // Unset the session cookie
        setcookie(session_name(), session_id(), time()-86400);
        
        // Flush session
        $_SESSION = array();
        
        // Cycle and destroy the session
        session_destroy();
    }
}

