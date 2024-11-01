<?php
/**
 * Event Api Class
 *
 * @package Eventin\Event
 */
namespace Eventin\Event\Api;

use Error;
use Etn\Core\Event\Event_Exporter;
use Etn\Core\Event\Event_Importer;
use Etn\Core\Event\Event_Model;
use Eventin\Event\MeetingPlatforms\MeetingPlatform;
use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Event Controller Class
 */
class EventController extends WP_REST_Controller {
    /**
     * Store event taxonomy
     *
     * @var string
     */
    protected $taxonomy = 'etn_category';

    /**
     * Store event tags
     *
     * @var string
     */
    protected $tag_taxonomy = 'etn_tags';

    /**
     * Store event cache key
     *
     * @var string
     */
    protected $event_cache_key = 'etn_event_list';

    /**
     * Constructor for EventController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'events';
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
        // back to wordpress data from elementor data 
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)' . '/back-wordpress',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __( 'Unique identifier for the post.', 'eventin' ),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'convert_elementor_to_wordpress' ),
                    'permission_callback' => array( $this, 'update_item_permissions_check' ),
                ),
 
            ),
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/export',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __( 'Unique identifier for the post.', 'eventin' ),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'export_items' ),
                    'permission_callback' => array( $this, 'export_permissions_check' ),
                ),
 
            ),
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/import',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __( 'Unique identifier for the post.', 'eventin' ),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'import_items' ),
                    'permission_callback' => array( $this, 'import_permissions_check' ),
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
        return true;
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $per_page       = ! empty( $request['per_page'] ) ? intval( $request['per_page'] ) : 20;
        $paged          = ! empty( $request['paged'] ) ? intval( $request['paged'] ) : 1;
        $type           = ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : '';
        $start_date     = ! empty( $request['start_date'] ) ? sanitize_text_field( $request['start_date'] ) : '';
        $end_date       = ! empty( $request['end_date'] ) ? sanitize_text_field( $request['end_date'] ) : '';
        $search_keyword = ! empty( $request['search_keyword'] ) ? sanitize_text_field( $request['search_keyword'] ) : '';
        $status         = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'all';
    
        $args = [
            'post_type'      => 'etn',
            'post_status'    => 'any',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
        ];

        if ( ! current_user_can( 'manage_options' ) ) {
            $args['author'] = get_current_user_id(); 
        }

        $meta_query = [];
    
        if ( $start_date && $end_date ) {
            $meta_query[] = [
                'relation' => 'AND',
                [
                    'key'     => 'etn_start_date',
                    'value'   => $start_date,
                    'compare' => '>=',
                    'type'    => 'DATETIME'
                ],
                [
                    'key'     => 'etn_end_date',
                    'value'   => $end_date,
                    'compare' => '<=',
                    'type'    => 'DATETIME'
                ],
            ];
        }
    
        if ( $search_keyword ) {
            $args['s'] = $search_keyword;
        }
    
        $current_datetime = date( 'Y-m-d H:i:s' );
        $tomorrow = date( 'Y-m-d H:i:s', strtotime( '+1 day' ) );
        $yesterday = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
    
        if ( 'draft' === $status ) {
            $args['post_status'] = 'draft';
        } elseif ( 'all' !== $status ) {
            // Exclude drafts unless explicitly requested
            $args['post_status'] = array('publish', 'pending', 'future', 'private', 'inherit');
    
            if ( 'upcoming' === $status ) {
                $meta_query[] = [
                    'key'     => 'etn_start_date',
                    'value'   => $current_datetime,
                    'compare' => '>',
                    'type'    => 'DATETIME'
                ];
            } elseif ( 'past' === $status ) {
                $meta_query[] = [
                    'key'     => 'etn_end_date',
                    'value'   => $current_datetime,
                    'compare' => '<',
                    'type'    => 'DATETIME'
                ];
            } elseif ( 'ongoing' === $status ) {
                $meta_query[] = [
                    'relation' => 'AND',
                    [
                        'key'     => 'etn_start_date',
                        'value'   => $current_datetime,
                        'compare' => '<=',
                        'type'    => 'DATETIME'
                    ],
                    [
                        'key'     => 'etn_end_date',
                        'value'   => $current_datetime,
                        'compare' => '>=',
                        'type'    => 'DATETIME'
                    ],
                ];
            }
        }
    
        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;   
        }

        if ( $meta_query || $search_keyword || $status ) {
            return $this->get_event_list( $args, $request );
        }

        // Define cache key and group
        $cache_key = $this->event_cache_key;

        // Try to get cached data
        $response = get_transient( $cache_key );
        
        if ( ! $response ) {
            $response = $this->get_event_list( $args, $request );

            // Set the cache
            set_transient( $cache_key, $response, 3 * HOUR_IN_SECONDS );
        }

        return $response;
    }

    /**
     * Get event lists
     *
     * @param   [type]  $args     [$args description]
     * @param   [type]  $request  [$request description]
     *
     * @return  [type]            [return description]
     */
    protected function get_event_list( $args, $request ) {
        $events = [];

        $post_query   = new WP_Query( $args );
        $query_result = $post_query->posts;

        $total_posts  = $post_query->found_posts;
    
        foreach ( $query_result as $post ) {
            $post_data = $this->prepare_item_for_response( $post, $request );
    
            $events[] = $this->prepare_response_for_collection( $post_data );
        }
    
        $data = [
            'total_items' => $total_posts,
            'items' => $events,
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
        $post = get_post( $id );

        $item = $this->prepare_item_for_response( $post, $request );

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

        if ( is_wp_error( $prepared_event ) ) {
            return $prepared_event;
        }

        $event   = new Event_Model();
        $created = $event->create( $prepared_event );

        if ( ! $created ) {
            return new WP_Error(
                'event_create_error',
                __( 'Event can not be created.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        // Update event categories.
        if ( ! empty( $prepared_event['categories'] ) ) {
            $this->assign_categories( $request['id'], $prepared_event['categories'] );
        }

        // Update event tags.
        if ( ! empty( $prepared_event['tags'] ) ) {
            $this->assign_tags( $request['id'], $prepared_event['tags'] );
        }

        // Manage online event.
        if ( 'online' === $prepared_event['event_type'] ) {
            $meeting_link = $this->prepare_meeting_link( $prepared_event );

            if ( is_wp_error( $meeting_link ) ) {
                return $meeting_link;
            }

            $event->update( [
                'meeting_link' => $meeting_link,
            ] );
        }

        if(isset($request['id'])){ 
            set_post_thumbnail($request['id'], $request['event_banner_id']);
        }

        $post = get_post( $event->id );

        $item = $this->prepare_item_for_response( $post, $request );

        do_action( 'eventin_event_created', $event, $request );

        delete_transient( $this->event_cache_key );

        $response = rest_ensure_response( $item );
        $response->set_status( 201 );

        return $response;
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
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
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
        $prepared_event = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $prepared_event ) ) {
            return $prepared_event;
        }

        $event   = new Event_Model( $request['id'] );

        if ( $event->is_clone ) {
            $prepared_event['is_clone'] = false;
        }

        $updated = $event->update( $prepared_event );

        if ( ! $updated ) {
            return new WP_Error(
                'event_update_error',
                __( 'Event can not be updated.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        // Update event categories.
        if ( isset( $prepared_event['categories'] ) ) {
            $this->assign_categories( $request['id'], $prepared_event['categories'] );
        }

        // Update event tags.
        if ( isset( $prepared_event['tags'] ) ) {
            $this->assign_tags( $request['id'], $prepared_event['tags'] );
        } 
        if(isset($request['id'])){ 
            set_post_thumbnail($request['id'], $request['event_banner_id']);
        }

        // Manage online event.
        $platform = is_array( $prepared_event['location'] ) && ! empty( $prepared_event['location']['integration'] ) ? $prepared_event['location']['integration'] : '';

        if ( 'online' === $prepared_event['event_type'] ) {
            
            $meeting_link = $this->prepare_meeting_link( $prepared_event );
            if ( is_wp_error( $meeting_link ) ) {
                return $meeting_link;
            }

            $event->update( [
                'meeting_link' => $meeting_link,
            ] );
        }

        $post = get_post( $event->id );

        $item = $this->prepare_item_for_response( $post, $request );

        do_action( 'eventin_event_updated', $event, $request );

        delete_transient( $this->event_cache_key );

        $response = rest_ensure_response( $item );

        return $response;
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

        $previous = $this->prepare_item_for_response( $post, $request );

        $event = new Event_Model( $id );

        do_action( 'eventin_event_before_delete', $event );

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

        delete_transient( $this->event_cache_key );

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
                __( 'Event ids can not be empty.', 'eventin' ),
                array( 'status' => 400 )
            );
        }
        $count = 0;

        foreach ( $ids as $id ) {
            $event = new Event_Model( $id );

            if ( $event->delete() ) {
                $count++;
            }
        }

        if ( $count == 0 ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Event cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        $message = sprintf( __( '%d events are deleted of %d', 'eventin' ), $count, count( $ids ) );

        delete_transient( $this->event_cache_key );

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
        $event_id = intval( $request['id'] );

        $event = new Event_Model( $event_id );

        $clone_event = $event->clone();

        $response = $this->prepare_item_for_response( get_post( $clone_event->id ), $request );

        // Manage categories.
        $categories = get_the_terms( $event_id, $this->taxonomy );
        $categories = $categories ? array_column( $categories, 'term_id' ) : [];
        $this->assign_categories( $clone_event->id, $categories );


        // Manage tags.
        $tags = get_the_terms( $event_id, $this->tag_taxonomy );
        $tags = $tags ? array_column( $tags, 'term_id' ) : [];
        $this->assign_tags( $clone_event->id, $tags );

        do_action( 'eventin_event_after_clone', $clone_event );

        return rest_ensure_response( $response );
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
        $event          = new Event_Model( $item->ID );
        $status         = get_post_status( $item->ID );
        $id             = $item->ID;
        $parent         = $item->post_parent;
        $author         = get_userdata( $item->post_author )->display_name;
        $categories     = get_the_terms( $id, 'etn_category' );
        $category_names = wp_list_pluck( $categories, 'name', 'term_id' );
        $categories     = $categories ? array_column( $categories, 'term_id' ) : [];
        $tags           = get_the_terms( $id, $this->tag_taxonomy );
        $tags           = $tags ? array_column( $tags, 'term_id' ) : [];
        $end_date       = strtotime( get_post_meta( $id, 'etn_end_date', true ) );
        $curretn_date   = strtotime( date( 'Y-m-d' ) );

        $schedules       = get_post_meta( $id, 'etn_event_schedule', true );
        $organizer       = get_post_meta( $id, 'etn_event_organizer', true );
        $speaker         = get_post_meta( $id, 'etn_event_speaker', true );
        $organizer_group = get_post_meta( $id, 'organizer_group', true );
        $speaker_group   = get_post_meta( $id, 'speaker_group', true );
        $extra_fields    = get_post_meta( $id, 'attendee_extra_fields', true );
        $meeting_link    = get_post_meta( $id, 'meeting_link', true );
        $rsvp_settings   = get_post_meta( $id, 'rsvp_settings', true );
        $speaker_type    = get_post_meta( $id, 'speaker_type', true );
        $organizer_type  = get_post_meta( $id, 'organizer_type', true );
        $event_slug      = '';

        if ( ! empty( $rsvp_settings['rsvp_form_type'] ) ) {
            $rsvp_settings['rsvp_form_type'] = is_array( $rsvp_settings['rsvp_form_type'] ) ? array_values( $rsvp_settings['rsvp_form_type'] ) : [];
        }

        $extra_fields    = is_array( $extra_fields ) ? array_values( $extra_fields ) : [];
        
        if ( 'publish' === $status ) {
            $status = $curretn_date > $end_date ? __( 'Past', 'eventin' ) : __( 'Upcoming', 'eventin' );
        }

        $post = get_post( $id );

        $event_data = [
            'id'                      => $id,
            'title'                   => get_the_title( $id ),
            'event_slug'              => $post->post_name,
            'description'             => $post->post_content,
            'excerpt'                 => $post->post_excerpt,
            'excerpt_enable'          => get_post_meta( $id, 'excerpt_enable', true ),
            'schedule_type'           => get_post_meta( $id, 'etn_select_speaker_schedule_type', true ),
            'author'                  => $author,
            'categories'              => $categories,
            'tags'                    => $tags,
            'status'                  => $status,
            'link'                    => get_permalink( $id ),
            'schedules'               => is_array( $schedules ) ? array_map('intval', $schedules ) : [],
            'organizer'               => 'single' === $organizer_type && $organizer ? $organizer : [],
            'organizer_type'          => $organizer_type,
            'organizer_group'         => 'group' === $organizer_type && is_array( $organizer_group ) ? array_map( 'intval', $organizer_group ) : [],
            'speaker'                 => 'single' === $speaker_type && $speaker ? $speaker : [],
            'speaker_type'            => $speaker_type,
            'speaker_group'           => 'group' === $speaker_type && is_array( $speaker_group ) ? array_map( 'intval', $speaker_group ) : [],
            'timezone'                => get_post_meta( $id, 'event_timezone', true ),
            'start_date'              => get_post_meta( $id, 'etn_start_date', true ),
            'end_date'                => get_post_meta( $id, 'etn_end_date', true ),
            'start_time'              => get_post_meta( $id, 'etn_start_time', true ),
            'end_time'                => get_post_meta( $id, 'etn_end_time', true ),
            'ticket_availability'     => get_post_meta( $id, 'etn_ticket_availability', true ),
            'event_logo'              => get_post_meta( $id, 'etn_event_logo', true ),
            'calendar_bg'             => get_post_meta( $id, 'etn_event_calendar_bg', true ),
            'calendar_text_color'     => get_post_meta( $id, 'etn_event_calendar_text_color', true ),
            'registration_deadline'   => get_post_meta( $id, 'etn_registration_deadline', true ),
            'attende_page_link'       => get_post_meta( $id, 'attende_page_link', true ),
            'zoom_event'              => get_post_meta( $id, 'etn_zoom_event', true ),
            'zoom_id'                 => get_post_meta( $id, 'etn_zoom_id', true ),
            'total_ticket'            => $event->get_total_ticket(),
            'sold_tickets'            => $event->get_total_sold_ticket(),
            'ticket_variations'       => get_post_meta( $id, 'etn_ticket_variations', true ),
            'event_socials'           => get_post_meta( $id, 'etn_event_socials', true ),
            'google_meet'             => get_post_meta( $id, 'etn_google_meet', true ),
            'google_meet_link'        => get_post_meta( $id, 'etn_google_meet_link', true ),
            'google_meet_description' => get_post_meta( $id, 'etn_google_meet_short_description', true ),
            'fluent_crm'              => get_post_meta( $id, 'fluent_crm', true ),
            'fluent_crm_webhook'      => get_post_meta( $id, 'fluent_crm_webhook', true ),
            'faq'                     => get_post_meta( $id, 'etn_event_faq', true ),
            'external_link'           => get_post_meta( $id, 'external_link', true ),
            'ticket_template'         => get_post_meta( $id, 'ticket_template', true ),
            'certificate_template'    => get_post_meta( $id, 'certificate_template', true ),
            'seat_plan'               => get_post_meta( $id, 'seat_plan', true ),
            'rsvp_settings'           => $rsvp_settings,
            'recurring_enabled'       => get_post_meta( $id, 'recurring_enabled', true ),
            'event_recurrence'        => get_post_meta( $id, 'etn_event_recurrence', true ),
            'event_banner'            => get_post_meta( $id, 'event_banner', true ),
            'event_layout'            => get_post_meta( $id, 'event_layout', true ),
            'is_clone'                => get_post_meta( $id, 'is_clone', true ),
            'extra_fields'            => $extra_fields ? $extra_fields : [],
            'category_names'          => $category_names,
            'meeting_link'            => $meeting_link,
            'parent'                  => $parent,
            'event_type'              => get_post_meta( $id, 'event_type', true ),
            '_virtual'                => get_post_meta( $id, '_virtual', true ),
            'etn_event_logo_url'      => get_post_meta( $id, 'etn_event_logo_url', true ),
            'banner_bg_image_url'     => get_post_meta( $id, 'banner_bg_image_url', true ),
            'event_logo_id'           => get_post_meta( $id, 'event_logo_id', true ),
            'event_banner_id'         => get_post_meta( $id, 'event_banner_id', true ),
            'edit_with_elementor'     => $this->check_post_edit_with_elementor( $id ),
            'elementor_supported'     => $this->is_etn_post_type_supported_by_elementor( ),
        ];

        $location_type = get_post_meta( $id, 'etn_event_location_type', true );
        $location      = get_post_meta( $id, 'etn_event_location', true );

        if ( 'new_location' == $location_type ) {
            $location = get_post_meta( $id, 'etn_event_location_list', true );
        }

        $event_data['location_type'] = $location_type;
        $event_data['location']      = $location;

        return $event_data;
    }

    /**
     * Assgin event categories
     *
     * @param   integer  $post_id
     * @param   array  $new_categories
     *
     * @return  void
     */
    protected function assign_categories( $post_id, $new_categories ) {
        // Update event categories.
        $categories = get_the_terms( $post_id, 'etn_category' );
        $categories = $categories ? array_column( $categories, 'term_id' ) : [];

        if ( $categories ) {
            wp_remove_object_terms( $post_id, $categories, $this->taxonomy );
        }

        wp_set_post_terms( $post_id, $new_categories, $this->taxonomy, true );
    }

    /**
     * Assgin event tags
     *
     * @param   integer  $post_id
     * @param   array  $new_tags
     *
     * @return  void
     */
    protected function assign_tags( $post_id, $new_tags ) {
        // Update event tags.
        $tags = get_the_terms( $post_id, $this->tag_taxonomy );
        $tags = $tags ? array_column( $tags, 'term_id' ) : [];

        if ( $tags ) {
            wp_remove_object_terms( $post_id, $tags, $this->tag_taxonomy );
        }

        wp_set_post_terms( $post_id, $new_tags, $this->tag_taxonomy, true );
    }

    /**
     * Prepare online meeting link
     *
     * @param   array  $prepared_event
     *
     * @return  mixed
     */
    protected function prepare_meeting_link( $prepared_event ) {
        $platform_name = is_array( $prepared_event['location'] ) ? $prepared_event['location']['integration'] : '';

        try {
            $meeting_platform = MeetingPlatform::get_platform( $platform_name );

            if ( ! $meeting_platform->is_connected() ) {
                return new WP_Error( 'not_connected', __( $platform_name . ' is not connected', 'eventin' ), ['status' => 400] );
            }

            $args = [
                'title'      => $prepared_event['post_title'],
                'start_date' => $prepared_event['etn_start_date'],
                'start_time' => $prepared_event['etn_start_time'],
                'end_date'   => $prepared_event['etn_end_date'],
                'end_time'   => $prepared_event['etn_end_time'],
                'custom_url' => $prepared_event['custom_url']
            ];

            if ( ! empty( $prepared_event['location']['custom_url'] ) ) {
                $args['custom_url'] = $prepared_event['location']['custom_url'];
            }

            $meeting_link = $meeting_platform->create_link( $args );

            

            return $meeting_link;
        } catch ( \Exception $e ) {

            return new WP_Error( 'unsupported_platform', $e->getMessage() );
        }
    }

    /**
     * Prepare speakers for the event
     *
     * @param   array  $args
     *
     * @return  array
     */
    private function prepare_speaker( $args = [] ) {
        $speaker_type   = isset( $args['speaker_type'] ) ? $args['speaker_type'] : '';
        $speakers       = isset( $args['speaker'] ) ? $args['speaker'] : '';
        $speaker_groups = isset( $args['speaker_group'] ) ? $args['speaker_group'] : '';

        if ( 'single' === $speaker_type ) {
            return $speakers;
        }

        if ( ! $speaker_groups ) {
            return [];
        }

        $user_ids = [];

        foreach( $speaker_groups as $group_id ) {
            $args = array(
                'fields'     => 'ID',
                'number'     => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'etn_speaker_group',
                        'value'   => strval($group_id),
                        'compare' => 'LIKE',
                    ),
                ),
            );

            $user_ids = array_merge( $user_ids, get_users( $args ) );

            $user_ids = array_unique( $user_ids );
        }

        return $user_ids;
    }

    /**
     * Prepare organizer for the event
     *
     * @param   array  $args  [$args description]
     *
     * @return  array
     */
    private function prepare_organizer( $args = [] ) {
        $orgnizer_type    = isset( $args['organizer_type'] ) ? $args['organizer_type'] : '';
        $organizers       = isset( $args['organizer'] ) ? $args['organizer'] : '';
        $organizer_groups = isset( $args['organizer_group'] ) ? $args['organizer_group'] : '';

        if ( 'single' === $orgnizer_type ) {
            return $organizers;
        }

        if ( ! $organizer_groups ) {
            return [];
        }

        $user_ids = [];

        foreach( $organizer_groups as $group_id ) {
            $args = array(
                'fields'     => 'ID',
                'number'     => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'etn_speaker_group',
                        'value'   => strval($group_id),
                        'compare' => 'LIKE',
                    ),
                ),
            );

            $user_ids = array_merge( $user_ids, get_users( $args ) );

            $user_ids = array_unique( $user_ids );
        }

        return $user_ids;
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|object $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        $input_data = json_decode( $request->get_body(), true ) ?? [];
         $validate   = etn_validate( $input_data, [
            'title'      => [
                'required',
            ],
            'timezone'   => [
                'required',
            ],
            'start_date' => [
                'required',
            ],
            'end_date'   => [
                'required',
            ],
            'start_time' => [
                'required',
            ],
            'end_time'   => [
                'required',
            ],
        ] );

        if ( is_wp_error( $validate ) ) {
            return $validate;
        }

        $event_data = [];
        if ( isset( $input_data['title'] ) ) {
            $event_data['post_title'] = $input_data['title'];            
        }        
        

        if ( isset( $input_data['description'] ) ) {
            $event_data['post_content'] = $input_data['description'];
        }

        if ( isset( $input_data['excerpt'] ) ) {
            $event_data['post_excerpt'] = $input_data['excerpt'];
        }

        if ( isset( $input_data['excerpt_enable'] ) ) {
            $event_data['excerpt_enable'] = $input_data['excerpt_enable'];
        }

        if ( isset( $input_data['schedule_type'] ) ) {
            $event_data['etn_select_speaker_schedule_type'] = $input_data['schedule_type'];
        }

        if ( isset( $input_data['organizer'] ) ) {
            $event_data['etn_event_organizer'] = $this->prepare_organizer( $input_data );
        }

        if ( isset( $input_data['speaker'] ) ) {
            $event_data['etn_event_speaker'] = $this->prepare_speaker( $input_data );
        }

        if ( isset( $input_data['timezone'] ) ) {
            $event_data['event_timezone'] = $input_data['timezone'];
        }

        if ( isset( $input_data['start_date'] ) ) {
            $event_data['etn_start_date'] = $input_data['start_date'];
        }

        if ( isset( $input_data['end_date'] ) ) {
            $event_data['etn_end_date'] = $input_data['end_date'];
        }

        if ( isset( $input_data['start_time'] ) ) {
            $event_data['etn_start_time'] = $input_data['start_time'];
        }

        if ( isset( $input_data['end_time'] ) ) {
            $event_data['etn_end_time'] = $input_data['end_time'];
        }

        if ( isset( $input_data['ticket_availability'] ) ) {
            $event_data['etn_ticket_availability'] = $input_data['ticket_availability'];
        }

        if ( isset( $input_data['ticket_variations'] ) ) {
            $event_data['etn_ticket_variations'] = $input_data['ticket_variations'];
        }

        if ( isset( $input_data['event_logo'] ) ) {
            $event_data['etn_event_logo'] = $input_data['event_logo'];
        }

        if ( isset( $input_data['event_logo_id'] ) ) {
            $event_data['event_logo_id'] = $input_data['event_logo_id'];
        }

        if ( isset( $input_data['event_banner_id'] ) ) {
            $event_data['event_banner_id'] = $input_data['event_banner_id']; 
        }

        if ( isset( $input_data['calendar_text_color'] ) ) {
            $event_data['etn_event_calendar_text_color'] = $input_data['calendar_text_color'];
        }

        if ( isset( $input_data['calendar_bg'] ) ) {
            $event_data['etn_event_calendar_bg'] = $input_data['calendar_bg'];
        }

        if ( isset( $input_data['registration_deadline'] ) ) {
            $event_data['etn_registration_deadline'] = $input_data['registration_deadline'];
        }

        if ( isset( $input_data['attende_page_link'] ) ) {
            $event_data['attende_page_link'] = $input_data['attende_page_link'];
        }

        if ( isset( $input_data['zoom_id'] ) ) {
            $event_data['etn_zoom_id'] = $input_data['zoom_id'];
        }

        if ( isset( $input_data['location_type'] ) ) {
            $event_data['etn_event_location_type'] = $input_data['location_type'];
        }

        if ( isset( $input_data['location'] ) ) {
            $event_data['etn_event_location'] = $input_data['location'];
        }

        if ( isset( $input_data['zoom_event'] ) ) {
            $event_data['etn_zoom_event'] = $input_data['zoom_event'];
        }

        if ( isset( $input_data['total_ticket'] ) ) {
            $event_data['etn_total_avaiilable_tickets'] = $input_data['total_ticket'];
        }

        if ( isset( $input_data['google_meet'] ) ) {
            $event_data['etn_google_meet'] = $input_data['google_meet'];
        }

        if ( isset( $input_data['google_meet_description'] ) ) {
            $event_data['etn_google_meet_short_description'] = $input_data['google_meet_description'];
        }

        if ( isset( $input_data['fluent_crm'] ) ) {
            $event_data['fluent_crm'] = $input_data['fluent_crm'];
        }

        if ( isset( $input_data['location_type'] ) ) {
            $event_data['etn_event_location_type'] = $input_data['location_type'];
        }

        if ( isset( $input_data['event_socials'] ) ) {
            $event_data['etn_event_socials'] = $input_data['event_socials'];
        }

        if ( isset( $input_data['schedules'] ) ) {
            $event_data['etn_event_schedule'] = $input_data['schedules'];
        }

        if ( isset( $input_data['categories'] ) ) {
            $event_data['categories'] = $input_data['categories'];
        }

        if ( isset( $input_data['tags'] ) ) {
            $event_data['tags'] = $input_data['tags'];
        }

        if ( isset( $input_data['faq'] ) ) {
            $event_data['etn_event_faq'] = $input_data['faq'];
        }

        if ( isset( $input_data['extra_fields'] ) ) {
            $event_data['attendee_extra_fields'] = $input_data['extra_fields'];
        }

        // Support speaker and organizer group.
        if ( isset( $input_data['speaker_type'] ) ) {
            $event_data['speaker_type'] = $input_data['speaker_type'];
        }

        if ( isset( $input_data['speaker_group'] ) ) {
            $event_data['speaker_group'] = $input_data['speaker_group'];
        }

        if ( isset( $input_data['organizer_type'] ) ) {
            $event_data['organizer_type'] = $input_data['organizer_type'];
        }

        if ( isset( $input_data['organizer_group'] ) ) {
            $event_data['organizer_group'] = $input_data['organizer_group'];
        }

        if ( isset( $input_data['fluent_crm_webhook'] ) ) {
            $event_data['fluent_crm_webhook'] = $input_data['fluent_crm_webhook'];
        }

        // Recurring event data.
        if ( isset( $input_data['recurring_enabled'] ) ) {
            $event_data['recurring_enabled'] = $input_data['recurring_enabled'];
        }

        if ( isset( $input_data['event_recurrence'] ) ) {
            $event_data['etn_event_recurrence'] = $input_data['event_recurrence'];
        }

        // RSVP support.
        if ( isset( $input_data['rsvp_settings'] ) ) {
            $event_data['rsvp_settings'] = $input_data['rsvp_settings'];
        }

        // Seat Plan Support.
        if ( isset( $input_data['seat_plan'] ) ) {
            $event_data['seat_plan'] = $input_data['seat_plan'];
        }

        if ( isset( $input_data['seat_plan_settings'] ) ) {
            $event_data['seat_plan_settings'] = $input_data['seat_plan_settings'];
        }

        // Template support.
        if ( isset( $input_data['ticket_template'] ) ) {
            $event_data['ticket_template'] = $input_data['ticket_template'];
        }

        if ( isset( $input_data['certificate_template'] ) ) {
            $event_data['certificate_template'] = $input_data['certificate_template'];
        }

        if ( isset( $input_data['external_link'] ) ) {
            $event_data['external_link'] = $input_data['external_link'];
        }

        if ( isset( $input_data['event_banner'] ) ) {
            $event_data['event_banner'] = $input_data['event_banner'];
        }

        if ( isset( $input_data['event_layout'] ) ) {
            $event_data['event_layout'] = $input_data['event_layout'];
        }

        if ( isset( $input_data['status'] ) ) {
            $event_data['post_status'] = $input_data['status'];
        }

        if ( ! empty( $input_data['event_slug'] ) ) {
            $event_data['post_name'] = sanitize_title( $input_data['event_slug'] );
        }

        if ( isset( $input_data['event_type'] ) ) {
            $event_data['event_type'] = $input_data['event_type'];
        }

        if ( isset( $input_data['location'] ) ) {
            $event_data['location'] = $input_data['location'];
        }

        // certificate prefference.
        if ( isset( $input_data['certificate_preference'] ) ) {
            $event_data['certificate_preference'] = $input_data['certificate_preference'];
        }

        if ( isset( $input_data['_virtual'] ) ) {
            $event_data['_virtual'] = $input_data['_virtual'];
        }

        
        
        return $event_data;
    }

    /**
     * back to wordpress data from elementor data
     *
     * @param   int  $event_id
     *
     * @return  bool
     */
    public function convert_elementor_to_wordpress( $request ) {
        // Check if Elementor is loaded
        if ( ! did_action( 'elementor/loaded' ) ) {
            return false;
        }
    
        // Check if the request has an 'id' parameter
        if ( ! isset( $request['id'] ) ) {
            return false;
        }
    
        $document = \Elementor\Plugin::$instance->documents->get( $request['id'] );
    
        // Check if the document exists and is built with Elementor
        if ( $document && $document->is_built_with_elementor() ) {
            $document->set_is_built_with_elementor( false );
            return false;
        } 
        return false;
    }
    
    /**
     * Check if a post type is supported by Elementor
     *
     * @param   string  $post_type  The post type to check
     *
     * @return  bool    True if the post type is supported, false otherwise
     */
    public function is_etn_post_type_supported_by_elementor($post_type='etn') {
        // Get the list of post types supported by Elementor

        if ( ! did_action( 'elementor/loaded' ) ) {
            return false;
        }
        $elementor_post_types = get_option('elementor_cpt_support', []);

        // Check if your custom post type is in the list
        if (!empty($elementor_post_types) && in_array($post_type, $elementor_post_types)  ) {
            return true;
        } else {
            return false;
        }
    } 


    /**
     * Disable Elementor for a given post
     *
     * @param int $post_id The ID of the post to disable Elementor for
     * @return void
     */
    public function check_post_edit_with_elementor( $post_id ) {

        if ( ! did_action( 'elementor/loaded' ) ) {
            return false;
        }

        // Get the Elementor document for the post
        $document = \Elementor\Plugin::$instance->documents->get( $post_id );
        
        // Check if the post is built with Elementor
        $built_with_elementor =  $document->is_built_with_elementor();
        
        return $built_with_elementor;
    }

    /**
     * Export items
     *
     * @return  WP_Rest_Response | WP_Error
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

        $exporter = new Event_Exporter();
        $exporter->export( $ids, $format );
    }

    /**
     * Check permissions for export events
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  bool
     */
    public function export_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
    }

    /**
     * Import events
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  [type]            [return description]
     */
    public function import_items( $request ) {
        $data = $request->get_file_params();
        $file = ! empty( $data['event_import'] ) ? $data['event_import'] : '';

        if ( ! $file ) {
            return new WP_Error( 'empty_file', __( 'You must provide a valid file.', 'eventin' ), ['status' => 409] );
        }

        $importer = new Event_Importer();
        $importer->import( $file );

        $response = [
            'message' => __( 'Successfully imported event', 'eventin' ),
        ];

        return rest_ensure_response( $response );
    }

    /**
     * Check permissions for export events
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  bool
     */
    public function import_permissions_check( $request ) {
        return current_user_can( 'manage_options' ) 
                || current_user_can( 'seller' )
                || current_user_can( 'editor' );
    }
}
