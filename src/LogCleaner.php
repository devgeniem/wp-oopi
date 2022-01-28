<?php
/**
 * The LogCleaner class file.
 */

namespace Geniem\Oopi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class LogCleaner
 *
 * This class controls cleaning the log database table.
 *
 * @package Geniem\Oopi
 */
class LogCleaner {

    /**
     * The key used for this cron job.
     */
    const KEY = 'clean_log';

    /**
     * The hook used for this cron job.
     */
    const HOOK = 'oopi_cron_clean_log';

    /**
     * This handles cleaning the log table.
     * Deletes all rows older than the set threshold.
     *
     * @return void
     */
    public static function run() : void {
        global $wpdb;

        $table_name = Log::get_table_name();

        /**
         * Allow filtering the statuses to remove.
         * Defaults to removing rows with OK status only.
         * Pass empty array to clean all rows regardless of status.
         */
        $statuses   = \apply_filters(
            'oopi_cron_log_cleaner_statuses',
            [ Settings::get( 'log_status_ok' ) ]
        );
        
        /**
         * Set the threshold for the log cleaner. Rows older than the thresholds
         * are removed. Only use values compatible with MySQL intervals.
         * Empty string can be passed to ignore import date.
         */
        $threshold = \apply_filters(
            'oopi_cron_log_cleaner_threshold',
            '2 WEEK'
        );

        $conditions = [];

        if ( is_array( $statuses ) && ! empty( $statuses ) ) {
            $status_list = '"' . implode( '", "', $statuses ) . '"';
            $conditions[] = "status IN ($status_list)";
        }

        if ( is_string( $threshold ) && ! empty( $threshold ) ) {
            $conditions[] = "import_date_gmt < NOW() - INTERVAL $threshold";
        }

        $where = implode( ' AND ', $conditions );

        $raw_query = "DELETE FROM $table_name";

        if ( ! empty( $where ) ) {
            $raw_query .= " WHERE $where";
        }

        $wpdb->query( $wpdb->prepare( $raw_query ) );
    }
}
