<?php
/**
 * neuFramework v5 - Cache Object
 */

namespace neufw\app\core;

use Memcached;
use MemcachedException;
use Redis;
use RedisException;

/**
 * VERY generic cache initializer and instance storage
 *
 * @version     v5.0.0
 * @author      Nathan (nate/synapt) Bishop
 * @link        https://github.com/synapt/neuframework Github repo
 * @license     MIT (see LICENSE)
 *
 */
class cache {
    /**
     * @var         array          $instances          An array of potential cache instances for use
     */
    protected static array $instances;
    
    /**
     * cache::redisConnect()
     *
     * Connect to a redis service and return it
     *
     * @access      public
     *
     * @param       array          $details            Relevant Redis connection info
     * @param       string         $prefix             Caching prefix, also used for instance key
     *
     * @return      Redis
     */
    private static function redisConnect(array $details, string $prefix): Redis {
        if (isset(self::$instances[$prefix]) === true) {
            trigger_error('Instance with that name/prefix already exists.', E_USER_ERROR);
        }
        
        try {
            // Redis
            $redis = new Redis();
            $redis->connect($details['host'], $details['port'], $details['connectTimeout']);
            if (isset($details['password']) === true) {
                $redis->auth($details['password']);
            }
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
            $redis->setOption(Redis::OPT_PREFIX, $prefix . ':');
        }
        catch (RedisException $e) {
            // Something went wrong
            trigger_error('Attempt to connect to redis service failed;\n' . $e->getMessage(), E_USER_ERROR);
        }
        
        // Set the instances property
        self::$instances[$prefix] = $redis;
        
        return self::$instances[$prefix];
    }
    
    /**
     * cache::memcachedConnect()
     *
     * Connect to a memcached service and return it
     *
     * @access      public
     *
     * @param       array          $details            Relevant memcached connection info
     * @param       string         $prefix             Caching prefix, also used for instance key
     *
     * @return      Memcached
     */
    private static function memcachedConnect(array $details, string $prefix): Memcached {
        if (isset(self::$instances[$prefix]) === true) {
            trigger_error('Instance with that name/prefix already exists.', E_USER_ERROR);
        }
        
        try {
            // Memcached
            $memcached = new Memcached();
            $memcached->addServer($details['host'], $details['port']);
            if (isset($details['options']) === true && is_array($details['options']) === true) {
                $memcached->setOptions($details['options']);
            }
            $memcached->setOption(Memcached::OPT_PREFIX_KEY, $prefix);
        }
        catch (MemcachedException $e) {
            // Something went wrong
            trigger_error('Attempt to connect to memcached service failed;\n' . $e->getMessage(), E_USER_ERROR);
        }
        self::$instances[$prefix] = $memcached;
        
        return self::$instances[$prefix];
    }
    
    /**
     * cache::open()
     *
     * Create a caching instance/connection to save
     *
     * @access      public
     *
     * @param       string         $type               Caching engine type to initialize
     * @param       array          $details            Array of connection/settings details
     * @param       string         $identifier         Pool specific identifier (such as a key prefix)
     *
     * @return      Memcached|Redis|null
     */
    public static function open(string $type, array $details, string $identifier): Memcached|Redis|null {
        if ($type === 'redis') {
            return self::redisConnect($details, $identifier);
        }
        if ($type === 'memcached') {
            return self::memcachedConnect($details, $identifier);
        }
        
        // Not a valid type
        return null;
    }
    
    /**
     * cache::getInstance()
     *
     * Get the requested cache instance by name
     *
     * @access      public
     *
     * @param       string         $name               Cache instance name reference to fetch
     *
     * @return      Memcached|Redis
     */
    public static function getInstance(string $name): Memcached|Redis {
        if (array_key_exists($name, self::$instances) !== true) {
            trigger_error('Unable to find requested resource.', E_USER_ERROR);
        }
        
        return self::$instances[$name];
    }
}