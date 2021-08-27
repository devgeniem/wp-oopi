<?php
/**
 * The Log class file.
 */

namespace Geniem\Oopi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Log
 *
 * This class controls logging into the custom database table.
 *
 * @package Geniem\Oopi
 */
class Log {

    /**
     * Log id.
     *
     * @var int
     */
    protected $id;

    /**
     * The importer id of the logged item.
     *
     * @var string
     */
    protected $oopi_id;

    /**
     * The WordPress item id of the logged item.
     *
     * @var int
     */
    protected $wp_id;

    /**
     * The gmt timestamp of the log.
     *
     * @var string
     */
    protected $import_date_gmt;

    /**
     * Importer post data.
     *
     * @var object|string
     */
    protected $data;

    /**
     * Import status.
     *
     * @var string
     */
    protected $status;

    /**
     * Get the id.
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the importer id.
     *
     * @return string
     */
    public function get_oopi_id() {
        return $this->oopi_id;
    }

    /**
     * Get the WP item id.
     *
     * @return int
     */
    public function get_wp_id() {
        return $this->wp_id;
    }

    /**
     * Get the log timestamp.
     *
     * @return mixed
     */
    public function get_import_date_gmt() {
        return $this->import_date_gmt;
    }

    /**
     * Get importer post data.
     *
     * @return object
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get import status.
     *
     * @return string
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Log constructor.
     *
     * An instance can be made out of a Importer Post object or from a log entry data.
     * If the $data is a Post instance, a new log entry is saved automatically.
     *
     * @param mixed $data The data from which the log instance is parsed.
     */
    public function __construct( $data ) {
        // This is an importer Post object. Save the log entry.
        if ( $data instanceof Post ) {
            // Get status texts.
            $ok_status   = Settings::get( 'log_status_ok' );
            $fail_status = Settings::get( 'log_status_fail' );

            // Data for the log entry.
            $this->oopi_id         = $data->get_oopi_id();
            $this->wp_id           = $data->get_wp_id();
            $this->import_date_gmt = \current_time( 'mysql', true );
            $this->data            = $data->to_json();
            $this->status          = empty( $data->get_errors() ) ? $ok_status : $fail_status;
        }
        // This is fetch.
        else {
            $this->oopi_id         = isset( $data->oopi_id ) ? $data->oopi_id : null;
            $this->wp_id           = isset( $data->wp_id ) ? (int) $data->wp_id : null;
            $this->import_date_gmt = isset( $data->import_date_gmt ) ? $data->import_date_gmt : null;
            $this->data            = isset( $data->data ) ? $data->data : null;
            $this->status          = isset( $data->status ) ? $data->status : null;

            // Data might not be decoded yet.
            $this->data = Util::is_json( $this->data ) ? json_decode( $this->data ) : $this->data;
        }
    }

    /**
     * Save the log entry into the database.
     *
     * @return int|false The number of rows inserted, or false on error.
     */
    public function save() {
        global $wpdb;

        // Insert into database.
        $table = $wpdb->prefix . Settings::get( 'table_name' );
        return $wpdb->insert( // phpcs:ignore
            $table,
            [
                'oopi_id'         => $this->oopi_id,
                'wp_id'           => $this->wp_id,
                'import_date_gmt' => $this->import_date_gmt,
                'data'            => $this->data,
                'status'          => $this->status,
            ],
            [
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );
    }

    /**
     * Fetches the last successful import from the database for a given post id.
     *
     * @param integer $post_id A WP post id.
     *
     * @return Log|null
     */
    public static function get_last_successful_import( $post_id ) : ?Log {
        global $wpdb;

        $table_name = static::get_table_name();
        $ok_status  = Settings::get( 'log_status_ok' );

        // phpcs:disable
        $row = $wpdb->get_row( $wpdb->prepare(
            "
            SELECT * FROM $table_name
            WHERE wp_id = %d
            AND status = %s
            ORDER BY import_date_gmt DESC;
            ",
            $post_id,
            $ok_status
        ) );
        // phpcs:enable

        if ( $row !== null ) {
            // Make things visible and help IDEs to interpret the object.
            return new Log( $row );
        }

        return null;
    }

    /**
     * Get the logger database table name.
     *
     * @return string
     */
    public static function get_table_name() : string {
        global $wpdb;
        return $wpdb->prefix . Settings::get( 'table_name' );
    }

    /**
     * Create the logger database table on install.
     */
    public static function install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name      = static::get_table_name();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
              id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
              oopi_id VARCHAR(255) NOT NULL,
              wp_id BIGINT(20) UNSIGNED,
              import_date_gmt DATETIME NULL,
              data LONGTEXT NOT NULL,
              status VARCHAR(10) NOT NULL,
              PRIMARY KEY (id),
              INDEX oopi_id (oopi_id(255)),
              INDEX postid_date (wp_id, import_date_gmt, status(10))
            ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $response = dbDelta( $sql );
    }
}
