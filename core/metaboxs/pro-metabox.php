<?php

namespace Etn\Core\Metaboxs;

use Etn\Utils\Helper;
use Etn\Core\Metaboxs\Event_manager_metabox;
use Wpeventin;

defined( 'ABSPATH' ) || exit;

class Pro_metabox extends Event_manager_metabox {

	use \Etn\Traits\Singleton;

	public $event_fields = [];
	public $cpt_id = 'etn';

	/**
	 * Call all hooks
	 */
	public function init() {
		add_filter("etn_event_fields", [$this, "update_event_meta"]);
		add_filter("etn/metaboxs/etn_metaboxs", [$this, "register_meta_boxes"]);
		add_filter("etn/banner_fields/etn_metaboxs", [$this, "update_banner_meta"]);
		add_filter("etn_event_fields", [$this, "locations_meta_box"]);
		add_filter("etn_event_meta_fields_after_default", [$this, "update_event_general_meta"]);

		// RSVP Metabox 
		add_filter( "etn/metaboxs/etn_metaboxs", [$this, "register_rsvp_meta_boxes"] );
		add_filter( "etn/metabox/tab", [$this, "register_rsvp_meta_boxes_tab"] );

		if( class_exists( 'Woocommerce' ) ){
            add_filter( "etn_event_meta_fields_after_ticket", [$this, "event_min_max_meta"] );
            add_action( 'etn_before_add_to_cart_total_price', [$this, 'show_min_max_in_event_form'], 9, 3 );
        }

		add_filter( "etn_event_meta_fields_google_meet", [$this, "etn_google_meet_meta_fields"] );

		add_filter( "etn_speaker_fields", [$this, "update_speaker_meta"] );


	}

	/**
	 * Add metaboxes
	 */
	public function register_meta_boxes($existing_boxes) {

		unset($existing_boxes['etn_report']);

		return $existing_boxes;
	}

	/**
	 * Add event speaker metaboxes
	 */
	public function update_event_general_meta($metabox_fields) {
		$metabox_fields['etn_event_speaker'] = [
			'label'    => esc_html__('Event Speakers', 'eventin'),
			'desc'     => esc_html__('Select the category which will be used as speaker.', 'eventin'),
			'type'     => 'select_single',
			'options'  => Helper::get_orgs(),
			'priority' => 1,
			'required' => true,
			'attr'     => ['class' => 'etn-label-item etn-label-top', 'tab' => 'general_settings'],
			'warning'       => esc_html__('Create Speaker', 'eventin'),
			'warning_url'   => admin_url( 'edit.php?post_type=etn-speaker' ),
			'pro'			=> 'yes',
		];
	
		return $metabox_fields;
	}

	/**
	 * Add extra field to event form
	 *
	 */
	public function update_event_meta($metabox_fields) {

		$metabox_fields["fluent_crm"] = [
			'label'        => esc_html__('Integrate fluent CRM', 'eventin'),
			'desc'         => esc_html__('Enable Fluent CRM integration with this event.', 'eventin'),
			'type'         => 'checkbox',
			'left_choice'  => 'yes',
			'right_choice' => 'no',
			'attr'         => ['class' => 'etn-label-item etn-enable-fluent-crm', 'tab' => 'crm'],
			'conditional'  => true,
			'condition-id' => 'fluent_crm_webhook',
			'pro'		   => 'yes'
		];

		$metabox_fields["fluent_crm_webhook"] = [
			'label'         => esc_html__('Fluent Webhook', 'eventin'),
			'desc'          => esc_html__('Enter fluent web hook here to integrate fluent CRM with this event.', 'eventin'),
			'type'          => 'text',
			'default'       => '',
			'value'         => '',
			'priority'      => 1,
			'placeholder'   => esc_html__('Enter URL', 'eventin'),
			'required'      => true,
			'attr'          => ['class' => 'etn-label-item conditional-item', 'tab' => 'crm'],
			'tooltip_title' => '',
			'tooltip_desc'  => '',
			'pro'			=> 'yes'
		];

		if(!empty(\Etn\Utils\Helper::get_option("etn_groundhogg_api"))) {
			$metabox_fields["groundhogg_tags"] = [
				'label'       => esc_html__('Groundhogg Tags', 'eventin'),
				'desc'        => esc_html__('Enter groundhogg tags(seperate by comma for multiple)', 'eventin'),
				'type'        => 'text',
				'default'     => '',
				'value'       => '',
				'priority'    => 1,
				'placeholder' => 'tag1,tag2,tag3',
				'required'    => false,
				'attr'        => ['class' => 'etn-label-item', 'tab' => 'crm'],
				'pro'         => 'yes'
			];
		}

		if(!empty(\Etn\Utils\Helper::get_option("attendee_registration"))) {

			$metabox_fields["attende_page_link"] = [
				'label'         => esc_html__('Attendee Page URL', 'eventin'),
				'desc'          => esc_html__('Page link where the details of the attendees of this event is located.', 'eventin'),
				'type'          => 'text',
				'default'       => '',
				'value'         => '',
				'priority'      => 1,
				'required'      => true,
				'placeholder'   => esc_html__('Enter Attendee Page URL', 'eventin'),
				'attr'          => ['class' => 'etn-label-item', 'tab' => 'miscellaneous'],
				'tooltip_title' => '',
				'tooltip_desc'  => '',
				'pro'  			=> 'yes'
			];

			$metabox_fields["attendee_extra_fields"] = [
				'label'         => esc_html__('Extra Fields', 'eventin'),
				'desc'          => esc_html__('Page link where the details of the attendees of this event is located.', 'eventin'),
				'type'          => 'markup',
				'file'       	=> \Wpeventin::core_dir() . '/metaboxs/views/fields/extra-fields.php',
				'attr'          => ['class' => 'etn-label-item', 'tab' => 'extra-fields'],
				'tooltip_title' => '',
				'tooltip_desc'  => '',
				'pro'  			=> 'yes'
			];
		}


		$metabox_fields['etn_event_logo'] = [
			'label'    => esc_html__('Event logo', 'eventin'),
			'type'     => 'upload',
			'multiple' => true,
			'default'  => '',
			'value'    => '',
			'desc'     => esc_html__('Event logo will be shown on single page', "eventin"),
			'priority' => 1,
			'required' => false,
			'attr'     => ['class' => ' banner etn-label-item', 'tab' => 'miscellaneous'],
			'pro'      => 'yes'

		];

		$metabox_fields['etn_event_calendar_bg'] = [
			'label'         => esc_html__('Background Color For Calendar', 'eventin'),
			'desc'          => esc_html__('This color will be used as the background on calendar module', "eventin"),
			'type'          => 'text',
			'default-color' => '#FF55FF',
			'attr'          => ['class' => ' etn-label-item', 'tab' => 'miscellaneous'],
			'tooltip_title' => '',
			'tooltip_desc'  => '',
			'pro'  			=> 'yes'
		];

		$metabox_fields['etn_event_calendar_text_color'] = [
			'label'         => esc_html__('Text Color For Calendar', 'eventin'),
			'desc'          => esc_html__('This color will be used as the text color on calendar module', "eventin"),
			'type'          => 'text',
			'default-color' => '#000000',
			'attr'          => ['class' => ' etn-label-item', 'tab' => 'miscellaneous'],
			'tooltip_title' => '',
			'tooltip_desc'  => '',
			'pro' 	 		=> 'yes'
		];
		$metabox_fields['etn_event_certificate'] = [
			'label'    => esc_html__('Select Certificate Template', 'eventin'),
			'desc'     => esc_html__('Select the page template which will be used as event certificate.', 'eventin'),
			'type'     => 'select_single',
			'options'  => Helper::get_pages(),
			'priority' => 1,
			'required' => true,
			'attr'     => ['class' => 'etn-label-item etn-label-top ', 'tab' => 'miscellaneous'],
			'warning'       => esc_html__('Create Certificate Template', 'eventin'),
			'warning_url'   => admin_url( 'post-new.php?post_type=page' ),
			'pro' 	 		=> 'yes'
		];
 
		$metabox_fields["event_external_link"] = [
			'label'         => esc_html__('Event External Link', 'eventin'),
			'desc'          => esc_html__('An external link where the event details will redirect', 'eventin'),
			'type'          => 'text',
			'default'       => '',
			'value'         => '',
			'priority'      => 1,
			'required'      => true,
			'placeholder'   => esc_html__('Enter External link', 'eventin'),
			'attr'          => ['class' => 'etn-label-item', 'tab' => 'miscellaneous'],
			'tooltip_title' => '',
			'tooltip_desc'  => '',
			'pro' 	 		=> 'yes'
		];

		
		$metabox_fields['etn_event_faq'] = [
			'label'            => esc_html__('Event FAQ\'s', 'eventin'),
			'type'             => 'repeater',
			'default'          => '',
			'value'            => '',
			'walkthrough_desc' => Helper::kses(esc_html__('Checkout Walkthrough Video ', 'eventin'). '<a href="https://www.youtube.com/watch?v=mwJzkXh8nT0&t=3s" target="_blank">'. esc_html__('Documentation', 'eventin').'</a>', 'eventin'),
			'options'          => [
				'etn_faq_title'   => [
					'label'       => esc_html__('FAQ Title', 'eventin'),
					'type'        => 'text',
					'default'     => '',
					'value'       => '',
					'desc'        => '',
					'priority'    => 1,
					'placeholder' => esc_html__('Title Here', 'eventin'),
					'attr'        => ['class' => ''],
					'required'    => true,
				],
				'etn_faq_content' => [
					'label'       => esc_html__('FAQ Content', 'eventin'),
					'type'        => 'textarea',
					'default'     => '',
					'value'       => '',
					'desc'        => '',
					'attr'        => [
						'class' => 'schedule',
						'row'   => 14,
						'col'   => 50,
					],
					'placeholder' => esc_html__('FAQ Content Here', 'eventin'),
					'required'    => true,
				],
			],
			'desc'             => esc_html__('Add all frequently asked questions here', "eventin"),
			'attr'             => ['class' => '', 'tab' => 'faq'],
			'priority'         => 1,
			'required'         => true,
			'pro'         	   => 'yes'
		];

		
		return $metabox_fields;
	}

	/**
	 * Add extra field to banner form
	 *
	 */
	public function update_banner_meta($metabox_fields) {
		$metabox_fields['etn_banner'] = [
			'label'        => esc_html__('Display Banner', 'eventin'),
			'desc'         => esc_html__('Place banner to event page. Banner will be displayed in Event template 2 and template 3.', 'eventin'),
			'type'         => 'checkbox',
			'left_choice'  => 'Show',
			'right_choice' => 'Hide',
			'attr'         => ['class' => 'etn-label-item etn-label-banner', 'tab' => 'banner'],
			'pro'          => 'yes'
		];

		$metabox_fields['banner_bg_type']  = [
			'label'        => esc_html__('Background type', 'eventin'),
			'desc'         => esc_html__('Choose background type text or image', 'eventin'),
			'type'         => 'checkbox',
			'left_choice'  => 'Color',
			'right_choice' => 'Image',
			'attr'         => ['class' => 'etn-label-item banner_bg_type', 'tab' => 'banner'],
		];
		$metabox_fields['banner_bg_color'] = [
			'label'         => esc_html__('Background color', 'eventin'),
			'desc'          => esc_html__('Choose background color of banner', 'eventin'),
			'type'          => 'text',
			'default-color' => '#FF55FF',
			'attr'          => ['class' => 'etn-label-item banner_bg_color', 'tab' => 'banner'],
		];
		$metabox_fields['banner_bg_image'] = [
			'label' => esc_html__('Background image', 'eventin'),
			'desc'  => esc_html__('Choose background image of banner', 'eventin'),
			'type'  => 'upload',
			'attr'  => ['class' => 'etn-label-item', 'tab' => 'banner'],
		];

		return $metabox_fields;
	}

	/**
	 * Add location fields in single event venue/location tab
	 *
	 */
	public function locations_meta_box($metabox_fields) {

		if(get_post_meta(get_the_ID(), "etn_event_location_type", true) == 'new_location') {
			$class  = 'etn-existing-items-hide';
			$class2 = 'etn-existing-items-show';
		} else {
			$class  = '';
			$class2 = '';
		}

		$metabox_fields['etn_event_location_type'] = [
			'label'       => esc_html__('Location Type', 'eventin'),
			'desc'        => esc_html__('Select locations type', 'eventin'),
			'placeholder' => esc_html__('Select locations type', 'eventin'),
			'type'        => 'select_single',
			'options'     => [
				'existing_location' => esc_html__('Enter Full Address', 'eventin'),
				'new_location'      => esc_html__('Existing Locations', 'eventin')
			],
			'priority'    => 1,
			'required'    => true,
			'attr'        => ['class' => 'etn-label-item etn-label-top', 'tab' => 'locations'],
			'pro'		  => 'yes'
		];

		$metabox_fields['etn_event_location'] = [
			'label'         => esc_html__('Event Location', 'eventin'),
			'desc'          => esc_html__('Place event location', 'eventin'),
			'placeholder'   => esc_html__('Place event location', 'eventin'),
			'type'          => 'text',
			'priority'      => 1,
			'required'      => true,
			'attr'          => ['class' => 'etn-label-item etn-existing-items ' . $class, 'tab' => 'locations'],
			'tooltip_title' => '',
			'tooltip_desc'  => '',
		];

		if(class_exists( 'Wpeventin_Pro' )){
			$metabox_fields['etn_event_location_list'] = [
				'label'       => esc_html__('Event Location', 'eventin'),
				'desc'        => esc_html__('Select locations', 'eventin'),
				'placeholder' => esc_html__('Select locations', 'eventin'),
				'type'        => 'select2',
				'options'     => \Etn_Pro\Utils\Helper::get_location_data('', 'yes'),
				'options'     => '',
				'priority'    => 1,
				'required'    => true,
				'attr'        => ['class' => 'etn-label-item etn-new-items ' . $class2, 'tab' => 'locations','hello'],
				'warning'     => esc_html__('Create New Locations', 'eventin'),
				'warning_url' => admin_url('edit-tags.php?taxonomy=etn_location'),
				'pro'		  => 'yes'
			];
		}

		return $metabox_fields;
	}

	/**
	 * Add RSVP fields in single event
	 *
	*/
	public function register_rsvp_meta_boxes( $existing_boxes ) {
		$rsvp = \Etn\Core\Addons\Helper::instance()->check_active_module('rsvp');
		if ( $rsvp == '' ) {
			return $existing_boxes;
		}
		
		$existing_boxes['etn_rsvp_settings'] = [
			'label'        => esc_html__( 'RSVP Settings', 'eventin' ),
			'instance'     => $this,
			'callback'     => 'display_callback',
			'cpt_id'       => 'etn',
			'display_type' => 'tab',
		];

		return $existing_boxes;
	}

	public function etn_meta_fields() {
		// Tab
		$tab_items = $this->get_tab_pane();
		// Tab item wise meta
		$event_rsvp_fields                    = [];
		// global
		$event_rsvp_fields['etn_enable_rsvp_form'] = [
			'label'        => esc_html__( 'Enable RSVP?', 'eventin' ),
			'desc'         => esc_html__( 'Do you want to enable RSVP for this event?', "eventin" ),
			'type'         => 'checkbox',
			'left_choice'  => 'no',
			'right_choice' => 'yes',
			'attr'         => ['class' => 'etn-label-item', 'tab' => 'rsvp-general-Settings'],
			'pro'		   => 'yes'
		];
		// global
		$event_rsvp_fields['etn_disable_purchase_form'] = [
			'label'        => esc_html__( 'Disable Purchase Form?', 'eventin' ),
			'desc'         => esc_html__( 'Disable selling for this event?', "eventin" ),
			'type'         => 'checkbox',
			'left_choice'  => 'no',
			'right_choice' => 'yes',
			'attr'         => ['class' => 'etn-label-item', 'tab' => 'rsvp-general-Settings'],
			'pro'		   => 'yes'
		];
		// stock
		$event_rsvp_fields['etn_rsvp_limit'] = [
			'label'        => esc_html__( 'Limit RSVP attendee capacity.', 'eventin' ),
			'desc'         => esc_html__( 'If you want to maintain the limit for attendee capacity, turn on the switcher.', "eventin" ),
			'type'         => 'checkbox',
			'left_choice'  => 'yes',
			'right_choice' => 'no',
			'attr'         => ['class' => 'etn-label-item', 'tab' => 'etn-rsvp-stock'],
			'data'         => ['limit_info' => ''],
			'conditional'  => true,
			'condition-id' => 'etn_rsvp_limit_amount',
			'pro'		   => 'yes'
		];

		$event_rsvp_fields['etn_rsvp_limit_amount'] = [
			'label' => esc_html__( 'RSVP capacity attendee limit', 'eventin' ),
			'desc'  => esc_html__( 'Total attendee for this RSVP', "eventin" ),
			'type'  => 'number',
			'attr'  => ['class' => 'etn-label-item etn_rsvp_limit_amount conditional-item', 'tab' => 'etn-rsvp-stock'],
			'pro'	=> 'yes'
		];
		$event_rsvp_fields['etn_rsvp_attendee_form_limit'] = [
			'label'        => esc_html__( 'Maximum attendee registration for each response', 'eventin' ),
			'desc'         => esc_html__( 'Total attendee registration for a single response', "eventin" ),
			'type'         => 'number',
			'min'          => 1,
			'attr'         => ['class' => 'etn-label-item etn_rsvp_attendee_form_limit', 'tab' => 'etn-rsvp-stock'],
			'pro'		   => 'yes'
		];
		$event_rsvp_fields['etn_rsvp_miminum_attendee_to_start'] = [
			'label'        => esc_html__( 'Minimum attendee to start event', 'eventin' ),
			'desc'         => esc_html__( 'Minimum attendee to start a event', "eventin" ),
			'type'         => 'number',
			'min'          => 0,
			'value'		   => '',
			'default'      => '',
			'placeholder'  => esc_html__( '0', 'eventin' ),
			'attr'         => ['class' => 'etn-label-item etn_rsvp_attendee_form_limit', 'tab' => 'etn-rsvp-stock'],
			'pro'		   => 'yes'
		];
		// form
		$event_rsvp_fields['etn_rsvp_form_type'] = [
			'label'         => esc_html__( 'RSVP Form Type', 'eventin' ),
			'type'          => 'multi_checkbox',
			'desc'          => esc_html__( 'How many form will be shown in form', 'eventin' ),
			'inputs'        => [esc_html__( 'Going', 'eventin' ), esc_html__( 'Not Going', 'eventin' ), esc_html__( 'Maybe', 'eventin' )],
			'input_checked' => array('going'),
			'attr'          => ['class' => 'etn-label-item', 'tab' => 'etn-rsvp-forms'],
			'pro'			=> 'yes'
		];

		$event_rsvp_fields['etn_show_rsvp_attendee'] = [
			'label'        => esc_html__( 'Display Attendee list', 'eventin' ),
			'desc'         => esc_html__( 'Do you want to display going attendee list?', "eventin" ),
			'type'         => 'checkbox',
			'left_choice'  => 'no',
			'right_choice' => 'yes',
			'attr'         => ['class' => 'etn-label-item', 'tab' => 'etn-rsvp-forms'],
			'conditional'  => true,
			'condition-id' => 'etn_attendee_list_limit',
			'pro'			=> 'yes'
		];

		$event_rsvp_fields['etn_attendee_list_limit'] = [
			'label'        => esc_html__( 'Attendee List Limit', 'eventin' ),
			'desc'         => esc_html__( 'Number of attendee you want to show in the single event page. Empty or "-1" will show all the entries.', "eventin" ),
			'type'         => 'number',
			'min'          => 1,
			'attr'         => ['class' => 'etn-label-item etn_attendee_list_limit', 'tab' => 'etn-rsvp-forms'],
			'pro'			=> 'yes'
		];

		if(class_exists( 'Wpeventin_Pro' )){
			$event_rsvp_fields['etn_rsvp_attendee_link'] = [
				'type'         => 'markup',
				'text'         => \Etn_Pro\Core\Modules\Rsvp\Admin\Admin::instance()->get_rsvp_summary_markup( get_the_ID() ),
				'attr'         => ['class' => 'etn-label-item', 'tab' => 'rsvp-attendee-list'],
			];
		}

		$this->event_fields = $event_rsvp_fields;

		return ['fields' => $this->event_fields, 'tab_items' => $tab_items, 'display' => 'tab'];

	}

	public function get_tab_pane() {
		$tab_items = [
			[
				'name' => esc_html__( 'General Settings', 'eventin' ),
				'id'   => 'rsvp-general-Settings',
				'icon' => '<svg width="14" height="13" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path d="M64 448c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L178.8 256L41.38 118.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l160 160c12.5 12.5 12.5 32.75 0 45.25l-160 160C80.38 444.9 72.19 448 64 448z"></path></svg>',
			],
			[
				'name' => esc_html__( 'Forms', 'eventin' ),
				'id'   => 'etn-rsvp-forms',
				'icon' => '<svg width="14" height="13" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path d="M64 448c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L178.8 256L41.38 118.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l160 160c12.5 12.5 12.5 32.75 0 45.25l-160 160C80.38 444.9 72.19 448 64 448z"></path></svg>',
			],
			[
				'name' => esc_html__( 'Stock', 'eventin' ),
				'id'   => 'etn-rsvp-stock',
				'icon' => '<svg width="14" height="13" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path d="M64 448c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L178.8 256L41.38 118.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l160 160c12.5 12.5 12.5 32.75 0 45.25l-160 160C80.38 444.9 72.19 448 64 448z"></path></svg>',
			],
			[
				'name' => esc_html__( 'Attendee List', 'eventin' ),
				'id'   => 'rsvp-attendee-list',
				'icon' => '<svg width="14" height="13" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path d="M64 448c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L178.8 256L41.38 118.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l160 160c12.5 12.5 12.5 32.75 0 45.25l-160 160C80.38 444.9 72.19 448 64 448z"></path></svg>',
			],
		];

		return $tab_items;
	}

	 /**
     * Add extra field to event form
     *
     */
    public function event_min_max_meta( $meta_box_fields ) {

        $meta_box_fields_min_max['etn_min_ticket'] = [
            'label'    => esc_html__( 'Minimum Purchase Qty', 'eventin' ),
            'type'     => 'number',
            'default'  => '',
            'value'    => '',
            'desc'     => esc_html__( 'minimum ticket purchase per order', 'eventin' ),
            'priority' => 1,
            'placeholder' => esc_html__( '0', 'eventin' ),
            'attr'     => ['class' => 'etn-label-item etn-set-min-qty'],
			'pro'	   => 'yes'
        ];
        $meta_box_fields_min_max['etn_max_ticket'] = [
            'label'    => esc_html__( 'Maximum Purchase Qty', 'eventin' ),
            'type'     => 'number',
            'default'  => '',
            'value'    => '',
            'placeholder' => esc_html__( '0', 'eventin' ),
            'desc'     => esc_html__( 'maximum ticket purchase per order', 'eventin' ),
            'priority' => 1,
            'attr'     => ['class' => 'etn-label-item etn-set-max-qty'],
			'pro'	   => 'yes'
        ];
        $meta_box_fields['etn_show_min_max_text'] = [
            'label'        => esc_html__( 'Show Min Max Qty Text In Form', 'eventin' ),
            'desc'         => esc_html__( 'Set if you want to show min max qty information inside form', "eventin" ),
            'type'         => 'checkbox',
            'left_choice'  => 'yes',
            'right_choice' => 'no',
            'attr'         => ['class' => 'etn-label-item etn-limit-event-ticket'],
			'pro'		   => 'yes'
        ];

        $meta_box_fields['etn_ticket_variations']['options'] += $meta_box_fields_min_max;

        return $meta_box_fields;
    }

	/**
     * show minimum and maximum ticket purchaseable notice for each variation ticket
     *
     * @param [int] $single_event_id
     * @param [int] $variation_index
     * @param [array] $ticket_variation
     * @return void
     */
    public function show_min_max_in_event_form( $single_event_id, $variation_index, $ticket_variation ) {
        $etn_show_min_max_text = ( !empty( get_post_meta( $single_event_id, 'etn_show_min_max_text', true ) ) && get_post_meta( $single_event_id, 'etn_show_min_max_text', true ) == 'on' ) ? 1 : 0 ;

        if ( $etn_show_min_max_text ) {
            $etn_avaiilable_tickets = !empty( absint( $ticket_variation['etn_avaiilable_tickets'] ) ) ? absint( $ticket_variation['etn_avaiilable_tickets'] ) : 100000;
            $etn_sold_tickets       = absint( $ticket_variation['etn_sold_tickets'] );
            $remaining_ticket       = $etn_avaiilable_tickets - $etn_sold_tickets;

            if ( $remaining_ticket > 0 ) {
                $etn_min_ticket = !empty( absint( $ticket_variation['etn_min_ticket'] ) ) ? absint( $ticket_variation['etn_min_ticket'] ) : 1;
                $etn_max_ticket = !empty( absint( $ticket_variation['etn_max_ticket'] ) ) ? absint( $ticket_variation['etn_max_ticket'] ) : $etn_avaiilable_tickets;
                $etn_max_ticket = min( $remaining_ticket, $etn_max_ticket );

                if ( $etn_min_ticket > $etn_max_ticket ) {
                    $swap           = $etn_min_ticket;
                    $etn_min_ticket = $etn_max_ticket;
                    $etn_max_ticket = $swap;
                }
                ?>
                <div class='etn-min-max-ticket-form-text min-max-purchase-notice-<?php echo esc_attr( absint( $variation_index ) ); ?>'><?php echo esc_html__('You can purchase ', 'eventin') . $etn_min_ticket . '-'. $etn_max_ticket . esc_html__(' tickets at once ', 'eventin'); ?></div>
                <?php
            }
        }
    }

	/**
	 * Google Meet Meta Field
	 *
	 */
	public function etn_google_meet_meta_fields( $event_google_meet_fields ) {

		$meet_data 		   = get_post_meta( get_the_ID(), 'google_calendar_event_data', true );
		$google_meet_url   = ( !empty( $meet_data['hangoutLink'] ) ) ? $meet_data['hangoutLink'] : '';

		$event_google_meet_fields['etn_google_meet'] = [
			'label'        => esc_html__( 'Google Meet', 'eventin' ),
			'desc'         => esc_html__( 'Enable if this event is a Google meet event', 'eventin' ),
			'type'         => 'checkbox',
			'left_choice'  => 'Yes',
			'right_choice' => 'no',
			'attr'         => ['class' => 'etn-label-item etn-googlemeet-event', 'tab' => 'google_meet_settings'],
			'conditional'  => true,
			'condition-id' => 'etn_google_meet_link',
			'pro'		   => 'yes'
		];

		$event_google_meet_fields['etn_google_meet_link'] = [
			'label'         => esc_html__( 'Google Meet Link', 'eventin' ),
			'type'          => 'text',
			'default'       => '',
			'desc'          => esc_html__( 'Link will be generated when you publish the event.', 'eventin' ),
			'value'         => $google_meet_url,
			'priority'      => 1,
			'readonly'		=> true,
			'placeholder'   => esc_html__( 'Google Meet link here', 'eventin' ),
			'required'      => false,
			'attr'          => [
				'class' 	=> 'etn-label-item conditional-item',
				'icon'  	=> '',
				'tab'   	=> 'google_meet_settings'
			],
			'tooltip_title' => '',
			'tooltip_desc'  => '',
			'pro'		    => 'yes'
		];

		$event_google_meet_fields['etn_google_meet_short_description'] = [
			'label'       => esc_html__( 'Google Meet Description', 'eventin' ),
			'desc'        => esc_html__( 'Short description about the meeting.', 'eventin' ),
			'default'     => '',
			'value'       => '',
			'type'        => 'textarea',
			'priority'    => 1,
			'placeholder' => esc_html__( 'A short description for Google Meet', 'eventin' ),
			'attr'        => ['class' => 'etn-label-item conditional-item', 'tab' => 'google_meet_settings'],
			'pro'		  => 'yes'
		];

		return $event_google_meet_fields;
	}

	 /**
     * add new field function
     *
     */
    public function update_speaker_meta( $metabox_fields ) {
        $metabox_fields['etn_speaker_url'] = [
            'label'    => esc_html__( 'Company Url', 'eventin' ),
            'desc'     => esc_html__('Provide speaker / company site link', "eventin"),
            'type'     => 'url',
            'default'  => '',
            'value'    => '',
            'priority' => 1,
            'placeholder' => 'https://company.com',
            'attr'     => ['class' => 'etn-label-item'],
            'placeholder' => esc_html__( 'Type URL', 'eventin' ),
            'required' => true,
			'pro'	   => 'yes'
        ];
        
        return $metabox_fields;
    }
	
}
