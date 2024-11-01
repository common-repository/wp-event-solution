<?php
/**
 * Zoom Integration
 *
 * @package Eventin
 */
namespace Eventin\Integrations\Zoom;

use Eventin\Interfaces\MeetingPlatformInterface;

/**
 * Zoom meeting class
 */
class Zoom implements MeetingPlatformInterface {
    /**
     * Store meeting data
     *
     * @var array
     */
    protected static $meeting_data;

    /**
     * Check zoom is connected or not
     *
     * @return  bool
     */
    public static function is_connected(): bool {
        if ( ZoomToken::get() ) {
            return true;
        }

        return false;
    }

    /**
     * Get zoom meeting link
     *
     * @return string
     */
    public static function create_link( $args = [] ): string {
        $defaults = [
            'title'      => '',
            'start_date' => '',
            'start_time' => '',
            'end_date'   => '',
            'end_time'   => '',
            'time_zone'  => 'America/New_York',
        ];

        $args = wp_parse_args( $args, $defaults );

        return self::create_meeting( $args )->get_join_url();
    }

    /**
     * Get meeting join url
     *
     * @return  string
     */
    public static function get_join_url() {
        return self::$meeting_data['join_url'];
    }

    /**
     * Get meeting start url
     *
     * @return  string
     */
    public static function get_start_url() {
        return self::$meeting_data['start_url'];
    }

    /**
     * Get meeting data
     *
     * @return  array
     */
    public static function get_meeting_data() {
        return self::$meeting_data;
    }

    /**
     * Create zoom meeting
     *
     * @param   array  $args  Meeting args
     *
     * @return  Object
     */
    private static function create_meeting( $args ) {
        $access_token = ZoomToken::get();
        $zoom         = new ZoomClient( $access_token );

        self::$meeting_data = $zoom->create_meeting( $args );

        return new self;
    }
}
