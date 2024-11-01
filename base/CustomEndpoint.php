<?php
/**
 * Register Custom Endpoint
 *
 * @package Eventin
 */
namespace Eventin;

/**
 * Custom Endpoint Class
 */
class CustomEndpoint {
    /**
     * Register all custom endpoints
     *
     * @return  void
     */
    public static function register() {
        $endpoints = self::get_endpoints();

        foreach ( $endpoints as $endpoint ) {
            add_rewrite_endpoint( $endpoint, EP_ALL );
        }

        // Flush rewrite rules after register all custom endpoints.
        flush_rewrite_rules( true );
    }

    /**
     * Get all endpoints
     *
     * @return  array
     */
    public static function get_endpoints() {
        /**
         * All endpoints that have to be register
         */
        return [
            'eventin-integration',
            'eventin-purchase'
        ];
    }
};
