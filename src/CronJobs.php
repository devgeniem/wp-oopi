<?php
/**
 * The CronJobs class file.
 */

namespace Geniem\Oopi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CronJobs
 *
 * This class controls cron jobs the plugin registers.
 *
 * @package Geniem\Oopi
 */
class CronJobs {

    /**
     * Register cron jobs.
     *
     * @return void
     */
    public static function install() : void {
        static::schedule_cronjobs();
    }

    /**
     * Terminate cron jobs.
     *
     * @return void
     */
    public static function uninstall() : void {
        static::unschedule_cronjobs();
    }

    /**
     * Return the cronjobs.
     *
     * @return array
     */
    public static function get_cronjobs() : array {
        return [
            LogCleaner::class,
        ];
    }

    /**
     * Register the hooks to fire when cron is run.
     *
     * @return void
     */
    public static function init() : void {
        $cronjobs = static::get_cronjobs();
        foreach ( $cronjobs as $job ) {

            if ( ! \method_exists( $job, 'run' ) ) {
                continue;
            }

            \add_action( $job::HOOK, [ $job, 'run' ] );
        }
    }

    /**
     * Schedule all cronjobs.
     *
     * @return void
     */
    public static function schedule_cronjobs() : void {
        $cronjobs = static::get_cronjobs();
        foreach ( $cronjobs as $job ) {
            $key       = $job::KEY;
            $interval  = Settings::get( "cron_interval_$key" );
            if ( false === $interval ) {
                continue;
            }

            if ( ! \wp_next_scheduled( $job::HOOK ) ) {
                \wp_schedule_event( time(), $interval, $job::HOOK );
            }
        }
    }

    /**
     * Unschedule all cronjobs.
     *
     * @return void
     */
    public static function unschedule_cronjobs() : void {
        $cronjobs = static::get_cronjobs();
        foreach ( $cronjobs as $job ) {
            $timestamp = \wp_next_scheduled( $job::HOOK );
            if ( $timestamp ) {
                \wp_unschedule_event( $timestamp, $job::HOOK );
            }
        }
    }
}
