<?php
/**
 * Customer Api Class
 *
 * @package Eventin\Customer
 */
namespace Eventin\Customer\Api;

use Eventin\Customer\CustomerModel;
use Eventin\Input;
use Timetics\Core\Customers\Customer;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Customer Controller Class
 */
class CustomerController extends WP_REST_Controller {

    /**
     * Constructor for EventController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'customers';
    }

    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function register_routes() {
        register_rest_route( $this->namespace, $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_items'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ] );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                'args'   => array(
                    'id' => array(
                        'description' => __( 'Unique identifier for the post.', 'eventin' ),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_item' ),
                    'permission_callback' => array( $this, 'update_item_permissions_check' ),
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_item' ),
                    'permission_callback' => array( $this, 'delete_item_permissions_check' ),
                    'args'                => array(
                        'force' => array(
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => __( 'Whether to bypass Trash and force deletion.', 'eventin' ),
                        ),
                    ),
                ),
                // 'allow_batch' => $this->allow_batch,
                'schema' => array( $this, 'get_item_schema' ),
            ),
        );
    }

    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        return true;
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $customers = CustomerModel::all();

        return rest_ensure_response( $customers );
    }

    /**
     * Get one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $id       = intval( $request['id'] );
        $customer = CustomerModel::find( $id );

        if ( ! $customer ) {
            return new WP_Error( 'not_found', __( 'Customer not found', 'eventin' ), ['status' => 404] );
        }

        return rest_ensure_response( $customer );
    }

    /**
     * Creates a single customer.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_item( $request ) {
        $prepared_event = $this->prepare_item_for_database( $request );

        $customer = CustomerModel::create( $prepared_event );

        if ( is_wp_error( $customer ) ) {
            return new WP_Error( 'customer_error', $customer->get_error_message() );
        }

        return rest_ensure_response( $customer );
    }

    /**
     * Checks if a given request has access to create a event.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
     */
    public function create_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Updates a single customer.
     *
     * @since 4.0.11
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        $prepared_customer = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $prepared_customer ) ) {
            return $prepared_customer;
        }

        $customer   = new CustomerModel( $request['id'] );

        $update = $customer->update( $prepared_customer );

        if ( is_wp_error( $customer ) ) {
            return new WP_Error( 'customer_error', $update->get_error_message() );
        }

        return rest_ensure_response( $update );
    }

    /**
     * Checks if a given request has access to update a event.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
     */
    public function update_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Delete one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item( $request ) {
        $id       = intval( $request['id'] );
        $customer = CustomerModel::find( $id );

        if ( ! $customer ) {
            return new WP_Error( 'not_found', __( 'Customer not found', 'eventin' ), ['status' => 404] );
        }

        if ( $customer->delete() ) {
            return [
                'message' => __( 'Successfully deleted', 'eventin' )
            ];
        }

        if ( ! $customer ) {
            return new WP_Error( 'delete_error', __( 'Something went wrong, couldn\'t delete the customer', 'eventin' ), ['status' => 422] );
        }
    }

    /**
     * Delete multiple items from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_items( $request ) {
        $ids = ! empty( $request['ids'] ) ? $request['ids'] : [];

        if ( ! $ids ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Customer ids can not be empty.', 'eventin' ),
                array( 'status' => 400 )
            );
        }
        $count = 0;

        foreach ( $ids as $id ) {
            $customer = new CustomerModel( $id );

            if ( $customer->delete() ) {
                $count++;
            }
        }

        if ( $count == 0 ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Customer cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        $message = sprintf( __( '%d customers are deleted of %d', 'eventin' ), $count, count( $ids ) );

        return rest_ensure_response( $message );
    }

    /**
     * Delete one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|object $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        $input_data = json_decode( $request->get_body(), true ) ?? [];
        $input      = new Input( $input_data );

        $validate   = etn_validate( $input_data, [
            'first_name'      => [
                'required',
            ],
            'email' => [
                'required',
            ]
        ] );

        if ( is_wp_error( $validate ) ) {
            return $validate;
        }

        $customer_data = [
            'first_name' => $input->get( 'first_name' ),
            'last_name'  => $input->get( 'last_name' ),
            'email'      => $input->get( 'email' ),
        ];

        return $customer_data;
    }
}
