<?php
/**
 * Event Model Class
 *
 * @package Eventin
 */
namespace Etn\Core\Event;

use Etn\Base\Post_Model;
use Eventin\Input;

/**
 * Event Model
 */
class Event_Model extends Post_Model {
    /**
     * Store post type
     *
     * @var string
     */
    protected $post_type = 'etn';

    /**
     * Store event data
     *
     * @var array
     */
    protected $data = [
        'etn_select_speaker_schedule_type'  => '',
        'etn_event_organizer'               => '',
        'etn_event_speaker'                 => '',
        'event_timezone'                    => '',
        'etn_start_date'                    => '',
        'etn_end_date'                      => '',
        'etn_start_time'                    => '',
        'etn_end_time'                      => '',
        'etn_ticket_availability'           => '',
        'etn_total_sold_tickets'            => '',
        'etn_ticket_variations'             => '',
        'etn_registration_deadline'         => '',
        'etn_zoom_id'                       => '',
        'etn_zoom_event'                    => '',
        'etn_total_avaiilable_tickets'      => '',
        'etn_google_meet'                   => '',
        'etn_google_meet_short_description' => '',
        'fluent_crm'                        => '',
        'etn_event_location_type'           => '',
        'etn_event_location'                => '',
        'etn_event_socials'                 => [],
        'etn_event_schedule'                => [],
        'etn_event_faq'                     => [],
        'recurring_enabled'                 => '',
        'etn_event_recurrence'              => [],
        'etn_google_meet'                   => '',
        'rsvp_settings'                     => '',
        'seat_plan'                         => '',
        'seat_plan_settings'                => '',
        'certificate_template'              => '',
        'ticket_template'                   => '',
        'external_link'                     => '',
        'speaker_type'                      => '',
        'organizer_type'                    => '',
        'speaker_group'                     => '',
        'organizer_group'                   => '',
        'etn_event_logo'                    => '',
        'etn_event_calendar_bg'             => '',
        'etn_event_calendar_text_color'     => '',
        'fluent_crm_webhook'                => '',
        'attende_page_link'                 => '',
        'event_banner'                      => '',
        'event_layout'                      => '',
        'attendee_extra_fields'             => '',
        'event_type'                        => '',
        'location'                          => '',
        'meeting_link'                      => '',
        'is_clone'                          => false,
        'certificate_preference'            => '',
        '_virtual'                           => '',
        'event_logo_id'                      => '',
        'event_banner_id'                   => '',
        'excerpt_enable'                    => false,         
    ];

    /**
     * Get total tickets
     *
     * @return  integer
     */
    public function get_total_ticket() {
        $ticket_variations = $this->etn_ticket_variations;
        $total_ticket      = 0;

        if ( is_array( $ticket_variations ) ) {
            foreach ( $ticket_variations as $ticket ) {
                if ( ! empty( $ticket['etn_avaiilable_tickets'] ) ) {
                    $total_ticket += $ticket['etn_avaiilable_tickets'];
                }
            }
        }

        return $total_ticket;
    }

    /**
     * Get event status
     *
     * @return  string
     */
    public function get_status() {
        $end_date   = $this->etn_end_date;
        $end_time   = $this->etn_end_time;
        $status     = get_post_status( $this->id );
        $timezone   = $this->event_timezone ? etn_create_date_timezone( $this->event_timezone ) : 'Asia/Dhaka';

        $end_date_time = $end_date . ' ' . $end_time;

        // Create a DateTime object for the start date and time in the given timezone
        $end_date = new \DateTime( $end_date_time, new \DateTimeZone( $timezone ) );
    
        // Create a DateTime object for the current date and time in the given timezone
        $current_date = new \DateTime('now', new \DateTimeZone( $timezone ) );

        if ( 'publish' === $status ) {
            $status = $current_date > $end_date ? __( 'Expired', 'eventin' ) : __( 'Upcoming', 'eventin' );
        }

        return $status;
    }

    /**
     * Get tickets
     *
     * @param   string  $slug
     *
     * @return  array
     */
    public function get_ticket( $slug = '' ) {
        $ticket_variations = $this->etn_ticket_variations;

        if ( ! $ticket_variations ) {
            return;
        }

        if ( ! $slug ) {
            return $ticket_variations;
        }
        
        foreach( $ticket_variations as $variation ) {
            if ( $slug === $variation['etn_ticket_slug'] ) {
                return $variation;
            }
        }
    }

    /**
     * Get event title
     *
     * @return  string
     */
    public function get_title() {
        $post = get_post( $this->id );

        return $post->post_title;
    }

    /**
     * Get event location address
     *
     * @return  string
     */
    public function get_address() {
        $address = '';

        if ( $this->event_type === 'offline' ) {
            $location = $this->etn_event_location;

            $address = ! empty( $location['address'] ) ? $location['address'] : '';
        }

        return $address;
    }

    /**
     * Get event start date time
     *
     * @param   string  $format  [$format description]
     *
     * @return  string Event start date time
     */
    public function get_start_datetime( $format = 'Y-m-d h:i a' ) {
        $datetime = $this->get_datetime( $this->etn_start_date, $this->etn_start_time );

        return $datetime->format( $format );
    }

    /**
     * Get end date time
     *
     * @param   string  $format  [$format description]
     *
     * @return  string           [return description]
     */
    public function get_end_datetime($format = 'Y-m-d h:i a') {
        $datetime = $this->get_datetime( $this->etn_end_date, $this->etn_end_time );
        
        return $datetime->format( $format );
    }

    /**
     * Get event timezone
     *
     * @return  string
     */
    public function get_timezone() {
        $timezone   = $this->event_timezone ? etn_create_date_timezone( $this->event_timezone ) : 'Asia/Dhaka';

        return $timezone;
    }

    /**
     * Get date time object
     *
     * @param   string  $date
     * @param   string  $time  [$time description]
     *
     * @return  Datetime
     */
    private function get_datetime($date, $time) {
        $date_time_string = $date . ' ' . $time;

        $datetime = new \DateTime( $date_time_string, new \DateTimeZone( $this->get_timezone() ) );

        return $datetime;
    }

    /**
     * Check an event is expired or not
     *
     * @return  bool
     */
    public function is_expaired() {
        return time() > strtotime( $this->get_end_datetime() );
    }

    /**
     * Get total sold tickets
     *
     * @return  integer  Total number of sold tickets
     */
    public function get_total_sold_ticket() {
        $ticket_variations = $this->etn_ticket_variations;
        $total_ticket      = 0;

        if ( is_array( $ticket_variations ) ) {
            foreach ( $ticket_variations as $ticket ) {
                if ( ! empty( $ticket['etn_sold_tickets'] ) ) {
                    $total_ticket += $ticket['etn_sold_tickets'];
                }
            }
        }

        return $total_ticket;
    }

    /**
     * Get ticket price by ticket name
     *
     * @param   string  $ticket_name  [$ticket_name description]
     *
     * @return  int | float
     */
    public function get_ticket_price_by_name( $ticket_name ) {
        $tickets = $this->etn_ticket_variations;

        if ( is_array( $tickets ) ) {
            foreach( $tickets as $ticket ) {
                $input = new Input( $ticket );

                if ( $input->get( 'etn_ticket_name' ) === $ticket_name ) {
                    return $input->get( 'etn_ticket_price' );
                }
            }
        }

        return 0;
    }
}
