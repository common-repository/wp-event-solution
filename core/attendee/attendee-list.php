<?php
	namespace Etn\Core\Attendee;

	use Etn\Utils\Helper;

	defined( 'ABSPATH' ) || exit;

	class Attendee_List {
	use \Etn\Traits\Singleton;

	/**
	 * Class constructor.
	 */
	public function init() {
		add_filter( "manage_etn-attendee_posts_columns", [$this, "attendee_post_columns"] );
		add_action( "manage_etn-attendee_posts_custom_column", [$this, 'attendee_custom_column_data'], 10, 2 );
		add_action( 'add_meta_boxes', [$this, 'customize_admin_backend_cpt'], 0 );

		//register cron-job depending on settings
		$settings            = Helper::get_settings();
		$attendee_reg_enable = ! empty( $settings["attendee_registration"] ) ? true : false;

		if ( $attendee_reg_enable ) {
			// Attendee cron job to remove attendee
			\Etn\Core\Attendee\Cron::instance()->init();
		}

		// hide preview , view , edit link
		add_filter( 'page_row_actions', [$this, 'remove_row_actions'], 10, 2 );

		// create custom attendee status
		add_action( 'init', [$this, 'etn_custom_attendee_status'] );

		// add custom attendee status on the publish sidebar
		add_action( 'admin_footer-post.php', [$this, 'etn_trashed_attendee_in_publish_status'] );

		// generate pdf
		add_action( 'template_redirect', [$this, 'generate_ticket_pdf'] );
		add_filter( 'admin_head', [$this, 'change_link'], 10, 1 );
	}

	/**
	 * Add event id for event wise attendee
	 */
	public function change_link() {
		global $post_new_file, $post_type_object;
		if ( ( ! isset( $post_type_object ) || 'etn-attendee' !== $post_type_object->name ) ) {
			return false;
		}

		if ( !empty($_GET['event_id']) ) {
			$new_file = $post_new_file == "" ? "post-new.php?post_type=etn-attendee" : $post_new_file;
			$post_new_file = $new_file . "&event_id=".$_GET['event_id'];
		}


	}

	/**
	 * Remove slug metabox
	 */
	public function customize_admin_backend_cpt() {
		remove_meta_box( 'slugdiv', 'etn-attendee', 'normal' );
	}

	/**
	 * Attendee data array both for generate and download
	 */
	public function attendee_ticket_data( $data ) {
		$result_data                   		= [];
		$result_data['user_id']        		= intval( $data["attendee_id"] );
		$result_data['ticket_price']   		= $data['etn_ticket_price'];
		$result_data['event_location_type'] = $data['event_location_type'];
		$result_data['event_location'] 		= $data['event_location'];
		$result_data['event_terms'] 		= $data['event_terms'];

		$result_data['event_name']     = $data['event_name'];
		$result_data['ticket_name']    = ! empty( $data['ticket_name'] ) ? $data['ticket_name'] : ETN_DEFAULT_TICKET_NAME;
		$result_data['attendee_seat']    = ! empty( $data['attendee_seat'] ) ? $data['attendee_seat'] : '';

		$settings                 = \Etn\Utils\Helper::get_settings();
		$date_options             = \Etn\Utils\Helper::get_date_formats();
		$etn_settings_time_format = empty( $settings["time_format"] ) ? '12' : $settings["time_format"];
		$etn_settings_time_format = $etn_settings_time_format == '24' ? "H:i" : get_option( "time_format" );
		$etn_settings_date_format = ! empty( $settings["date_format"] ) ? $date_options[$settings["date_format"]] : get_option( "date_format" );

		$date_format = !empty($settings['date_format']) ? $etn_settings_date_format : get_option("date_format");
		$result_data['start_date'] = date_i18n($date_format, strtotime($data['etn_start_date']));
		$result_data['end_date'] = !empty($data['etn_end_date']) 
		? date_i18n($date_format, strtotime($data['etn_end_date'])) 
		: '';

		$result_data['start_time']     = ! empty( $settings['time_format'] ) ? date_i18n( $etn_settings_time_format, strtotime( $data['etn_start_time'] ) ) : date_i18n( get_option( "time_format" ), strtotime( $data['etn_start_time'] ) );
		$result_data['end_time']       = ! empty( $settings['time_format'] ) ? date_i18n( $etn_settings_time_format, strtotime( $data['etn_end_time'] ) ) : date_i18n( get_option( "time_format" ), strtotime( $data['etn_end_time'] ) );
		$result_data['event_timezone'] = ! empty( $data['event_timezone'] ) ? $data['event_timezone'] : $data['event_timezone'];

		$result_data['attendee_name']  = get_post_meta( $result_data['user_id'], 'etn_name', true );
		$result_data['attendee_email'] = get_post_meta( $result_data['user_id'], "etn_email", true );
		$result_data['attendee_phone'] = get_post_meta( $result_data['user_id'], "etn_phone", true );
		$result_data['attendee_seat']  = get_post_meta( $result_data['user_id'], "attendee_seat", true );

		return $result_data;
	}

	/**
	 * Download PDF from email and admin dashboard
	 */
	public function generate_ticket_pdf() {
		if ( isset( $_GET['etn_action'] ) && sanitize_text_field( $_GET['etn_action'] ) === 'download_ticket' ) {

			$get_arr = filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( empty( $get_arr["attendee_id"] ) || empty( $get_arr["etn_info_edit_token"] ) ) {
				Helper::show_attendee_pdf_invalid_data_page();
				exit;
			}

			if ( ! Helper::verify_attendee_edit_token( $get_arr["attendee_id"], $get_arr["etn_info_edit_token"] ) ) {
				Helper::show_attendee_pdf_invalid_data_page();
				exit;
			}
			$attendee_id = $get_arr["attendee_id"];
			$event_id    = get_post_meta( $attendee_id, "etn_event_id", true );

			$attendee_data = [
				"attendee_id"      		=> $attendee_id,
				"etn_ticket_price" 		=> get_post_meta( $attendee_id, "etn_ticket_price", true ),
				"event_location_type"   => get_post_meta( $event_id, "etn_event_location_type", true ),
				"event_terms"           => !empty(get_the_terms($event_id, 'etn_location')) ? get_the_terms($event_id, 'etn_location') : [],
				"event_location"   		=> get_post_meta( $event_id, "etn_event_location", true ),
				"event_name"       		=> get_post_field( 'post_title', $event_id, 'raw' ),
				"ticket_name"      		=> !empty( get_post_meta( $attendee_id, 'ticket_name', true ) ) ? html_entity_decode( get_post_meta( $attendee_id, 'ticket_name', true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : ETN_DEFAULT_TICKET_NAME,
				"etn_start_date"   		=> get_post_meta( $event_id, "etn_start_date", true ),
				"etn_end_date"     		=> get_post_meta( $event_id, "etn_end_date", true ),
				"etn_start_time"   		=> get_post_meta( $event_id, "etn_start_time", true ),
				"etn_end_time"     		=> get_post_meta( $event_id, "etn_end_time", true ),
				"etn_end_time"     		=> get_post_meta( $event_id, "etn_end_time", true ),
				"event_timezone"   		=> get_post_meta( $event_id, "event_timezone", true ),
			];

			$result_data = $this->attendee_ticket_data( $attendee_data );
			
			if ( is_array( $result_data ) && ! empty( $result_data ) ) {
				$this->generate_pdf(
					$attendee_id,
					$event_id,
					$result_data['event_name'],
					$result_data['start_date'],
					$result_data['end_date'],
					$result_data['start_time'],
					$result_data['end_time'],
					$result_data['event_location'],
					$result_data['event_location_type'],
					$result_data['event_terms'],
					$result_data['ticket_name'],
					$result_data['ticket_price'],
					$result_data['attendee_name'],
					$result_data['attendee_email'],
					$result_data['attendee_phone'],
					$result_data['event_timezone'],
					$result_data['attendee_seat']
				);
			}
			exit;
		}

		return;
	}

	/**
	 * Generate PDF file with provided data
	 */
	public function generate_pdf( $attendee_id, $event_id, $event_name,
		$start_date, $end_date, $start_time, $end_time,
		$event_location, $event_location_type, $event_terms, $ticket_name, $ticket_price, $attendee_name,
		$attendee_email, $attendee_phone, $time_zone , $attendee_seat ) {
		$settings       = Helper::get_settings();
		$include_phone  = ! empty( $settings["reg_require_phone"] ) ? true : false;
		$include_email  = ! empty( $settings["reg_require_email"] ) ? true : false;
		$time_zone      = ! empty( $time_zone ) ? ' (' . $time_zone . ') ' : '';
		$date_separator = ! empty( $end_date ) ? ' - ' : '';
		$time_separator = ! empty( $end_time ) ? ' - ' : '';
		$date           = $start_date . $date_separator . $end_date;
		$time           = $start_time . $time_separator . $end_time . $time_zone;

		$ticket_style = isset( $settings['attendee_ticket_style'] ) ? $settings['attendee_ticket_style'] : 'style-1';

		$event_ticket_template = get_post_meta( $event_id, 'ticket_template', true );

		$layouts = [
			'1' => 'style-1',
			'2' => 'style-2',
		];

		if ( ! empty( $layouts[$event_ticket_template] ) ) {
			$ticket_style = $layouts[$event_ticket_template];
		}

		if ( $ticket_style === 'style-1' && file_exists( \Wpeventin::core_dir() . "attendee/views/ticket/ticket-markup.php" ) ) {
			include_once \Wpeventin::core_dir() . "attendee/views/ticket/ticket-markup.php";
		} else if ( class_exists( 'Wpeventin_Pro' ) && $settings['attendee_ticket_style'] === 'style-2' && file_exists( \Wpeventin_Pro::core_dir() . "attendee/ticket-markup-style-2.php" ) ) {
			include_once \Wpeventin_Pro::core_dir() . "attendee/ticket-markup-style-2.php";
		}
	}

	/**
	 * hide preview , view , edit link
	 */
	public function remove_row_actions( $actions, $post ) {

		if ( $post->post_type === 'etn-attendee' ):
			unset( $actions['view'] );
			unset( $actions['inline hide-if-no-js'] );
		endif;

		return $actions;
	}

	/**
	 * Column name
	 */
	public function attendee_post_columns( $columns ) {
		unset( $columns['date'] );
		unset( $columns['title'] );

		$columns['id']       = esc_html__( 'Attendee ID', 'eventin' );
		$columns['etn_name'] = esc_html__( 'Name', 'eventin' );
		if ( ! empty( Helper::get_option( 'reg_require_email' ) ) ) {
			$columns['etn_email'] = esc_html__( 'Email', 'eventin' );
		}
		if ( ! empty( Helper::get_option( 'reg_require_phone' ) ) ) {
			$columns['etn_phone'] = esc_html__( 'Phone', 'eventin' );
		}
		$columns['etn_event']                   = esc_html__( 'Event', 'eventin' );
		$columns['etn_status']                  = esc_html__( 'Payment Status', 'eventin' );
		$columns['etn_attendeee_ticket_status'] = esc_html__( 'Ticket Status', 'eventin' );
		$columns['etn_download_ticket']         = esc_html__( 'Action', 'eventin' );

		return $columns;
	}

	/**
	 * Return row
	 */
	public function attendee_custom_column_data( $column, $post_id ) {
		$event_id               = get_post_meta( $post_id, 'etn_event_id', true );
		$attendee_name          = get_post_meta( $post_id, 'etn_name', true );
		$attendee_email         = get_post_meta( $post_id, 'etn_email', true );
		$attendee_phone         = get_post_meta( $post_id, 'etn_phone', true );
		$payment_status         = get_post_meta( $post_id, 'etn_status', true );
		$attendee_ticket_status = get_post_meta( $post_id, 'etn_attendeee_ticket_status', true );
		$event_name             = get_the_title( $event_id );

		$attempt_status = 'unused';
		$status_label   = esc_html__( 'Used', 'eventin' );
		if ( $attendee_ticket_status == 'unused' ) {
			$attempt_status = 'used';
			$status_label   = esc_html__( 'Unused', 'eventin' );
		}

		$payment_status_label = esc_html__( 'Success', 'eventin' );
		if ( $payment_status == 'failed' ) {
			$payment_status_label = esc_html__( 'Failed', 'eventin' );
		}

		switch ( $column ) {
		case 'id':
			echo intval( $post_id );
			break;
		case 'etn_name':
			echo esc_html( $attendee_name );
			break;
		case 'etn_email':
			echo esc_html( $attendee_email );
			break;
		case 'etn_phone':
			echo esc_html( $attendee_phone );
			break;
		case 'etn_event':
			echo esc_html( $event_name );
			break;
		case 'etn_status':
			echo esc_html( $payment_status_label );
			break;
		case 'etn_attendeee_ticket_status':
		?>
		<div class="ticket_status_wrap">
				<input type="checkbox" name="etn_ticket_status" id="etn_ticket_status_<?php echo esc_attr( $post_id ); ?>" class="etn_ticket_status" data-attendee_id="<?php echo esc_attr( $post_id ); ?>"
						value="<?php echo esc_attr( $attempt_status ); ?>"<?php checked( 'used', $attendee_ticket_status );?> />
				<label class="etn_ticket_status_label" for="etn_ticket_status_<?php echo esc_attr( $post_id ); ?>"><?php echo esc_html( $status_label ); ?></label>
		</div>
		<span class="ticket_status_msg"></span>
		<?php
			break;
			case 'etn_download_ticket':
				$attendee_id          = intval( $post_id );
				$edit_token           = get_post_meta( $attendee_id, 'etn_info_edit_token', true );
				$base_url             = home_url();
				$attendee_cpt         = new \Etn\Core\Attendee\Cpt();
				$attendee_endpoint    = $attendee_cpt->get_name();
				$action_url           = $base_url . "/" . $attendee_endpoint;
				$ticket_download_link = $action_url . "?etn_action=" . urlencode( 'download_ticket' ) . "&attendee_id=" . urlencode( $attendee_id ) . "&etn_info_edit_token=" . urlencode( $edit_token );
			?>
		<div class="etn-attendee-details-button-download">
				<a class="etn-btn-text etn-success download-details"  href="<?php echo esc_url( $ticket_download_link ); ?>" rel="noopener"><?php echo esc_html__( 'Ticket', 'eventin' ); ?></a>
		</div>
		<?php
			break;
		}

	}

	// create custom attendee status
	public function etn_custom_attendee_status() {
		register_post_status( 'etn-trashed-attendee', array(
			'label'                     => _x( 'Trashed Attendee', 'post', 'eventin' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Trashed Attendee (%s)', 'Trashed Attendee (%s)', 'eventin' ),
		) );
	}

	// add custom attendee status on the publish sidebar
	public function etn_trashed_attendee_in_publish_status() {
		$selected = ( 'etn-trashed-attendee' == get_post_status() ) ? 'selected=\"selected\"' : '';
		if ( 'etn-attendee' == get_post_type() ) {
			echo "<script>
		jQuery(document).ready( function() {
				jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"etn-trashed-attendee\" {$selected}>" . esc_html__( 'Trashed Attendee', 'eventin' ) . "</option>' );
		});
		</script>";
			}
	}
	
}
