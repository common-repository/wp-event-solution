<?php
namespace Eventin\Reports;

use Eventin\Input;
use Eventin\Order\OrderModel;
use Etn\Core\Event\Event_Model;

/**
 * Revenue  Report class
 * 
 * @package Eventin
 */
class RevenueReport extends AbstractReport {
    /**
     * Get total revenue
     *
     * @param   array  $dates  Start and end date
     *
     * @return  number
     */
    public static function get_total_revenue( $dates = [] ) {
        $total = 0;
        $orders = OrderReport::get_orders( $dates );

        if ( $orders ) {
            foreach( $orders as $order_id ) {
                $order = new OrderModel( $order_id );
                $total += $order->total_price;
            }
        }

        return $total;
    }

    /**
     * Get revenue by event
     *
     * @param   array  $data  Date range and event id
     *
     * @return  array
     */
    public static function get_reports_by_event( $data = [] ) {
        $reports = [
            'total' => self::get_total_revenue_by_event( $data ),
        ];

        $ticket_reports = self::get_total_revenue_by_tickets( $data );

        return array_merge( $reports, $ticket_reports );
    }

    /**
     * Get total revenue by event
     *
     * @param   array  $data  [$data description]
     *
     * @return  integer
     */
    public static function get_total_revenue_by_event( $data = [] ) {
        $orders = OrderReport::get_orders_by_event( $data );
        $total = 0;

        if ( is_array( $orders ) ) {
            foreach( $orders as $order_id ) {
                $order = new OrderModel( $order_id );
                $total += $order->total_price;
            }
        }

        return $total;
    }

    /**
     * Get revenue for every ticket
     *
     * @param   array  $data  Event data
     *
     * @return  array
     */
    private static function get_total_revenue_by_tickets( $data ) {
        $tickets    = EventReport::get_ticket_reports_by_event( $data );
        $total      = 0;
        $event      = new Event_Model( $data['event_id'] );
        $variations = $event->etn_ticket_variations;
        $revenue    = [];

        if ( is_array( $tickets ) ) {
            foreach( $tickets as $ticket_name => $ticket ) {
                $price = $event->get_ticket_price_by_name( $ticket_name );
                $revenue[$ticket_name] = $tickets[$ticket_name]['sold'] * $price;
            }
        }
        
        return $revenue;
    }
}
