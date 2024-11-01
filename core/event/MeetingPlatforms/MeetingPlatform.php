<?php
/**
 * Manage online meeting platform
 *
 * @package Eventin
 */

namespace Eventin\Event\MeetingPlatforms;

use Eventin\Integrations\CustomUrl;
use Eventin\Integrations\Zoom\Zoom;
use Exception;

/**
 * Manage online meeting
 */
class MeetingPlatform {
    /**
     * Store platforms
     *
     * @var array
     */
    private static $platforms;

    /**
     * Get meeting platform
     *
     * @return MeetingPlatformInterface
     */
    public static function get_platform( $platform ) {
        if ( ! isset( self::$platforms[$platform] ) ) {
            self::$platforms[$platform] = self::create_platform( $platform );
        }

        return new self::$platforms[$platform];
    }

    /**
     * Create platform
     *
     * @param   array  $platform
     *
     * @return  MeetingPlatformInterface
     */
    private static function create_platform( $platform ) {
        $platforms = self::get_platforms();

        if ( ! isset( $platforms[$platform] ) ) {
            throw new Exception( __( 'Unsupported platform ' . $platform, 'eventin' ) );
        }

        return $platforms[$platform];
    }

    /**
     * Get online meeting platforms
     *
     * @return  array
     */
    private static function get_platforms() {
        $platforms = [
            'zoom'       => Zoom::class,
            'custom_url' => CustomUrl::class,
        ];

        return apply_filters( 'eventin_online_meeting_platforms', $platforms );
    }
}
