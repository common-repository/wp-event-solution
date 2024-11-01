<?php
/**
 * Updater for version 4.0.10
 *
 * @package Eventin\Upgrade
 */
namespace Eventin\Upgrade\Upgraders;

use Etn\Core\Attendee\Attendee_Model;
use Eventin\Order\OrderModel;

/**
 * Updater class for v4.0.10
 *
 * @since 4.0.9
 */
class V_4_0_9 implements UpdateInterface {
    /**
     * Run the updater
     *
     * @return  void
     */
    public function run() {
        $this->migrate_reports();
    }

    /**
     * Migrate purchase reports
     *
     * @return  void
     */
    private function migrate_reports() {
        $reports  = $this->get_purchase_reports();
        $statuses = ['Processing', 'Completed', 'Success'];

        if ( $reports && is_array( $reports ) ) {
            foreach( $reports as $report ) {
                $payment_method = $report->payment_type == 'woocommerce' ? 'wc' : $report->payment_type;
                $status = in_array( $report->status, $statuses ) ? 'completed' : 'pending' ;

                if ( $report->payment_type == 'woocommerce' && function_exists('WC') ) {
                    $order = wc_get_order( $report->form_id );
                    
                    if ( $order ) {
                        $customer_fname = $order->get_billing_first_name();
                        $customer_lname = $order->get_billing_last_name();
                        $customer_email = $order->get_billing_email();
                        $customer_phone = $order->get_billing_phone();
                    }
                }else {
                    $customer_fname = get_post_meta( $report->form_id, '_billing_first_name', true );
                    $customer_lname = get_post_meta( $report->form_id, '_billing_last_name', true );
                    $customer_email = get_post_meta( $report->form_id, '_billing_email', true );
                    $customer_phone = get_post_meta( $report->form_id, '_billing_phone', true );
                }

                $args = [
                    'customer_fname'    => $customer_fname,
                    'customer_lname'    => $customer_lname,
                    'customer_email'    => $customer_email,
                    'customer_phone'    => $customer_phone,
                    'date_time'         => $report->date_time,
                    'event_id'          => $report->post_id,
                    'payment_method'    => $payment_method,
                    'status'            => $status,
                    'user_id'           => $report->user_id,
                    'tickets'           => $this->prepare_tickets(maybe_unserialize($report->ticket_variations) ),
                    'total_price'       => $report->event_amount,
                ];

                $order = new OrderModel();
                $order->create( $args );

                $this->update_attendees( $order->id, $report->form_id );
                $this->update_event_order_id( $order->id, $report );
            }
        }
    }
    
    /**
     * Get all purchase reports
     *
     * @return  array
     */
    private function get_purchase_reports() {
        global $wpdb;

        $table   = $wpdb->prefix . 'etn_events';
        $reports = $wpdb->get_results("SELECT * FROM {$table}");

        return $reports;
    }

    /**
     * Prepare order tickets
     *
     * @param   array  $ticket_variations
     *
     * @return  array
     */
    private function prepare_tickets( $ticket_variations ) {
        $tickets = [];

        if ( $ticket_variations ) {

            foreach( $ticket_variations as $variation ) {
                $ticket = [
                    'ticket_slug'     => $variation['etn_ticket_slug'],
                    'ticket_quantity' => $variation['etn_ticket_qty'],
                ];

                $tickets[] = $ticket;
            }
        }

        return $tickets;
    }

    /**
     * Update all attendees with order id
     *
     * @param   integer  $order_id
     * @param   integer  $old_order_id
     *
     * @return  void
     */
    public function update_attendees( $order_id, $old_order_id ) {
        $args = array(
            'post_type'     => 'etn-attendee',
            'post_status'   => 'any',
            'post_per_page' => -1,
            'fields'        => 'ids',
            'meta_query' => array(
                array(
                    'key'       => 'etn_attendee_order_id',
                    'value'     => $old_order_id,
                    'compare'   => '=',
                )
            )
        );

        $attendees = get_posts( $args );

        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendees = new Attendee_Model( $attendee );
                $attendees->update([
                    'eventin_order_id' => $order_id
                ]);
            }
        }
    }

    /**
     * Update woocommerce order id
     *
     * @param   integer  $order_id  [$order_id description]
     * @param   Object  $report    [$report description]
     *
     * @return  void
     */
    public function update_event_order_id( $order_id, $report ) {
        if ( 'woocommerce' === $report->payment_type ) {
            update_post_meta( $report->form_id, 'eventin_order_id', $order_id );
        }
    }
}
