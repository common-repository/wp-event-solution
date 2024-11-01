<?php
/**
 * Location Api Class
 *
 * @package Eventin\Event
 */
namespace Eventin\Location\Api;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Location Controller Class
 */
class LocationController extends WP_REST_Controller {
    /**
     * Taxonomy key
     *
     * @var string
     */
    protected $taxonomy = 'etn_location';

    /**
     * Constructor for LocationController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'event/locations';
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
        $prepared_args = array( 
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false, 
        );
        
        $query_result  = get_terms( $prepared_args );

        $response = array();

        foreach ( $query_result as $term ) {
            $response[] = $this->prepare_item_for_response( $term, $request );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Get one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $location = $this->get_location( $request['id'] );
        if ( is_wp_error( $location ) ) {
            return $location;
        }

        $response = $this->prepare_item_for_response( $location, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get the location, if the ID is valid.
     *
     * @since 4.0.0
     *
     * @param int $id Supplied ID.
     * @return WP_Term|WP_Error Location object if ID is valid, WP_Error otherwise.
     */
    protected function get_location( $id ) {
        $error = new WP_Error(
            'rest_location_invalid',
            __( 'Location does not exist.', 'eventin' ),
            array( 'status' => 404 )
        );

        if ( (int) $id <= 0 ) {
            return $error;
        }

        $term = get_term( (int) $id, $this->taxonomy );
        if ( empty( $term ) || $term->taxonomy !== $this->taxonomy ) {
            return $error;
        }

        return $term;
    }

    /**
     * Prepare the item for the REST response.
     *
     * @param mixed           $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response
     */
    public function prepare_item_for_response( $item, $request ) {
        $location_data = [
            'id'          => $item->term_id,
            'count'       => $item->count,
            'description' => $item->description,
            'link'        => get_term_link( $item ),
            'name'        => $item->name,
            'slug'        => $item->slug,
            'email'       => get_term_meta( $item->term_id, 'location_email', true ),
            'address'     => get_term_meta( $item->term_id, 'address', true ),
            'latitude'    => get_term_meta( $item->term_id, 'location_latitude', true ),
            'longitude'   => get_term_meta( $item->term_id, 'location_longitude', true ),
        ];

        return $location_data;
    }
}
