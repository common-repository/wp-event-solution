<?php

namespace Etn\Core\Modules\Seat_Plan\Frontend;
use Etn\Utils\Helper;

defined( 'ABSPATH' ) || die();

class Frontend {

	use \Etn\Traits\Singleton;

	public function init() {

		// enqueue scripts
		$this->enqueue_scripts();

		\Etn\Core\Modules\Seat_Plan\Frontend\Views\Seatplan_Form::instance()->init();
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		add_action( 'wp_enqueue_scripts', array( $this, 'js_css_front_end' ) );
	}

	/**
	 *  Front-end scripts.
	 */
	public function js_css_front_end() {
		$screen = get_current_screen();
		// Main script of seat plan script and js
 	}

	/**
	 * Adding meta to cart
	 *
	 * @param Type|null $var
	 * @return void
	 */
	public function adding_meta_cart() {
		// test
	}

	/**
	 * Adding meta to order
	 *
	 * @param Type|null $var
	 * @return void
	 */
	public function adding_meta_order() {

	}

}
