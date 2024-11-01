<?php
namespace Eventin\Order;

use Etn\Core\Attendee\Attendee_Model;
use Etn\Core\Event\Event_Model;
use Eventin\Attendee\Attendee\TicketIdGenerator;
use Eventin\Customer\CustomerModel;
use Eventin\Input;
use Eventin\Emails\AdminOrderEmail;
use Eventin\Emails\AttendeeOrderEmail;
use Eventin\Mails\Mail;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Order controller class
 * 
 * @package Eventin
 */
class OrderController extends WP_REST_Controller {
    /**
     * Constructor for OrderController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'orders';
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

        register_rest_route( $this->namespace, $this->rest_base . '/export', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'export_items'],
                'permission_callback' => [$this, 'export_item_permissions_check'],
            ],
        ] );

        register_rest_route( $this->namespace, $this->rest_base . '/import', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'import_items'],
                'permission_callback' => [$this, 'import_item_permissions_check'],
            ],
        ] );

        register_rest_route( $this->namespace, $this->rest_base . '/(?P<id>[\d]+)' . '/resend-ticket', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'resend_ticket'],
                'permission_callback' => [$this, 'resend_ticket_permissions_check'],
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
        return true;
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
        $event_id   = ! empty( $request['event_id'] ) ? sanitize_text_field( $request['event_id'] ) : '';
        $status = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : '';
        $payment_method = ! empty( $request['payment_method'] ) ? sanitize_text_field( $request['payment_method'] ) : '';

        $search   = ! empty( $request['search_keyword'] ) ? sanitize_text_field( $request['search_keyword'] ) : '';

        $strt_datetime = ! empty( $request['strt_datetime'] ) ? sanitize_text_field( $request['strt_datetime'] ) : '';

        $end_datetime = ! empty( $request['end_datetime'] ) ? sanitize_text_field( $request['end_datetime'] ) : '';

        $args = [
            'post_type'      => 'etn-order',
            'post_status'    => 'any',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
        ];

        if ( is_numeric( $search ) ) {
            $args['p'] = $search;
        }

        $meta_query = [];

        if ( ! empty( $event_id ) ) {
            $meta_query[] = [
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ];
        }

        if ( ! empty( $status ) ) {
            $meta_query[] = [
                'key'     => 'status',
                'value'   => $status,
                'compare' => '=',
            ];
        }

        if ( ! empty( $payment_method ) ) {
            $meta_query[] = [
                'key'     => 'payment_method',
                'value'   => $payment_method,
                'compare' => '=',
            ];
        }

        if ( $strt_datetime && $end_datetime ) {
            $args['date_query'] = [
                'relation' => 'AND',
                [
                    'before'    => [
                        'year'  => gmdate('Y', strtotime($end_datetime)),
                        'month' => gmdate('m', strtotime($end_datetime)),
                        'day'   => gmdate('d', strtotime($end_datetime)),
                    ],
                    'after'     => [
                        'year'  => gmdate('Y', strtotime($strt_datetime)),
                        'month' => gmdate('m', strtotime($strt_datetime)),
                        'day'   => gmdate('d', strtotime($strt_datetime)),
                    ],
                    'inclusive' => true, // Include posts that match the exact date range boundaries
                ],
            ];
        }

        if ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';

            $args['meta_query'] = $meta_query; 
        }

        if ( $search && ! is_numeric( $search ) ) {
            $meta_query = array(
                'relation' => 'OR', // 'OR' means any meta key can match
                array(
                    'key'     => 'customer_fname',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'customer_lname',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'customer_email',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'customer_phone',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'payment_method',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ),
            );

            $args['meta_query'] = $meta_query; 
        }

        

        $post_query   = new \WP_Query();
        $query_result = $post_query->query( $args );
        $total_posts  = $post_query->found_posts;
        $orders = [];
    
        foreach ( $query_result as $post ) {
            $order     = new OrderModel( $post->ID );
            $post_data = $this->prepare_item_for_response( $order, $request );
    
            $orders[] = $this->prepare_response_for_collection( $post_data );
        }
    
        $response = rest_ensure_response($orders );
    
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
        $id = intval( $request['id'] );

        $order    = new OrderModel( $id );
        $response = $this->prepare_item_for_response( $order, $request );

        return rest_ensure_response( $response );
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
        $prepared_order = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $prepared_order ) ) {
            return new WP_Error( 'order_create_error', $prepared_order->get_error_message(), ['status' => 400] );
        }

        $prepared_order['date_time'] = date('Y-m-d h:i A');
        // Create order.
        $order     = new OrderModel();
        $order->create( $prepared_order );

        // Create attendees.
        $attendees = $this->prepare_attendee_data( $prepared_order['attendees'], $prepared_order['event_id'], $order->id );

        $this->create_attendees( $attendees );

        // Make customer.
        $this->create_customer($order, $request);
        
        $response = $this->prepare_item_for_response( $order, $request ); 
        
        do_action( 'eventin_after_order_create', $order);

        return rest_ensure_response( $response );
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
        return true;
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
        $id = intval( $request['id'] );
        
        $prepared_order = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $prepared_order ) ) {
            return new WP_Error( 'order_create_error', $prepared_order->get_error_message(), ['status' => 400] );
        }

        
        // Create order.
        $order     = new OrderModel( $id );
        $order->update( $prepared_order );

        // Create attendees.
        $attendees = $this->prepare_attendee_data( $prepared_order['attendees'], $prepared_order['event_id'], $order->id );

        $this->update_attendees( $attendees );

        $response = $this->prepare_item_for_response( $order, $request );

        do_action( 'eventin_after_order_update', $order );

        return rest_ensure_response( $response );

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

        $order = new OrderModel( $id );

        $previous = $this->prepare_item_for_response( $order, $request );

        do_action( 'eventin_order_before_delete', $order );

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
                __( 'The order cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        do_action( 'eventin_order_deleted', $id );

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
                __( 'Order ids can not be empty.', 'eventin' ),
                array( 'status' => 400 )
            );
        }
        $count = 0;

        foreach ( $ids as $id ) {
            $order = new OrderModel( $id );

            if ( $order->delete() ) {
                $count++;
            }
        }

        if ( $count == 0 ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Order cannot be deleted.', 'eventin' ),
                array( 'status' => 500 )
            );
        }

        $message = sprintf( __( '%d orders are deleted of %d', 'eventin' ), $count, count( $ids ) );

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
        $order = new OrderModel( $item );
        $event = get_post( $order->event_id );

        $order_data = [
            'id'                => $order->id,
            'customer_fname'    => $order->customer_fname,
            'customer_lname'    => $order->customer_lname,
            'customer_email'    => $order->customer_email,
            'customer_phone'    => $order->customer_phone,
            'date_time'         => $order->date_time,
            'event_id'          => $order->event_id,
            'event_name'        => $event ? $event->post_title : '',
            'payment_method'    => $order->payment_method,
            'status'            => $order->status,
            'total_price'       => $order->total_price,
            'total_ticket'      => $order->get_total_ticket(),
            'ticket_items'      => $order->get_tickets(),
            'attendees'         => $order->get_attendees(),
            'seat_ids'          => $order->seat_ids,
            'attendee_seats'    => $order->attendee_seats,
            'customer'          => $order->get_customer(),
        ];

        return $order_data;
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
            'tickets'           => ['required'],
        ] );

        if ( is_wp_error( $validate ) ) {
            return $validate;
        }

        $order_data = [];

        // Prepare customer data.
        if ( isset( $input_data['customer_fname'] ) ) {
            $order_data['customer_fname'] = $input_data['customer_fname'];
        }

        if ( isset( $input_data['customer_lname'] ) ) {
            $order_data['customer_lname'] = $input_data['customer_lname'];
        }

        if ( isset( $input_data['customer_email'] ) ) {
            $order_data['customer_email'] = $input_data['customer_email'];
        }

        $order_data['status'] = isset( $input_data['status'] ) ? $input_data['status'] : 'failed';

        // Prepare event data
        if ( isset( $input_data['event_id'] ) ) {
            $order_data['event_id'] = $input_data['event_id'];
        }

        if ( isset( $input_data['tickets'] ) ) {
            $order_data['tickets'] = $input_data['tickets'];
        }

        // Prepare attendee data
        if ( isset( $input_data['attendees'] ) ) {
            $order_data['attendees'] = $input_data['attendees'];
        }

        // Prepare seat ids.
        if ( isset( $input_data['seat_ids'] ) ) {
            $order_data['seat_ids'] = $input_data['seat_ids'];
        }

        if ( isset( $input_data['attendee_seats'] ) ) {
            $order_data['attendee_seats'] = $input_data['attendee_seats'];
        }

        $order_data['total_price'] = $this->total_price($order_data['event_id'], $order_data['tickets']);

        return $order_data;
    }

    /**
     * Prepare attendee data
     *
     * @param   array  $attendees  Attendees
     * @param   integer  $event_id   Event id that will be purchased
     * @param   integer  $order_id   Order id
     *
     * @return  array   Attendee data
     */
    protected function prepare_attendee_data( $attendees, $event_id, $order_id ) {
        $attendee_data = [];
        $event = new Event_Model( $event_id );

        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $ticket_slug = isset( $attendee['ticket_slug'] ) ? $attendee['ticket_slug'] : '';
                $ticket = $event->get_ticket( $ticket_slug );

                $new_attendee = [
                    'id'                   => isset( $attendee['id'] ) ? $attendee['id'] : '',
                    'etn_name'             => isset( $attendee['name'] ) ? $attendee['name'] : '', 
                    'etn_email'            => isset( $attendee['email'] ) ? $attendee['email'] : '', 
                    'etn_phone'            => isset( $attendee['phone'] ) ? $attendee['phone'] : '', 
                    'attendee_seat'        => isset( $attendee['attendee_seat'] ) ? $attendee['attendee_seat'] : '', 
                    'etn_event_id'         => $event_id,
                    'etn_status'           => '',
                    'ticket_name'          => $ticket['etn_ticket_name'],
                    'etn_ticket_price'     => $ticket['etn_ticket_price'],
                    'etn_info_edit_token'  => md5( $ticket['etn_ticket_name'] ),
                    'etn_unique_ticket_id' => substr(md5($ticket['etn_ticket_price']), 0, 10),
                    'eventin_order_id'     => $order_id,
                    'post_status'          => 'publish',

                ];

                $extra_fields = isset( $attendee['extra_fields'] ) ? $this->prepare_attendee_extra_fields( $attendee['extra_fields'] ) : [];


                $attendee_data[] = array_merge( $new_attendee, $extra_fields );;
            }
        }

        return $attendee_data;
    }

    /**
     * Prepare attendee extra fields
     *
     * @param   array  $extra_fields Attendee extra fields
     *
     * @return  array Prepare extra fields data for database
     */
    protected function prepare_attendee_extra_fields( $extra_fields ) {
        $prefix = 'etn_attendee_extra_field_';

        $data = [];

        // Add extra fields meta key prefix.
        foreach( $extra_fields as $key => $value ) {
            $meta_key = $prefix . $key;

            $data[$meta_key] = $value;
        }

        return $data;
    }

    /**
     * Create all attendee
     *
     * @param   array  $attendees  Attendees
     *
     * @return  void Create attendees
     */
    protected function create_attendees( $attendees ) {
        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendee_object = new Attendee_Model();
                $attendee['etn_status'] = 'failed';
                $attendee['etn_info_edit_token'] = md5(time() . 'etn-attendee-info');
                
                $attendee['etn_attendeee_ticket_status'] = 'unused';
                $attendee['etn_unique_ticket_id']        =  TicketIdGenerator::generate_ticket_id();
                $attendee_object->set_fields( $attendee );
                $attendee_object->create( $attendee );

                do_action( 'eventin_order_attendee_created', $attendee_object, $attendee );
            }
        }
    }

    /**
     * Create all attendee
     *
     * @param   array  $attendees  Attendees
     *
     * @return  void Create attendees
     */
    protected function update_attendees( $attendees ) {

        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendee_object = new Attendee_Model( $attendee['id'] );

                $attendee_object->set_fields( $attendee );
                $attendee_object->update( $attendee );
            }
        }
    }

    /**
     * Calculate total price for an order
     *
     * @param   integer  $event_id       Event id
     * @param   array  $order_tickets  Selected order ticket variations
     *
     * @return  integer
     */
    protected function total_price( $event_id, $order_tickets ) {
        $event         = new Event_Model( $event_id );
        $total_price   = 0;

        foreach( $order_tickets as $ticket ) {
            $event_ticket = $event->get_ticket( $ticket['ticket_slug'] );

            $total_price += $event_ticket['etn_ticket_price'] * $ticket['ticket_quantity'];
        }

        return $total_price;
    }

    /**
     * Export items
     *
     * @return  JSON
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

        $exporter = new OrderExporter();
        $response = $exporter->export( $ids, $format );

        if ( is_wp_error( $response ) ) {
            return $response;
        }
    }

    /**
     * Export items permission check
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  JSON
     */
    public function export_item_permissions_check( $request ) {
        return true;
    }

    /**
     * Import items
     *
     * @return  JSON
     */
    public function import_items( $request ) {
        $data = $request->get_file_params();
        $file = ! empty( $data['order_import'] ) ? $data['order_import'] : '';

        if ( ! $file ) {
            return new WP_Error( 'empty_file', __( 'You must provide a valid file.', 'eventin' ), ['status' => 409] );
        }

        $importer = new OrderImporter();
        $importer->import( $file );

        $response = [
            'message' => __( 'Successfully imported order', 'eventin' ),
        ];

        return rest_ensure_response( $response );
    }

    /**
     * Export items permission check
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  JSON
     */
    public function import_item_permissions_check( $request ) {
        return true;
    }

    /**
     * Create customer
     *
     * @param   OrderModel  $order  [$order description]
     * @param   array  $data   [$data description]
     *
     * @return  void
     */
    public function create_customer( $order, $data ) {
        $input = new Input( $data );

        $email = $input->get('customer_email');

        if ( email_exists( $email ) ) {
            $user_data = get_user_by( 'email', $email );

            $customer = new CustomerModel( $user_data->ID );
            $customer->assign_role(['etn-customer']);
        } else {
            $customer = CustomerModel::create([
                'first_name'    => $input->get('customer_fname'),
                'last_name'     => $input->get('customer_lname'),
                'email'         => $email,
            ]);
        }

        $order->update( [
            'customer_id' => $customer->id
        ] );
    }

     /** 
     * Resend ticket email to order customer and attendees
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  WP_Error | WP_Rest_Response
     */
    public function resend_ticket( $request ) {
        $id = intval( $request['id'] );

        $post = get_post( $id );

        if ( ! $post ) {
            return new WP_Error( 'id_error', __( 'Invalid order id', 'eventin' ) );
        }

        if ( 'etn-order' !== $post->post_type ) {
            return new WP_Error( 'id_error', __( 'Invalid order id', 'eventin' ) );
        }

        $order = new OrderModel( $id );
        $event = new Event_Model( $order->event_id );
        $attendees = $order->get_attendees();
        $from      = etn_get_email_settings( 'purchase_email' )['from'];
        
        // Send email to customer.
        Mail::to( $order->customer_email )->from( $from )->send( new AdminOrderEmail( $order ) );

        // Send to attendees email.
        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendee = new Attendee_Model( $attendee['id'] );

                if ( $attendee->etn_email ) {
                    Mail::to( $attendee->etn_email )->from( $from )->send( new AttendeeOrderEmail( $event, $attendee ) );
                }
            }
        }

        $response = [
            'message'   => __( 'Successfully send ticket email to', 'eventin' ),
        ];

        return rest_ensure_response( $response );
    }

    /**
     * Check permissions for resend ticket to attendee
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  bool
     */
    public function resend_ticket_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }
}