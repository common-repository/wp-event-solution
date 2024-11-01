<?php

namespace Etn\Core\Modules\Eventin_Ai\Admin;

defined( 'ABSPATH' ) || die();

class Admin {

	use \Etn\Traits\Singleton;

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'eventin_ai_admin_scripts' ] );
		add_action( 'admin_footer', [ $this, 'eventin_ai_modal' ] );
	}

	/**
	 * Add the modal to the admin footer
	 * 
	 * @return void
	 */
	public function eventin_ai_modal() {
		$screen    = get_current_screen();
		$screen_id = $screen->id;
		
		if ( 'toplevel_page_eventin' === $screen_id ) {
			include_once \Wpeventin::core_dir() . 'modules/eventin-ai/admin/view/modal.php';
		}
	}

	/**
	 * Enqueue the admin scripts
	 * 
	 * @return void
	 */
	public function eventin_ai_admin_scripts() {
		wp_enqueue_script( 'etn-ai-admin-js', \Wpeventin::core_url() . 'modules/eventin-ai/assets/js/admin.js', [ 'jquery', 'wp-hooks' ], \Wpeventin::version(), true );
		$eventin_ai_local_data = [
			'evnetin_ai_active'  => class_exists( 'EventinAI' ) ? true : false,
			'evnetin_pro_active' => class_exists( 'Wpeventin_Pro' ) ? true : false,
		];
		wp_localize_script( 'etn-ai-admin-js', 'eventin_ai_local_data', $eventin_ai_local_data );
	}
}
