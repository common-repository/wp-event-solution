<?php

namespace Etn\Core\Modules\Seat_Plan\Admin;

defined( 'ABSPATH' ) || die();

class Admin {

	use \Etn\Traits\Singleton;

	/**
	 * Call js/css files
	 *
	 * @return void
	 */
	public function init() {
		// enqueue scripts
		$this->enqueue_scripts();

		\Etn\Core\Modules\Seat_Plan\Admin\Metaboxes::instance()->init();
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		add_action( 'admin_enqueue_scripts', array( $this, 'js_css_admin' ) );
	}

	/**
	 *  Admin scripts.
	 */
	public function js_css_admin() {
		$screen = get_current_screen();

		// Main script of seat plan
		if ( "etn" === $screen->id ) {
			wp_enqueue_script( 'etn-seat-plan-admin-js', \Wpeventin::core_url() . 'modules/seat-plan/assets/js/admin.js', ['jquery'], \Wpeventin::version(), false );
		}

		if( 'shop_order' === $screen->id ) {
			wp_enqueue_style( 'etn-order-style', \Wpeventin::core_url() . 'modules/seat-plan/assets/css/admin.css', [], \Wpeventin::version(), false);
		}
	}
}
