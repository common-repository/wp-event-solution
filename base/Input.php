<?php
/**
 * Input class
 * 
 * @package Eventin
 */
namespace Eventin;

/**
 * Input class
 */
class Input {
    /**
     * Store data
     *
     * @var array
     */
    private $data;
    /**
     * Constructor for input class
     *
     * @return  void
     */
    public function __construct( $data = [] ) {
        $this->data = $data;
    }

    /**
     * Check input is empty or not default blank
     *
     * @return  mixed
     */
    public function get( $key, $defaul = null ) {
        $data = ! empty( $this->data[$key] ) ? $this->data[$key] : $defaul;

        return $data;
    }
}
