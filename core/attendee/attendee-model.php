<?php
/**
 * Attendee Model Class
 *
 * @package Eventin
 */
namespace Etn\Core\Attendee;

use Etn\Base\Post_Model;

/**
 * Attendee Model
 */
class Attendee_Model extends Post_Model {
    protected $post_type = 'etn-attendee';

    /**
     * Store attendee data
     *
     * @var array
     */
    protected $data = [
        'etn_name'                    => '',
        'etn_email'                   => '',
        'etn_phone'                   => '',
        'etn_event_id'                => '',
        'eventin_order_id'            => '',
        'etn_unique_ticket_id'        => '',
        'ticket_name'                 => '',
        'ticket_slug'                 => '',
        'etn_attendeee_ticket_status' => 'unused',
        'etn_ticket_price'            => '',
        'etn_status'                  => 'failed',
        'etn_info_edit_token'         => '',
        'extra_fields'                => '',
        'attendee_seat'               => '',
    ];

    /**
     * Set extra field data
     *
     * @param   string  $name   Extra field name
     * @param   mixed  $value  Extra field value
     *
     * @return  void
     */
    public function __set( $name, $value ) {
        $this->data[$name] = $value;
    }

    /**
     * Set extra fields
     *
     * @param   array  $extra_fields  Attendee extra fields
     *
     * @return  void
     */
    public function set_fields( $extra_fields ) {
        foreach( $extra_fields as $key => $value ) {
            $this->data[$key] = $value;
        }
    }

    /**
     * Get all attendees by key. Example all attendess for an event or an order
     *
     * @param   string  $key    [$key description]
     * @param   mixed  $value  [$value description]
     *
     * @return  array
     */
    public function get_attendees_by( $key, $value ) {
        $args = [
            'post_type'      => 'etn-attendee',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => $key,
                    'value'   => $value,
                    'compare' => '=',
                ]
            ]
        ];

        $attendees = get_posts( $args );

        $data = [];

        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendee_object = new Attendee_Model( $attendee->ID );
                $attendee_data   = $attendee_object->get_data();
                $attendee_data['extra_fields'] = $attendee_object->get_extra_fields();
                $data[] = $attendee_data;
            }
        }

        return $data;
    }

    /**
     * Get attendee extra fields data
     *
     * @return  array  Extra fields data
     */
    public function get_extra_fields() {
        $fields = get_post_meta( $this->id );
        $extra_fields = [];

        if ( ! is_array( $fields ) ) {
            return $extra_fields;
        }
        
        foreach( $fields as $key => $value ) {
            
            // Check extra fields exist or not.
            if ( strpos( $key, 'etn_attendee_extra_field_' ) !== false ) {
                $new_key = str_replace( 'etn_attendee_extra_field_', '', $key );
                $extra_fields[$new_key] = get_post_meta( $this->id, $key, true );
            }
        }

        return $extra_fields;
    }

    /**
     * Get attendde data
     *
     * @return  array
     */
    public function get_data() {
        $event_id = get_post_meta( $this->id, 'etn_event_id', true );
        $event = get_post( intval($event_id) );
        $response_data = [
            'id'         => $this->id,
            'event_name' => $event ? $event->post_title : '',
        ];

        foreach ( $this->data as $key => $value ) {
            $meta_key = $this->meta_prefix . $key;

            $meta_value = get_post_meta( $this->id, $meta_key, true );
            
            $response_data[$key] = $meta_value;
        }

        return $response_data;
    }
}
