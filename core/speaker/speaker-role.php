<?php
/**
 * Register Custom Role
 *
 * @package Eventin
 */
namespace Eventin\Base;

use Etn\Base\Role;
use Etn\Traits\Singleton;

/**
 * Class Role
 */
class Speaker_role extends Role {
    use Singleton;

    /**
     * Initialize
     *
     * @return void
     */
    public function init() {
        $this->register_role();
        $this->add_cap();
    }

    /**
     * Register role for staff
     *
     * @return void
     */
    public function register_role() {
        $roles = $this->get_role();

        foreach ( $roles as $role ) {
            add_role(
                $role['name'],
                $role['display_name'],
                $role['capabilities']
            );
        }
    }

    /**
     * Get roles
     *
     * @return  array
     */
    public function get_role() {
        $role = [
            [
                'name'         => 'etn-speaker',
                'display_name' => esc_html__( 'Eventin Speaker', 'eventin' ),
                'capabilities' => [
                    'read'                   => true,
                    'delete_posts'           => true,
                    'delete_published_posts' => true,
                    'edit_posts'             => true,
                    'publish_posts'          => true,
                    'upload_files'           => true,
                    'edit_pages'             => true,
                    'edit_published_pages'   => true,
                    'publish_pages'          => true,
                    'delete_published_pages' => false,
                    'read_booking'           => true,
                    'read_meeting'           => true,
                    'edit_profile'           => true,
                    'manage_eventin'         => true,

                ],
            ], 
            [
                'name'         => 'etn-organizer',
                'display_name' => esc_html__( 'Eventin Organizer', 'eventin' ),
                'capabilities' => [
                    'read'                   => true,
                    'delete_posts'           => true,
                    'delete_published_posts' => true,
                    'edit_posts'             => true,
                    'publish_posts'          => true,
                    'upload_files'           => true,
                    'edit_pages'             => true,
                    'edit_published_pages'   => true,
                    'publish_pages'          => true,
                    'delete_published_pages' => false,
                    'read_booking'           => true,
                    'read_meeting'           => true,
                    'edit_profile'           => true,
                    'manage_eventin'         => true,

                ],
            ]
        ];

        return apply_filters( 'eventin_speaker_role', $role );
    }

    /**
     * Add capabilites to a role
     *
     * @return void
     */
    public function add_cap() {
        global $wp_roles;

        $capabilities = $this->get_capabilities();
        $wp_roles->add_cap( 'administrator', 'manage_eventin' );

        foreach ( $capabilities as $cap_group ) {
            foreach ( $cap_group as $cap ) {
                $wp_roles->add_cap( 'administrator', $cap );
            }
        }
    }

    /**
     * Get capabilities
     *
     * @return  array
     */
    public function get_capabilities() {
        $capability_types = ['speaker', 'organizer'];

        $capabilites = [];

        foreach ( $capability_types as $capability_type ) {
            $capabilites[$capability_type] = [
                // Post type.
                "edit_{$capability_type}",
                "read_{$capability_type}",
                "delete_{$capability_type}",
                "edit_{$capability_type}s",
                "edit_others_{$capability_type}s",
                "publish_{$capability_type}s",
                "read_private_{$capability_type}s",
                "delete_{$capability_type}s",
                "delete_private_{$capability_type}s",
                "delete_published_{$capability_type}s",
                "delete_others_{$capability_type}s",
                "edit_private_{$capability_type}s",
            ];
        }

        return $capabilites;
    }

}