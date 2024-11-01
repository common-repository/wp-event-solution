<?php
namespace Eventin\Reports;
use Eventin\Input;

/**
 * Speaker Report class
 * 
 * @package Eventin
 */
class AttendeeReport extends AbstractReport {
    /**
     * Get total attendees
     *
     * @param   array  $dates  Date range
     *
     * @return  number Total number of attendee
     */
    public static function get_total_attendee( $dates = [] ) {
        $attendees = self::get_attendees( $dates );

        if ( is_array( $attendees ) ) {
            return count( $attendees );
        }

        return 0;
    }

    /**
     * Get attendee reports by event
     *
     * @param   array  $data  [$data description]
     *
     * @return  array        [return description]
     */
    public static function get_reports_by_event( $data ) {
        $reports = [
            'total'     => self::get_total_attendee_by_event( $data ),
            'success'   => self::get_total_attendee_by_event_success( $data ),
            'failed'    => self::get_total_attendee_by_event_failed( $data ),
        ];

        return $reports;
    }

    /**
     * Get attendees
     *
     * @param   array  $data  Attendee data
     *
     * @return  array
     */
    private static function get_attendees( $data = [] ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );

        return self::get_posts( [
            'post_type'  => 'etn-attendee',
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ] );
    }

    /**
     * Get total attendee by events
     *
     * @param   array  $data  [$data description]
     *
     * @return  integer         Total attendee
     */
    private static function get_total_attendee_by_event( $data ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );

        $attendees= self::get_posts( [
            'post_type'  => 'etn-attendee',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'meta_query' => [
                [
                    'key'     => 'etn_event_id',
                    'value'   => $data['event_id'],
                    'compare' => '=',
                ]
            ]
        ] );

        if ( is_array( $attendees ) ) {
            return count( $attendees );
        }
    }

    /**
     * Get total attendee by events status success
     *
     * @param   array  $data  [$data description]
     *
     * @return  integer         Total attendee
     */
    private static function get_total_attendee_by_event_success( $data ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );

        $attendees= self::get_posts( [
            'post_type'  => 'etn-attendee',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'meta_query' => [
                [
                    'key'     => 'etn_event_id',
                    'value'   => $data['event_id'],
                    'compare' => '=',
                ],
                [
                    'key'     => 'etn_status',
                    'value'   => 'success',
                    'compare' => '=',
                ],
            ]
        ] );

        if ( is_array( $attendees ) ) {
            return count( $attendees );
        }
    }

    /**
     * Get total attendee by events status failed
     *
     * @param   array  $data  [$data description]
     *
     * @return  integer         Total attendee
     */
    private static function get_total_attendee_by_event_failed( $data ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );

        $attendees= self::get_posts( [
            'post_type'  => 'etn-attendee',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'meta_query' => [
                [
                    'key'     => 'etn_event_id',
                    'value'   => $data['event_id'],
                    'compare' => '=',
                ],
                [
                    'key'     => 'etn_status',
                    'value'   => 'failed',
                    'compare' => '=',
                ]
            ]
        ] );

        if ( is_array( $attendees ) ) {
            return count( $attendees );
        }
    }
}

