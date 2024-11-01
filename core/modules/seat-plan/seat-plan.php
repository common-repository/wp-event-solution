<?php

namespace Etn\Core\Modules\Seat_Plan;

defined( 'ABSPATH' ) || die();

class Seat_Plan {

	use \Etn\Traits\Singleton;

	public function init() {
		if ( is_admin() ) {		
			\Etn\Core\Modules\Seat_Plan\Admin\Admin::instance()->init();
		} else {
			\Etn\Core\Modules\Seat_Plan\Frontend\Frontend::instance()->init();
		}
	}

}
