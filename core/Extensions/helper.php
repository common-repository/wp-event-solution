<?php

namespace Etn\Core\Addons;

defined( 'ABSPATH' ) || exit;

class Helper {

	use \Etn\Traits\Singleton;

	/**
	 * Check active module
	 */
	public function check_active_module( $modules_name = '' ) {
		$addons_options = get_option( 'etn_addons_options' );
		$enable_module  = false;
		switch ( $modules_name ) {
		case 'dokan':
			$enable_module = ( class_exists( 'WeDevs_Dokan' ) && class_exists( 'Woocommerce' ) &&
			( ! empty( $addons_options['dokan'] ) && $addons_options['dokan'] == "on" )  ) ? true : false;
			break;
		case 'buddyboss':
			$enable_module = is_plugin_active('buddyboss-platform/bp-loader.php') && ( ! empty( $addons_options['buddyboss'] ) && $addons_options['buddyboss'] == "on" ) ? true : false;
			break;
		case 'certificate_builder':
			$enable_module = ! empty( $addons_options['certificate_builder'] ) && $addons_options['certificate_builder'] == "on" ? true : false;
			break;
		case 'rsvp':
			$enable_module = ( empty( $addons_options['rsvp'] ) || $addons_options['rsvp'] == "on") ? true : false;
			break;
		case 'seat_map':
			$enable_module = ( class_exists('TimeticsPro') && ! empty( $addons_options['seat_map'] )  &&  $addons_options['seat_map'] == "on") ? true : false;
			break;
		case 'google_meet':
			$enable_module = ( empty( $addons_options['google_meet'] ) || $addons_options['google_meet'] == "on") ? true : false;
			break;
		case 'facebook_events':
			$enable_module = ( ( class_exists( 'Wpeventin_Pro' ) &&  class_exists( 'EtnFBAddon' ) ) && ( ! empty( $addons_options['facebook_events'] ) && $addons_options['facebook_events'] == "on" ) ) ? true : false;
			break;
		default:
			$enable_module = ( ! empty( $addons_options[$modules_name] ) && $addons_options[$modules_name] == "on" ) ? true : false;
			break;
		}
		return $enable_module;
	}

	/**
	 * Version checking for current version and provided version to check
	 *
	 * @param string $version_number
	 * @return void
	 */
	public function pro_version_checking( $version_number = '1.0.0' ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/eventin-pro/eventin-pro.php' );
		if ( ! ( version_compare( $plugin_data['Version'], $version_number, '<' ) ) ) {
			return true;
		} else {
			return false;
		}
		
	}
	
}