<?php
namespace Eventin\Integrations\Zoom;

/**
 * Manage zoom access token
 *
 * @package Eventin\Integrations\Zoom
 */
class ZoomToken {

    /**
     * Get zoom access token
     *
     * @return  string
     */
    public static function get(): string {
        $token_data        = etn_get_option( 'zoom_token' );
        $access_token      = ! empty( $token_data['access_token'] ) ? $token_data['access_token'] : '';
        $token_expire_time = ! empty( $token_data['expire_time'] ) ? $token_data['expire_time'] : 0;
        $refresh_token     = ! empty( $token_data['refresh_token'] ) ? $token_data['refresh_token'] : '';

        if ( $access_token && $token_expire_time > time() ) {
            return $access_token;
        }

        $token_data = self::get_remote( $refresh_token, 'refresh_token' );

        if ( ! $token_data ) {
            return false;
        }

        if ( ! empty( $token_data['access_token'] ) ) {
            return $token_data['access_token'];
        }

        return false;
    }

    /**
     * Update zoom access token
     *
     * @return  void
     */
    public static function get_remote( $code, $grant_type = 'authorization_code' ) {
        $token_url = 'https://zoom.us/oauth/token';

        $parms = [
            'redirect_uri' => ZoomCredential::get_redirect_uri(),
            'grant_type'   => $grant_type,
        ];

        if ( 'refresh_token' === $grant_type ) {
            $parms['refresh_token'] = $code;
        } else {
            $parms['code'] = $code;
        }

        $client_id     = ZoomCredential::get_client_id();
        $client_secret = ZoomCredential::get_client_secret();

        $auth_code = base64_encode( "{$client_id}:{$client_secret}" );

        $requst_data = [
            'headers' => [
                'Authorization' => "Basic {$auth_code}",
            ],
            'body'    => $parms,
        ];

        $response = wp_remote_post( $token_url, $requst_data );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }

        if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        $data['expire_time'] = time() + $data['expires_in'];

        etn_update_option( 'zoom_token', $data );

        return $data;
    }
}
