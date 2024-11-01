<?php
/**
 * Input Validation calss
 *
 * @package Eventin\Validation
 */
namespace Eventin\Validation;

use WP_Error;

class Validator {
    /**
     * Store validation data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Store validation rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Store aliases
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Store errors
     *
     * @var Object
     */
    protected $errors;

    /**
     * Initialize
     *
     * @param   array  $data
     *
     * @return  void
     */
    public function __construct( array $data ) {
        $this->data   = $data;
        $this->errors = new WP_Error();
    }

    /**
     * Set validation rules
     *
     * @param   array  $rules
     *
     * @return  void
     */
    public function set_rules( $rules ) {
        $this->rules = $rules;
    }

    /**
     * Set field validation aliases
     *
     * @param   array  $aliases
     *
     * @return  void
     */
    public function set_aliases( $aliases ) {
        $this->aliases = $aliases;
    }

    /**
     * Validate data
     *
     * @return  bool
     */
    public function validate() {
        foreach ( $this->rules as $field => $rules ) {
            foreach ( $this->resolve_rules( $rules ) as $rule ) {
                $this->validate_rule( $field, $rule );
            }
        }

        return ! $this->errors->has_errors();
    }

    /**
     * Get validation error
     *
     * @return  WP_Error
     */
    public function get_error() {
        return $this->errors;
    }

    /**
     * Validate a single rule
     *
     * @param void $field
     * @param Rule $rule
     * @return bool
     */
    protected function validate_rule( $field, $rule ) {
        if ( ! $rule->passes( $field, $this->get_field_value( $field ) ) ) {
            $this->errors->add( $field, $rule->message( $this->get_field_alias( $field ) ), ['status' => '400'] );
        }
    }

    /**
     * Resolve rules
     *
     * @param array $rules
     *
     * @return array
     */
    protected function resolve_rules( array $rules ) {
        return array_map( function ( $rule ) {
            if ( is_string( $rule ) ) {
                return $this->get_rule_from_string( $rule );
            }
            return $rule;
        }, $rules );
    }

    /**
     * Extract rules from string value
     *
     * @param   string | array  $rule
     *
     * @return  Rule
     */
    protected function get_rule_from_string( $rule ) {
        $exploaded = explode( ':', $rule );
        $rule      = $exploaded[0];
        $options   = explode( ',', end( $exploaded ) );

        return RuleMap::resolve_rule_map( $rule, $options );
    }

    /**
     * Get field value
     *
     * @param   string  $field
     *
     * @return  mixed
     */
    protected function get_field_value( $field ) {
        return $this->data[$field] ?? null;
    }

    /**
     * Get field alias
     *
     * @param   string  $field
     *
     * @return  mixed
     */
    protected function get_field_alias( $field ) {
        return $this->aliases[$field] ?? $field;
    }
}
