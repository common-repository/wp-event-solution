<?php
namespace Eventin\Validation\Rules;

/**
 * Rule interface
 */
interface Rule {
    /**
     * Check the validation rule passed or not
     *
     * @param string $field
     *
     * @param string $value
     *
     * @return bool
     */
    public function passes( $field, $value );

    /**
     * Set message for validation
     *
     * @param string $field
     *
     * @return string
     */
    public function message( $field );
}
