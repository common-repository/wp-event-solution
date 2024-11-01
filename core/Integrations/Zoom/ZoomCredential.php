<?php
namespace Eventin\Integrations\Zoom;

/**
 * Manage zoom credentials
 *
 * @package Eventin/Integrations/Zoom
 */

/**
 * Manage zoom credentials
 */
class ZoomCredential {
    /**
     * Get client id
     *
     * @return  string
     */
    public static function get_client_id(): string {
        return etn_get_option( 'zoom_client_id' );
    }

    /**
     * Get client secret
     *
     * @return  string
     */
    public static function get_client_secret(): string {
        return etn_get_option( 'zoom_client_secret' );
    }

    /**
     * Get auth url
     *
     * @return  string
     */
    public static function get_auth_url(): string {
        $client_id     = self::get_client_id();
        $redirect_uri  = self::get_redirect_uri();
        $zoom_auth_url = 'https://zoom.us/oauth/authorize';

        $args = [
            'response_type' => 'code',
            'redirect_uri'  => $redirect_uri,
            'client_id'     => $client_id,
        ];

        return add_query_arg( $args, $zoom_auth_url );
    }

    /**
     * Get auth redirect uri
     *
     * @return  string
     */
    public static function get_redirect_uri(): string {
        return site_url( 'eventin-integration/zoom-auth' );
    }
}
