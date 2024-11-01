<?php
/**
 * JSON Exporter Class
 * 
 * @package Eventin
 */
namespace Etn\Base\Exporter;

/**
 * JSON Exporter Class
 *  Export data for json
 */
class Json_Exporter implements Exporter_Interface {
    /**
     * Exported data
     *
     * @var array
     */
    private $data;

    /**
     * Store column name that will be exported
     *
     * @var array
     */
    private $columns;

    /**
     * Exported file name
     *
     * @var string
     */
    private $file_name = 'data';

    /**
     * Export data
     *
     * @param   array  $data
     * @param   data  $columns
     *
     * @return  void
     */
    public function export( $data, $columns = [], $file_name = '' ) {
        $this->data = $data;
        $this->columns = $columns;
        $this->file_name = $file_name;
        $this->export_json();
    }
    /**
     * Set content type
     *
     * @return void
     */
    protected function send_headers() {
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $this->file_name . '.json' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
    }

    /**
     * Export data to JSON format
     *
     * @return  void
     */
    public function export_json() {
        $this->send_headers();
        $data = $this->data;

        $output = json_encode( $data, JSON_PRETTY_PRINT );
        echo $output;
        die();
    }
}
