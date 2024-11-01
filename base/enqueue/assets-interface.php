<?php
/**
 * Assets interface
 * 
 * @package Eventin
 */
namespace Etn\Base\Enqueue;

/**
 * Assets interface
 */
interface AssetsInterface {
    /**
     * Get scripts for register
     *
     * @return  array
     */
    public static function get_scripts();

    /**
     * Get styles for register
     *
     * @return  array
     */
    public static function get_styles();
}
