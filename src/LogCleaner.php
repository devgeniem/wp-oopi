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
     *
     * This deletes all rows with OK status by ID except the latest one.
     * This enables keeping the log table clean while maintaining the
     * ability to rollback individual posts.
     *
     * @return void
     */
    public static function run() : void {
        \error_log( 'Running cron' );
        global $wpdb;

        $table_name = Log::get_table_name();
        $ok_status  = Settings::get( 'log_status_ok' );
        $query      = $wpdb->prepare(
            "
            DELETE wlo1.*
            FROM $table_name wlo1
            JOIN (
                SELECT 
                    wp_id,
                    COALESCE(
                        (
                            SELECT import_date_gmt
                            FROM $table_name wlo2
                            WHERE wlo2.wp_id = wlo4.wp_id
                                AND wlo2.status = %s
                            ORDER BY
                                wlo2.wp_id DESC, wlo2.import_date_gmt DESC
                            LIMIT 1, 1
                        ),
                        CAST('0001-01-01' AS DATETIME)
                    ) AS mts,
                    COALESCE(
                        (
                            SELECT id
                            FROM $table_name wlo2
                            WHERE wlo2.wp_id = wlo4.wp_id
                                AND wlo2.status = %s
                            ORDER BY
                                wlo2.wp_id DESC, wlo2.import_date_gmt DESC, wlo2.id DESC
                            LIMIT 1, 1
                        ),
                        -1
                    ) AS mid
                FROM (
                    SELECT DISTINCT wp_id
                    FROM $table_name wlo3
                ) wlo4
            ) wlo3
            ON wlo1.wp_id = wlo3.wp_id
            AND (wlo1.import_date_gmt, wlo1.id) <= (mts, mid)
            WHERE wlo1.status = %s
            ",
            [
                $ok_status,
                $ok_status,
                $ok_status,
            ]
        );

        $wpdb->query( $query );

        \error_log( 'OOPI log table cleaned' );
    }
}
