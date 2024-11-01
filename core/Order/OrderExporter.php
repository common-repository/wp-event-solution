<?php
/**
 * Attendee Exporter Class
 *
 * @package Eventin
 */
namespace Eventin\Order;

use Etn\Base\Exporter\Exporter_Factory;
use Etn\Base\Exporter\Post_Exporter_Interface;
use Exception;
use WP_Error;

/**
 * Class Order Exporter
 *
 * Export Order Data
 */
class OrderExporter implements Post_Exporter_Interface {
    /**
     * Store file name
     *
     * @var string
     */
    private $file_name = 'order-data';

    /**
     * Store attendee data
     *
     * @var array
     */
    private $data;

    /**
     * Store file format
     *
     * @var string
     */
    private $format;
    
    /**
     * Export attendee data
     *
     * @return void
     */
    public function export( $data, $format ) {
        $this->data   = $data;
        $this->format = $format;

        $rows      = $this->prepare_data();
        $columns   = $this->get_columns();
        $file_name = $this->file_name;

        try {
            $exporter = Exporter_Factory::get_exporter( $format );

            $exporter->export( $rows, $columns, $file_name );
        } catch(Exception $e) {
            return new WP_Error( 'export_error', $e->getMessage(), ['status' => 409] );
        }

        
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
            $order = new OrderModel( $id );

            $tickets    = $order->get_tickets();
            $attendees  = $order->get_attendees();

            if ( 'csv' === $this->format ) {
                $tickets   = json_encode( $tickets );
                $attendees = json_encode( $attendees );
            }

            $order_data = [
                'id'                => $order->id,
                'customer_fname'    => $order->customer_fname,
                'customer_lname'    => $order->customer_lname,
                'customer_email'    => $order->customer_email,
                'customer_phone'    => $order->customer_phone,
                'date_time'         => $order->date_time,
                'event_id'          => $order->event_id,
                'payment_method'    => $order->payment_method,
                'status'            => $order->status,
                'total_price'       => $order->total_price,
                'ticket_items'      => $tickets,
                'attendees'         => $attendees,
            ];
            
            array_push( $exported_data, $order_data );
        }

        return $exported_data;
    }

    /**
     * Get columns
     *
     * @return  array
     */
    private function get_columns() {
        $columns = [
            'id'                 => __( 'Id', 'eventin' ),
            'customer_fname'     => __( 'Customer Fname', 'eventin' ),
            'customer_lname'     => __( 'Customer Lname', 'eventin' ),
            'customer_email'     => __( 'Customer Email', 'eventin' ),
            'customer_phone'     => __( 'Phone', 'eventin' ),
            'date_time'          => __( 'Date Time', 'eventin' ),
            'event_id'           => __( 'Event ID', 'eventin' ),
            'payment_method'     => __( 'Payment Method', 'eventin' ),
            'status'             => __( 'Status', 'eventin' ),
            'total_price'        => __( 'Total Price', 'eventin' ),
            'ticket_items'       => __( 'Ticket Items', 'eventin' ),
            'attendees'          => __( 'Attendees', 'eventin' ),
        ];

        return $columns;
    }
}
