<?php
/**
 * CSV Reader Class
 *
 * @package Eventin
 */
namespace Etn\Base\Importer;

/**
 * CSV Reader Class
 */
class CSV_Reader implements Reader_Interface {
    /**
     * Store file
     *
     * @var string
     */
    private $file;

    /**
     * JSON Reader Constructor
     *
     * @param   string  $file
     *
     * @return  void
     */
    public function __construct( $file ) {
        $this->file = $file;
    }

    /**
     * Read file
     *
     * @return  array
     */
    public function read_file() {
        $data = $this->prepare_data();

        return $data;
    }

    /**
     * Prepare data from file
     *
     * @return  array
     */
    private function prepare_data() {
        $file     = $this->file;
        $csv_data = [];

        $handle  = fopen( $file, 'r' );
        $headers = fgetcsv( $handle );

        if ( ! $headers ) {
            return $csv_data;
        }

        if ( $handle !== false ) {
            $header_count = count( $headers );

            while (  ( $data = fgetcsv( $handle ) ) !== false ) {
                $row = [];

                for ( $i = 0; $i < $header_count; $i++ ) {
                    $header = str_replace( ' ', '_', strtolower( $headers[$i] ) );
                    $row[$header] = $data[$i];
                }

                $csv_data[] = $row;
            }
        }

        fclose( $handle );

        return $csv_data;
    }
}
