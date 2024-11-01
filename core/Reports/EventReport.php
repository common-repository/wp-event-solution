<?php
namespace Eventin\Reports;

use Eventin\Input;
use Etn\Core\Event\Event_Model;
use Eventin\Order\OrderModel;

/**
 * Event Report class
 * 
 * @package Eventin
 */
class EventReport extends AbstractReport {
    /**
     * Get reports by event
     *
     * @return  array  Event report data
     */
    public static function get_reports( $data = [] ) {
        $reports = [
            'total' => self::get_total_sold_tickets_by_event( $data ),
        ];

        $ticket_reports = self::get_ticket_reports_by_event( $data );

        return array_merge( $reports, $ticket_reports );
    }

    /**
     * Get total events
     *
     * @param   array  $dates  Date range
     *
     * @return  number Number of total events
     */
    public static function get_total_event( $dates = [] ) {
        $events = self::get_events( $dates );

        if ( is_array( $events ) ) {
            return count( $events );
        }

        return 0;
    }

    /**
     * Get events
     *
     * @param   array  $data  Events data
     *
     * @return  array
     */
    private static function get_events( $data = [] ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );

        return self::get_posts( [
            'post_type'  => 'etn',
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ] );
    }

    /**
     * Get total sold ticket by event
     *
     * @param   integer  $event_id  Event Id
     *
     * @return  integer
     */
    private static function get_total_sold_tickets_by_event( $data = [] ) {
        $orders = OrderReport::get_orders_by_event( $data );
        $total = 0;

        if ( is_array( $orders ) ) {
            foreach( $orders as $order_id ) {
                $order = new OrderModel( $order_id );
                $total += $order->get_total_ticket();
            }
        }

        return $total;
    }

    /**
     * Get ticket reports by event and date range
     *
     * @param   array  $data  Date range and event id
     *
     * @return  array Ticket reports
     */
    public static function get_ticket_reports_by_event( $data = [] ) {
        $orders     = OrderReport::get_orders_by_event( $data );
        $total      = 0;
        $event      = new Event_Model( $data['event_id'] );
        $variations = $event->etn_ticket_variations;

        $tickets = [];

        if ( is_array( $variations ) ) {
            foreach( $variations as $variation ) {
                $ticket_slug = $variation['etn_ticket_slug'];

                if ( is_array( $orders ) ) {
                    foreach( $orders as $order_id ) {
                        $order = new OrderModel( $order_id );
                        if ( array_key_exists( $variation['etn_ticket_name'], $tickets ) ) {
                            $tickets[$variation['etn_ticket_name']]['sold'] += $order->get_total_ticket_by_ticket( $ticket_slug );
                        } else {
                            $tickets[$variation['etn_ticket_name']] = [
                                'total' => $variation['etn_avaiilable_tickets'],
                                'sold'  => $order->get_total_ticket_by_ticket( $ticket_slug ),
                            ];
                        }
                    }
                }
            }
        }

        return $tickets;
    }
}
