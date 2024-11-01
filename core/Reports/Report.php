<?php
namespace Eventin\Reports;

use Etn\Core\Event\Event_Model;
use Eventin\Input;
use Eventin\Order\OrderModel;

/**
 * Class Report
 */
class Report {
    /**
     * Get reports
     *
     * @param   array  $dates  [$dates description]
     *
     * @return  array
     */
    public static function get_reports( $dates = [] ) {
        $defaults = [
            'start_date' => '',
            'end_date'   => '',
        ];

        $dates = wp_parse_args( $dates, $defaults );

        return [
            'booking'      => OrderReport::get_total_order( $dates ),
            'event'        => EventReport::get_total_event( $dates ),
            'attendee'     => AttendeeReport::get_total_attendee( $dates ),
            'speaker'      => SpeakerReport::get_total_speaker( $dates ),
            'revenue'      => RevenueReport::get_total_revenue( $dates ),
            'date_reports' => self::get_report_by_date_range( $dates ), 
        ];
    }

    /**
     * Get reports by event
     *
     * @param   array  $data  Event data and date range
     *
     * @return  array Reports by event data
     */
    public static function get_reports_by_event( $data = [] ) {
        $defaults = [
            'start_date' => '',
            'end_date'   => '',
            'event_id'   => 0,
        ];

        $data = wp_parse_args( $data, $defaults );

        return [
            'sold_tickets' => EventReport::get_reports( $data ),
            'revenue'      => RevenueReport::get_reports_by_event( $data ),
            'attendees'    => AttendeeReport::get_reports_by_event( $data ),
            'date_reports' => self::get_report_by_date_range( $data ),
        ];
    }

    /**
     * Get report by date range
     *
     * @param   array  $data  [$data description]
     *
     * @return  array
     */
    public static function get_report_by_date_range( $data = [] ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );
        
        if ( ! $start_date || ! $end_date ) {
            return self::generate_monthly_report( $data );
        }

        return self::generate_date_range_report( $data );
    }

    /**
     * Generate report by date range
     *
     * @param   string  $start_date  [$start_date description]
     * @param   string  $end_date    [$end_date description]
     *
     * @return  array 
     */
    private static function generate_date_range_report( $data ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );
        $event_id   = $input->get( 'event_id' );

        $dates = self::get_dates( $start_date, $end_date );
        $report = [];
    
        foreach ( $dates as $date ) {
            $data = [
                'start_date'    => $date,
                'end_date'      => $date,
                'event_id'      => $event_id,
            ];

            $report[] = $event_id ? self::fetch_report_data_by_event( $data ) : self::fetch_report_data( $data );
        }
    
        return $report;
    }

    /**
     * Generate monthly report
     *
     * @param   string  $start_date  Starting day
     * @param   string  $end_date    End day
     *
     * @return  array
     */
    private static function generate_monthly_report( $data ) {
        $input = new Input( $data );

        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );
        $event_id   = $input->get( 'event_id' );

        $months = self::get_monthly_date_ranges( $start_date, $end_date );
        $report = [];
    
        foreach ( $months as $month ) {
            $data = [
                'start_date'    => $month['start'],
                'end_date'      => $month['end'],
                'event_id'      => $event_id,
            ];

            $report[] = $event_id ? self::fetch_report_data_by_event( $data, 'F-Y' ): self::fetch_report_data( $data, 'F-Y' );
        }
    
        return $report;
    }

    /**
     * Fetch report from database
     *
     * @param   array  $dates  Start date and End date
     *
     * @return  array             Report data
     */
    private static function fetch_report_data( $dates = [], $format = 'Y-m-d' ) {
        $date = new \DateTime( $dates['start_date'] );

        return [
            'date'      => $date->format( $format ),
            'revenue'   => RevenueReport::get_total_revenue( $dates ),
        ];
    }

    /**
     * Fetch report from database
     *
     * @param   array  $dates  Start date and End date
     *
     * @return  array             Report data
     */
    private static function fetch_report_data_by_event( $data = [], $format = 'Y-m-d' ) {
        $date = new \DateTime( $data['start_date'] );

        return [
            'date'      => $date->format( $format ),
            'revenue'   => RevenueReport::get_total_revenue_by_event( $data ),
        ];
    }

    /**
     * Get date array
     *
     * @param   string  $start_date  Start date for the date range
     * @param   string  $end_date    End date for the date range
     * @param   string  $format      Date format
     *
     * @return  array   Date array
     */
    private static function get_dates( $start_date, $end_date, $format = 'Y-m-d' ) {
        $dates = [];
        $current_date = new \DateTime( $start_date );
        $end_date     = new \DateTime( $end_date );
    
        while ( $current_date <= $end_date ) {
            $dates[] = $current_date->format($format);
            $current_date->modify('+1 day');
        }
    
        return $dates;
    }

    /**
     * Get dates by year
     *
     * @param   string  $start_date  [$start_date description]
     * @param   string  $end_date    [$end_date description]
     *
     * @return  array
     */
    private static function get_monthly_date_ranges( $start_date = null, $end_date = null ) {
        $monthly_ranges = [];
    
        if ( ! $start_date ) {
            $start_date = date( 'Y-m-01', strtotime( '-11 months' ) ); // 12 months ago, starting from the first day of the month
        }

        if ( ! $end_date ) {
            $end_date = date('Y-m-t'); // Last day of the current month
        }
    
        $current_date = new \DateTime( $start_date );
        $end_date = new \DateTime( $end_date );
    
        while ( $current_date <= $end_date ) {
            // Get first and last date of the current month
            $first_day_of_month = $current_date->format('Y-m-01');
            $last_day_of_month  = $current_date->format('Y-m-t'); // t returns the last day of the month
    
            $monthly_ranges[] = [
                'month' => $current_date->format('F-Y'), // e.g., January-2024
                'start' => $first_day_of_month,
                'end'   => $last_day_of_month,
            ];
    
            $current_date->modify('first day of next month');
        }
    
        return $monthly_ranges;
    }
}
