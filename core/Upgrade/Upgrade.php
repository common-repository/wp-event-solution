<?php
namespace Eventin\Upgrade;

use Eventin\Eventin;
use Eventin\Upgrade\Upgraders\UpdateInterface;
use Eventin\Upgrade\Upgraders\V_4_0_0;
use Eventin\Upgrade\Upgraders\V_4_0_2;
use Eventin\Upgrade\Upgraders\V_4_0_4;
use Eventin\Upgrade\Upgraders\V_4_0_6;
use Eventin\Upgrade\Upgraders\V_4_0_7;
use Eventin\Upgrade\Upgraders\V_4_0_8;
use Eventin\Upgrade\Upgraders\V_4_0_9;
use Wpeventin;

/**
 * Upgrade class
 *
 * @since 4.0.0
 */
class Upgrade {
    /**
     * Store all upgraders
     *
     * @var array
     */
    protected static $upgraders = [
        '4.0.0' => V_4_0_0::class,
        '4.0.2' => V_4_0_2::class,
        '4.0.4' => V_4_0_4::class,
        '4.0.6' => V_4_0_6::class,
        '4.0.7' => V_4_0_7::class,
        '4.0.8' => V_4_0_8::class,
        '4.0.9' => V_4_0_9::class,
    ];

    /**
     * Checks if upgrade is required or not
     *
     * @since 4.0.0
     *
     * @param bool $is_required
     *
     * @return bool
     */
    public static function is_upgrade_required( $is_required = false ) {
        $installed_version = get_option( 'etn_version' );
        $upgrade_versions  = array_keys( self::$upgraders );

        if ( $installed_version && version_compare( $installed_version, end( $upgrade_versions ), '<' ) ) {
            return true;
        }

        return $is_required;
    }

    /**
     * Registers services with the container.
     *
     * @return void
     * @throws \ReflectionException
     */
    public static function register() {
        $current_version = Wpeventin::version();

        foreach ( self::$upgraders as $version => $upgrader ) {
            if ( ! class_exists( $upgrader ) ) {
                continue;
            }

            if ( version_compare( $version, $current_version, '<=' ) ) {
                continue;
            }

            if ( is_subclass_of( $upgrader, UpdateInterface::class ) ) {
                $upgrader_object = new $upgrader();
                $upgrader_object->run();
            }
        }

        // if run the migration it will update the etn_is_migrated option.
        update_option( 'etn_is_migrated', true );
    }
}
