<?php
/**
 * RoleInterfacace 
 * 
 * @package Eventin
 */
namespace Eventin\Admin\Role;

/**
 * Role interface
 */
interface RoleInterfacace {
    /**
     * Get role name
     *
     * @return  string
     */
    public function get_name();

    /**
     * Get role display name
     *
     * @return  string
     */
    public function get_display_name();

    /**
     * Get all capabilities for a role
     *
     * @return  array
     */
    public function get_capabilities();
}
