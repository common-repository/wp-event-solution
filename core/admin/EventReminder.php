<?php
namespace Eventin\Admin;

use Etn\Core\Attendee\Attendee_Model;
use Etn\Core\Event\Event_Model;
use Eventin\Emails\AttendeeEventReminderEmail;
use Eventin\Interfaces\HookableInterface;
use Eventin\Mails\Mail;

class EventReminder implements HookableInterface {

    /**
     * Register service
     *
     * @return  void
     */
    public function register_hooks(): void {
        add_action( 'eventin_event_created', [ $this, 'register_schedule' ] );

        add_action( 'init', [ $this, 'run_event_schedule' ] );
    }

    /**
     * Register cron job for schedule a reminder email
     *
     * @param   integer  $event_id
     *
     * @return  void
     */
    public function register_schedule( $event ) {

        $date = $event->etn_start_date;
        $time = $event->etn_start_time;

        $event_timestamp = strtotime( $date . ' ' . $time );

        $reminder_time = etn_get_option( 'remainder_time' );

        if ( ! $reminder_time ) {
            return;
        }

        foreach ( $reminder_time as $time ) {
            $timestamp = '';
            $duration  = intval( $time['duration-time'] );

            switch ( $time['custom_duration_type'] ) {
            case 'min':
                $timestamp = $duration * 60;
                break;
            case 'hour':
                $timestamp = $duration * 60 * 60;
                break;
            case 'day':
                $timestamp = ( $duration * 24 ) * 60 * 60;
                break;
            }

            $timestamp = $event_timestamp - $timestamp;

            if ( ! wp_next_scheduled( 'event_remainder_' . $event->id ) ) {
                wp_schedule_single_event( $timestamp, 'event_remainder_' . $event->id, [$event->id] );
            }
        }
    }

    /**
     * event schedule
     *
     * @return  void
     */
    public function run_event_schedule() {
        $events = (new Event_Model())->all();

        $events = $events['items'];

        if ( ! $events ) {
            return;
        }

        // Run cron action.
        foreach ( $events as $event ) {
            add_action( 'event_remainder_' . $event->id, [$this, 'send_reminder_email'] );
        }
    }

    /**
     * Send email to attendees
     *
     * @param   integer  $event_id  Event id
     *
     * @return  void
     */
    public function send_reminder_email( $event_id ) {
        $args = array(
            'post_type'         => 'etn-attendee',
            'post_status'       => 'any',
            'posts_per_page'    => -1,
            
            'meta_query' => array(
                array(
                    'key'     => 'etn_event_id',
                    'value'   => $event_id,
                    'compare' => '=',
                ),
            ),
        );

        $attendees = get_posts( $args );
        $event = new Event_Model( $event_id );

        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendee = new Attendee_Model( $attendee->ID );
                Mail::to( $attendee->etn_email )->send( new AttendeeEventReminderEmail( $event, $attendee ) );
            }
        }
    }
}
