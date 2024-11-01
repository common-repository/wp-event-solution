<?php

namespace Etn\Core\Modules\Seat_Plan\Admin;

defined( 'ABSPATH' ) || die();

class Metaboxes {

	use \Etn\Traits\Singleton;

	/**
	 * Call hooks
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'eventin/event_single_meta_tab', [$this, 'add_tab_item'], 10, 1 );
		add_filter( 'etn_event_meta_fields_after_default', [$this, 'add_seat_planning_meta'], 10, 1 );
	}

	/**
	 * Add Seat Plan tab
	 *
	 * @param Type|null $var
	 * @return void
	 */
	public function add_tab_item( $tab_items ) {
		$seat_plan_tab = array(
			'name' => esc_html__( 'Visual Seat Map', 'eventin' ),
			'id'   => 'visual_seat_map',
			'icon' => '<svg width="14" height="13" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path d="M64 448c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L178.8 256L41.38 118.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l160 160c12.5 12.5 12.5 32.75 0 45.25l-160 160C80.38 444.9 72.19 448 64 448z"></path></svg>',
		);

		array_push( $tab_items, $seat_plan_tab );

		return $tab_items;
	}

	public function add_seat_planning_meta( $default_fields ) {
		$seat_plan_link = "#";
		if ( class_exists('TimeticsPro') &&
		 	get_post_type() == 'etn' &&
		 	isset($_GET['action'])  &&
			$_GET['action'] === 'edit' ) {
				$seat_plan_link = admin_url() ."?page=timetics#/seats/".get_the_ID()."/create?eventin=true";
		}

		$default_fields['seat_plan_module_enable'] = [
			'label'        => esc_html__( 'Enable Seat Plan?', 'eventin' ),
			'desc'         => esc_html__( 'Do you want to enable seat plan for this event?', 'eventin' ),
			'type'         => 'checkbox',
			'left_choice'  => 'no',
			'right_choice' => 'yes',
			'attr'         => ['class' => 'etn-label-item', 'tab' => 'visual_seat_map'],
			'conditional'  => true,
			'condition-id' => 'enable_seat_plan',
		];

		$default_fields['enable_seat_plan'] = [
			'label' => esc_html__( 'Create Visual Seat Plan', 'eventin' ),
			'desc'  => esc_html__( 'Enable and save the event to get the visual seat plan option', 'eventin' ),
			'type'  => 'button',
			'text'  => esc_html__( 'Go to Canvas', 'eventin' ),
			'url'   => $seat_plan_link,
			'attr'  => ['class' => 'conditional-item etn-label-item', 'button_class'=> 'go_to_canvas', 'tab' => 'visual_seat_map'],
		];


		return $default_fields;
	}

}