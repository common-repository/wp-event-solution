<?php

namespace Etn\Core\Attendee;

use \Etn\Utils\Helper;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class Api extends \Etn\Base\Api_Handler{

    /**
     * define prefix and parameter patten
     *
     * @return void
     */
    public function config() {
        $this->prefix = 'attendees';
        $this->param  = ''; // /(?P<id>\w+)/
    }

    /**
     * get attendee list
     * @API Link www.domain.com/wp-json/eventin/v1/attendees/
     * @return array status_code, messages, content
     */
    public function get_attendees() {

        $status_code     = 0;
        $messages        = $content        = [];
        $translated_text = ['see_details_text' => esc_html__( 'See Details', 'eventin' )];
        $request         = $this->request;

        if ( !empty( $request['event'] ) && is_numeric( $request['event'] ) ) {
            // request for all attendees of a specific event
            $paged              = !empty( $request['paged'] ) && is_numeric( $request['paged'] ) ? $request['paged'] : 1;
            $posts_per_page     = !empty( $request['posts_per_page'] ) && is_numeric( $request['posts_per_page'] ) ? $request['posts_per_page'] : -1;
            $event_attendees    = Helper::get_attendees_by_event( intval( $request['event'] ), $posts_per_page, $paged );
            $attendee_count     = Helper::get_attendee_count( intval( $request['event'] ) );
            if ( is_wp_error( $event_attendees ) || !is_array( $event_attendees ) || empty( $event_attendees ) ) {
                return [
                    'status_code' => 403,
                    'messages'    => [
                    'error' => esc_html__( 'No attendee found with this Event.', 'eventin' ),
                    ],
                    'content'     => $content,
                ];
            }

            $attendees = [];
            foreach ( $event_attendees as $key => $event_attendee ) {
                $id            = $event_attendee->post_id;
                $attendee      = (array) Helper::get_attendee( $id );
                $attendee_meta = get_post_meta( $id );

                // prepare attendee meta
                foreach ( $attendee_meta as $key => $val ) {
                    if ( is_array( $val ) ) {
                        $attendee_meta[$key] = $val[0];
                    }

                }

                $attendees[] = $attendee + $attendee_meta;
            }

            return [
                'status_code'     => 200,
                'messages'        => [
                    'success'     => esc_html__( 'Successfully retrieved attendee data.', 'eventin' ),
                ],
                'content'         => $attendees,
                'count'           => $attendee_count
            ];
        } else if ( !empty( $request['id'] ) && is_numeric( $request['id'] ) ) {

            // request for a single event
            $attendee_id = $request['id'];
            $attendee    = (array) Helper::get_attendee( $attendee_id );

            // obj
            if ( empty( $attendee ) ) {
                return [
                    'status_code' => 403,
                    'messages'    => [
                        'error' => esc_html__( 'No attendee found with this ID.', 'eventin' ),
                    ],
                    'content'     => $attendee,
                ];
            }

            $attendee_meta = get_post_meta( $attendee_id );

            // prepare attendee meta
            foreach ( $attendee_meta as $key => $val ) {
                if ( is_array( $val ) ) {
                    $attendee_meta[$key] = $val[0];
                }

            }

            $content = $attendee + $attendee_meta;
            return [
                'status_code' => 200,
                'messages'    => [
                    'success' => esc_html__( 'Attendee data retrieved successfully', 'eventin' ),
                ],
                'content'     => $content,
            ];

        } else {
            // request for all attendees
            $paged          = !empty( $request['paged'] ) && is_numeric( $request['paged'] ) ? $request['paged'] : 1;
            $posts_per_page = !empty( $request['posts_per_page'] ) && is_numeric( $request['posts_per_page'] ) ? $request['posts_per_page'] : -1;
            $attendees      = (array) Helper::get_attendee( null, $posts_per_page, $paged );
            $attendee_count = Helper::get_attendee_count();
            
            if ( is_wp_error( $attendees ) || !is_array( $attendees ) || empty( $attendees ) ) {
                return [
                    'status_code' => 403,
                    'messages'    => [
                        'error' => esc_html__( 'No attendee found.', 'eventin' ),
                    ],
                    'content'     => [],
                ];
            }

            $attendees_arr = [];

            foreach ( $attendees as $key => $attendee ) {
                $id            = $attendee->ID;
                $attendee_meta = get_post_meta( $id );

                // prepare attendee meta
                foreach ( $attendee_meta as $key => $val ) {
                    if ( is_array( $val ) ) {
                        $attendee_meta[$key] = $val[0];
                    }

                }

                $attendees_arr[] = (array) $attendee + $attendee_meta;
            }

            $status_code         = 200;
            $messages['success'] = esc_html__( 'Attendee data retrieved successfully', 'eventin' );
            $content             = $attendees_arr;

            return [
                'status_code' => $status_code,
                'messages'    => $messages,
                'content'     => $content,
                'count'       => $attendee_count
            ];
        }

    }

    /**
     * Delete Single Attendee
     *
     * @since 3.3.5
     * @return void
     */
    public function delete_attendees(){
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }

        $status_code     = 0;
        $messages        = $content        = [];
        $request         = $this->request;

        if ( empty( $request['ids'] ) ) {
            return [
                'status_code' => 403,
                'messages'    => [
                    'error' => esc_html__( 'Attendee Id(s) required.', 'eventin' ),
                ],
                'content'     => $content,
            ];
        }

        $ids = explode(',', $request['ids']);
        foreach( $ids as $id ){

            $deleted = wp_delete_post( $id, true );
            
            if( is_wp_error( $deleted ) ){
                return [
                    'status_code' => 403,
                    'messages'    => [
                        'error' => esc_html__( 'One ore more attendee could not be deleted.', 'eventin' ),
                    ],
                    'content'     => $content,
                ];
            }
        }

        return [
            'status_code'     => 200,
            'messages'        => [
                'success'     => esc_html__( 'Successfully deleted attendee.', 'eventin' ),
            ]
        ];
    }
    
    /**
     * Update Attendee Meta Data
     *
     * @since 3.3.5
     * @return void
     */
    public function put_attendee(){
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }

        $status_code     = 0;
        $messages        = $content        = [];
        $request         = $this->request;
        $status          = [
            'used',
            'unused'
        ];
        if ( empty( $request->get_param('id') ) || !is_numeric( $request->get_param('id') ) || empty( $request->get_param('etn_info_edit_token') ) || empty( $request->get_param('name') ) || empty( $request->get_param('ticket') )|| empty( $request->get_param('payment') )) {
            return [
                'status_code' => 403,
                'messages'    => [
                    'error' => esc_html__( 'Required parameters are missing.', 'eventin' ),
                ],
                'content'     => $content,
            ];
        }

        $attendee_id                              = intval($request->get_param('id'));
        $edit_token                               = $request->get_param('etn_info_edit_token');
        $meta_data                                = [];
        $meta_data['etn_name']                    = $request->get_param('name');
        $meta_data['etn_attendeee_ticket_status'] = in_array( $request->get_param('ticket'), $status ) ? $request->get_param('ticket') : 'unused';
        $meta_data['etn_status']                  = $request->get_param('payment');

        if( !empty( $request->get_param('email') ) && is_email( $request->get_param('email') ) ){
            $meta_data['etn_email'] = $request->get_param('email');
        }

        if( !empty( $request->get_param('phone') ) ){
            $meta_data['etn_phone'] = $request->get_param('phone');
        }

        if( get_post_meta( $attendee_id, 'etn_info_edit_token', true) !== $edit_token ){
            return [
                'status_code' => 403,
                'messages'    => [
                    'error'   => esc_html__( 'Invalid edit token id.', 'eventin' ),
                ],
                'content'     => $content,
            ];
        }

        foreach( $meta_data as $meta_key => $meta_value ){
            $updated = update_post_meta( $attendee_id, $meta_key, $meta_value );

            if( is_wp_error( $updated ) ){
                return [
                    'status_code' => 403,
                    'messages'    => [
                        'error'   => esc_html__( 'One ore more attendee data not be updated.', 'eventin' ),
                    ],
                    'content'     => $content,
                ];
            }
        }



        return [
            'status_code'     => 200,
            'messages'        => [
                'success'     => esc_html__( 'Successfully updated attendee data.', 'eventin' ),
            ]
        ];
    }

    /**
     * Update Attendee Ticket Status
     *
     * @since 3.3.5
     * @return void
     */
    public function put_ticket(){
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }
        
        $status_code     = 0;
        $messages        = $content        = [];
        $request         = $this->request;
        $status          = [
            'used',
            'unused'
        ];

        if ( empty( $request->get_param('attendee') ) || !is_numeric( $request->get_param('attendee') ) || empty( $request->get_param('ticket') ) || empty( $request->get_param('status') )) {
            return [
                'status_code' => 403,
                'messages'    => [
                    'error'   => esc_html__( 'Required parameters are missing.', 'eventin' ),
                ],
                'content'     => $content,
            ];
        }
        $attendee_id    = intval($request->get_param('attendee'));
        $ticket_id      = $request->get_param('ticket');
        $ticket_status  = in_array( $request->get_param('status'), $status ) ? $request->get_param('status') : 'unused';
        
        if( get_post_meta( $attendee_id, 'etn_unique_ticket_id', true) !== $ticket_id ){
            return [
                'status_code' => 403,
                'messages'    => [
                    'error'   => esc_html__( 'Invalid ticket id.', 'eventin' ),
                ],
                'content'     => $content,
            ];
        }

        $updated = update_post_meta( $attendee_id, 'etn_attendeee_ticket_status', $ticket_status);

        if ( is_wp_error( $updated ) ) {
            return [
                'status_code' => 403,
                'messages'    => [
                    'error'   => esc_html__( 'Could not update attendee ticket status.', 'eventin' ),
                ],
                'content'     => $content,
            ];
        } else {
            return [
                'status_code'     => 200,
                'messages'        => [
                    'success'     => esc_html__( 'Successfully updated attendee ticket status.', 'eventin' ),
                ]
            ];
        }

    }

}

new Api();