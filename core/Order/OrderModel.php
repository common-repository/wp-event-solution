<?php
namespace Eventin\Order;
use Etn\Base\Post_Model;
use Etn\Core\Attendee\Attendee_Model;
use Etn\Core\Event\Event_Model;
use Eventin\Customer\CustomerModel;

/**
 * Order Model
 * 
 * @package Eventin
 */

class OrderModel extends Post_Model {
    use OrderEmailTrait;

    /**
     * Store post type
     *
     * @var string
     */
    protected $post_type = 'etn-order';

    /**
     * Store order data
     *
     * @var array
     */
    protected $data = [
        'customer_fname'    => '',
        'customer_lname'    => '',
        'customer_email'    => '',
        'customer_phone'    => '',
        'date_time'         => '',
        'event_id'          => '',
        'payment_method'    => '',
        'status'            => '',
        'user_id'           => '',
        'tickets'           => '',
        'seat_ids'          => '',
        'total_price'       => '',
        'payment_id'        => '',
        'attendee_seats'    => '',
        'customer_id'       => '',
    ];

    /**
     * Get total ticket for an order
     *
     * @return  integer
     */
    public function get_total_ticket() {
        $variations = $this->tickets;
        $total_ticket = 0;

        if ( $variations && is_array( $variations ) ) {
            foreach( $variations as $variation ) {
                $total_ticket += $variation['ticket_quantity'];
            }
        }

        return $total_ticket;
    }

    /**
     * Get total ticket by ticket slug
     *
     * @param   string  $ticket_slug 
     *
     * @return  integer
     */
    public function get_total_ticket_by_ticket( $ticket_slug ) {
        $variations = $this->tickets;

        if ( $variations && is_array( $variations ) ) {
            foreach( $variations as $variation ) {
                if ( $variation['ticket_slug'] === $ticket_slug ) {
                    return $variation['ticket_quantity'];
                }
            }
        }

        return 0;
    }

    /**
     * Get all attenddes for an order
     *
     * @return  array Attendee data
     */
    public function get_attendees() {
        $attendee_obect = new Attendee_Model();

        $attendees = $attendee_obect->get_attendees_by( 'eventin_order_id', $this->id );

        return $attendees;
    }

    /**
     * Get all tickets for an order
     *
     * @return  array  Tickets data
     */
    public function get_tickets() {
        $tickets = [];
        $event   = new Event_Model( $this->event_id );

        if ( $this->tickets ) {
            foreach( $this->tickets as $ticket ) {
                $ticket_item = $event->get_ticket( $ticket['ticket_slug'] );
                if ( ! $ticket_item ) {
                    continue;
                }
                
                $ticket_data = [
                    'etn_ticket_name'   => $ticket_item['etn_ticket_name'],
                    'etn_ticket_price'  => $ticket_item['etn_ticket_price'],
                    'etn_ticket_slug'   => $ticket_item['etn_ticket_slug'],
                    'etn_ticket_qty'    => $ticket['ticket_quantity'],
                ];

                if ( ! empty( $ticket['seats'] ) ) {
                    $ticket_data['seats'] = $ticket['seats'];
                }

                $tickets[] = $ticket_data;
            }
        }

        return $tickets;
    }

    /**
     * Get order date time
     *
     * @param   string  $format  
     *
     * @return  string
     */
    public function get_datetime( $format = 'Y-m-d h:i A') {
        $post = get_post( $this->id );

        $datetime = new \DateTime( $post->post_date );

        return $datetime->format($format);
    }

    /**
     * Get order customer
     *
     * @return  CustomerModel
     */
    public function get_customer() {
        return CustomerModel::find( $this->customer_id );
    }
}

