<?php
/**
 * Zoom Integration
 *
 * @package Eventin/Zoom
 */
namespace Eventin\Integrations\Zoom;

/**
 * Zoom Client class
 */
class ZoomClient {
    /**
     * Store api url
     *
     * @var string
     */
    private $api = 'https://api.zoom.us/v2/users/me/meetings';

    /**
     * Store access token
     *
     * @var string
     */
    private $token;

    /**
     * Initialization
     *
     * @param   string  $token
     *
     * @return  void
     */
    public function __construct( $token = '' ) {
        if ( ! $token ) {
            throw new \Exception( __( 'You must provide access token', 'eventin' ) );
        }

        $this->token = $token;
    }

    /**
     * Create zoom meeting
     *
     * @param   array  $args
     *
     * @return  mixed
     */
    public function create_meeting( $args = [] ) {
        $args = [
            'topic'                  => $args['title'],
            'type'                   => 2, // Scheduled meeting
            'start_time'            => $args['start_time'], // Meeting start time in UTC
            'duration' => 60, // Meeting duration in minutes
            'timezone' => 'America/New_York', // Timezone for the meeting
            'agenda' => '',
            'waiting_room'           => true,
            'join_before_host'       => false,
            'meeting_authentication' => false,
        ];
        
        $request_data = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ],
            'body'    => json_encode( $args ),
        ];

        $response = wp_remote_post( $this->api, $request_data );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }

        if ( 201 != wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    /**
     * Update zoom meeting
     *
     * @param   integer  $meeting_id
     * @param   array  $args
     *
     * @return  mixed
     */
    public function update_meeting( $meeting_id, $args = [] ) {
        $defaults = [
            'topic' => '',
            'type'  => 2, // Scheduled meeting
            'start_time' => '', // Meeting start time in UTC
            'duration' => 60, // Meeting duration in minutes
            'timezone' => 'America/New_York', // Timezone for the meeting
            'agenda' => '',
        ];

        $args         = wp_parse_args( $args, $defaults );
        $request_data = [
            'method'  => 'PATCH',
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ],
            'body'    => json_encode( $args ),
        ];

        $url = 'https://api.zoom.us/v2/meetings/' . $meeting_id;

        $response = wp_remote_request( $url, $request_data );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        if ( 201 != wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    /**
     * Delete zoom meeting
     *
     * @param integer  $meeting_id
     * @return  mixed
     */
    public function delete_meeting( $meeting_id ) {
        $url = 'https://api.zoom.us/v2/meetings/' . $meeting_id;

        $request_data = [
            'method'  => 'DELETE',
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ];

        $response = wp_remote_request( $url, $request_data );

        if ( 204 === wp_remote_retrieve_response_code( $response ) ) {
            return true;
        }

        return false;
    }
}
