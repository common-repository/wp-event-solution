<?php
/**
 * Speaker Category Api Class
 *
 * @package Eventin\Speaker
 */
namespace Eventin\Speaker\Api;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Speaker Category Controller Class
 */
class SpeakerCategoryController extends WP_REST_Controller {
    /**
     * Taxonomy key
     *
     * @var string
     */
    protected $taxonomy = 'etn_speaker_category';

    /**
     * Constructor for categoryController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'speaker/categories';
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
    }

    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) || current_user_can( 'seller' );
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
            $response[] = $this->prepare_item_for_response( $term->term_id, $request );
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

        return $respons;
    }

    /**
     * Check if a given request has access to create items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) || current_user_can( 'seller' );
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

        return $respons;
    }

    /**
     * Check if a given request has access to update a specific item.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) || current_user_can( 'seller' );
    }

    /**
     * Deletes a single term from a taxonomy.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        $term = $this->get_category( $request['id'] );
        if ( is_wp_error( $term ) ) {
            return $term;
        }

        $request->set_param( 'context', 'view' );

        $previous = $this->prepare_item_for_response( $term->term_id, $request );

        $retval = wp_delete_term( $term->term_id, $term->taxonomy );

        if ( ! $retval ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'The term cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        $response = new \WP_REST_Response();
        $response->set_data(
            array(
                'deleted'  => true,
                'previous' => $previous,
            )
        );

        return $response;
    }

    /**
     * Checks if a request has access to delete the specified term.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to delete the item, otherwise false or WP_Error object.
     */
    public function delete_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) || current_user_can( 'seller' );
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
    
        // Fetch user IDs associated with this category
        $user_ids = $this->get_matching_user_ids_by_category($category_id);
    
        $category_data = [
            'id'          => $item->term_id,
            'count'       => count( $user_ids ),
            'description' => $item->description,
            'link'        => get_term_link( $item ),
            'name'        => $item->name,
            'slug'        => $item->slug,
            'user_ids'    => $user_ids, 
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

    public function get_matching_user_ids_by_category( $category_id ) {
        // Fetch all users with the meta key 'etn_speaker_group' or 'etn_speaker_speaker_group'
        $users = get_users(array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key'     => 'etn_speaker_group',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => 'etn_speaker_speaker_group',
                    'compare' => 'EXISTS',
                ),
            ),
            'fields' => array('ID'),
        ));
    
        $matching_user_ids = array();
    
        // Iterate over each user and check if the category ID is in their serialized array
        foreach ( $users as $user ) {
            $user_id = $user->ID;
            $speaker_group = get_user_meta($user_id, 'etn_speaker_group', true);
            $speaker_speaker_group = get_user_meta($user_id, 'etn_speaker_speaker_group', true);
    
            // Check if category ID exists in either meta value
            if ( (is_array($speaker_group) && in_array($category_id, $speaker_group) ) ||
                ( is_array($speaker_speaker_group) && in_array( $category_id, $speaker_speaker_group ) ) ) {
                $matching_user_ids[] = $user_id;
            }
        }
    
        return $matching_user_ids;
    }
    
}
