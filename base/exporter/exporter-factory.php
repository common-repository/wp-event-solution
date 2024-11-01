<?php
/**
 * Exporter Class
 * 
 * @package Eventin
 */
namespace Etn\Base\Exporter;

use Exception;

/**
 * Class Exporter
 */
class Exporter_Factory {
    /**
     * Get exporter method
     *
     * @return  \Exporter_Interface
     */
    public static function get_exporter( $format ) {
        switch( $format ) {
            case 'csv':
                return new CSV_Exporter();

            case 'json':
                return new Json_Exporter();
            
            default:
                throw new Exception( __( 'Unknown format', 'eventin' ) );
        }
    }
}
