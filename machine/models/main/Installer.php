<?php
/**
 * APIShift Engine v1.0.0
 * 
 * Copyright 2020-present Sapir Shemer, DevShift (devshift.biz)
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *  http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @author Sapir Shemer
 */

namespace APIShift\Models\Main;

use APIShift\Core\CacheManager;
use APIShift\Core\DatabaseManager;
use APIShift\Core\Status;
use Exception;

/**
 * Model containing an interface of functions that help manage the installation process of the system
 */
class Installer {
    /**
     * Upload the initial database structure and data for the system to function
     * 
     * @param string $db_host Database host
     * @param string $db_name Database schema name
     * @param string $db_user Database username
     * @param int $db_pass Database password
     * @param string $db_port Database port
     * @param string $user Admin user to install
     * @param string $pass Admin password to install with
     * 
     * @return void
     */
    public function createDB($db_host, $db_name, $db_user, $db_pass, $db_port = 3306, $user, $pass) {
        // Step 1: Connect to DB
        DatabaseManager::addConnection("main", $db_host, $db_user, $db_pass, $db_port, $db_name, false);

        try {
            // Create schema if not exists
            if(Status::getStatus() == Status::DB_CONNECTION_FAILED) {
                DatabaseManager::addConnection("main", $db_host, $db_user, $db_pass, $db_port);
                $add_schema = DatabaseManager::query("main", "CREATE SCHEMA {$db_name}");
                if(!$add_schema) Status::message(Status::ERROR, "Couldn't create DB schema");
                $add_schema = DatabaseManager::query("main", "USE {$db_name}");
                if(!$add_schema) Status::message(Status::ERROR, "Couldn't use DB schema");
            }

            // Step 2: Load sql file of installation, and import the initial data
            $data_to_import = file_get_contents("data/initial.sql");
            if(!$data_to_import) {
                Status::message(
                    Status::ERROR,
                    "Couldn't open initial data SQL file, please check permissions, re-download the system or add missing files"
                );
            }
            DatabaseManager::getInstance("main")->exec($data_to_import);
            
            // Add admin user to DB
            $remove_existing = DatabaseManager::query("main", "TRUNCATE TABLE admin_users");
            if(!$remove_existing) Status::message(Status::ERROR, "Couldn't clear the admin_users table");
            $add_admin = DatabaseManager::query("main",
                "INSERT INTO admin_users (username, password, created) VALUES (:username, :password, NOW())",
                [
                    "username" => $user,
                    "password" => password_hash($pass, PASSWORD_BCRYPT)
                ]
            );
            if(!$add_admin) Status::message(Status::ERROR, "Couldn't upload system user data to DB");
        }
        catch (Exception $e) {
            Status::message(Status::ERROR, "Couldn't upload data to DB " + $e->getMessage());
        }
    }

    /**
     * Modify configurations file using the installation data
     * 
     * @param string $db_host Database host
     * @param string $db_name Database schema name
     * @param string $db_user Database username
     * @param int $db_pass Database password
     * @param int $db_port Database port
     * @param string $db_type Database type ("MySQL" | "MSSQL" | "PGSQL" | "MongoDB", etc..)
     * @param int $cache_system Type of cache system to use (CacheManager::APCU | CacheManager::REDIS | CacheManager::MEMCACHED)
     * @param string $cache_host Host nae of cache server
     * @param int $cache_port Post to connect with cache system
     * @param string $cache_pass Password needed to authenticate cache system
     * 
     * @return void
     */
    public function createConfigFile($db_host, $db_name, $db_user, $db_pass, $db_port = 3306,
                                        $db_type = "MySQL", $cache_system = CacheManager::APCU, $cache_host = "",
                                        $cache_port = 0, $cache_pass = "") {
        // Determine the cache system string
        switch($cache_system) {
            case CacheManager::APCU: $cache_system = "CacheManager::APCU"; break;
            case CacheManager::REDIS: $cache_system = "CacheManager::REDIS"; break;
            case CacheManager::MEMCACHED: $cache_system = "CacheManager::MEMCACHED"; break;
            default: Status::message(Status::ERROR, "Invalid cache system");
        }

        $newConfigFileData = <<<EOT
<?php
/**
 * APIShift Engine v1.0.0
 * 
 * Copyright 2020-present Sapir Shemer, DevShift (devshift.biz)
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *  http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @author Sapir Shemer
 */
        
namespace APIShift\Core;

/**
 * Contains the core configuration to make the system run
 */
class Configurations {
    /**
     * When true system acts as though itshould be installed, false otehrwise
     */
    const INSTALLED = true;

    /**
     * Database login info
     */
    const DB_HOST = "{$db_host}";
    const DB_PORT = {$db_port};
    const DB_USER = "{$db_user}";
    const DB_PASS = "{$db_pass}";
    const DB_NAME = "{$db_name}";
    const DB_TYPE = "{$db_type}";

    /**
     * When true server requires the use oh HTTPS at each request
     */
    const USE_HTTPS = true;

    /**
     * Cache system configurations
     */
    const CACHE_TYPE = {$cache_system};
    const CACHE_HOST = "{$cache_host}";
    const CACHE_PORT = {$cache_port};
    const CACHE_PASS = "{$cache_pass}";
}
EOT;

        if(!file_put_contents("core/Configurations.php", $newConfigFileData))
            Status::message(Status::ERROR, "Couldn't change the configurations file, please check permissions");
    }
  }
?>