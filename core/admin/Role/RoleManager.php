<?php
namespace Eventin\Admin\Role;
use Eventin\Interfaces\HookableInterface;

/**
 * Role Manager class
 * 
 * @package Eventin
 */

class RoleManager implements HookableInterface {
    /**
     * Store all roles
     *
     * @var array
     */
    private $roles = [
        CustomerRole::class,
    ];

    /**
     * Register all hooks
     *
     * @return  void 
     */
    public function register_hooks(): void {
        add_action( 'admin_init', [ $this, 'register_role' ] );
    }

    /**
     * Register all roles
     *
     * @return  void
     */
    public function register_role() {
        foreach ( $this->roles as $role ) {

            $role_object = new $role();
            $role_name   = $role_object->get_name();
            if ( ! $this->is_role_exist( $role_name ) ) {
                add_role(
                    $role_object->get_name(),
                    $role_object->get_display_name(),
                    $role_object->get_capabilities(),
                );
            }
        }
    }

    /**
     * Check a role is exist or not
     *
     * @param   string  $role_name Role name, that is need to check
     *
     * @return  bool
     */
    private function is_role_exist( $role_name ) {
        $role = get_role( $role_name );

        if ( $role ) {
            return true;
        }

        return false;
    }
}
