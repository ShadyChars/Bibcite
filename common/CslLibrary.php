<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor\autoload.php';

/**
 * Defines a citation library held in the WordPress database.
 *
 * @link       https://github.com/OrkneyDullard/bibcite
 * @since      1.0.0
 *
 * @package    Bibcite/Common
 */

/**
 * Defines a CSL citation library held in the WordPress database.
 * 
 * Each instance of this class manages a named database of CSL entries.
 *
 * @since      1.0.0
 * @package    Bibcite/Common
 * @author     Keith Houston <keith@shadycharacters.co.uk>
 */
class CslLibrary
{
    // Names of key and CSL values columns
    private const KEY = 'csl_key';
    private const CSL_VALUE = 'csl_value';

    // Source URL and corresponding table name.
    private $name;
    private $table_name;

    /**
     * Create a DB table for the specified name, if one does not already exist.
     */
    public function __construct($name)
    {
        global $wpdb;

        // Record the requested table name, then create a DB-friendly, prefixed version.
        $this->name = $name;
        $this->table_name = $wpdb->prefix . BIBCITE_SC_PREFIX . "_" . md5($name);
        Logger::instance()->debug("Using table ($name) with name ($this->table_name).");

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

        global $wpdb;

        $table_prefix = $wpdb->prefix . BIBCITE_SC_PREFIX . "_";
        $tables = $wpdb->get_results("SHOW TABLES");
        foreach ($tables as $table)
            foreach ($table as $table_name)
                if (strncasecmp($table_name, $table_prefix, strlen($table_prefix)) == 0) {
                    \Bibcite\Common\Logger::instance()->debug("Dropping table: $table_name...");
                    $wpdb->query("DROP TABLE $table_name");
                }
    }

    /**
     * Get the name of this library.
     */
    public function getName()
    {
        return $this->name;
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
        // Serialise to a JSON string
        $csl_json_string = json_encode($csl_json_object);

        global $wpdb;
        $wpdb->replace(
            $this->table_name,
            array( self::KEY => $key, self::CSL_VALUE => $csl_json_string)
        );
    }

    /**
     * Get a single CSL JSON object from the library corresponding to a supplied key.
     *
     * @param string $key the citation key to search for
     * @return object|false returns the CSL JSON object if found and false if not.
     */
    public function get($key)
    {
        global $wpdb;
        $key_name = self::KEY;
        $row = $wpdb->get_row(
            "SELECT * FROM $this->table_name WHERE $key_name='$key'"
        );
        
        if ($row) {
            $csl_json_string = $row->{self::CSL_VALUE};
            return json_encode($csl_json_string);
        } else {
            return false;
        }
    }
}
