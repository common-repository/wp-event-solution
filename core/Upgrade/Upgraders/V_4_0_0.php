<?php
/**
 * Updater for version 4.0.0
 *
 * @package Eventin\Upgrade
 */
namespace Eventin\Upgrade\Upgraders;

use Etn\Core\Event\Event_Model;

/**
 * Updater class for v4.0.0
 *
 * @since 4.0.0
 */
class V_4_0_0 implements UpdateInterface {
    /**
     * Run the updater
     *
     * @return  void
     */
    public function run() {
        $this->migrate_event_ticket_variations();
        $this->migrate_event();
        $this->migrate_event_rsvp();
    }

    /**
     * Migrate event ticket variations
     *
     * @return  array
     */
    protected function migrate_event_ticket_variations() {
        $args = [
            'post_type'      => 'etn',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];
        $events = [];

        $post_query   = new \WP_Query();
        $query_result = $post_query->query( $args );

        foreach ( $query_result as $post ) {
            $event = new Event_Model( $post->ID );
            $event->update( [
                'etn_ticket_variations' => $this->prepare_event_ticket_data( $event ),
            ] );
        }
    }

    /**
     * Prepare event ticket data for database
     *
     * @param  Event_Model   $event  Event Object
     *
     * @return  array
     */
    protected function prepare_event_ticket_data( $event ) {
        $ticket_variations       = $event->etn_ticket_variations;
        $event_start_date        = $event->etn_start_date;
        $prepared_data           = [];
        $registration_deadline   = get_post_meta( $event->id, 'etn_registration_deadline', true );
        $event_create_date       = get_the_date( 'Y-m-d', $event->id );
        $ticket_sale_end_date    = $registration_deadline ?: $event->etn_end_date;

        foreach ( $ticket_variations as $variation ) {
            $updated_variation = [
                'etn_ticket_name'        => $variation['etn_ticket_name'],
                'etn_ticket_price'       => $variation['etn_ticket_price'],
                'total_ticket'           => $variation['etn_avaiilable_tickets'],
                'etn_avaiilable_tickets' => $variation['etn_avaiilable_tickets'],
                'etn_sold_tickets'       => $variation['etn_sold_tickets'],
                'start_date'             => $event_create_date,
                'end_date'               => $this->etn_convert_to_date( $ticket_sale_end_date ),
                'start_time'             => $event->etn_start_time,
                'end_time'               => $event->etn_end_time,
                'etn_min_ticket'         => $variation['etn_min_ticket'],
                'etn_max_ticket'         => $variation['etn_max_ticket'],
                'etn_ticket_slug'        => $variation['etn_ticket_slug'],
            ];

            $prepared_data[] = $updated_variation;
        }

        return $prepared_data;
    }

    /**
     * Migrate rsvp data
     *
     * @return  void
     */
    protected function migrate_event_rsvp() {
        $args = [
            'post_type'      => 'etn',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];
        $events = [];

        $post_query   = new \WP_Query();
        $query_result = $post_query->query( $args );

        foreach ( $query_result as $post ) {
            $event = new Event_Model( $post->ID );
            $event->update( [
                'rsvp_settings' => $this->prepare_event_rsvp( $post->ID ),
            ] );
        }
    }

    /**
     * Prepare rsvp data
     *
     * @param   integer  $event_id
     *
     * @return  array
     */
    protected function prepare_event_rsvp( $event_id ) {
        $rsvp = [
            'enable_rsvp_form'               => get_post_meta( $event_id, 'etn_enable_rsvp_form', true ),
            'disable_purchase_form'          => get_post_meta( $event_id, 'etn_disable_purchase_form', true ),
            'rsvp_limit'                     => get_post_meta( $event_id, 'etn_rsvp_limit', true ),
            'rsvp_limit_amount'              => get_post_meta( $event_id, 'etn_rsvp_limit_amount', true ),
            'rsvp_attendee_form_limit'       => get_post_meta( $event_id, 'etn_rsvp_attendee_form_limit', true ),
            'rsvp_miminum_attendee_to_start' => get_post_meta( $event_id, 'etn_rsvp_miminum_attendee_to_start', true ),
            'show_rsvp_attendee'             => get_post_meta( $event_id, 'etn_show_rsvp_attendee', true ),
            'rsvp_form_type'             => unserialize( get_post_meta( $event_id, 'etn_rsvp_form_type', true ) ),
        ];

        return $rsvp;
    }

    /**
     * Migrate speaker and organizer
     *
     * @return  void
     */
    protected function migrate_event() {
        $args = [
            'post_type'      => 'etn',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ];
        $events = [];

        $post_query   = new \WP_Query();
        $query_result = $post_query->query( $args );

        foreach ( $query_result as $post ) {
            $event = new Event_Model( $post->ID );

            $this->migrate_zoom_meeting( $event );
            $this->migrate_google_meet( $event );
            $this->migrate_event_location( $event );
            $this->migrate_banner_image( $event );
            $this->migrate_extra_field( $event );
            $this->migrate_event_speaker_organizer( $event );
        }
    }

    /**
     * Migrate event speaker and organizer
     *
     * @param   Event_Model  $event  [$event description]
     *
     * @return  void
     */
    protected function migrate_event_speaker_organizer( $event ) {
        $organizer = get_post_meta( $event->id, 'etn_event_organizer', true );
        $speaker   = get_post_meta( $event->id, 'etn_event_speaker', true );

        $speaker_category   = get_term_by( 'slug', $speaker, 'etn_speaker_category' );
        $organizer_category = get_term_by( 'slug', $organizer, 'etn_speaker_category' );

        if ( $speaker_category ) {
            $speaker_category = $speaker_category->term_id;
        }

        if ( $organizer_category ) {
            $organizer_category = $organizer_category->term_id;
        }

        if ( $organizer ) {
            $event->update( [
                'etn_event_organizer' => $this->prepare_organizer(),
                'organizer_type'      => 'group',
                'organizer_group'     => [$organizer_category],
            ] );
        }

        if ( $speaker ) {
            $event->update( [
                'etn_event_speaker' => $this->prepare_speaker(),
                'speaker_type'      => 'group',
                'speaker_group'     => [$speaker_category],
            ] );
        }
    }

    /**
     * Migrate event location
     *
     * @param   Event_Model  $event
     *
     * @return  void
     */
    protected function migrate_event_location( $event ) {
        $location_type  = get_post_meta( $event->id, 'etn_event_location_type', true );
        $event_location = get_post_meta( $event->id, 'etn_event_location', true );
        $event_type     = get_post_meta( $event->id, 'event_type', true );
        $location = '';
        
        if ( 'offline' == $event_type ) {
            $latitude  = ! empty( $event_location['latitude'] ) ? $event_location['latitude'] : '';
            $longitude = ! empty( $event_location['longitude'] ) ? $event_location['longitude'] : '';
            $address   = ! empty( $event_location['address'] ) ? $event_location['address'] : '';

            $location = [
                'address'   => $address,
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];
        }

        if ( 'new_location' == $location_type ) {
            $location_terms = get_post_meta( $event->id, 'etn_event_location_list', true );
            $location_term  = is_array( $location_terms ) ? $location_terms[0] : '';

            $term = get_term_by( 'slug', $location_term, 'etn_location' );

            if ( $term ) {
                $location = [
                    'email'       => get_term_meta( $term->term_id, 'location_email', true ),
                    'address'     => get_term_meta( $term->term_id, 'address', true ),
                    'latitude'    => get_term_meta( $term->term_id, 'location_latitude', true ),
                    'longitude'   => get_term_meta( $term->term_id, 'location_longitude', true ),
                ];
            }
            
        }

        if ( 'existing_location' == $location_type ) {
            $location = [
                'address'     => $event_location,
            ];
        }

        if ( $location ) {
            $event->update(
                [
                    'etn_event_location' => $location,
                    'event_type'         => 'offline'
                ]
            );
        }
    }

    /**
     * Migrate zoom meeting
     *
     * @param   Event_Model  $event
     *
     * @return  void
     */
    protected function migrate_zoom_meeting( $event ) {
        $zoom_id        = get_post( $event->id, 'etn_zoom_id', true );
        $zoom_start_url = get_post_meta( $zoom_id, 'zoom_join_url', true );

        if ( $zoom_id && $zoom_start_url ) {
            $event->update( [
                'meeting_link' => $zoom_start_url,
            ] );
        }
    }

    /**
     * Migrate google meet
     *
     * @param   Event_Model  $event
     *
     * @return  void
     */
    protected function migrate_google_meet( $event ) {
        $google_meet_enabled  = get_post_meta( $event->id, 'etn_google_meet', true );
        $google_meet_link     = get_post_meta( $event->id, 'etn_google_meet_link', true );

        if ( $google_meet_enabled && $google_meet_link ) {
            $event->update( [
                'meeting_link' => $google_meet_link,
            ] );
        }
    }

    /**
     * Migrate extra fields
     *
     * @param   [type]  $event  [$event description]
     *
     * @return  [type]          [return description]
     */
    protected function migrate_extra_field( $event ) {
        $extra_fields = get_post_meta( $event->id, 'attendee_extra_fields', true);

        $updated_fields = [];

        if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {

            foreach( $extra_fields as $field ) {
                $field_type = ! empty( $field['type'] ) ? $field['type'] : '';
                $required   = ! empty( $field['etn_field_type'] ) ? $field['etn_field_type'] : '';

                $required = 'required' == $required ? true : false;
                $new_field = [];

                switch( $field_type ) {
                    case 'text':
                        $new_field = [
                            'label'             => $field['label'],
                            'field_type'        => $field_type,
                            'placeholder_text'  => $field['place_holder'],
                            'required'          => $required,
                        ];
                        break;
                    
                    case 'number':
                        $new_field = [
                            'label'             => $field['label'],
                            'field_type'        => $field_type,
                            'placeholder_text'  => $field['place_holder'],
                            'required'          => $required,
                        ];
                        break;
                    
                    case 'date':
                        $new_field = [
                            'label'             => $field['label'],
                            'field_type'        => $field_type,
                            'placeholder_text'  => $field['place_holder'],
                            'required'          => $required,
                        ];
                        break;
                    
                    case 'radio':
                        $new_field = [
                            'label'             => $field['label'],
                            'field_type'        => $field_type,
                            'placeholder_text'  => $field['place_holder'],
                            'field_options'     => $this->prepare_field_options( $field['radio'] ),
                            'required'          => $required,
                        ];
                        break;

                        case 'checkbox':
                            $new_field = [
                                'label'             => $field['label'],
                                'field_type'        => $field_type,
                                'placeholder_text'  => $field['place_holder'],
                                'field_options'     => $this->prepare_field_options( $field['checkbox'] ),
                                'required'          => $required,
                            ];
                            break;
                }

                $updated_fields[] = $new_field;
            }
        }

        $event->update([
            'attendee_extra_fields' => $updated_fields
        ]);
    }

    /**
     * Migrate banner image
     *
     * @param   Event_Model  $event  [$event description]
     *
     * @return  void
     */
    protected function migrate_banner_image( $event ) {
        $banner_image = get_the_post_thumbnail_url( $event->id );

        $event->update( [
            'event_banner' => $banner_image
        ] );
    }

    /**
     * Prepare extra field options
     *
     * @param   array  $options
     *
     * @return  array
     */
    protected function prepare_field_options( $options ) {
        $new_fields = [];

        foreach ( $options as $option ) {
            $new_fields[] = array( 'value' => $option );
        }

        return $new_fields;
    }

    /**
     * Get organizer by term slug
     *
     * @return  array
     */
    protected function prepare_organizer() {
        $args = array(
            'numberposts'   => -1,
            'post_type'     => 'etn-speaker',
            'post_status'   => 'any',
            'fields'        => 'ids',
            
            'tax_query' => array(
                'relation' => 'AND',
                [
                    'taxonomy' => 'etn_speaker_category',
                    'field'    => 'slug',
                    'terms'    => 'organizer'
                ]
            )
        );

        $organizers = get_posts( $args );

        return $organizers;
    }

    /**
     * Get speaker by term slug
     *
     * @return  array
     */
    protected function prepare_speaker() {
        $args = array(
            'numberposts'   => -1,
            'post_type'     => 'etn-speaker',
            'post_status'   => 'any',
            'fields'        => 'ids',
            
            'tax_query' => array(
                'relation' => 'AND',
                [
                    'taxonomy' => 'etn_speaker_category',
                    'field'    => 'slug',
                    'terms'    => 'speaker'
                ]
            )
        );

        $speakers = get_posts( $args );

        return $speakers;
    }

    /**
     * Convert to date from date time string
     *
     * @param   string  $datetimeString  Datetime string
     *
     * @return  string  Date string
     */
    protected function etn_convert_to_date( $datetimeString ) {
        try {
            // Create a DateTime object using the provided datetime string
            $datetime = new \DateTime( $datetimeString );
            
            // Return the formatted date in 'Y-m-d' format
            return $datetime->format( 'Y-m-d' );
        } catch ( \Exception $e ) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
