<?php
/**
 * Custom URL for meeting platform
 */
namespace Eventin\Integrations;

use Eventin\Interfaces\MeetingPlatformInterface;

class CustomUrl implements MeetingPlatformInterface {
    /**
     * Check zoom is connected or not
     *
     * @return  bool
     */
    public static function is_connected(): bool {
        return true;
    }

    /**
     * Get zoom meeting link
     *
     * @return string
     */
    public static function create_link( $args = [] ): string {
        return $args['custom_url'] ? $args['custom_url'] : ''; 
    }
}
