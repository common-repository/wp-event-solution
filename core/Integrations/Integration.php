<?php
/**
 * Integration provider;
 */
namespace Eventin\Integrations;

use Eventin\Integrations\Zoom\Zoom;
use Eventin\Integrations\Zoom\ZoomToken;
use Eventin\Interfaces\HookableInterface;

/**
 * Integration service
 *
 * @package Eventin
 */
class Integration implements HookableInterface {
    /**
     * Register service
     *
     * @return  void
     */
    public function register_hooks(): void {
        add_action( 'template_redirect', [$this, 'authenticate'] );

        add_filter( 'eventin_settings', [$this, 'added_zoom_connection'] );
    }

    /**
     * Authenticate integration
     *
     * @return  void
     */
    public function authenticate() {
        $query_var = get_query_var( 'eventin-integration', false );
        $code      = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';

        $endpoints = [
            'zoom-auth'   => 'zoom',
            'google-auth' => 'google-meet',
        ];

        $endpoint = ! empty( $endpoints[$query_var] ) ? $endpoints[$query_var] : '';

        if ( ! $query_var ) {
            return;
        }

        switch ( $query_var ) {
        case 'zoom-auth':
            $this->zoom_auth( $code );
            break;
        }

        do_action( 'eventin_integration_auth', $query_var, $code );

        $redirect_url = admin_url( 'admin.php?page=eventin#/settings/integrations/' . $endpoint );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Authentication for zoom
     *
     * @param   string  $code
     *
     * @return void
     */
    public function zoom_auth( $code = '' ) {
        ZoomToken::get_remote( $code );
    }

    /**
     * Added zoom connection settings
     *
     * @param   array  $settings  Setting
     *
     * @return  array $settings
     */
    public function added_zoom_connection( $settings ) {
        $settings['zoom_connected'] = Zoom::is_connected();

        return $settings;
    }
}
