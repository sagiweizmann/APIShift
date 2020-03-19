<?php
/**
 * APIShift Engine v1.0.0
 * (c) 2020-present Sapir Shemer, DevShift (devshift.biz)
 * Released under the MIT License with the additions present in the LICENSE.md
 * file in the root folder of the APIShift Engine original release source-code
 * @author Sapir Shemer
 */

 namespace APIShift\Core;

 /**
  * Manages cache data on your command, sir!
  */
 class CacheManager {
    // Cache types
    public const APCU = 0;
    public const MEMCACHED = 1;
    public const REDIS = 2;
    // Holds the connection object to call the cache system
    private static $cache_connection = null;

    /**
     * Initializes the cache system and validates accessibility
     */
    public static function initialize() {
        switch(Configurations::CACHE_TYPE) {
            case self::APCU:
                if(!extension_loaded("apcu")) Status::message(Status::ERROR, "Please install/enable APCu or configure to use another system (Redis/Memcached)");
                break;
            case self::MEMCACHED:
                if(!extension_loaded("memcached")) Status::message(Status::ERROR, "Please install/enable Memcached or configure to use another system (APCu/Redis)");
                self::$cache_connection = new Memcached('_');
                $result = self::$cache_connection->addServer(Configurations::CACHE_HOST, Configurations::CACHE_PORT);
                if(!$result) Status::message("Memcached: Couldn't start connection with cache host, please check host name/port");
            break;
            case self::REDIS:
                if(!extension_loaded("redis")) Status::message(Status::ERROR, "Please install/enable Redis or configure to use another system (APCu/Memcached)");
                self::$cache_connection = new Redis();
                $result = self::$cache_connection->connect(Configurations::CACHE_HOST, Configurations::CACHE_PORT);
                if(!$result) Status::message("Redis: Couldn't start connection with cache host, please check host name/port");
                $result = self::$cache_connection->auth(Configurations::CACHE_PASS);
                if(!$result) Status::message("Redis: Couldn't authenticate credentials with cache system");
            break;
            default:
                Status::message(Status::ERROR, "Unrecognized cache system, please check your configurations");
        }
    }

     /**
      * Load default cache data
      * @param bool $refresh Set true to refresh the cache data
      */
    public static function loadDefaults(bool $refresh = false) {
        // Initialize cache system
        self::initialize();

        // Get session states into cache if not cached
        if($refresh || !self::exists('StateCollection')) {
            $collection_to_load = [];
            if(DatabaseManager::fetchInto("main", $collection_to_load, "SELECT * FROM session_states", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of States");
            // Load to cache
            self::set('StateCollection', $collection_to_load);
        }

        // Load available return statuses
        if($refresh || !self::exists('StatusCollection')) {
            $temp_statuses = [];
            if(DatabaseManager::fetchInto("main", $temp_statuses, "SELECT * FROM statuses", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of Statuses");
            self::set('StatusCollection', $temp_statuses);
        }

        // Load Data sources to cache
        if($refresh || !self::exists('DataSources')) {
            $temp_sources = [];
            if(DatabaseManager::fetchInto("main", $temp_sources, "SELECT * FROM data_sources", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of data sources");
            self::set('DataSources', $temp_sources);
        }

        // Load Data entries to cache
        if($refresh || !self::exists('DataEntries')) {
            $temp_entries = [];
            if(DatabaseManager::fetchInto("main", $temp_entries, "SELECT * FROM data_entries", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of data entries");
            self::set('DataEntries', $temp_entries);
        }

        // Load Data source types to cache
        if($refresh || !self::exists('DataSourceTypes')) {
            $temp_source_types = [];
            if(DatabaseManager::fetchInto("main", $temp_source_types, "SELECT * FROM data_source_types", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of data source types");
            self::set('DataSourceTypes', $temp_source_types);
        }

        // Load Data entry types to cache
        if($refresh || !self::exists('DataEntryTypes')) {
            $temp_entry_types = [];
            if(DatabaseManager::fetchInto("main", $temp_entry_types, "SELECT * FROM data_entry_types", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of data entry types");
            self::set('DataEntryTypes', $temp_entry_types);
        }

        // Load Connection types to cache
        if($refresh || !self::exists('ConnectionTypes')) {
            $temp_connection_types = [];
            if(DatabaseManager::fetchInto("main", $temp_connection_types, "SELECT * FROM connection_types", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of connection types");
            self::set('ConnectionTypes', $temp_connection_types);
        }

        // Load Connection node types to cache
        if($refresh || !self::exists('ConnectionNodeTypes')) {
            $temp_connection_node_types = [];
            if(DatabaseManager::fetchInto("main", $temp_connection_node_types, "SELECT * FROM connection_node_types", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of connection node types");
            self::set('ConnectionNodeTypes', $temp_connection_node_types);
        }

        // Load Items to cache
        if($refresh || !self::exists('Items')) {
            $temp_items = [];
            if(DatabaseManager::fetchInto("main", $temp_items, "SELECT * FROM items", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of Items");
            self::set('Items', $temp_items);
        }
        
        // Load Relations to cache
        if($refresh || !self::exists('Relations')) {
            $temp_relations = [];
            if(DatabaseManager::fetchInto("main", $temp_relations, "SELECT * FROM relations", [], 'id') === false)
                Status::message(Status::ERROR, "Couldn't retrieve collection of Relations");
            self::set('Relations', $temp_relations);
        }
    }

    /**
     * Check if a given key exists
     * @param string $key Key name to check
     * @return bool TRUE in case exists, FALSE otherwise
     */
    public static function exists($key) {
        switch(Configurations::CACHE_TYPE) {
            case self::APCU: return apcu_exists($key) !== false;
            case self::MEMCACHED:
                return self::$cache_connection->get($key) !== false;
                break;
            case self::REDIS:
                return self::$cache_connection->exists($key) != 0;
                break;
            default:
                Status::message(Status::ERROR, "Unrecognized cache system, please check your configurations");
        }
    }

    
    /**
     * Get value of a given key
     * @param string $key Key name to check
     * @return string|array|bool Value in case exists, FALSE otherwise
     */
    public static function get($key) {
        switch(Configurations::CACHE_TYPE) {
            case self::APCU: return apcu_fetch($key);
            case self::MEMCACHED:
                return self::$cache_connection->get($key);
                break;
            case self::REDIS:
                $value = self::$cache_connection->get($key);
                if(strpos($value, '{') !== false) $value = json_decode($value);
                return $value;
                break;
            default:
                Status::message(Status::ERROR, "Unrecognized cache system, please check your configurations");
        }
    }

    /**
     * Set variable in cache system
     * @param string $key Key name to assign to the data
     * @param mixed $value Value to store upon key
     * @return void
     */
    public static function set($key, $value) {
        switch(Configurations::CACHE_TYPE) {
            case self::APCU:
                apcu_store(Status::ERROR, "Please install/enable APCu");
                break;
            case self::MEMCACHED:
                self::$cache_connection->set($key, $value);
                break;
            case self::REDIS:
                if(gettype($value) != 'array') self::$cache_connection->set($key, $value);
                else self::$cache_connection->set($key, json_encode($value));
                break;
            default:
                Status::message(Status::ERROR, "Unrecognized cache system, please check your configurations");
        }
    }
 }
?>