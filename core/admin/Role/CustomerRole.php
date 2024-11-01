<?php
/**
 * Custoer Role Class
 */
namespace Eventin\Admin\Role;

/**
 * Customer role class
 */
class CustomerRole implements RoleInterfacace {
    /**
     * Get role name
     *
     * @return  string
     */
    public function get_name() {
        return 'etn-customer';
    }

    /**
     * Get role display name
     *
     * @return  string
     */
    public function get_display_name() {
        return __( 'Eventin Customer', 'eventin' );
    }

    /**
     * Get all capabilities a role
     *
     * @return  array Customer role capabilities
     */
    public function get_capabilities() {
        return [
            'read' => true,
        ];
    }
}
