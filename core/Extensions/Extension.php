<?php
/**
 * Extentions class
 * 
 * @package Eventin
 */
namespace Eventin\Extensions;

/**
 * Class extention
 */
class Extension {

    /**
     * Get all modules
     *
     * @return  array
     */
    public static function modules() {
        $extentions = self::get();

        return array_filter( $extentions, function( $extension ) {
            return $extension['type'] === 'module';
        } );
    }

    /**
     * Get all addons
     *
     * @return  array
     */
    public static function addons() {
        $extensions = self::get(); // Fixed the typo

        return array_values( array_filter($extensions, function($extension) {
            return $extension['type'] === 'addon';
        } ) );
    }

    /**
     * Get all extensions
     *
     * @return  array
     */
    public static function get() {
        $extensions = self::extensions();
        
        return array_map( function( $extension ) {
            $settings = get_option( 'etn_addons_options', [] );
            if ( isset( $settings[ $extension['name'] ] ) && $settings[ $extension['name']] === 'on' ) {
                $extension['status'] = 'on';
    
                if ( self::dependencies_resolved( $extension['name'] ) ) {
                    $extension['notice'] = false;
                }
            }
            return $extension;
            
        }, $extensions );
    }

    /**
     * Find extension by name
     *
     * @return  array
     */
    public static function find( $name ) {
        $extensions = self::extensions();
        
        if ( array_key_exists( $name, $extensions ) ) {
            return $extensions[$name];  
        }

        return null;
    }

    /**
     * Update extension status
     *
     * @param   string  $name
     *
     * @return  bool
     */
    public static function update( $name, $status ) {
        $extension = self::find( $name );

        if ( ! $extension ) {
            return false;
        }

        $settings = self::get_settings();

        $settings[$name] = $status;

        $slug = ! empty( $extension['slug'] ) ? $extension['slug'] : '';

        if ( 'addon' === $extension['type'] && 'on' === $status ) {
            if ( ! $slug ) {
                return false;
            }

            if ( ! PluginManager::is_installed( $slug ) ) {
                PluginManager::install_plugin( $slug );
            }

            if ( ! PluginManager::is_activated( $slug ) ) {
                PluginManager::activate_plugin( $slug );
            }
        }

        if ( 'addon' === $extension['type'] && 'off' === $status ) {

            if ( ! $slug ) {
                return false;
            }

            if ( PluginManager::is_activated( $slug ) ) {
                PluginManager::deactivate_plugin( $slug );
            }
        }

        update_option( 'etn_addons_options', $settings );

        return true;
    }

    /**
     * Get settings
     *
     * @return  array
     */
    public static function get_settings() {
        $settings = get_option( 'etn_addons_options', [] );

        return $settings;
    }

    /**
     * Check if an extension's dependencies are resolved.
     *
     * @param string $extension_name Name of the extension.
     * @return bool True if all dependencies are resolved, false otherwise.
     */
    public static function dependencies_resolved( $extension_name ) {
        $depencies = self::get_depencies( $extension_name );

        if ( ! $depencies ) {
            return true;
        }

        foreach ( $depencies as $dependency ) {
            if ( ! PluginManager::is_activated( $dependency ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get dependencies
     *
     * @param   string  $extension_name  [$extension_name description]
     *
     * @return array
     */
    public static function get_depencies( $extension_name ) {
        $extension = self::find( $extension_name );

        if ( ! $extension ) {
            return null;
        }

        if ( empty( $extension['deps'] ) ) {
            return null;
        }

        return $extension['deps'];
    }

    /**
     * Get dependencies
     *
     * @param   string  $extension_name  [$extension_name description]
     *
     * @return array
     */
    public static function get_depency_names( $extension_name ) {
        $depencies = self::get_depencies( $extension_name );
        
        $names = [];

        if ( is_array( $depencies ) ) {
            foreach( $depencies as $dependency ) {
                $names[] = PluginManager::get_plugin_name_by_slug( $dependency );
            }
        }

        return $names;
    }

    /**
     * Get dependencies
     *
     * @param   string  $extension_name  [$extension_name description]
     *
     * @return string
     */
    public static function get_depency_string( $extension_name ) {
        $depencies = self::get_depency_names( $extension_name );
        
        return implode( ',', $depencies );
    }
    
    /**
     * List of all extensions
     *
     * @return  array
     */
    private static function extensions() {
        return [
            'dokan' => [
                'name'          => 'dokan',
                'type'          => 'module',
                'status'        => 'off',
                'is_pro'        => true,
                'deps'          => ['dokan-lite'],
                'title'         => __( 'Dokan', 'eventin' ),
                'description'   => __( 'It allows you to create a Multivendor Event marketplace and make commission for each sale.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'dokan' ),
                'notice'        => __( 'NB: Need to active Dokan plugin', 'eventin' ),
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => '',
                'doc_link'      => '',
            ],
            'buddyboss' => [
                'name'          => 'buddyboss',
                'type'          => 'module',
                'status'        => 'off',
                'is_pro'        => true,
                'deps'          => ['buddypress'],
                'title'         => __( 'BuddyBoss', 'eventin' ),
                'description'   => __( 'It allows you to create and manage events and sell tickets inside the BuddyBoss theme.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'buddyboss' ),
                'notice'        => __( 'NB: Need to active BuddyBoss plugin', 'eventin' ),
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => '',
                'doc_link'      => '',
            ],
            'certificate_builder' => [
                'name'          => 'certificate_builder',
                'type'          => 'module',
                'status'        => 'off',
                'is_pro'        => true,
                'title'         => __( 'Certificate Builder', 'eventin' ),
                'description'   => __( 'You can design and send a PDF certificate for the event attendee.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'certificate_builder' ),
                'notice'        => __( 'NB: Need to active Dokan plugin', 'eventin' ),
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => admin_url( 'admin.php?page=eventin#/settings/event-settings/attendees' ),
                'doc_link'      => '',
            ],
            'rsvp' => [
                'name'          => 'rsvp',
                'type'          => 'module',
                'status'        => 'off',
                'is_pro'        => true,
                'title'         => __( 'RSVP Module', 'eventin' ),
                'description'   => __( 'It allows you to add RSVP at your upcoming events and grab user\'s attention easily.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'rsvp' ),
                'notice'        => __( 'NB: Need to active Dokan plugin', 'eventin' ),
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => admin_url( 'admin.php?page=eventin#/settings/email/purchase-email' ),
                'doc_link'      => '',
            ],
            'google_meet' => [
                'name'          => 'google_meet',
                'type'          => 'module',
                'status'        => 'off',
                'is_pro'        => true,
                'title'         => __( 'Google Meet', 'eventin' ),
                'description'   => __( 'Use Google Meet to host your meetings and manage virtual events from your dashboard.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'google_meet' ),
                'notice'        => '',
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => admin_url( 'admin.php?page=eventin#/settings/integrations/google-meet' ),
                'doc_link'      => '',
            ],
            'facebook_events' => [
                'name'          => 'facebook_events',
                'type'          => 'module',
                'status'        => 'off',
                'is_pro'        => true,
                'title'         => __( 'Facebook Event', 'eventin' ),
                'description'   => __( 'It allows you to import events from Facebook easily. And you can show it in different place on your website.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'facebook_events' ),
                'notice'        => __( 'NB: Need to active Eventin Facebook plugin', 'eventin' ),
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => '',
                'doc_link'      => '',
            ],
            'seat_map' => [
                'name'          => 'seat_map',
                'type'          => 'module',
                'status'        => 'off',
                'is_pro'        => false,
                'deps'          => ['timetics-pro'],
                'title'         => __( 'Seat Map', 'eventin' ),
                'description'   => __( 'With the features, you can now add a visual seat plan with different ticket pricing for events.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'seat_map' ),
                'notice'        => __( 'NB: Need to active Eventin Facebook plugin', 'eventin' ),
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => '',
                'doc_link'      => '',
            ],
            'eventin-divi-addon' => [
                'name'          => 'eventin-divi-addon',
                'type'          => 'addon',
                'status'        => 'off',
                'slug'          => 'eventin-divi-addon',
                'title'         => __( 'Eventin Divi Addon', 'eventin' ),
                'description'   => __( 'It enable the Eventin featured and module inside DIVI editing panel.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'eventin-divi-addon' ),
                'notice'        => '',
                'demo_link'     => 'https://product.themewinter.com/eventin/',
                'settings_link' => '',
                'doc_link'      => '',
            ],
            'eventin-bricks-builder' => [
                'name'          => 'eventin-bricks-builder',
                'type'          => 'addon',
                'status'        => 'off',
                'title'         => __( 'Eventin Bricks Addon', 'eventin' ),
                'description'   => __( 'It\'s enable the Eventin featured and module inside Bricks editing panel.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'eventin-bricks-builder' ),
                'notice'        => '',
                'demo_link'     => 'https://support.themewinter.com/docs/plugins/plugin-docs/integration/bricks-builder-integration/',
                'settings_link' => '',
                'doc_link'      => '',
            ],
            'eventin-oxygen-addon' => [
                'name'          => 'eventin-oxygen-addon',
                'type'          => 'addon',
                'status'        => 'off',
                'title'         => __( 'Eventin Oxygen Addon', 'eventin' ),
                'description'   => __( 'It\'s enable the Eventin featured and module inside Oxygen editing panel.', 'eventin' ),
                'icon'          => ExtensionIcon::get( 'eventin-oxygen-addon' ),
                'notice'        => '',
                'demo_link'     => 'https://support.themewinter.com/docs/plugins/plugin-docs/integration/oxygen-builder-integration-pro',
                'settings_link' => '',
                'doc_link'      => '',
            ],
        ];
    }
}
