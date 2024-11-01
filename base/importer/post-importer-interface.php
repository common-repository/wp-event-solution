<?php
/**
 * Post Importer Interface
 * 
 * @package Eventin
 */
namespace Etn\Base\Importer;

/**
 * Post Importer Interface
 */
interface Post_Importer_Interface {
    /**
     * Import file
     *
     * @return  void
     */
    public function import( $file );
}
