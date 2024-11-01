<?php

namespace Etn\Core\Modules\Seat_Plan\Frontend\Views;

defined( 'ABSPATH' ) || die();

class Seatplan_Form {

	use \Etn\Traits\Singleton;

	/**
	 * Call js/css files
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'etn_after_single_event_details_rsvp_form', array( $this, 'seat_plan_form' ), 10 );
	}

	/**
	 * Enqueue scripts.
	 */
	public function seat_plan_form() {
		$errors = isset( $_GET['etn_errors'] ) ? $_GET['etn_errors'] : '';
		remove_query_arg( 'etn_errors', get_the_permalink(get_the_ID()) );
		$seats = get_post_meta( get_the_ID(),'seat_plan', true );

		// Early return if $seats is empty
		if (empty($seats)) {
			return;
		}
	 
		?>
		<form method="POST">
			<?php  wp_nonce_field('ticket_purchase_next_step_two','ticket_purchase_next_step_two'); ?>
			<?php if ( ! empty( $errors['seat_limit_error'] ) ): ?>
				<p style="text-align: center; color: red"><?php echo $errors['seat_limit_error'] ?></p>
			<?php endif; ?>
			<div class="wrap-seat-plan-form timetics-shortcode-wrapper">
				<div id="etn-seat-plan" data-id="<?php echo intval(get_the_ID()); ?>"></div>
				<input name="event_name" type="hidden" value="<?php echo esc_html(get_the_title(get_the_ID())); ?>"/>
			</div>
		</form>
		<?php
	}

}
