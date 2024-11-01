<?php
/**
 * Event Category Api Class
 *
 * @package Eventin\Event
 */
namespace Eventin\EventCategory\Api;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * EventCategory Controller Class
 */
class EventCategoryController extends WP_REST_Controller {
    /**
     * Taxonomy key
     *
     * @var string
     */
    protected $taxonomy = 'etn_category';

    /**
     * Constructor for categoryController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'event/categories';
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
                'callback'            => array( $this, 'delete_items' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
            ]
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
                    'permission_callback' => array( $this, 'update_item_permissions_check' ),
                    'args'                => array(
                        'id' => array(
                            'description' => __( 'Unique identifier for the post.', 'eventin' ),
                            'type'        => 'integer',
                        ),
                    ),
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
    $prepared_args = array(
        'taxonomy'   => $this->taxonomy,
        'hide_empty' => false,
    );

    $query_result = get_terms( $prepared_args );

    $response = array();

    foreach ( $query_result as $term ) {
        $item = $this->prepare_item_for_response( $term->term_id, $request );

        // Get the parent term name if the parent term exists
        if ( $term->parent ) {
            $parent_term = get_term( $term->parent, $this->taxonomy );
            if ( ! is_wp_error( $parent_term ) ) {
                $item['parent_name'] = $parent_term->name;
            } else {
                $item['parent_name'] = null; // or some default value
            }
        } else {
            $item['parent_name'] = null; // or some default value
        }

        $response[] = $item;
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
        $category = $this->get_category( $request['id'] );
        
        if ( is_wp_error( $category ) ) {
            return $category;
        }

        $response = $this->prepare_item_for_response( $request['id'], $request );

        return rest_ensure_response( $response );
    }

    /**
     * Create one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {
        $prepared_data = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $prepared_data ) ) {
            return $prepared_data;
        }

        $category = wp_insert_term( $prepared_data['name'], $this->taxonomy, $prepared_data );

        if ( is_wp_error( $category ) ) {
            return $category;
        }

        $item = $this->prepare_item_for_response( $category['term_id'], $request );

        $respons = rest_ensure_response( $item );
        $respons->set_status( 201 );

        do_action( 'etn_category_created', $category['term_id'], json_decode( $request->get_body(), true ) );

        return $respons;
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
        $prepared_data = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $prepared_data ) ) {
            return $prepared_data;
        }

        $category = wp_update_term( $request['id'], $this->taxonomy, $prepared_data );

        if ( is_wp_error( $category ) ) {
            return $category;
        }

        $item = $this->prepare_item_for_response( $category['term_id'], $request );

        $respons = rest_ensure_response( $item );

        do_action( 'etn_category_updated', $category['term_id'], json_decode( $request->get_body(), true ) );

        return $respons;
    }

    /**
     * Deletes an item from the database.
     *
     * @param array $request The request data containing the item ID.
     * @return WP_REST_Response|WP_Error The response indicating the success or failure of the deletion.
     */
    public function delete_item( $request ) {
        // Sanitize and validate the ID
        $id = sanitize_text_field( $request['id'] );
        if ( ! is_numeric( $id ) ) {
            return new WP_Error( 'rest_invalid_param', __( 'The term ID must be a number.', 'eventin' ), array( 'status' => 400 ) );
        }
    
        $term = get_term( $id, $this->taxonomy );
    
        if ( ! $term || is_wp_error( $term ) ) {
            return new WP_Error( 'rest_term_invalid', __( 'Term does not exist.', 'eventin' ), array( 'status' => 404 ) );
        }
    
        $deleted = wp_delete_term( $id, $this->taxonomy );
    
        if ( is_wp_error( $deleted ) ) {
            return $deleted;
        }
    
        if ( ! $deleted ) {
            return new WP_Error( 'rest_cannot_delete', __( 'The term cannot be deleted.', 'eventin' ), array( 'status' => 500 ) );
        }
    
        return new \WP_REST_Response( null, 204 );
    }

    /**
     * Bulk delete items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_REST_Response Response object.
     */
    public function delete_items( $request ) {
        $ids = ! empty( $request['ids'] ) ? $request['ids'] : [];

        if ( ! is_array( $ids ) ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Category ids must be an array.', 'eventin' ),
                array( 'status' => 400 )
            );
        }

        if ( ! $ids ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Category ids can not be empty.', 'eventin' ),
                array( 'status' => 400 )
            );
        }
        $count = 0;

        foreach ( $ids as $id ) {
            $deleted = wp_delete_term( $id, $this->taxonomy );

            if ( ! is_wp_error( $deleted ) ) {
                $count++;
            }
        }

        if ( $count == 0 ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Category cannot be deleted.', 'eventin' ),
                array( 'status' => 400 )
            );
        }

        $message = sprintf( __( '%d categories are deleted of %d', 'eventin' ), $count, count( $ids ) );

        return rest_ensure_response( $message );
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
     * Get the category, if the ID is valid.
     *
     * @since 4.0.0
     *
     * @param int $id Supplied ID.
     * @return WP_Term|WP_Error category object if ID is valid, WP_Error otherwise.
     */
    protected function get_category( $id ) {
        $error = new WP_Error(
            'rest_category_invalid',
            __( 'Category does not exist.', 'eventin' ),
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
    public function prepare_item_for_response( $category_id, $request ) {
        $item = get_term( $category_id, $this->taxonomy );

        $category_data = [
            'id'          => $item->term_id,
            'count'       => $item->count,
            'description' => $item->description,
            'link'        => get_term_link( $item ),
            'name'        => $item->name,
            'slug'        => $item->slug,
            'parent'      => $item->parent
        ];

        return $category_data;
    }

    /**
     * Prepare data for database
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  array
     */
    protected function prepare_item_for_database( $request ) {
        $input_data = json_decode( $request->get_body(), true );
        $validate   = etn_validate( $input_data, [
            'name' => [
                'required',
            ],
        ] );

        $prepared_data = [];

        if ( is_wp_error( $validate ) ) {
            return $validate;
        }

        if ( ! empty( $input_data['name'] ) ) {
            $prepared_data['name'] = $input_data['name'];
        }

        if ( ! empty( $input_data['description'] ) ) {
            $prepared_data['description'] = $input_data['description'];
        }

        if ( ! empty( $input_data['slug'] ) ) {
            $prepared_data['slug'] = $input_data['slug'];
        }

        if ( ! empty( $input_data['parent'] ) ) {
            $prepared_data['parent'] = $input_data['parent'];
        }

        return $prepared_data;
    }
}