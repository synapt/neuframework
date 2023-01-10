<?php
/**
 * neuFramework v5 - Utility Object
 */

namespace neufw\app\core;

use \JsonException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * General object for common formulas/duplicate code things
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class utility {
    /**
     * utility::apiV1Response()
     *
     * Generic API standard structure formatter
     *
     * @access      public
     *
     * @param       null|string    $node               Node invoked
     * @param       mixed          $data               Content output of a successful call
     * @param       bool|string    $success            Boolean or string message relative to successful message
     * @param       bool|array     $error              Boolean or array containing error details provided
     *
     * @return      never
     */
    public static function apiV1Response(null|string $node, mixed $data, bool|string $success = true, bool|array $error = false): never {
        header('Content-Type: application/vnd.api+json; charset=utf-8');
        
        // General structure
        $apiStructure = [
            'api' => [
                'version' => 'v1',
                'node' => $node,
                'domain' => config::getSetting('domain'),
            ],
            'error' => $error,
            'success' => $success,
            'data' => $data
        ];
        
        try {
            echo json_encode($apiStructure, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
        exit(-1);
    }
    
    /**
     * utility::httpRedirect()
     *
     * Generic HTTP redirect wrapper.  Meant for internal uses, does not sanitize $url!
     *
     * @access      public
     *
     * @param       string         $url               URL to redirect to
     * @param       bool           $permanent         Send as a 301 Permanent or not
     *
     * @return      void
     */
    public static function httpRedirect(string $url, bool $permanent = false): void {
        if (true === $permanent) {
            http_response_code(301);
        }
        else {
            http_response_code(302);
        }
        
        header('Location: ' . $url);
        exit(-1);
    }
    
    /**
     * utility::validateRoute()
     *
     * Routing loader for dynamic template calls via /loader.php
     *
     * @access      public
     *
     * @param       string         $page               Page name requested
     * @param       null|string    $section            'Section' (folder) name if provided
     *
     * @return      bool|string
     */
    public static function validateRoute(string $page, null|string $section = null): bool|string {
        $twigDirectory = config::getSetting('twig_directory');

        $route = sprintf('%s/%s.twig', $section, $page);
        if ($section !== null
            && preg_match('/^([a-z0-9\-]{1,50})$/i', $section) !== false
            && is_dir($twigDirectory . $section) === true
            && preg_match('/^([a-z0-9\-]{1,75})$/i', $page) !== false
            && is_readable($twigDirectory . $route) === true) {
            return $route;
        }
        
        $route = $page . '.twig';
        if (preg_match('/^([a-z0-9\-]{1,75})$/i', $page) !== false
            && is_readable($twigDirectory . $route) === true) {
            return $route;
        }
        
        return false;
    }
    
    /**
     * utility::checkPostValue()
     *
     * $_POST wrapper to verify an item exists, isn't empty and to set a default if wanted
     *
     * @access      public
     *
     * @param       string         $key                A $_POST key name
     * @param       mixed          $default            A default value to return other than null if not a valid $_POST item
     *
     * @return      string|null    The value of the $_POST item else $default
     */
    public static function checkPostValue(string $key, mixed $default = null): ?string {
        if (isset($_POST[$key]) === false || $_POST[$key] === '') {
            return $default;
        }
        return $_POST[$key];
    }
    
    /**
     * utility::checkGetValue()
     *
     * $_GET wrapper to verify an item exists, isn't empty and to set a default if wanted
     *
     * @access      public
     *
     * @param       string         $key                A $_GET key name
     * @param       mixed          $default            A default value to return other than null if not a valid $_GET item
     *
     * @return      string|null    The value of the $_GET item else null
     */
    public static function checkGetValue(string $key, mixed $default = null): ?string {
        if (isset($_GET[$key]) === false || $_GET[$key] === '') {
            return $default;
        }
        return $_GET[$key];
    }
    
    /**
     * utility::smtpSendEmail()
     *
     * PHPMailer wrapper for sending an email to a single recipient
     *
     * @access      public
     *
     * @param       array          $from               Array defining from email and name
     * @param       array          $replyTo            Array defining the replyTo email and name
     * @param       array          $to                 Array defining the recipient email and name
     * @param       string         $subject            Subject of the email
     * @param       string         $body               Main (or HTML) body of the email
     * @param       string|null    $plainBody          Plain text alternative body if sending HTML email
     *
     * @return      bool           Boolean response of success or failure
     */
    public static function smtpSendEmail(array $from, array $replyTo, array $to, string $subject, string $body, null|string $plainBody = null): bool {
        // Super basic validations
        if (count($from) !== 2) {
            trigger_error('From needs to be an array of the email and associative name.', E_USER_WARNING);
            return false;
        }
        if (count($to) !== 2) {
            trigger_error('To needs to be an array of the email and associative name.', E_USER_WARNING);
            return false;
        }
        if (count($replyTo) !== 2) {
            trigger_error('ReplyTo needs to be an array of the email and associative name.', E_USER_WARNING);
            return false;
        }

        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->SMTPDebug  = SMTP::DEBUG_OFF;
            $mail->SMTPAuth   = true;
            $mail->Host       = config::getSetting('email_host');
            $mail->Username   = config::getSetting('email_user');
            $mail->Password   = config::getSetting('email_pass');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPOptions = config::getSetting('phpmailer_options');
            $mail->Port       = 465;
            $mail->XMailer    = 'neuFramework (https://github.com/synapt/neuframework)';
            
            // Email settings
            $mail->setFrom($from[0], $from[1]);
            $mail->addAddress($to[0], $to[1]);
            $mail->addReplyTo($replyTo[0], $replyTo[1]);
            
            // Body Settings
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if ($plainBody !== null) {
                $mail->isHTML(true);
                $mail->AltBody = $plainBody;
                
            }

            return $mail->send();
        }
        catch (Exception $e) {
            // Log the exception
            trigger_error('An exception was thrown during mailing attempt.', E_USER_WARNING);
            logger::writeToLogDated(sprintf('An exception was thrown during mailing attempt, error was; %s', $e->getMessage()), 'error');

            return false;
        }
    }
}