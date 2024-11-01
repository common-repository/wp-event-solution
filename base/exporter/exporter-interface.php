<?php
/**
 * Exporter Interface
 * 
 * @package Eventin
 */
namespace Etn\Base\Exporter;

/**
 * Exporter interface
 */
interface Exporter_Interface {
    /**
     * Export data
     *
     * @return void
     */
    public function export( $data, $columns = [], $file_name = '' );
}
