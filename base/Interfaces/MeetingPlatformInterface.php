<?php

namespace Eventin\Interfaces;

/**
 * Interface MeetingPlatformInterface
 *
 * @package Eventin\Interfaces
 */
interface MeetingPlatformInterface {

    /**
     * Create meeting link
     *
     * @return string
     */
    public static function create_link( $args = [] ): string;

    /**
     * Check platform is connected or not
     *
     * @return bool
     */
    public static function is_connected(): bool;
}
