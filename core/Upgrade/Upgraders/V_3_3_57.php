<?php
/**
 * Updater for version 4.0.0
 *
 * @package Eventin\Upgrade
 */
namespace Eventin\Upgrade\Upgraders;

use Etn\Core\Event\Event_Model;

/**
 * Updater class for v3.3.57
 *
 * @since 4.0.0
 */
class V_3_3_57 implements UpdateInterface {
    /**
     * Run the updater
     *
     * @return  void
     */
    public function run() {
        $this->migrate_event();
    }

    /**
     * Migrate event
     *
     * @return  void
     */
    protected function migrate_event() {
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

            $this->migrate_speaker_organizer( $event );
            $this->migrate_location( $event );
            $this->migrate_extra_field( $event );
        }
    }

    /**
     * Migrate speaker organizer
     *
     * @param   Event_Model  $event
     *
     * @return  void
     */
    protected function migrate_speaker_organizer( $event ) {
        $organizer = get_post_meta( $event->id, 'etn_event_organizer', true );
        $speaker   = get_post_meta( $event->id, 'etn_event_speaker', true );
        
        if ( $organizer ) {
            $event->update( [
                'etn_event_organizer' => 'organizer',
            ] );
        }

        if ( $speaker ) {
            $event->update( [
                'etn_event_speaker' => 'speaker',
            ] );
        }
    }

    /**
     * Migrate event location
     *
     * @param   Event_Model  $event  [$event description]
     *
     * @return  void
     */
    protected function migrate_location( $event ) {
        $location = get_post_meta( $event->id, 'etn_event_location', true );

        $address = ! empty( $location['address'] ) ? $location['address'] : '';

        $event->update([
            'etn_event_location' => $address,
        ]);
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
                $field_type = ! empty( $field['field_type'] ) ? $field['field_type'] : '';

                switch( $field_type ) {
                    case 'text':
                        $new_field = [
                            'label'             => $field['label'],
                            'type'              => $field_type,
                            'place_holder'      => $field['placeholder_text'],

                            
                        ];
                        break;
                    
                    case 'radio':
                        $new_field = [
                            'label'             => $field['label'],
                            'type'              => $field_type,
                            'place_holder'      => $field['placeholder_text'],
                            'radio'             => $this->prepare_field_options( $field['field_options'] ),
                        ];
                        break;

                        case 'checkbox':
                            $new_field = [
                                'label'             => $field['label'],
                                'type'              => $field_type,
                                'place_holder'      => $field['placeholder_text'],
                                'checkbox'          => $this->prepare_field_options( $field['field_options'] ),
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
     * Prepare extra field options
     *
     * @param   array  $options
     *
     * @return  array
     */
    protected function prepare_field_options( $options ) {
        $new_fields = [];

        foreach ( $options as $item ) {
            $new_fields[] = $item['value'];
        }

        return $new_fields;
    }
}
