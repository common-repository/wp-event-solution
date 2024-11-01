<?php
/**
 * File Reader Factory
 * 
 * @package Eventin
 */
namespace Etn\Base\Importer;

class Reader_Factory {
    /**
     * Get reader depends on file type
     *
     * @return Reader_Interface
     */
    public static function get_reader( $file ) {
        $file_name  = ! empty( $file['tmp_name'] ) ? $file['tmp_name'] : '';
        $file_type  = ! empty( $file['type'] ) ? $file['type'] : '';


        switch( $file_type ) {
            case 'application/json':
                return new JSON_Reader( $file_name );
            case 'text/csv':
                return new CSV_Reader( $file_name );
            default:
                throw new \Exception( __( 'You must provide a valid file type', 'eventin' ) );
        }
    }
}
