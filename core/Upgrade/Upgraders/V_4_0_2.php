<?php
/**
 * Updater for version 4.0.0
 *
 * @package Eventin\Upgrade
 */
namespace Eventin\Upgrade\Upgraders;

use Etn\Core\Event\Event_Model;

/**
 * Updater class for v4.0.2
 *
 * @since 4.0.0
 */
class V_4_0_2 implements UpdateInterface {
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
            $this->migrate_extra_field( $event );
        }

        $this->migrate_global_extra_field();
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
                $required   = ! empty( $field['etn_field_type'] ) ? $field['etn_field_type'] : '';

                if ( ! $required ) {
                    $required = ! empty( $field['required'] ) ? $field['required'] : '';
                }

                $required = $required || 'required' == $required ? true : false;

                $new_field = $field;
                $new_field['required'] = $required;

                $updated_fields[] = $new_field;
            }
        }

        $event->update([
            'attendee_extra_fields' => $updated_fields
        ]);
    }

    protected function migrate_global_extra_field() {
        $extra_fields = etn_get_option( 'attendee_extra_fields' );

        $updated_fields = [];

        if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {

            foreach( $extra_fields as $field ) {
                $field_type = ! empty( $field['type'] ) ? $field['type'] : '';
                $required   = ! empty( $field['etn_field_type'] ) ? $field['etn_field_type'] : '';

                $required = 'required' == $required ? true : false;

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

            etn_update_option( 'attendee_extra_fields', $updated_fields );
        }
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
}
