<?php
namespace Eventin\Validation;

use Eventin\Validation\Rules\RequiredRule;

/**
 * Rule map class
 */
class RuleMap {
    /**
     * Store rule map
     *
     * @var array
     */
    protected static $map = [
        'required' => RequiredRule::class,
    ];

    /**
     * Resolve rules
     *
     * @param   string  $rule
     * @param   array  $options
     *
     * @return  Rule
     */
    public static function resolve_rule_map( $rule, $options ) {
        return new static::$map[$rule]( ...$options );
    }
}
