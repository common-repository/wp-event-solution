<?php
/**
 * Schedule Importer Class
 * 
 * @package Eventin
 */
namespace Etn\Core\Schedule;

use Etn\Base\Importer\Post_Importer_Interface;
use Etn\Base\Importer\Reader_Factory;

/**
 * Class Schedule Importer
 */
class Schedule_Importer implements Post_Importer_Interface {
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
     * Schedule import
     *
     * @return  void
     */
    public function import( $file ) {
        $this->file = $file;
        $file_reader = Reader_Factory::get_reader( $file );

        $this->data = $file_reader->read_file();
        $this->create_schedule();
    }

    /**
     * Create schedule
     *
     * @return  void
     */
    private function create_schedule() {
        $schedule   = new Schedule_Model();
        $file_type  = ! empty( $this->file['type'] ) ? $this->file['type'] : '';

        $rows = $this->data;

        foreach( $rows as $row ) {
            $slots = ! empty( $row['schedule_slot'] ) ? $row['schedule_slot'] : '';
            // error_log(print_r($row, true));
            if ( 'text/csv' == $file_type ) {
                $slots = json_decode( $slots, true );
            }

            $args = [
                'etn_schedule_title'    => ! empty( $row['program_title'] ) ? $row['program_title'] : '',
                'etn_schedule_date'     => ! empty( $row['date'] ) ? $row['date'] : '',
                'etn_schedule_day'      => ! empty( $row['day_name'] ) ? $row['day_name'] : '',
                'etn_schedule_topics'   => $slots
            ];

            $schedule->create( $args );
        }
 
    }
}
