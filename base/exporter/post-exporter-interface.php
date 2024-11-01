<?php
/**
 * Post Exporter Interface
 * 
 * @package Eventin
 */
namespace Etn\Base\Exporter;

/**
 * Post exporter interface
 */
interface Post_Exporter_Interface {
    /**
     * Export data
     *
     * @param   array  $data
     * @param   string  $format
     *
     * @return void
     */
    public function export( $data, $format );
}
