<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

/**
 * Defines a CSL citation library held in the WordPress database.
 * 
 * Each instance of this class manages a named database of CSL entries.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/ShadyChars/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class CslLibrary
{
    // Names of key and CSL values columns
    private const KEY = 'csl_key';
    private const CSL_VALUE = 'csl_value';

    // Specified scope and corresponding table name.
    private $scope;
    private $table_name;

    // Set of known scopes.
    private static $scopes = array();

    // Cached query results. This is a static array shared by all CslLibrary
    // instances, accessed using the static add_or_update_cached_value() and 
    // get_cached_value() methods.
    private static $cached_keys_to_entries = array();

    /**
     * Constructor. Give access to key/CSL value pairs scoped to a given name.
     * Each scope is managed as a separate database table.
     *
     * @param string $scope
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @link https://github.com/ShadyChars/Bibcite
     * @package Bibcite
     * @since 1.0.0
     */
    public function __construct(string $scope)
    {
        global $wpdb;
        $logger = new ScopedLogger(Logger::instance(), __METHOD__ ." - ");

        // Record the requested scope, then create a DB-friendly, prefixed 
        // version of it.
        $this->scope = $scope;
        $this->table_name = $wpdb->prefix . BIBCITE_PREFIX . "_" . md5($scope);
        $logger->debug(
            "Using table name '$this->table_name' for scope '$scope'"
        );

        // If we've already created the table, exit now.
        if (\in_array($this->scope, self::$scopes))
            return;

        // Create the DB, if it doesn't already exist.
        $charset_collate = $wpdb->get_charset_collate();
        $key_name = self::KEY;  // Can't add const to double-quoted string.
        $value_name = self::CSL_VALUE;
        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
			$key_name varchar(128) NOT NULL,
			$value_name text NOT NULL,
			PRIMARY KEY  ($key_name)
		) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Done. Record the scope so we don't try to recreate the table.
        self::$scopes[] = $this->scope;
    }

    /**
     * Clear out cached data.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function clear_cache() {
        self::uninstall();
    }

    /**
     * Drop all tables created by this class.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function uninstall() {

        $logger = new ScopedLogger(Logger::instance(), __METHOD__ ." - ");

        // Clear our static caches.
        self::$scopes = array();
        self::$cached_keys_to_entries = array();

        // Clear our DBs.
        global $wpdb;

        $table_prefix = $wpdb->prefix . BIBCITE_PREFIX . "_";
        $tables = $wpdb->get_results("SHOW TABLES");
        foreach ($tables as $table)
            foreach ($table as $table_name)
                if (strncasecmp(
                    $table_name, $table_prefix, strlen($table_prefix)
                ) == 0) {
                    $logger->debug("Dropping table: $table_name...");
                    $wpdb->query("DROP TABLE $table_name");
                }
    }

    /**
     * Get the scope of this library.
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Add a CSL entry as a single JSON object.
     *
     * @param string $key the key that identifies the entry
     * @param string $csl_json_object a CSL JSON object describing the citation
     * @return void
     */
    public function add_or_update(string $key, $csl_json_object)
    {
        // Update our local cache first.
        self::add_or_update_cached_value($this->scope, $key, $csl_json_object);

        // Next, serialise to a JSON string and write to the DB.
        $csl_json_string = json_encode($csl_json_object);

        global $wpdb;
        $wpdb->replace(
            $this->table_name, 
            array(self::KEY => $key, self::CSL_VALUE => $csl_json_string)
        );        
    }

    /**
     * Get a single CSL JSON object from the library corresponding to a supplied 
     * key.
     *
     * @param string $key the citation key to search for
     * @return object|false returns the CSL JSON object if found and false if 
     * not
     */
    public function get(string $key)
    {
        // Can we return this from our local cache?
        $cached_value = self::get_cached_value($this->scope, $key);
        if ($cached_value !== false)
            return $cached_value;

        // If not, get the result from our table and deserialize it.
        global $wpdb;
        $key_name = self::KEY;
        $row = $wpdb->get_row(
            "SELECT * FROM $this->table_name WHERE $key_name='$key'"
        );
        
        if ($row) {
            // Deserialize to PHP object
            $csl_json_string = $row->{self::CSL_VALUE};

            // Add to our local cache so we can avoid hitting the DB next time.
            $csl_json_object = json_decode($csl_json_string);
            self::add_or_update_cached_value(
                $this->scope, $key, $csl_json_object
            );

            // And return.
            return $csl_json_object;
        } else {
            return false;
        }
    }

    /**
     * Add a CSL entry as a single JSON object to our local cache.
     *
     * @param string $scope the scope in which the (key, value) pair lives
     * @param string $key the key that identifies the entry
     * @param string $csl_json_object a CSL JSON object describing the citation
     * @return void
     */
    private static function add_or_update_cached_value(
        string $scope, string $key, $csl_json_object
    ) {
        $scoped_key = "$scope:$key";
        self::$cached_keys_to_entries[$scoped_key] = $csl_json_object;
    }

    /**
     * Get a single CSL JSON object from our local cache.
     *
     * @param string $scope the scope in which the key lives
     * @param string $key the citation key to search for
     * @return object|false returns the CSL JSON object if found and false if 
     * not
     */
    public static function get_cached_value(string $scope, string $key) {
        
        $scoped_key = "$scope:$key";
        if (isset(self::$cached_keys_to_entries[$scoped_key]))
            return self::$cached_keys_to_entries[$scoped_key];
        else 
            return false;
    }
}
