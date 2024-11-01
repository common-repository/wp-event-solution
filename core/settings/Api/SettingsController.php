<?php
/**
 * Settings Controller
 *
 * @package Eventin\Settings
 */
namespace Eventin\Settings\Api;

use Eventin\Settings;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Settings Controller Class
 */
class SettingsController extends WP_REST_Controller {
    /**
     * Constructor for SettingsController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'settings';
    }

    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'args'                => array(),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_item' ),
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/public',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_public_item' ),
                    'args'                => array(),
                    'permission_callback' => array( $this, 'get_public_item_permissions_check' ),
                ),
            )
        );
    }

    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $settings = Settings::get();
        
        $settings = apply_filters( 'eventin_settings', $settings );

        return rest_ensure_response( $settings );
    }

    /**
     * Update plugin settings
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  WP_Rest_Response | WP_Error
     */
    public function update_item( $request ) {
        $params = $request->get_params();

        Settings::update( $params );

        return $this->get_item( $request );
    }

    /**
     * Get public settings
     *
     * @return  WP_Rest_Response | WP_Error
     */
    public function get_public_item( $request ) {
        $extra_fields = etn_get_option( 'extra_fields', [] ) ?: etn_get_option( 'attendee_extra_fields', [] );
        $items = [
            'extra_fields'             => $extra_fields,
            'striple_publishable_key'  => etn_get_option( 'stripe_live_publishable_key' ),
            'paypal_client_id'         => etn_get_option( 'paypal_client_id' ),
            'currency'                 => etn_currency(),
            'currency_symbol'          => etn_currency_symbol(),
            "paypal_status"            => etn_get_option( 'paypal_status' ),
            "attendee_registration"    => etn_get_option( 'attendee_registration' ),
            "reg_require_phone"        => etn_get_option( 'reg_require_phone' ),
            "reg_require_email"        => etn_get_option( 'reg_require_email' ),
            "enable_attendee_bulk"     => etn_get_option( 'enable_attendee_bulk' ),
            "add_to_cart_redirect"     => etn_get_option( 'add_to_cart_redirect' ),
            'etn_purchase_login_required' => etn_get_option( 'etn_purchase_login_required' ),
            'decimal_separator'        => etn_get_decimal_separator(),
            'thousand_separator'       => etn_get_thousand_separator(),
            'decimals'                 => etn_get_decimals(),
            'price_format'             => etn_get_price_format(),
            'currency_position'        => etn_get_currency_position(),
        ];

        if ( function_exists( 'WC' ) ) {
            $items['wc_checkout_url'] = wc_get_checkout_url();
            $items['wc_cart_url']     = wc_get_cart_url();
        }

        return rest_ensure_response( $items );
    }

    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_public_item_permissions_check( $request ) {
        return true;
    }
}