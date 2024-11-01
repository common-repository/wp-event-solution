<?php
/**
 * Schedule Api Class
 *
 * @package Eventin\Schedule
 */
namespace Eventin\Schedule\Api;

use Etn\Core\Schedule\Schedule_Exporter;
use Etn\Core\Schedule\Schedule_Importer;
use Etn\Core\Schedule\Schedule_Model;
use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Schedule Controller Class
 */
class ScheduleController extends WP_REST_Controller {
    /**
     * Constructor for ScheduleController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'schedules';
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
                'args' => array(
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
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_item' ),
                    'permission_callback' => array( $this, 'delete_item_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
            ),
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)' . '/clone',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __( 'Unique identifier for the post.', 'eventin' ),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'clone_item' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),

                // 'allow_batch' => $this->allow_batch,
                'schema' => array( $this, 'get_item_schema' ),
            ),
        );

        register_rest_route( $this->namespace, $this->rest_base . '/export', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'export_items'],
                'permission_callback' => [$this, 'export_items_permissions_check'],
            ],
        ] );

        register_rest_route( $this->namespace, $this->rest_base . '/import', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'import_items'],
                'permission_callback' => [$this, 'import_items_permissions_check'],
            ],
        ] );
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
    public function get_items( $request ) {

        $per_page = ! empty( $request['per_page'] ) ? intval( $request['per_page'] ) : 20;
        $paged    = ! empty( $request['paged'] ) ? intval( $request['paged'] ) : 1;
        $type     = ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : '';
        $search   = ! empty( $request['search'] ) ? sanitize_text_field( $request['search'] ) : '';
        $year     = ! empty( $request['year'] ) ? intval( $request['year'] ) : 0;


        $args = [
            'post_type'      => 'etn-schedule',
            'post_status'    => 'any',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'meta_query'     => $this->get_search_content( $search ),
            'date_query'     => $this->get_year( $year )
        ];

        if ( ! current_user_can( 'manage_options' ) ) {
            $args['author'] = get_current_user_id(); 
        }

        $events = [];

        $post_query   = new WP_Query();
        $query_result = $post_query->query( $args );
        $total_posts  = $post_query->found_posts;

        foreach ( $query_result as $post ) {
            $schedule = new Schedule_Model( $post->ID );
            $post_data = $this->prepare_item_for_response( $schedule, $request );

            $events[] = $this->prepare_response_for_collection( $post_data );
        }

        $data = [
            'total_items' => $total_posts,
            'items'       => $events
        ];

        $response = rest_ensure_response( $data );

        $response->header( 'X-WP-Total', $total_posts );

        return $response;
    }

    /**
     * Get one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $id   = intval( $request['id'] );
        $schedule = new Schedule_Model( $id );

        $item = $this->prepare_item_for_response( $schedule, $request );

        $response = rest_ensure_response( $item );

        return $response;
    }

    /**
     * Create one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {
        $data = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $schedule = new Schedule_Model();
        $created  = $schedule->create( $data );

        if ( ! $created ) {
            return new WP_Error( 'schedule_create_error', __( 'Can not create schedule', 'eventin' ), ['status' => 409] );
        }

        $item     = $this->prepare_item_for_response( $schedule, $request );
        $response = rest_ensure_response( $item );
        $response->set_status( 201 );

        do_action( 'eventin_schedule_created', $schedule );

        return $response;
    }

    /**
     * Check if a given request has access to create items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
    }

    /**
     * Update one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item( $request ) {
        $data = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $schedule = new Schedule_Model( $request['id'] );
        $updated  = $schedule->update( $data );

        if ( ! $updated ) {
            return new WP_Error( 'schedule_update_error', __( 'Can not update schedule', 'eventin' ), ['status' => 409] );
        }

        $item     = $this->prepare_item_for_response( $schedule, $request );
        $response = rest_ensure_response( $item );

        do_action( 'eventin_schedule_updated', $schedule );

        return $response;
    }

    /**
     * Check if a given request has access to update a specific item.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
    }

    /**
     * Delete one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item( $request ) {
        $id = intval( $request['id'] );

        $schedule = new Schedule_Model( $id );
        $previous = $this->prepare_item_for_response( $schedule, $request );

        do_action( 'eventin_schedule_before_delete', $schedule );

        $deleted  = $schedule->delete();
        $response = new \WP_REST_Response();

        $response->set_data(
            array(
                'deleted'  => true,
                'previous' => $previous,
            )
        );

        if ( ! $deleted ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'The schedule cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        do_action( 'eventin_schedule_deleted', $id );

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
                __( 'Schedule ids can not be empty.', 'eventin' ),
                array( 'status' => 400 )
            );
        }
        $count = 0;

        foreach ( $ids as $id ) {
            $event = new Schedule_Model( $id );

            if ( $event->delete() ) {
                $count++;
            }
        }

        if ( $count == 0 ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Schedule cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        $message = sprintf( __( '%d Schedule are deleted of %d', 'eventin' ), $count, count( $ids ) );

        return rest_ensure_response( $message );
    }

    /**
     * Clone an item
     *
     * @param   array  $request
     *
     * @return  array
     */
    public function clone_item( $request ) {
        $schedule_id = intval( $request['id'] );

        if ( 'etn-schedule' !== get_post_type( $schedule_id ) ) {
            return new WP_Error( 'schedule_type_error', __( 'Invalid schedule id', 'eventin' ) );
        }

        $schedule = new Schedule_Model( $schedule_id );

        $clone_schedule = $schedule->clone();

        $response = $this->prepare_item_for_response( $clone_schedule, $request );

        do_action( 'eventin_event_after_clone', $clone_schedule->id );

        return rest_ensure_response( $response );
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|object $prepared_item
     */
    public function delete_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
    }

    /**
     * Prepare the item for the REST response.
     *
     * @param mixed           $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response
     */
    public function prepare_item_for_response( $item, $request ) {
        $id = $item->id;

        $schedule_data = [
            'id'            => $id,
            'program_title' => get_post_meta( $id, 'etn_schedule_title', true ),
            'date'          => get_post_meta( $id, 'etn_schedule_date', true ),
            'day_name'      => get_post_meta( $id, 'etn_schedule_day', true ),
            'schedule_slot' => get_post_meta( $id, 'etn_schedule_topics', true ),
        ];

        return $schedule_data;
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|object $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        $input_data    = json_decode( $request->get_body(), true );
        $prepared_data = [];

        if ( ! empty( $input_data['program_title'] ) ) {
            $prepared_data['etn_schedule_title'] = $input_data['program_title'];
        }

        if ( ! empty( $input_data['date'] ) ) {
            $date = new \DateTime( $input_data['date'] );
            // Format the DateTime object to only get the date part
            $formatted_date = $date->format('Y-m-d');
            $prepared_data['etn_schedule_date'] = $formatted_date;
        }

        if ( ! empty( $input_data['day_name'] ) ) {
            $prepared_data['etn_schedule_day'] = $input_data['day_name'];
        }

        if ( ! empty( $input_data['schedule_slot'] ) ) {
            $prepared_data['etn_schedule_topics'] = $input_data['schedule_slot'];
        }
        
        $prepared_data['post_status'] = ! empty( $input_data['post_status'] ) ? $input_data['post_status'] : 'publish' ;
        

        return $prepared_data;
    }

    public function get_search_content( $search ) {
        if ( empty( $search )) {
            return [];
        }
    
        $_query = [
            'relation' => 'OR',
            array(
                'key'     => 'etn_schedule_title',
                'value'   => $search,
                'compare' => 'LIKE',
            ), array(
                'key'     => 'etn_schedule_topics',
                'value'   => $search,
                'compare' => 'LIKE',
            ),
        ];

        return $_query;
    }

    public function get_year( $year ){
        if (empty($year)) {
            return [];
        }

        return array(
                array(
                    'year' => $year,
                ),
            );
        }

    /**
     * Export attendees
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  void
     */
    public function export_items( $request ) {
        $format = ! empty( $request['format'] ) ? sanitize_text_field( $request['format'] ) : '';

        $ids    = ! empty( $request['ids'] ) ? $request['ids'] : '';

        if ( ! $format ) {
            return new WP_Error( 'format_error', __( 'Invalid data format', 'eventin' ) );
        }

        if ( ! $ids ) {
            return new WP_Error( 'data_error', __( 'Invalid ids', 'eventin' ), ['status' => 409] );
        }

        $exporter = new Schedule_Exporter();
        $exporter->export( $ids, $format );
    }

    /**
     * Check export items permissions
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  void
     */
    public function export_items_permissions_check( $request ) {
        return true;
    }

    /**
     * Import items
     *
     * @return  void
     */
    public function import_items( $request ) {
        $data = $request->get_file_params();
        $file = ! empty( $data['schedule_import'] ) ? $data['schedule_import'] : '';

        if ( ! $file ) {
            return new WP_Error( 'empty_file', __( 'You must provide a valid file.', 'eventin' ), ['status' => 409] );
        }

        $importer = new Schedule_Importer();
        $importer->import( $file );

        $response = [
            'message' => __( 'Successfully imported schedule', 'eventin' ),
        ];

        return rest_ensure_response( $response );
    }

    /**
     * Permissions check for import items
     *
     * @param   WP_Rest_Request  $request
     *
     * @return bool 
     */
    public function import_items_permissions_check( $request ) {
        return true;
    }
}
