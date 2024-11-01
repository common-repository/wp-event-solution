<?php
/**
 * Schedule Exporter Class
 *
 * @package Eventin
 */
namespace Etn\Core\Schedule;

use Etn\Base\Exporter\Exporter_Factory;
use Etn\Base\Exporter\Post_Exporter_Interface;

/**
 * Class Schedule Exporter
 *
 * Export Schedule Data
 */
class Schedule_Exporter implements Post_Exporter_Interface {
    /**
     * Store file name
     *
     * @var string
     */
    private $file_name = 'schedule-data';

    /**
     * Store attendee data
     *
     * @var array
     */
    private $data;

    /**
     * Store data format to export data
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
        $this->data = $data;
        $this->format = $format;
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
            $slots = get_post_meta( $id, 'etn_schedule_topics', true );

            if ( 'csv' === $this->format ) {
                $slots = json_encode( $slots );
            }

            $schedule_data = [
                'id'            => $id,
                'program_title' => get_post_meta( $id, 'etn_schedule_title', true ),
                'date'          => get_post_meta( $id, 'etn_schedule_date', true ),
                'day_name'      => get_post_meta( $id, 'etn_schedule_day', true ),
                'schedule_slot' => $slots,
            ];

            array_push( $exported_data, $schedule_data );
        }

        return $exported_data;
    }

    /**
     * Get columns
     *
     * @return  array
     */
    private function get_columns() {
        return [
            'id'            => __( 'Id', 'eventin' ),
            'program_title' => __( 'Program Title', 'eventin' ),
            'date'          => __( 'Date', 'eventin' ),
            'day_name'      => __( 'Day Name', 'eventin' ),
            'schedule_slot' => __( 'Schedule Slot', 'eventin' ),
        ];
    }
}
