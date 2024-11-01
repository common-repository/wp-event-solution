<?php
/**
 * File Reader Interface
 * 
 * @package Eventin
 */
namespace Etn\Base\Importer;

/**
 * Reader Interface
 */
interface Reader_Interface {
    /**
     * Read file
     *
     * @return  array
     */
    public function read_file();
}
