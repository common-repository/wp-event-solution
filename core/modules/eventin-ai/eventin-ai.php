<?php

namespace Etn\Core\Modules\Eventin_Ai;


defined( 'ABSPATH' ) || die();

class Eventin_AI {

	use \Etn\Traits\Singleton;

    /**
     * Initialize the class
     *
     * @return void
     */
	public function init() {
		if ( is_admin() ) {
			\Etn\Core\Modules\Eventin_Ai\Admin\Admin::instance()->init();
		}
	}
}
