<?php
/**
 * Event Importer Class
 *
 * @package Eventin
 */
namespace Etn\Core\Event;

use Etn\Base\Importer\Post_Importer_Interface;
use Etn\Base\Importer\Reader_Factory;

/**
 * Class Event Importer
 */
class Event_Importer implements Post_Importer_Interface {
    /**
     * Store File
     *
     * @var string
     */
    private $file;

    /**
     * Store data
     *
     * @var array
     */
    private $data;

    /**
     * Event import
     *
     * @return  void
     */
    public function import( $file ) {
        $this->file  = $file;
        $file_reader = Reader_Factory::get_reader( $file );

        $this->data = $file_reader->read_file();
        $this->create_event();
    }

    /**
     * Create event
     *
     * @return  void
     */
    private function create_event() {
        $event     = new Event_Model();
        $file_type = ! empty( $this->file['type'] ) ? $this->file['type'] : '';

        $rows = $this->data;

        foreach ( $rows as $row ) {
            $args = [
                'post_status'                       => ! empty( $row['status'] ) ? $row['status'] : 'publish',
                'post_title'                        => ! empty( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '',
                'post_content'  => ! empty( $row['description'] ) ? sanitize_text_field( $row['description'] ) : '',
                'etn_start_date'                    => ! empty( $row['start_date'] ) ? sanitize_text_field( $row['start_date'] ) : '',
                'etn_end_date'  => ! empty( $row['end_date'] ) ? sanitize_text_field( $row['end_date'] ) : '',
                'etn_start_time'                    => ! empty( $row['start_time'] ) ? sanitize_text_field( $row['start_time'] ) : '',
                'etn_end_time'                      => ! empty( $row['end_time'] ) ? sanitize_text_field( $row['end_time'] ) : '',
                'event_timezone'                    => ! empty( $row['timezone'] ) ? sanitize_text_field( $row['timezone'] ) : '',

                'event_type'                    => ! empty( $row['event_type'] ) ? sanitize_text_field( $row['event_type'] ) : '',
                'speaker_type'                    => ! empty( $row['speaker_type'] ) ? sanitize_text_field( $row['speaker_type'] ) : '',
                'organizer_type'                    => ! empty( $row['organizer_type'] ) ? sanitize_text_field( $row['organizer_type'] ) : '',

                'etn_ticket_availability'           => ! empty( $row['ticket_availability'] ) ? sanitize_text_field( $row['ticket_availability'] ) : '',
                'etn_event_logo'                    => ! empty( $row['event_logo'] ) ? sanitize_text_field( $row['event_logo'] ) : '',
                'event_banner'                    => ! empty( $row['event_banner'] ) ? sanitize_text_field( $row['event_banner'] ) : '',
                'event_layout'                    => ! empty( $row['event_layout'] ) ? sanitize_text_field( $row['event_layout'] ) : '',
                'ticket_template'                    => ! empty( $row['ticket_template'] ) ? sanitize_text_field( $row['ticket_template'] ) : '',
                'certificate_template'                    => ! empty( $row['certificate_template'] ) ? sanitize_text_field( $row['certificate_template'] ) : '',


                'etn_event_calendar_bg'             => ! empty( $row['calendar_bg'] ) ? sanitize_text_field( $row['calendar_bg'] ) : '',
                'etn_event_calendar_text_color'     => ! empty( $row['calendar_text_color'] ) ? sanitize_text_field( $row['calendar_text_color'] ) : '',
                'etn_registration_deadline'         => ! empty( $row['registration_deadline'] ) ? sanitize_text_field( $row['registration_deadline'] ) : '',
                'attende_page_link'                 => ! empty( $row['attende_page_link'] ) ? sanitize_text_field( $row['attende_page_link'] ) : '',
                'etn_total_avaiilable_tickets'      => ! empty( $row['total_ticket'] ) ? sanitize_text_field( $row['total_ticket'] ) : '',
                'etn_total_sold_tickets'            => ! empty( $row['sold_tickets'] ) ? sanitize_text_field( $row['sold_tickets'] ) : '',
                'fluent_crm'                        => ! empty( $row['fluent_crm'] ) ? sanitize_text_field( $row['fluent_crm'] ) : '',
                'fluent_crm_webhook'                => ! empty( $row['fluent_crm_webhook'] ) ? sanitize_text_field( $row['fluent_crm_webhook'] ) : '',
            ];

            $location              = ! empty( $row['location'] ) ? sanitize_text_field( $row['location'] ) : '';
            $ticket_variations     = ! empty( $row['ticket_variations'] ) ? $row['ticket_variations'] : '';
            $event_socials         = ! empty( $row['event_socials'] ) ? $row['event_socials'] : '';
            $event_schedule        = ! empty( $row['schedules'] ) ? $row['schedules'] : '';
            $event_faq             = ! empty( $row['faq'] ) ? $row['faq'] : '';
            $attendee_extra_fields = ! empty( $row['extra_fields'] ) ? $row['extra_fields'] : '';

            $speaker                = ! empty( $row['speaker'] ) ? $row['speaker'] : '';
            $speaker_group          = ! empty( $row['speaker_groups'] ) ? $row['speaker_groups'] : '';
            $organizer              = ! empty( $row['organizer'] ) ? $row['organizer'] : '';
            $organizer_group        = ! empty( $row['organizer_group'] ) ? $row['organizer_group'] : '';
            $rsvp                   = ! empty( $row['rsvp'] ) ? $row['rsvp'] : '';
            $categories             = ! empty( $row['categories'] ) ? $row['categories'] : '';
            $tags                   = ! empty( $row['tags'] ) ? $row['tags'] : '';

            
            $args['etn_event_location']    = $location;
            $args['etn_ticket_variations'] = $ticket_variations;
            $args['etn_event_socials']     = $event_socials;
            $args['etn_event_schedule']    = $event_schedule;
            $args['etn_event_faq']         = $event_faq;
            $args['attendee_extra_fields'] = $attendee_extra_fields;
            $args['etn_event_speaker']     = $speaker;
            $args['speaker_group']         = $speaker_group;
            $args['etn_event_organizer']   = $organizer;
            $args['organizer_group']       = $organizer_group;
            $args['rsvp_settings']         = $rsvp;


            if ( 'text/csv' == $file_type ) {
                $args['etn_ticket_variations'] = $this->format_tickets( $ticket_variations );
                $args['etn_event_socials']     = etn_csv_column_multi_dimension_array( $event_socials );
                $args['etn_event_schedule']    = etn_csv_column_array( $event_schedule );
                $args['etn_event_faq']         = etn_csv_column_multi_dimension_array( $event_faq );
                $args['attendee_extra_fields'] = etn_csv_column_multi_dimension_array( $attendee_extra_fields );

                $event_type = ! empty( $row['event_type'] ) ? sanitize_text_field( $row['event_type'] ) : '';

                if ( 'offline' === $event_type ) {
                    list($key, $value) = explode( ':', $location );

                    $new_location = [];

                    if ( 'address' === $key ) {
                        $new_location[$key] = $value;
                    }
                }

                $args['etn_event_location']    = $new_location;
                $args['etn_event_speaker']     = etn_csv_column_array( $speaker );
                $args['speaker_group']         = etn_csv_column_array( $speaker_group );
                $args['etn_event_organizer']   = etn_csv_column_array( $organizer );
                $args['organizer_group']       = etn_csv_column_array( $organizer_group );

                $args['rsvp_settings']         =  $this->formate_rsvp_data( $rsvp );
                $categories = etn_csv_column_array( $categories );
                $tags       = etn_csv_column_array( $tags );
            }

            $event->create( $args );

            $this->assign_categories( $event->id, $categories );
            $this->assign_tags( $event->id, $tags );

            // Woo support meta.
            update_post_meta( $event->id, "_price", 0 );
            update_post_meta( $event->id, "_regular_price", 0 );
            update_post_meta( $event->id, "_sale_price", 0 );
            update_post_meta( $event->id, "_stock", 0 );
        }

    }

    /**
     * Format time
     *
     * @param   string  $time 
     *
     * @return  string
     */
    private function format_time( $time ) {
        // Just for verification if needed in your case, or can be used later
        $dateTime = \DateTime::createFromFormat('h:i A', $time);
        return $dateTime ? $dateTime->format('h:i A') : $time;
    }
    
    /**
     * Formate event tickets
     *
     * @param   string  $tickets
     *
     * @return  array
     */
    private function format_tickets( $tickets ) {
        if ( ! $tickets ) {
            return [];
        }

        $tickets = explode('|', $tickets );
        $finalArray = [];

        foreach ( $tickets as $ticket ) {
            // Use regex to capture key:value pairs, allowing for times like '12:40 AM'
            preg_match_all('/([^:,]+):([^,|]+)/', $ticket, $matches);
            
            $ticketData = [];
            foreach ( $matches[1] as $index => $key ) {
                $value = $matches[2][$index];
                
                // Apply custom handling for time, if needed
                if ( $key === 'start_time' || $key === 'end_time' ) {
                    $value = $this->format_time($value); // Format time if necessary
                }
        
                $ticketData[$key] = $value;
            }
        
            // Add ticket to the final array
            $finalArray[] = $ticketData;
        }

        return $finalArray;
    }

    /**
     * Formate RSVP data format for CSV data import
     *
     * @param   string  $data  [$data description]
     *
     * @return  array 
     */
    private function formate_rsvp_data( $data ) {

        if ( ! $data ) {
            return;
        }

        $pairs = explode(',', $data);
        $assocArray = [];

        foreach ( $pairs as $pair ) {
            // Split each pair by colon ':'
            if ( strpos($pair, ':' ) !== false) {
                list($key, $value) = explode(':', $pair, 2); // Use 2 as limit to avoid splitting value itself if it contains ':'
                // Handle empty values as null
                $assocArray[$key] = $value !== '' ? $value : null;
            }
        }

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
            wp_remove_object_terms( $post_id, $categories, 'etn_category' );
        }

        wp_set_post_terms( $post_id, $new_categories, 'etn_category', true );
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
        $tags = get_the_terms( $post_id, 'etn_tags' );
        $tags = $tags ? array_column( $tags, 'term_id' ) : [];

        if ( $tags ) {
            wp_remove_object_terms( $post_id, $tags, 'etn_tags' );
        }

        wp_set_post_terms( $post_id, $new_tags, 'etn_tags', true );
    }
}
