<?php
/**
 * Report Api Class
 *
 * @package Eventin\Schedule
 */
namespace Eventin\Reports\Api;

use Eventin\Input;
use Eventin\Reports\Report;
use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Report Controller Class
 */
class ReportController extends WP_REST_Controller {
    /**
     * Constructor for ReportController
     *
     * @return void
     */
    public function __construct() {
        $this->namespace = 'eventin/v2';
        $this->rest_base = 'reports';
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
                'callback'            => [$this, 'get_reports'],
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
                    'callback'            => array( $this, 'get_reports_by_event' ),
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
    public function get_reports( $request ) {
        $input = new Input( $request );

        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );

        $reports = Report::get_reports([
            'start_date'    => $start_date,
            'end_date'      => $end_date,
        ]);

        return rest_ensure_response( $reports );
    }

    /**
     * Get reports by event
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  WP_Error | WP_Response
     */
    public function get_reports_by_event( $request ) {
        $input = new Input( $request );

        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );
        $event_id   = $input->get( 'id' );

        $reports = Report::get_reports_by_event([
            'start_date'    => $start_date,
            'end_date'      => $end_date,
            'event_id'      => $event_id,
        ]);

        return rest_ensure_response( $reports );
    }
}
