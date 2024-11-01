<?php
/**
 * Purchase Controller Class
 *
 * @package Eventin\Event
 */
namespace Eventin\Event\Api;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Transaction Controller Class
 */
class TransactionController extends WP_REST_Controller {
    /**
     * Constructor for EventController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'transactions';
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
        return current_user_can( 'manage_options' );
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        $per_page = ! empty( $request['per_page'] ) ? intval( $request['per_page'] ) : 20;
        $paged    = ! empty( $request['paged'] ) ? intval( $request['paged'] ) : 1;
        $type     = ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : '';

        global $wpdb;

        // Define the name of your custom table
        $table_name = $wpdb->prefix . 'etn_events';

        // Query to retrieve data from the custom table
        $results = $wpdb->get_results( "SELECT * FROM $table_name", OBJECT );

        $items = [];

        foreach ( $results as $item ) {
            $items[] = $this->prepare_item_for_response( $item, $request );
        }

        return rest_ensure_response( $items );
    }

    /**
     * Get one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $id = intval( $request['id'] );
        global $wpdb;

        $table_name = $wpdb->prefix . 'etn_events';
        $query      = $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id );

        // Get the result of the query
        $item = $wpdb->get_row( $query, OBJECT );

        $item = $this->prepare_item_for_response( $item, $request );

        $response = rest_ensure_response( $item );

        return $response;
    }

    /**
     * Creates a single event.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_item( $request ) {
        $prepared_event = $this->prepare_item_for_database( $request );

        global $wpdb;

        $table_name = $wpdb->prefix . 'etn_events';

        if ( is_wp_error( $prepared_event ) ) {
            return $prepared_event;
        }

        $insert_result = $wpdb->insert( $table_name, $prepared_event );

        if ( $insert_result ) {
            return $this->get_item( ['id' => $wpdb->insert_id] );
        }

        return rest_ensure_response([
            'message' => __( 'Something went wrong. Please try again', 'eventin' )
        ]);
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
     * Updates a single event.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        $id = intval($request['id']);
        $prepared_event = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $prepared_event ) ) {
            return $prepared_event;
        }

        $prepared_event = $this->prepare_item_for_database( $request );

        global $wpdb;

        $table_name = $wpdb->prefix . 'etn_events';

        if ( is_wp_error( $prepared_event ) ) {
            return $prepared_event;
        }

        $insert_result = $wpdb->update( $table_name, $prepared_event, ['id' => $id] );

        if ( $insert_result ) {
            return $this->get_item( ['id' => $wpdb->insert_id] );
        }

        return rest_ensure_response([
            'message' => __( 'Something went wrong. Please try again', 'eventin' )
        ]);
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
        $id = intval( $request['id'] );

        $post = get_post( $id );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $previous = $this->prepare_item_for_response( $post, $request );
        $result   = wp_delete_post( $id, true );
        $response = new \WP_REST_Response();
        $response->set_data(
            array(
                'deleted'  => true,
                'previous' => $previous,
            )
        );

        if ( ! $result ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'The event cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        do_action( 'eventin_event_deleted', $id );

        return $response;
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
                __( 'Transaction ids can not be empty.', 'eventin' ),
                array( 'status' => 400 )
            );
        }

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
     * Get the item's schema for display / public consumption purposes.
     *
     * @return array
     */
    public function get_item_schema() {

    }

    /**
     * Prepare the item for the REST response.
     *
     * @param mixed           $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response
     */
    public function prepare_item_for_response( $item, $request ) {
        $transaction_data = [
            'event_id'          => $item->event_id,
            'invoice'           => $item->invoice,
            'amount'            => $item->event_amount,
            'ticket_quantity'   => $item->ticket_qty,
            'ticket_variations' => maybe_unserialize( $item->ticket_variations ),
        ];

        return $transaction_data;
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|object $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        $input_data = json_decode( $request->get_body(), true ) ?? [];

        $validate = etn_validate( $input_data, [
            'event_id'          => ['required'],
            'ticket_quantity'   => ['required'],
            'ticket_variations' => ['ticket_variations'],
        ] );

        if ( is_wp_error( $validate ) ) {
            return $validate;
        }

        $transaction_data = [];

        if ( isset( $input_data['event_id'] ) ) {
            $transaction_data['event_id'] = $input_data['event_id'];
        }

        if ( isset( $input_data['amount'] ) ) {
            $transaction_data['event_amount'] = $input_data['amount'];
        }

        if ( isset( $input_data['ticket_quantity'] ) ) {
            $transaction_data['ticket_qty'] = $input_data['ticket_quantity'];
        }

        if ( isset( $input_data['ticket_variations'] ) ) {
            $transaction_data['ticket_variations'] = $input_data['ticket_variations'];
        }

        return $transaction_data;
    }
}
