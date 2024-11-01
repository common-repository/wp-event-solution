<?php
/**
 * Order Importer Class
 *
 * @package Eventin
 */
namespace Eventin\Order;

use Etn\Base\Importer\Post_Importer_Interface;
use Etn\Base\Importer\Reader_Factory;
use Etn\Core\Attendee\Attendee_Model;

/**
 * Class Attendee Importer
 */
class OrderImporter implements Post_Importer_Interface {
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
     * Attendee import
     *
     * @return  void
     */
    public function import( $file ) {
        $this->file  = $file;
        $file_reader = Reader_Factory::get_reader( $file );

        $this->data = $file_reader->read_file();
        $this->create_attendee();
    }

    /**
     * Create Attendee
     *
     * @return  void
     */
    private function create_attendee() {
        $order  = new OrderModel();
        $file_type = ! empty( $this->file['type'] ) ? $this->file['type'] : '';

        $rows = $this->data;

        foreach ( $rows as $row ) {
            $ticket_items = ! empty( $row['ticket_items'] ) ? $row['ticket_items'] : '';
            $attendees    = ! empty( $row['attendees'] ) ? $row['attendees'] : '';

            if ( 'text/csv' === $file_type ) {
                $ticket_items = json_decode( $ticket_items, true );
                $attendees    = json_decode( $attendees, true );
            }

            $args = [
                'customer_fname'    => ! empty( $row['customer_fname'] ) ? $row['customer_fname'] : '',
                'customer_lname'    => ! empty( $row['customer_lname'] ) ? $row['customer_lname'] : '',
                'customer_email'    => ! empty( $row['customer_email'] ) ? $row['customer_email'] : '',
                'customer_phone'    => ! empty( $row['customer_phone'] ) ? $row['customer_phone'] : '',
                'date_time'         => ! empty( $row['date_time'] ) ? $row['date_time'] : '',
                'event_id'          => ! empty( $row['event_id'] ) ? $row['event_id'] : '',
                'payment_method'    => ! empty( $row['payment_method'] ) ? $row['payment_method'] : '',
                'status'            => ! empty( $row['status'] ) ? $row['status'] : '',
                'total_price'       => ! empty( $row['total_price'] ) ? $row['total_price'] : '',
                'tickets'           => $ticket_items,
                
            ];

            $order->create( $args );

            $this->create_attendees( $attendees, $order );
        }

    }

    /**
     * Create attendee of an order
     *
     * @param   array  $attendees
     * @param   OrderModel  $order      [$order description]
     *
     * @return  void
     */
    private function create_attendees( $attendees, $order ) {

        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendee['etn_event_id'] = $order->event_id;
                $attendee['order_id']     = $order->id;

                $attendee_model = new Attendee_Model();
                $attendee_model->create( $attendee );
            }
        }
    }
}
