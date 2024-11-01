<?php

namespace Etn\Core\Event;

use DateTime;
use \Etn\Core\Event\Pages\Event_single_post;
use \Etn\Utils\Helper;
use Etn_Pro\Core\Modules\Integrations\Google_Meet\Google_Meet;

defined( 'ABSPATH' ) || exit;

class Hooks {

	use \Etn\Traits\Singleton;

	public $cpt;
	public $action;
	public $base;
	public $category;
	public $tags;
	public $event;
	public $settings;

	public $actionPost_type = ['etn'];

	public function Init() {

		$this->cpt      = new Cpt();
		$this->category = new Category();
		$this->tags     = new Tags();
		$this->action   = new Action();

		$this->add_metaboxes();
		$this->add_taxonomy_menu();
		$this->add_single_page_template();
		$this->prepare_post_taxonomy_columns();

		add_filter( "etn_form_submit_visibility", [$this, "form_submit_visibility"], 10, 2 );
		add_filter('template_include', [$this, 'etn_search_template_chooser']); 

		// sorting event by start date
		add_action('restrict_manage_posts', [$this, 'sort_event_by_date']);
		add_filter('parse_query', [$this, 'event_filter_request_query']);
		add_action( 'init', [$this, 'create_taxonomy_pages'], 99999 );
		
		if ( file_exists( self::get_dir() . 'api.php' ) ) {
			include_once self::get_dir() . 'api.php';
		}

		// add header in custom post type and taxonomy page
		add_action('admin_notices',[$this,'etn_post_type_add_header']);

		// Add bulk actions.
        add_filter( 'bulk_actions-edit-etn', [ $this, 'add_bulk_actions' ] );

        add_filter( 'handle_bulk_actions-edit-etn', [ $this, 'handle_export_bulk_action' ], 10, 3 );

		// Update woocommerce supported meta data.
		add_action( 'eventin_event_created', [ $this, 'added_woo_supported_meta' ] );
		add_action( 'eventin_event_after_clone', [ $this, 'added_woo_supported_meta' ] );

		// Google meet support.
		add_action( 'eventin_event_created', [ $this, 'google_meet_support' ] );

		//upcoming permalink structure
		add_filter('post_type_link', [ $this, 'etn_upcoming_permalink' ], 10, 4);

	}

	/**
	 * Added woocommerce supported meta data
	 *
	 * @param   Event_Model  $event
	 *
	 * @return  void
	 */
	public function added_woo_supported_meta($event) {
		$event = new Event_Model( $event );
		
		update_post_meta( $event->id, "_price", 0 );
		update_post_meta( $event->id, "_regular_price", 0 );
		update_post_meta( $event->id, "_sale_price", 0 );
		update_post_meta( $event->id, "_stock", 0 );
	}

	/**
	 * add header in speaker,zoom,attendee,schedule,location page
	 */
	public function etn_post_type_add_header(){
		$event_post_type = ['etn','etn-schedule','etn-speaker','etn-zoom-meeting','etn-attendee'];
		if ( ( !empty($_GET['post']) && 'etn' == get_post_type( $_GET['post'] )) ||
		( !empty($_GET['post_type']) && in_array( $_GET['post_type'] , $event_post_type ) ) ) {
			// header start.
			include_once ETN_PLUGIN_TEMPLATE_DIR . "layout/header.php";
			//  header end
		}

	}

	/**
	 * get user module url
	 *
	 * @return string
	 */
	public static function get_url() {
		return \Wpeventin::core_url() . 'event/';
	}

	/**
	 * get user module directory path
	 *
	 * @return string
	 */
	public static function get_dir() {
		return \Wpeventin::core_dir() . 'event/';
	}

	/**
	 * Result of query
	 */
	public function event_filter_request_query($query){
		if (!(is_admin()) && $query->is_main_query()) {
				return $query;
		}

		$search_value = isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : null;
		if (!isset($query->query['post_type']) || ('etn' !== $query->query['post_type']) || !isset($search_value) ) {
				return $query;
		}

		if ( $search_value !== '') {
				$meta = [];

				if (!isset($query->query_vars['meta_query'])) {
						$query->query_vars['meta_query'] = array();
				}
				if ( $search_value == 'etn_start_date_past' || $search_value == 'etn_start_date_upcoming' ) {
						$query->set( 'meta_key', 'etn_start_date' );
						$query->set( 'order', 'ASC' );
						$query->set( 'orderby', 'meta_value');

						if ($search_value == 'etn_start_date_past') {
								$compare = "<=";
						}
						else if ($search_value == 'etn_start_date_upcoming') {
								$compare = ">=";
						} 

						// setup this functions meta values
						$meta[] = array(
								'key'           => 'etn_start_date',
								'meta-value'    => 'ASC',
								'value'         => date('Y-m-d'),
								'compare'       => $compare,
								'type'          => 'CHAR'
						);
				}

				$search_data = ['Past','Ongoing','Upcoming'];
				if ( in_array( $search_value , $search_data)) {
						// pro filter query
						$meta = apply_filters('etn/event_parse_query', $meta , $search_value );
				}

				// append to meta_query array
				$query->query_vars['meta_query'][] = $meta;
		}

		return $query;
	}

	/**
	 * sorting event by start date
	 */
	public function sort_event_by_date(){
		global $typenow;
		if ($typenow == 'etn') {

				$options = array( 'etn_start_date_past'=> esc_html__('Past events by event start date ' , 'eventin'),
				'etn_start_date_upcoming'=> esc_html__('Upcoming events by event start date ' , 'eventin') );
				// get pro filter param
				$filter_options = apply_filters('etn/event_filter' , $options) ;

				$selected = '';
				if ((isset($_GET['event_type']))  && isset($_GET['post_type'])
						&& !empty(sanitize_text_field($_GET['event_type'])) &&  sanitize_text_field($_GET['post_type']) == 'etn'
				) {
						$selected = sanitize_text_field($_GET['event_type']);
				}
				?>
				<select name="event_type">
						<?php
						foreach ( $filter_options as $key=>$value ) :
								$select = ( $key == $selected ) ? ' selected="selected"' : '';
								?>
								<option value="<?php echo esc_html( $key ); ?>" 
										<?php echo esc_html($select) ?>><?php echo sprintf('%s',$value); ?>
								</option>
								<?php
						endforeach;
						?>
				</select>
				<?php
		}
	}

	// Search template redirect to event archive page
	public function etn_search_template_chooser($template)  {
		global $wp_query;

		$post_type  = get_post_type(get_the_ID());
		$post       = get_post( get_the_ID() );
		$post_slug  = !empty( $post ) ? $post->post_name : null;

		if( $wp_query->is_search && $post_type == 'etn' && file_exists( \Wpeventin::core_dir() . 'event/views/event-archive-page.php' ) )   
		{
				return \Wpeventin::core_dir() . 'event/views/event-archive-page.php';
		}

		if (!empty($post_slug ) && ( $post_slug == "etn-tags" || $post_slug == "etn_category" || $post_slug == "etn-speaker-category" ) ) {

				return \Wpeventin::core_dir() . 'event/views/event-taxonomy-page.php';
		}

		return $template;
	}

	public function add_metaboxes() {
		$event_metabox = new \Etn\Core\Metaboxs\Event_meta();
		add_action( 'add_meta_boxes', [$event_metabox, 'register_meta_boxes'] );
		add_action( 'save_post', [$event_metabox, 'save_meta_box_data'] );

		if( class_exists('Etn_Pro\Core\Modules\Rsvp\Admin\Metaboxs\Metabox')  ){
			$module_check = \Etn\Core\Addons\Helper::instance()->check_active_module( 'rsvp' );
			if ( true == $module_check ) {
				$event_rsvp_metabox = new \Etn_Pro\Core\Modules\Rsvp\Admin\Metaboxs\Metabox();
				add_action( 'save_post', [$event_rsvp_metabox, 'save_meta_box_data'] );
			}
		}
		
	}


	public function prepare_post_taxonomy_columns() {
		//Add column
		add_filter( 'manage_etn_posts_columns', [$this, 'event_column_headers'] );
		add_action( 'manage_etn_posts_custom_column', [$this, 'event_column_data'], 10, 2 );

		add_filter( "manage_edit-etn_category_columns", [$this, 'category_column_header'], 10 );
		add_action( "manage_etn_category_custom_column", [$this, 'category_column_content'], 10, 3 );

		add_filter( "manage_edit-etn_tags_columns", [$this, 'category_column_header'], 10 );
		add_action( "manage_etn_tags_custom_column", [$this, 'category_column_content'], 10, 3 );
	}

	function category_column_header( $columns ) {
		$new_item["id"] = esc_html__( "Id", "eventin" );
		$new_array      = array_slice( $columns, 0, 1, true ) + $new_item + array_slice( $columns, 1, count( $columns ) - 1, true );
		return $new_array;
	}

	function category_column_content( $content, $column_name, $term_id ) {
		return $term_id;
	}

	function add_taxonomy_menu() { 
		if( class_exists('Wpeventin_Pro') && class_exists('\Etn_Pro\Core\Event\Event_Location') ) {
				$event_location = \Etn_Pro\Core\Event\Event_Location::instance();
				$event_location->init();
		}
	}

	function add_single_page_template() {
			$page = new Event_single_post();
	}

	/**
	 * Column name
	 */
	public function event_column_headers( $columns ) {
		unset( $columns['date'] );
		$new_item["id"]                 = esc_html__( "Id", "eventin" );
		$another_item["is_recurring"]   = esc_html__( "Recurring", "eventin" );
		$new_array                      = array_slice( $columns, 0, 1, true ) + $new_item + array_slice( $columns, 0, 2, true ) + $another_item + array_slice( $columns, 2, count( $columns ) - 1, true );
		$new_array['etn_date']          = esc_html__('Date',  'eventin' );
		$new_array['actions']           = esc_html__( 'Actions', 'eventin' );

		return $new_array;
	}

  	/**
	 * Return row
	 */
	public function event_column_data( $column, $post_id ) {
		switch ( $column ) {
			case 'id':
					echo intval( $post_id );
					break;
			case 'is_recurring':
					$is_recurring_parent = Helper::get_child_events( $post_id );

					if(Helper::is_recurrence( $post_id )){
							?>
							<div class="etn-event-dashboard-recurrence etn-event-dashboard-recurrence-child"><?php echo esc_html__('Yes - Recurrence', 'eventin');?></div>
							<?php
					}else{
						if( !$is_recurring_parent ){
								?>
								<div class="etn-event-dashboard-recurrence etn-event-dashboard-recurrence-no"><?php echo esc_html__('No', 'eventin');?></div>
								<?php
						} elseif( is_array( $is_recurring_parent ) && !empty( $is_recurring_parent ) ) {
							?>
							<div class="etn-event-dashboard-recurrence etn-event-dashboard-recurrence-parent "><?php echo esc_html__('Yes - Parent', 'eventin');?></div>
							<?php
						}
					}
					break;
			case 'etn_date':
				$end_date = !empty(get_post_meta($post_id,'etn_end_date',true)) ? ' - '. get_post_meta($post_id,'etn_end_date',true) : ''; 
				echo esc_html( get_post_meta($post_id,'etn_start_date',true).' '. $end_date );
				break; 
			case 'actions':

				if ( class_exists('Wpeventin_Pro') ) {
					// attendee link
					$settings                       = etn_get_option();
					$attendee_registration          = !empty( $settings['attendee_registration'] ) ? true : false;
					if ( $attendee_registration ) {
					?> 
					<a class="event-list-action-button" href="<?php echo esc_url(admin_url('edit.php?post_type=etn-attendee&event_id='.intval( $post_id )))?>">
						<?php echo esc_html__('Attendees','eventin')?>
					</a>
					<?php
					}
					// rsvp link
					$is_active_rsvp = \Etn\Core\Addons\Helper::instance()->check_active_module( 'rsvp' );
					if ( $is_active_rsvp ) {
						?>
							<a class="event-list-action-button" href="<?php echo esc_url(admin_url('admin.php?page=etn_rsvp_report&event_id='.intval( $post_id )))?>">
								<?php echo esc_html__('RSVP Report','eventin')?>
							</a>
						<?php
					}
				}

			break;
		}

	}

	/**
	 * set form submission button visibility
	 *
	 * @param [type] $visible
	 * @param [type] $post_id
	 */
	public function form_submit_visibility( $visible, $post_id ) {
		//get disable option setting from db
		$is_visible           = true;
		$reg_deadline_expired =  \Etn\Core\Event\Helper::instance()->event_registration_deadline( array('single_event_id' => $post_id ) );
		$is_visible           = $reg_deadline_expired == true ? false : true;

		return $is_visible;
	}

	public function create_taxonomy_pages(){
		$this->category->create_page();
		$this->tags->create_page();
	}
	
	/**
     * Add bulk action on event post type
     *
     * @param   array  $bulk_actions
     *
     * @return  array
     */
    public function add_bulk_actions( $bulk_actions ) {
        $bulk_actions['export-csv']  = __( 'Export CSV', 'eventin' );
        $bulk_actions['export-json'] = __( 'Export JSON', 'eventin' );

        return $bulk_actions;
    }

    /**
     * Handle bulk action for export
     *
     * @param   string  $redirect_url
     * @param   string  $action
     * @param   array  $post_ids
     *
     * @return  string
     */
    public function handle_export_bulk_action( $redirect_url, $action, $post_ids ) {
        $actions = [
            'export-csv',
            'export-json'
        ];

        if ( ! in_array( $action, $actions ) ) {
            return $redirect_url;
        }

        $export_type = 'json';

        if ( 'export-csv' == $action ) {
            $export_type = 'csv';
        }

        $event_exporter = new Event_Exporter();
        $event_exporter->export( $post_ids, $export_type );
    }

	/**
	 * Added google meet support event
	 *
	 * @param   Event_Model  $event
	 *
	 * @return  void
	 */
	public function google_meet_support( $event ) {
		$event = new Event_Model( $event );

		if ( 'yes' !== $event->etn_google_meet ) {
			return;
		}
		
		if ( class_exists( 'Google_Meet' ) ) {
			Google_Meet::instance()->etn_create_google_meet_meeting( $event->id );
		}
	}

	public function etn_upcoming_permalink($post_link, $post, $leavename, $sample) {
		
		if ($post->post_type == 'etn' && ( $post->post_status == 'upcoming' || $post->post_status == 'expired' ) ) {
			// Modify the permalink structure here
			$post_link = home_url('/etn/' . $post->post_name);
		}
		return $post_link;
	}
	
}
