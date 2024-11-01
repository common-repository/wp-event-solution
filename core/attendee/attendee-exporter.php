<?php
/**
 * Attendee Exporter Class
 *
 * @package Eventin
 */
namespace Etn\Core\Attendee;

use Etn\Base\Exporter\Exporter_Factory;
use Etn\Base\Exporter\Post_Exporter_Interface;
use Etn\Utils\Helper;

/**
 * Class Attendee Exporter
 *
 * Export Attendee Data
 */
class Attendee_Exporter implements Post_Exporter_Interface {
    /**
     * Store file name
     *
     * @var string
     */
    private $file_name = 'attedee-data';

    /**
     * Store attendee extra fields columns
     *
     * @var array
     */
    private $extra_fields = [];

    /**
     * Store attendee data
     *
     * @var array
     */
    private $data;

    /**
     * Export attendee data
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
            $attendee = [
                'id'             => $id,
                'name'           => get_post_meta( $id, 'etn_name', true ),
                'event_id'       => get_post_meta( $id, 'etn_event_id', true ),
                'event_name'     => get_the_title( get_post_meta( $id, 'etn_event_id', true ) ),
                'ticket_id'      => get_post_meta( $id, 'etn_unique_ticket_id', true ),
                'ticket_name'    => get_post_meta( $id, 'ticket_name', true ),
                'ticket_status'  => get_post_meta( $id, 'etn_attendeee_ticket_status', true ),
                'ticket_price'   => get_post_meta( $id, 'etn_ticket_price', true ),
                'payment_status' => get_post_meta( $id, 'etn_status', true ),
            ];

            
            $attendee['email'] = get_post_meta( $id, 'etn_email', true );

            $attendee['phone'] = get_post_meta( $id, 'etn_phone', true );
            

            $attendee = array_merge( $attendee, $this->get_extra_field_data( $id ) );

            array_push( $exported_data, $attendee );
        }

        return $exported_data;
    }

    /**
     * Prepare extra field data
     *
     * @param   integer  $attendee_id
     *
     * @return  array
     */
    private function get_extra_field_data( $attendee_id ) {
        $event_id     = get_post_meta( $attendee_id, 'etn_event_id', true );
        $extra_fields = get_post_meta( $event_id, 'attendee_extra_fields', true );
        $settings     = etn_get_option();
        $data         = [];
        if ( ! $extra_fields ) {
            $extra_fields = ! empty( $settings['attendee_extra_fields'] ) ? $settings['attendee_extra_fields'] : [];
        }

        if ( $extra_fields ) {
            foreach ( $extra_fields as $value ) {
                $key                        = \Etn_Pro\Utils\Helper::generate_name_from_label( "etn_attendee_extra_field_", $value['label'] );
                $this->extra_fields[$key]   = $value['label'];
                $extra_field_value          = get_post_meta( $attendee_id, $key, true );
                switch($value['field_type']){
                    case 'radio':
                        $data[$key] = $value['field_options'][$extra_field_value]['value'];
                    break;

                    case 'checkbox': 
                        $saved_checkbox_arr   = maybe_unserialize( $extra_field_value );
                        $defined_checkbox_arr = $value[$value['field_options']];
                        $data[$key] = '';
                        if ( is_array( $defined_checkbox_arr ) && count( $defined_checkbox_arr ) > 0 && is_array( $saved_checkbox_arr ) && count( $saved_checkbox_arr ) > 0 ) {
                            $selected_checkbox = array_intersect_key( $defined_checkbox_arr, array_flip( $saved_checkbox_arr ) );
                            $data[$key]         = join( ', ', $selected_checkbox );
                        }
                    break;

                    case 'date': 
                        $date_options    = Helper::get_date_formats();
                        $selected_format = Helper::get_option( 'date_format' );
                        $data[$key]      = ! empty( $extra_field_value) ? ( ! empty( $selected_format ) ? date_i18n( $date_options[ $selected_format ], strtotime( $post_meta_value ) ) : date_i18n( get_option( 'date_format' ), strtotime( $post_meta_value ) ) ) : ''; 
                    break;

                    default:
                        $data[$key] = get_post_meta( $attendee_id, $key, true );
                }
            }
        }
        
        return $data;
    }

    /**
     * Get columns
     *
     * @return  array
     */
    private function get_columns() {
        $columns = [
            'id'             => __( 'Id', 'eventin' ),
            'name'           => __( 'Name', 'eventin' ),
            'email'          => __( 'Email', 'eventin' ),
            'phone'          => __( 'Phone', 'eventin' ),
            'event_id'       => __( 'Event ID', 'eventin' ),
            'event_name'     => __( 'Event Name', 'eventin' ),
            'ticket_price'   => __( 'Ticket Price', 'eventin' ),
            'payment_status' => __( 'Payment Status', 'eventin' ),
            'ticket_status'  => __( 'Ticket Status', 'eventin' ),
            'ticket_id'      => __( 'Ticket ID', 'eventin' ),
            'ticket_name'    => __( 'Ticket Name', 'eventin' ),
        ];

        return array_merge( $columns, $this->extra_fields );
    }
}
