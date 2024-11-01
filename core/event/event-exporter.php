<?php
/**
 * Event Exporter Class
 *
 * @package Eventin
 */
namespace Etn\Core\Event;

use Etn\Base\Exporter\Exporter_Factory;
use Etn\Base\Exporter\Post_Exporter_Interface;

/**
 * Class Event Exporter
 *
 * Export Event Data
 */
class Event_Exporter implements Post_Exporter_Interface {
    /**
     * Store file name
     *
     * @var string
     */
    private $file_name = 'event-data';

    /**
     * Store event data
     *
     * @var array
     */
    private $data;

    /**
     * Export event data
     *
     * @return void
     */
    public function export( $data, $format ) {
        $this->data = $data;

        $rows      = $this->prepare_data();
        $columns   = $this->get_columns();
        $file_name = $this->file_name;

        $exporter = Exporter_Factory::get_exporter( $format );

        $exporter->export( $rows, $columns, $file_name );
    }

    /**
     * Prepare data to export
     *
     * @return  array
     */
    private function prepare_data() {
        $ids           = $this->data;
        $exported_data = [];

        foreach ( $ids as $id ) {
            $categories = get_the_terms( $id, 'etn_category' );
            $categories = $categories ? array_column( $categories, 'term_id' ) : [];
            $tags       = get_the_terms( $id, 'etn_tags' );
            $tags       = $tags ? array_column( $tags, 'term_id' ) : [];
            $post       = get_post( $id );

            $schedule_data = [
                'id'                      => $id,
                'title'                   => $post->post_title,
                'status'                  => $post->post_status,
                'description'             => $post->post_content,
                'start_date'              => get_post_meta( $id, 'etn_start_date', true ),
                'end_date'                => get_post_meta( $id, 'etn_end_date', true ),
                'start_time'              => get_post_meta( $id, 'etn_start_time', true ),
                'end_time'                => get_post_meta( $id, 'etn_end_time', true ),
                'timezone'                => get_post_meta( $id, 'event_timezone', true ),
                'event_type'              => get_post_meta( $id, 'event_type', true ),
                'location'                => get_post_meta( $id, 'etn_event_location', true ),
                'meeting_link'            => get_post_meta( $id, 'meeting_link', true ),
                'categories'              => $categories,
                'tags'                    => $tags,
                'speaker'                 => get_post_meta( $id, 'etn_event_speaker', true ),
                'speaker_type'            => get_post_meta( $id, 'speaker_type', true ),
                'speaker_groups'          => get_post_meta( $id, 'speaker_group', true ),
                'organizer'               => get_post_meta( $id, 'etn_event_organizer', true ),
                'organizer_type'          => get_post_meta( $id, 'organizer_type', true ),
                'organizer_groups'        => get_post_meta( $id, 'organizer_group', true ),
                'schedules'               => get_post_meta( $id, 'etn_event_schedule', true ),
                'ticket_availability'     => get_post_meta( $id, 'etn_ticket_availability', true ),
                'event_logo'              => get_post_meta( $id, 'etn_event_logo', true ),
                'event_banner'            => get_post_meta( $id, 'event_banner', true ),
                'event_layout'            => get_post_meta( $id, 'event_layout', true ),
                'ticket_template'         => get_post_meta( $id, 'ticket_template', true ),
                'certificate_template'    => get_post_meta( $id, 'certificate_template', true ),
                'calendar_bg'             => get_post_meta( $id, 'etn_event_calendar_bg', true ),
                'calendar_text_color'     => get_post_meta( $id, 'etn_event_calendar_text_color', true ),
                'registration_deadline'   => get_post_meta( $id, 'etn_registration_deadline', true ),
                'attende_page_link'       => get_post_meta( $id, 'attende_page_link', true ),
                'total_ticket'            => get_post_meta( $id, 'etn_total_avaiilable_tickets', true ),
                'sold_tickets'            => get_post_meta( $id, 'etn_total_sold_tickets', true ),
                'ticket_variations'       => get_post_meta( $id, 'etn_ticket_variations', true ),
                'event_socials'           => get_post_meta( $id, 'etn_event_socials', true ),
                'fluent_crm'              => get_post_meta( $id, 'fluent_crm', true ),
                'fluent_crm_webhook'      => get_post_meta( $id, 'fluent_crm_webhook', true ),
                'faq'                     => get_post_meta( $id, 'etn_event_faq', true ),
                'extra_fields'            => get_post_meta( $id, 'attendee_extra_fields', true ),
                'rsvp'                    => get_post_meta( $id, 'rsvp_settings', true ),
            ];

            $location_type = get_post_meta( $id, 'etn_event_location_type', true );
            $location      = get_post_meta( $id, 'etn_event_location', true );

            if ( 'new_location' == $location_type ) {
                $location = get_post_meta( $id, 'etn_event_location_list', true );
            }

            $schedule_data['location_type'] = $location_type;
            $schedule_data['location']      = $location;

            array_push( $exported_data, $schedule_data );
        }

        return $exported_data;
    }

    /**
     * Get columns
     *
     * @return  array
     */
    private function get_columns() {
        return [
            'id'                      => __( 'ID', 'eventin' ),
            'title'                   => __( 'Title', 'eventin' ),
            'status'                  => __( 'Status', 'eventin' ),
            'description'             => __( 'Description', 'eventin' ),
            'start_date'              => __( 'Start Date', 'eventin' ),
            'end_date'                => __( 'End Date', 'eventin' ),
            'start_time'              => __( 'Start Time', 'eventin' ),
            'end_time'                => __( 'End Time', 'eventin' ),
            'timezone'                => __( 'Timezone', 'eventin' ),
            'event_type'              => __( 'Event Type', 'eventin' ),
            'location'                => __( 'Location', 'eventin' ),
            'meeting_link'            => __( 'Meeting Link', 'eventin' ),
            'categories'              => __( 'Categories', 'eventin' ),
            'tags'                    => __( 'Tags', 'eventin' ),   
            'speaker_type'            => __( 'Speaker Type', 'eventin' ),
            'speaker'                 => __( 'Speaker', 'eventin' ),
            'speaker_groups'          => __( 'Speaker Groups', 'eventin' ),
            'organizer_type'          => __( 'Organizer Type', 'eventin' ),
            'organizer'               => __( 'Organizer', 'eventin' ),
            'organizer_groups'        => __( 'Organizer Groups', 'eventin' ),
            'schedules'               => __( 'Schedules', 'eventin' ),
            'ticket_variations'       => __( 'Ticket Variations', 'eventin' ),
            'ticket_availability'     => __( 'Ticket Availability', 'eventin' ),
            'event_logo'              => __( 'Logo', 'eventin' ),
            'event_banner'            => __( 'Banner', 'eventin' ),
            'event_layout'            => __( 'Layout', 'eventin' ),
            'ticket_template'         => __( 'Ticket Template', 'eventin' ),
            'certificate_template'    => __( 'Certificate Template', 'eventin' ),
            'calendar_bg'             => __( 'Calendar Background', 'eventin' ),
            'calendar_text_color'     => __( 'Calendar Text Color', 'eventin' ),
            'event_socials'           => __( 'Event Socials', 'eventin' ),
            'faq'                     => __( 'FAQ', 'eventin' ),
            'extra_fields'            => __( 'Extra Fields', 'eventin' ),
            'rsvp'                    => __( 'RSVP', 'eventin' ),
            'attende_page_link'       => __( 'Attendee Page Link', 'eventin' ),
            'total_ticket'            => __( 'Total Ticket', 'eventin' ),
            'sold_tickets'            => __( 'Sold Ticket', 'eventin' ),
            'fluent_crm'              => __( 'Fluent CRM', 'eventin' ),
            'fluent_crm_webhook'      => __( 'Fluent CRM Webhook', 'eventin' ),
        ];
    }
}
