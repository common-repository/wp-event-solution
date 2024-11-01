<?php
/**
 * Updater for version 4.0.7
 *
 * @package Eventin\Upgrade
 */
namespace Eventin\Upgrade\Upgraders;

use Etn\Core\Event\Event_Model;

/**
 * Updater class for v4.0.7
 *
 * @since 4.0.7
 */
class V_4_0_7 implements UpdateInterface {
    /**
     * Run the updater
     *
     * @return  void
     */
    public function run() {
        $this->migrate_event();
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
            $this->migrate_event_location( $event );   
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
                'address'   => $this->prepare_address( $address ),
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];

            update_post_meta( $event->id, 'etn_event_location_type', '' );
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
            
        } elseif ( 'existing_location' == $location_type ) {
            $location = [
                'address'     => $event_location,
            ];

            update_post_meta( $event->id, 'etn_event_location_type', '' );
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
     * Prepare address
     *
     * @param   array  $location  Event location
     *
     * @return  string
     */
    protected function prepare_address( $location ) {

        static $depth = 0;
    
        if ( $depth >= 20 ) {
            return '';
        }
    
        $depth++;
    
        if ( ! is_array( $location ) ) {
            return $location;
        }
    
        $address = ! empty( $location['address'] ) ? $location['address'] : '';
    
        return $this->prepare_address( $address );
    }

}
