<?php

namespace Etn\Core\Schedule;

use WP_HTTP_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class Api_Schedule extends \Etn\Base\Api_Handler {

    /**
     * define prefix and parameter patten
     *
     * @return void
     */
    public function config() {
        $this->prefix = 'schedule';
        $this->param  = ''; // /(?P<id>\w+)/
    }

    /**
     * get user profile when user is logged in
     * @API Link www.domain.com/wp-json/eventin/v1/events/
     * @return array status_code, messages, content
     */
    public function get_schedules() {
        $request  = $this->request;
        $per_page = ! empty( $request['per_page'] ) ? intval( $request['per_page'] ) : 20;
        $paged    = ! empty( $request['paged'] ) ? intval( $request['paged'] ) : 1;
        $user_id  = ! empty( $request['user_id'] ) ? intval( $request['user_id'] ) : 0;
        
        $args     = [
            'post_type'      => 'etn-schedule',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
        ];

        if ( $user_id ) {
            $args['author'] = $user_id;
        }

        $post  = new \WP_Query( $args );
        $total = $post->found_posts;
        $items = $post->posts;

        $data = [];

        foreach ( $items as $item ) {
            $data[] = $this->prepare_item_response( $item );
        }

        $response = [
            'total' => $total,
            'items' => $data,
        ];

        return new WP_HTTP_Response( $response, 200 );
    }

    /**
     * Get single schedule item
     *
     * @return  JSON
     */
    public function get_single_schedule() {
        $request = $this->request;

        $id   = ! empty( $request['id'] ) ? intval( $request['id'] ) : 0;
        $item = $this->prepare_item_response( $id );

        return new WP_HTTP_Response( $item, 200 );
    }

    /**
     * save settings data through api
     *
     * @return array
     */
    public function post_schedules() {
        if ( ! current_user_can( 'publish_posts' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }

        $request = json_decode( $this->request->get_body(), true );
        $user_id = ! empty( $request['user_id'] ) ? intval( $request['user_id'] ) : 1;

        $data = $this->prepare_item_for_database( $request );
        $args = [
            'etn_schedule_title' => $data['etn_schedule_title'],
            'post_type'          => 'etn-schedule',
            'post_status'        => 'publish',
            'post_author'        => $user_id,
        ];

        $post_id = wp_insert_post( $args );

        if ( is_wp_error( $post_id ) ) {
            return new WP_HTTP_Response( $post_id->get_error_message(), 400 );
        }

        $this->update_meta( $post_id, $data );

        $item = $this->prepare_item_response( $post_id );

        return new WP_HTTP_Response( $item, 201 );
    }

    /**
     * Update schedule data
     *
     * @return array
     */
    public function put_schedules() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }

        $request = json_decode( $this->request->get_body(), true );
        $user_id = ! empty( $request['user_id'] ) ? intval( $request['user_id'] ) : 1;

        $data = $this->prepare_item_for_database( $request );

        if ( ! $this->is_schedule( $request['id'] ) ) {
            $response = [
                'success' => 0,
                'message' => __( 'Invalid schedule id.', 'eventin' ),
            ];

            return new WP_HTTP_Response( $response, 400 );
        }

        $args = [
            'ID'                 => $request['id'],
            'etn_schedule_title' => $data['etn_schedule_title'],
            'post_type'          => 'etn-schedule',
            'post_status'        => 'publish',
            'post_author'        => $user_id,
        ];

        $post_id = wp_update_post( $args );

        if ( is_wp_error( $post_id ) ) {
            return new WP_HTTP_Response( $post_id->get_error_message(), 400 );
        }

        $this->update_meta( $post_id, $data );

        $item = $this->prepare_item_response( $post_id );

        return new WP_HTTP_Response( $item, 201 );
    }

    /**
     * Delete schedule
     *
     * @return  bool
     */
    public function delete_schedules() {

        if ( ! current_user_can( 'delete_posts' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }

        $request = $this->request;
        $id      = ! empty( $request['id'] ) ? $request['id'] : 0;

        if ( ! $this->is_schedule( $id ) ) {
            $data = [
                'success' => 0,
                'message' => __( 'Invalid schedule id.', 'eventin' ),
            ];

            return new WP_HTTP_Response( $data, 400 );
        }

        $deleted = wp_delete_post( $id );

        if ( ! $deleted ) {
            $data = [
                'success' => 0,
                'message' => __( 'Something went wrong please try again.', 'eventin' ),
            ];

            return new WP_HTTP_Response( $data, 409 );
        }

        $data = [
            'success' => 1,
            'message' => __( 'Successfully deleted.', 'eventin' ),
        ];

        return new WP_HTTP_Response( $data, 200 );
    }

    /**
     * Delete one or more events route
     *
     * @return  array
     */
    public function delete_bulk_delete() {
        if ( ! current_user_can( 'delete_posts' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }
        
        $request = json_decode( $this->request->get_body(), true );

        $ids     = ! empty( $request['ids'] ) ? $request['ids'] : [];
        $counter = 0;

        foreach ( $ids as $id ) {
            if ( ! $this->is_schedule( $id ) ) {
                continue;
            }

            $deleted = wp_delete_post( $id );

            if ( $deleted ) {
                $counter++;
            }
        }

        if ( $counter < 1 ) {
            $data = [
                'success' => 0,
                'message' => __( 'No items deleted. Please try again', 'eventin' ),
            ];

            return new WP_HTTP_Response( $data, 409 );
        }

        $data = [
            'success' => 1,
            'message' => sprintf( __( 'Succesfully deleted %s items of %s', 'eventin' ), $counter, count( $ids ) ),
        ];

        return new WP_HTTP_Response( $data, 200 );
    }

    /**
     * Prepare items for database
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  array
     */
    private function prepare_item_for_database( $request ) {
        $title    = ! empty( $request['title'] ) ? sanitize_text_field( $request['title'] ) : '';
        $date     = ! empty( $request['date'] ) ? sanitize_text_field( $request['date'] ) : '';
        $day_name = ! empty( $request['nameOfTheDay'] ) ? sanitize_text_field( $request['nameOfTheDay'] ) : '';
        $slots    = ! empty( $request['scheduleSlots'] ) ? $request['scheduleSlots'] : [];

        $schedule_topics = [];

        foreach ( $slots as $slot ) {
            $schedule_topics[] = [
                'etn_schedule_topic'     => $slot['topic'],
                'etn_shedule_start_time' => $slot['startTime'],
                'etn_shedule_end_time'   => $slot['endTime'],
                'etn_shedule_room'       => $slot['location'],
                'etn_shedule_speaker'    => $slot['speakers'],
                'etn_shedule_objective'  => $slot['details'],
            ];
        }

        $data = [
            'etn_schedule_title'  => $title,
            'etn_schedule_date'   => $date,
            'etn_schedule_day'    => $day_name,
            'etn_schedule_topics' => $schedule_topics,
        ];

        return apply_filters( 'etn_schedule_pre_inserted_data', $data );
    }

    /**
     * Prepare item for response
     *
     * @param   Object/int  $item  [$item description]
     *
     * @return  array
     */
    private function prepare_item_response( $item ) {
        if ( is_int( $item ) ) {
            $item = get_post( $item );
        }

        $_topics         = get_post_meta( $item->ID, 'etn_schedule_topics', true );
        $schedule_topics = [];

        if ( $_topics && is_array( $_topics ) ) {
            foreach ( $_topics as $_topic ) {
                $topic      = ! empty( $_topic['etn_schedule_topic'] ) ? $_topic['etn_schedule_topic'] : '';
                $start_time = ! empty( $_topic['etn_shedule_start_time'] ) ? $_topic['etn_shedule_start_time'] : '';
                $end_time   = ! empty( $_topic['etn_shedule_end_time'] ) ? $_topic['etn_shedule_end_time'] : '';
                $location   = ! empty( $_topic['etn_shedule_room'] ) ? $_topic['etn_shedule_room'] : '';
                $speakers   = ! empty( $_topic['etn_shedule_speaker'] ) ? $_topic['etn_shedule_speaker'] : '';
                $details    = ! empty( $_topic['etn_shedule_objective'] ) ? $_topic['etn_shedule_objective'] : '';

                $schedule_topics[] = [
                    'topic'     => $topic,
                    'startTime' => $start_time,
                    'endTime'   => $end_time,
                    'location'  => $location,
                    'speakers'  => $speakers,
                    'details'   => $details,
                ];
            }
        }

        $response = [
            'id'            => $item->ID,
            'title'         => get_post_meta( $item->ID, 'etn_schedule_title', true ),
            'date'          => get_post_meta( $item->ID, 'etn_schedule_date', true ),
            'nameOfTheDay'  => get_post_meta( $item->ID, 'etn_schedule_day', true ),
            'scheduleSlots' => $schedule_topics,
        ];

        return apply_filters( 'etn_schedule_response', $response );
    }

    /**
     * Update post meta
     *
     * @param   integer  $id    [$id description]
     * @param   array  $data  [$data description]
     *
     * @return  void
     */
    private function update_meta( $id, $data = [] ) {
        foreach ( $data as $key => $value ) {
            update_post_meta( $id, $key, $value );
        }
    }

    /**
     * Check the schedule is valid schedule or not
     *
     * @param   Mixed  $post
     *
     * @return  bool
     */
    private function is_schedule( $post ) {
        if ( ! is_object( $post ) ) {
            $post = get_post( $post );
        }

        return $post && 'etn-schedule' === $post->post_type;
    }
}
