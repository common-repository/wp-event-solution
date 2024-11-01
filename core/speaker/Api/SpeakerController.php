<?php
/**
 * Speaker Api Class
 *
 * @package Eventin\Speaker
 */
namespace Eventin\Speaker\Api;

use Etn\Core\Speaker\Speaker_Exporter;
use Etn\Core\Speaker\Speaker_Importer;
use Etn\Core\Speaker\User_Model;
use WP_Error;
use WP_User_Query;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Speaker Controller Class
 */
class SpeakerController extends WP_REST_Controller {
    
    /**
     * Meta Prefix key
     *
     * @var string
     */
    public $meta_prefix  = 'etn_speaker_';
    
    /**
     * Constructor for SpeakerController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'speakers';
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
                'permission_callback' => [$this, 'get_item_permissions_check'],
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
                'permission_callback' => [$this, 'export_item_permissions_check'],
            ]
        ] );

        register_rest_route( $this->namespace, $this->rest_base . '/import', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'import_items'],
                'permission_callback' => [$this, 'import_item_permissions_check'],
            ]
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
        $type     = ! empty( $request['category'] ) ?  $request['category']  : [];
        $group    = ! empty( $request['speaker_group'] ) ? $request['speaker_group'] : '';
        $search   = ! empty( $request['search'] ) ?   $request['search'] : 0;

        $user_ids = self::get_etn_user_role( $type );

        if ( $group ) {
            $group_user_ids = $this->get_user_ids_by_group($group);
            $user_ids       = array_intersect($user_ids, $group_user_ids);

        }
        
        $offset = ( $paged - 1 ) * $per_page;
        $exclude_ids = [];


        // Get the admin user ID to potentially exclude
        $admin_user = get_user_by( 'login', 'admin' );
        if ( $admin_user ) {
            // Check if the admin has the 'etn_speaker_category' meta_key
            $admin_meta_value = get_user_meta( $admin_user->ID, 'etn_speaker_category', true );
            if ( empty( $admin_meta_value ) ) {
                $exclude_ids[] = $admin_user->ID;
            }
        }

        $args = [
            'role__in' => [ 'etn-speaker', 'etn-organizer' ],
            // 'include'       => $user_ids,
            'number'        => $per_page,
            'offset'        => $offset,
            'exclude'       => $exclude_ids,
        ];
        
        if ( ! current_user_can( 'manage_options' ) ) {
            $args['meta_query'] = [
                [
                    'key'   => 'author',
                    'value' => get_current_user_id(),
                    'compare' => '='
                ]
            ];
        }

        // Search query across multiple fields
        if ( $search ) {
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key'     => 'etn_company_name',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'etn_speaker_summery',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'etn_speaker_title',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'etn_speaker_designation',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ], 
                [
                    'key'     => 'etn_speaker_website_email',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ]
            ];

            if ( ! current_user_can( 'manage_options' ) ) {
                $args['meta_query'][] = [
                    'key'   => 'author',
                    'value' => get_current_user_id(),
                    'compare' => '='
                ]; 
            }
        }
        
        $events = [];

        $item_query   = new WP_User_Query( $args );
        $query_result = $item_query->get_results();
        $total_posts  = $item_query->get_total();

        foreach ( $query_result as $user ) {
            $speaker   = new User_Model( $user->ID );
            $post_data = $speaker->get_data( $user->ID );

            $speaker_groups = [];

            // Check if speaker_group is an array (multiple categories)
            if ( is_array( $post_data['speaker_group'] ) ) {
                foreach ( $post_data['speaker_group'] as $group_id ) {
                    $category = get_term_by( 'term_id', $group_id, 'etn_speaker_category' );
                    if ( isset( $category->name ) && ! empty( $category->name ) ) {
                        $speaker_groups[] = $category->name;
                    }
                }
            } else { // If only a single category is provided
                $category = get_term_by( 'term_id', $post_data['speaker_group'], 'etn_speaker_category' );
                if ( isset( $category->name ) && ! empty( $category->name ) ) {
                    $speaker_groups[] = $category->name;
                }
            }
        
            // Assign the names back to speaker_group
            if ( ! empty( $speaker_groups ) ) {
                $post_data['speaker_group'] = $speaker_groups;
            }

            $events[] = $this->prepare_response_for_collection( $post_data );
        }

        $args     = apply_filters( 'eventin_speaker_query_args', $args, $events);

        $response = rest_ensure_response( $events );

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
        $user = new User_Model( $id );

        $item = $user->get_data( $request['id'] );

        $response = rest_ensure_response( $item );

        return $response;
    }    
    
    /**
     * Get one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function clone_item( $request ) {
        $id   = intval( $request['id'] );
        $user = new User_Model( $id );

        //get data for clone
        $clone = $user->clone_data( $request['id'] );

        $created = $user->create( $clone );

        if ( ! $created ) {
            return new WP_Error( 'create_error', __( 'Speaker can not create from clone id. Please try again', 'eventin' ), ['status' => 409] );
        }

        $item = User_Model::instance()->get_data( $created );

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

        $speaker = new User_Model();

        $created = $speaker->create( $data );

        if ( is_wp_error( $created ) ) {
            return new WP_Error( 'create_error', $created->get_error_message(), ['status' => 409] );
        }

        update_user_meta( $created, 'author', get_current_user_id() );

        $item = User_Model::instance()->get_data( $created );

        do_action( 'eventin_speaker_created', new User_Model( $created ), $request );

        $response = rest_ensure_response( $item );
        $response->set_status( 201 );

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

        $speaker = new User_Model( $request['id'] );

        $user = get_user_by( 'email', $data['etn_speaker_website_email'] );

        if ( $user && $speaker->get_speaker_website_email() != $data['etn_speaker_website_email'] ) {
            return new WP_Error( 'email_error', __( 'A user is exist with this email.', 'eventin' ), ['status' => 409] );
        }

        $updated = $speaker->update( $data );

        if ( ! $updated ) {
            return new WP_Error( 'update_error', __( 'Speaker can not updated. Please try again', 'eventin' ), ['status' => 409] );
        }

        $item = User_Model::instance()->get_data($updated);

        do_action( 'eventin_speaker_update', $speaker, $request );

        $response = rest_ensure_response( $item );

        return $response;
    }

    /**
     * Check if a given request has access to create items.
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

        $post = get_post( $id );

        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $speaker = new User_Model( $id );

        do_action( 'eventin_speaker_before_delete', $speaker );

        $deleted  = $speaker->delete();
        $response = new \WP_REST_Response();
        $response->set_data(
            array(
                'deleted'  => true,
            )
        );

        if ( ! $deleted ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'The speaker cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        do_action( 'eventin_speaker_deleted', $id );

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
                __( 'Speaker ids can not be empty.', 'eventin' ),
                array( 'status' => 400 )
            );
        }
        $count = 0;

        foreach ( $ids as $id ) {
            $speaker = new User_Model( $id );

            $previous = User_Model::instance()->get_data( $id );
            if( ! array_intersect( ['speaker', 'organizer'] , $previous['category'] ) ) {
                return new WP_Error(
                    'rest_cannot_delete',
                    __( 'The speaker cannot be deleted.', 'eventin' ),
                    array( 'status' => 500 )
                );
            }
            if ( $speaker->delete() ) {
                $count++;
            }
        }

        if ( $count == 0 ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Speaker cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        $message = sprintf( __( '%d speakers are deleted of %d', 'eventin' ), $count, count( $ids ) );

        return rest_ensure_response( $message );
    }

    /**
     * Delete one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|array $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        $prepared_data = [];
        $input_data    = json_decode( $request->get_body(), true );

        if ( ! empty( $input_data['id'] ) ) {
            $prepared_data['id'] = intval( $input_data['id'] );
        }

        if ( ! empty( $input_data['name'] ) ) {
            $prepared_data['etn_speaker_title'] = sanitize_text_field( $input_data['name'] );
            $prepared_data['display_name']      = sanitize_text_field( $input_data['name'] );
        }
        

        if ( ! empty( $input_data['email'] ) ) {
            $prepared_data['etn_speaker_website_email'] = sanitize_text_field( $input_data['email'] );

            $prepared_data['user_login']        = sanitize_email( $input_data['email'] );
        }


        if ( ! empty( $input_data['social'] ) ) {
            $prepared_data['etn_speaker_social'] = $input_data['social'] ;
        }


        if ( ! empty( $input_data['category'] ) && is_array( $input_data['category'] ) ) {
            $allowed_categories = ['speaker', 'organizer'];
            $filtered_categories = array_intersect( $input_data['category'], $allowed_categories );
        
            if ( ! empty( $filtered_categories ) ) {
                $prepared_data['etn_speaker_category'] = $filtered_categories;
            } else {
                unset( $prepared_data['etn_speaker_category'] ); // Or you can leave it out entirely
            }
        }       
        
        if ( ! empty( $input_data['speaker_group'] ) ) {
            $prepared_data['etn_speaker_group'] =  $input_data['speaker_group'] ? $input_data['speaker_group'] : [];
        }        
        
            
        //non mandatory field
        $prepared_data['date']                      =  $input_data['date'] ? $input_data['date'] : date("Y-m-d H:i:s");
        $prepared_data['etn_speaker_designation']   = ! empty( $input_data['designation'] ) ? sanitize_text_field( $input_data['designation'] ) : '';
        $prepared_data['etn_company_name']          = ! empty( $input_data['company_name'] ) ? sanitize_text_field( $input_data['company_name'] ) : '';
        $prepared_data['etn_speaker_url']           = ! empty( $input_data['company_url'] ) ? sanitize_url( $input_data['company_url'] ) : '';
        $prepared_data['etn_speaker_summery']       = ! empty( $input_data['summary'] ) ? wp_kses_post( $input_data['summary'] ) : '';
        $prepared_data['image']                     = ! empty( $input_data['image'] ) ? sanitize_url( $input_data['image'] ) : '';
        $prepared_data['image_id']                  = ! empty( $input_data['image_id'] ) ? intval( $input_data['image_id'] ) : attachment_url_to_postid( $input_data['image'] );
        $prepared_data['etn_speaker_company_logo']  = ! empty( $input_data['company_logo'] ) ? sanitize_url( $input_data['company_logo'] ) : '';
        $prepared_data['etn_company_logo_id']       = ! empty( $input_data['company_logo_id'] ) ? intval( $input_data['company_logo_id'] ): attachment_url_to_postid( $input_data['company_logo'] );

        return $prepared_data;
    }

    public static function get_etn_user_role( $category = null ) {
        $user_ids = [];
        
        $users = get_users( array(
            'meta_key' => 'etn_speaker_category',
            'meta_compare' => 'EXISTS'
        ));


        //taxonomy details
        $prepared_args = array(
            'taxonomy'   => 'etn_speaker_category',
            'hide_empty' => false,
        );

        $term_name = [];

        $taxonomy = get_terms( $prepared_args );
        if ( ! empty( $taxonomy ) ) {
            foreach ( $taxonomy as $term ) {
                $term_name[] = strtolower( $term->name );
            }
        }

        foreach ( $users as $user ) {
            $meta_value = get_user_meta( $user->ID, 'etn_speaker_category', true );
            
            if ( $category ) {
                // Check if any of the categories are in the serialized meta value
                if ( in_array( $category, maybe_unserialize( $meta_value ) ) ) {
                    $user_ids[] = $user->ID;
                }
            }
            
        }
        return $user_ids;
    }

    public function get_user_ids_by_group( $group ) {
        if ( ! $group ) {
            return [];
        }
    
        // Initialize an array to store matching user IDs.
        $matching_user_ids = [];
    
        // Step 1: Get all users with either 'etn_speaker_group' or 'etn_speaker_speaker_group' meta key.
        $user_query = new WP_User_Query([
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => 'etn_speaker_group',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => 'etn_speaker_speaker_group',
                    'compare' => 'EXISTS',
                ],
            ],
            'fields' => ['ID'],
        ]);
    
        if ( ! empty( $user_query->get_results() ) ) {
            foreach ( $user_query->get_results() as $user ) {
                $user_id = $user->ID;
    
                // Step 2: Fetch the serialized meta values
                $speaker_group = get_user_meta($user_id, 'etn_speaker_group', true);
                $speaker_speaker_group = get_user_meta($user_id, 'etn_speaker_speaker_group', true);
    
                // Step 3: Check if the provided $group exists in either of the serialized arrays
                if ( (is_array( $speaker_group ) && in_array( $group, $speaker_group ) ) ||
                    (is_array( $speaker_speaker_group ) && in_array( $group, $speaker_speaker_group ) ) ) {
                    $matching_user_ids[] = $user_id;
                }
            }
        }
    
        return $matching_user_ids;
    }
    
    /**
     * Export items
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  json
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

        $exporter = new Speaker_Exporter();
        $response = $exporter->export( $ids, $format );

        if ( is_wp_error( $response ) ) {
            return $response;
        }
    }

    /**
     * Check the permissions for export items
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  bool
     */
    public function export_item_permissions_check( $request ) {
        return true;
    }

    public function import_items( $request ) {
        $data = $request->get_file_params();
        $file = ! empty( $data['speaker_import'] ) ? $data['speaker_import'] : '';

        if ( ! $file ) {
            return new WP_Error( 'empty_file', __( 'You must provide a valid file.', 'eventin' ), ['status' => 409] );
        }

        $importer = new Speaker_Importer();
        $importer->import( $file );

        $response = [
            'message' => __( 'Successfully imported speaker', 'eventin' ),
        ];

        return rest_ensure_response( $response );
    }

    /**
     * Check permission for the import speakers
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  bool
     */
    public function import_item_permissions_check( $request ) {
        return true;
    }
}
