<?php

namespace Etn\Core\Recurring_Event;

use Etn\Core\Event\Event_Model;
use Etn\Traits\Singleton;
use Etn\Utils\Helper;

defined( 'ABSPATH' ) || exit;

class Hooks {

    use Singleton;

    /**
     * Initialize Recurring Event Hooks
	 * 
	 * @since 3.0.0
	 * @return void
     */
    public function init() {
        add_action( 'admin_notices', [$this, 'admin_notices'] );

        // fire on recurring event action
        add_action( 'eventin_event_created', [$this, 'create_recurrences'] );
        add_action( 'eventin_event_updated', [$this, 'create_recurrences'] );

        // change child post action title
        add_filter( 'page_row_actions', [$this, 'child_recurring_event_action'], 10, 2 );

        add_action( 'post_action_detach', [$this, 'detach_child_event'] );

        // check permission for manage user
        add_action( 'admin_menu', [$this, 'view_all_recurrence_page'] );

        if ( class_exists( 'WooCommerce' ) ) {
            add_filter( 'woocommerce_cart_item_permalink', [$this, 'custom_cart_item_permalink'], 10, 3 );
        }

    }

    /**
     * Change recurring event child item permalink
     */
    public function custom_cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {
        $content_post = get_post( $cart_item['data']->get_id() );

        if (  ( is_cart() || is_checkout() ) && !empty( $content_post ) && 0 !== wp_get_post_parent_id( $content_post ) ) {
            $permalink = esc_url( get_permalink( wp_get_post_parent_id( $content_post ) ) );
        }

        return $permalink;
    }

    /**
     * Create view recurrence list page function
     */
    public function view_all_recurrence_page() {

        if ( current_user_can( 'manage_etn_event' ) ) {
            add_submenu_page(
                '',
                '',
                '',
                'read',
                'view_recurrences', [$this, 'all_recurrence'],
                10
            );
        }

    }

	/**
	 * Shows All Recurrences Of A Parent Event
	 *
	 * @return void
	 */
    public function all_recurrence() {
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        $columns = [
            'name'     => esc_html__( 'Name', 'eventin' ),
            'location' => esc_html__( 'Location', 'eventin' ),
            'schedule' => esc_html__( 'Schedule', 'eventin' ),
        ];

        $recurrence_list = [
            'singular_name' => esc_html__( 'Recurrences', 'eventin' ),
            'plural_name'   => esc_html__( 'Recurrence', 'eventin' ),
            'event_id'      => $id,
            'columns'       => $columns,
        ];
        ?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'View all recurrences', 'eventin' )?></h1>
			<div class="wrap etn-recurring-list">
				<form method="POST">
					<?php
						$table = new \Etn\Base\Table( $recurrence_list );
						$table->preparing_items();
						$table->display();
					?>
				</form>
			</div>
		</div>
		<?php
	}

    /**
     * Detach child event from parent event
     *
     * @return void
     */
    public function detach_child_event() {

        if ( is_admin() && isset( $_GET['action'] ) && 'detach' == sanitize_text_field( $_GET['action'] ) ) {

            if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'detach_nonce' ) ) {
                return false;
            } else {
                $child_event_id = absint( $_GET['post'] ); // child event id

                $this->detach_this_child_event_from_parent( $child_event_id );

                // redirect to etn custom post type
                wp_safe_redirect(
                    esc_url(
                        site_url( '/wp-admin/edit.php?post_type=etn' )
                    )
                );
                exit();
            }

        }

    }

    /**
     * Override edit action button for child events
     */
    public function child_recurring_event_action( $actions, $post ) {

        if ( $post->post_type == 'etn' ) {
            $recurring_enabled = get_post_meta( $post->ID, 'recurring_enabled', true );
            $actions_link      = [];

            if ( 0 !== wp_get_post_parent_id( $post ) ) {

				// Recurring event has child event.
                //This condition is only for child custom post type
                $url       = admin_url( 'post.php?post=etn&post=' . absint( $post->ID ) );
                $recur     = admin_url( 'post.php?post=' . absint( wp_get_post_parent_id( $post ) ) );
                $recur_url = add_query_arg( [ 'action' => 'edit' ], $recur );
                // Add detach , recurrences button
                $detach_link = wp_nonce_url( add_query_arg( [ 'action' => 'detach' ], $url ), 'detach_nonce' );

                $actions_link = [
                    'edit_recur_link' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $recur_url ), esc_html__( 'Edit All Recurrences', 'eventin' ) ),
                    'detach'          => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $detach_link ), esc_html__( 'Detach', 'eventin' ) ),
                ];

            } elseif ( 0 == wp_get_post_parent_id( $post ) && "" !== $recurring_enabled && "no" !== $recurring_enabled ) {
                // event in parent and recurring is on.
                $view_recur   = admin_url( 'admin.php?page=view_recurrences&id=' . absint( $post->ID ) );
                $all_links    = wp_nonce_url( add_query_arg( [ 'action' => 'view_recurrences' ], $view_recur ), 'recurr_nonce' );
                $actions_link = [
                    'view_recurrences' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( $all_links ), esc_html__( 'View all recurrences', 'eventin' ) ),
                ];
            }

            if ( current_user_can( 'manage_options', $post->ID ) ) {
                // Add new link
                $actions = array_merge( $actions, $actions_link );
            }

        }

        return $actions;
    }

    /**
     * Create Recurrences After Insert / Update Event
     *
     * @param [type] $post_id
     * @param [type] $post
     * @param [type] $update
     * @return void
     */
    public function create_recurrences( $event) {
        $event = new Event_Model( $event );
        
        if ( 'yes' !== $event->recurring_enabled ) {
            return;
        }

        $post_id = $event->id;
		//If it's custom post type etn
        
        $recurring_events               = $this->check_recurring_rules( $post_id );
        $maybe_recurring_actions_needed = $recurring_events['is_recurring'] ? true : false;
        $is_recurring_option_enabled    = get_post_meta( $post_id, 'recurring_enabled', true ); // on/no

        if ( !isset( $is_recurring_option_enabled ) || $is_recurring_option_enabled == 'no' ) {

            // not trying to use the recurring event feature
            // check if there's existing recurrence. if so, try to remove them
            $this->remove_all_child_events_of_parent( $post_id );

            return;
        }

        if ( $maybe_recurring_actions_needed ) {
            $parent_post_slug    = basename( get_permalink( $post_id ) );
            $new_matching_events = ( !empty( $recurring_events['matching_events'] ) && is_array( $recurring_events['matching_events'] ) ) ? $recurring_events['matching_events'] : [];
            $existing_matches    = ( !empty( get_post_meta( $post_id, 'etn_recurrence_timestamps', true ) ) && is_array( get_post_meta( $post_id, 'etn_recurrence_timestamps', true ) ) ) ? get_post_meta( $post_id, 'etn_recurrence_timestamps', true ) : [];

            $recurrence_data        = $this->valid_recurrences( $new_matching_events, $existing_matches );
            $matching_to_be_created = !empty( $recurrence_data['create_events'] ) ? $recurrence_data['create_events'] : [];
            $events_to_be_removed   = !empty( $recurrence_data['remove_events'] ) ? $recurrence_data['remove_events'] : [];

            // process existing recurrence data which are now junk.
            $this->process_existing_junk_recurrences( $post_id, $parent_post_slug, $events_to_be_removed );

            // update and replace all recurrence meta data with parent meta
            $this->process_existing_recurrence_meta( $post_id );

            // process newly eligible valid recurrences
            if ( is_array( $matching_to_be_created ) && count( $matching_to_be_created ) > 0 ) {
                $recurrence_span  = empty( $recurring_events['recurrence_span'] ) ? 0 : absint( $recurring_events['recurrence_span'] ) - 1;
                $parent_post_meta = get_post_meta( $post_id );
                $child_post_meta  = $this->process_child_post_meta_from_parent_meta( $parent_post_meta );

                foreach ( $matching_to_be_created as $key => $single_match_day ) {
                    $recurrence_start_date = gmdate( "Y-m-d", $single_match_day );
                    $recurrence_end_date   = date( 'Y-m-d', strtotime( " + $recurrence_span day", strtotime( $recurrence_start_date ) ) );
                    $recurrence_event_slug = Helper::sanitize_recurring_event_slug( $parent_post_slug, $recurrence_start_date );

                    // copy taxonomies from parent event
                    $etn_taxonomies = [ 'etn_category', 'etn_tags' ];
                    $taxonomy_list  = [];

                    foreach ( $etn_taxonomies as $taxonomy ) {
                        $tax_ids                  = wp_get_post_terms( $post_id, $taxonomy, [ "fields" => "ids" ] );
                        $taxonomy_list[$taxonomy] = $tax_ids;
                    }

                    $recurrence_args = [
                        'post_id'      => $post_id,
                        'post_name'    => $recurrence_event_slug,
                        'post_title'   => get_the_title( $post_id ), // same post title as parent post
                        'post_content' => get_post( $post_id )->post_content, // same post content as parent post
                        'post_excerpt' => get_post( $post_id )->post_excerpt, // same post excerpt as parent post
                        'etn_start_date' => $recurrence_start_date,
                        'etn_end_date' => $recurrence_end_date,
                        'tax_input'    => $taxonomy_list,
                    ];

                    $child_post_meta['etn_start_date'] = $recurrence_start_date;
                    $child_post_meta['etn_end_date']   = $recurrence_end_date;
            
                    $this->create_recurring_event( $recurrence_args, $child_post_meta );
                }

                update_post_meta( $post_id, 'etn_recurrence_timestamps', $new_matching_events );
            }

            return;
        }
    }

    /**
     * Update Existing Recurrence Meta
     *
     * @param [type] $post_id
     * @return void
     */
    public function process_existing_recurrence_meta( $parent_post_id ) {
        $has_child_events = Helper::get_child_events( $parent_post_id );

		//check if this event has child events
        if ( false !== $has_child_events && is_array( $has_child_events ) && !empty( $has_child_events ) ) {

            // recurring event having existing child
            $parent_post_title   = get_the_title( $parent_post_id );
            $parent_post_content = get_the_content( null, false, $parent_post_id );
            $parent_post_excerpt = get_the_excerpt( $parent_post_id );
            $parent_post_meta    = get_post_meta( $parent_post_id );
            $recurrence_span     = !empty( get_post_meta( $parent_post_id, 'etn_event_recurrence', true )['recurrence_span'] ) ? get_post_meta( $parent_post_id, 'etn_event_recurrence', true )['recurrence_span'] : 1;
            $recurrence_span     = empty( $recurrence_span ) ? 0 : absint( $recurrence_span ) - 1;
            $child_post_meta     = $this->process_child_post_meta_from_parent_meta( $parent_post_meta );

            /**
             * Updated recurring child.
             * 
             * @since 4.0.0
             */
            $parent_event    = new Event_Model( $parent_post_id );
            $parent_metadata = $parent_event->get_data();

            // Get parent categories.
            $taxonomy        = 'etn_category';
            $categories      = $parent_event->get_term_ids( $taxonomy );

            // Get parent tags.
            $taxonomy = 'etn_tags';
            $tags     = $parent_event->get_term_ids( $taxonomy );
            
            $parent_post = get_post( $parent_post_id );
            $args = [
                'post_title'    => get_the_title( $parent_post_id ),
                'post_content'  => $parent_post->post_content,
                'post_status'   => get_post_status( $parent_post_id ),
            ];

            $args = wp_parse_args( $parent_metadata, $args );

            $ticket_variations = ! empty( $args['etn_ticket_variations'] ) ? $args['etn_ticket_variations'] : [];

            $args = $this->process_child_post_meta_from_parent_meta( $args );

            foreach ( $has_child_events as $child_event ) {
                $recurrence_id = $child_event->ID;

                $recurring_child_event = new Event_Model( $recurrence_id );
                $banner_id = get_post_meta( $parent_post_id, 'event_banner_id', true );
                set_post_thumbnail( $recurrence_id, $banner_id );

				// generate new start-date and end-date according to updated span days
                if ( !empty( get_post_meta( $recurrence_id, 'etn_start_date', true ) ) ) {
        $recurrence_start_date           = get_post_meta( $recurrence_id, 'etn_start_date', true );
                    $recurrence_end_date             = date( 'Y-m-d', strtotime( " + $recurrence_span day", strtotime( $recurrence_start_date ) ) );

                    $args['etn_end_date'] = $recurrence_end_date;
                }

                //generate new available and sold ticket
                $ticket_variations = $this->prepare_child_ticket_data( $recurrence_id, $ticket_variations );

                $args['etn_ticket_variations'] = $ticket_variations;
                
                $recurring_child_event->update( $args );

                // Update category.
                $recurring_child_event->assign_post_terms( 'etn_category', $categories );

                // Update tags.
                $recurring_child_event->assign_post_terms( 'etn_tags', $tags );
            }

        }

    }

    /**
     * Check if it is recurring
     */
    public function is_recurring( $post_id ) {

        $is_enabled = get_post_meta( $post_id, 'recurring_enabled', true );
        $start_date = get_post_meta( $post_id, 'etn_start_date', true );
        $end_date   = get_post_meta( $post_id, 'etn_end_date', true );

        if ( !empty( $start_date ) && !empty( $end_date ) && ($is_enabled == 'on' || $is_enabled == 'yes' ) ) {
            return true;
        }

        $args     = [ 'post_parent' => $post_id ];
        $children = get_children( $args );

        if ( !empty( $children ) ) {
            return true;
        }

        return false;
    }

    /**
     * CHeck For Existing Matched Recurrences
     *
     * @param [type] $new_matches
     * @param [type] $existing_matches
     * @return void
     */
    public function valid_recurrences( $new_matching_events, $existing_matching_events = [] ) {

        $recurrence_array = [
            'create_events' => [],
            'remove_events' => [],
        ];

        if ( empty( $existing_matching_events ) ) {
            $recurrence_array['create_events'] = $new_matching_events;
            return $recurrence_array;
        }

        $current_timestamp = ( new \DateTime() )->getTimestamp();
        $create_events     = array_diff( $new_matching_events, $existing_matching_events );
        $remove_events     = array_diff( $existing_matching_events, $new_matching_events );

        if ( is_array( $remove_events ) && !empty( $remove_events ) ) {

            foreach ( $remove_events as $key => $timestamp ) {

                if ( $timestamp < $current_timestamp ) {
                    unset( $remove_events[$key] );
                }

            }

        }

        $recurrence_array['create_events'] = $create_events;
        $recurrence_array['remove_events'] = $remove_events;

        return $recurrence_array;
    }

    /**
     * Check recurring rules. Is it daily/weekly/monthly/yearly
     */
    public function check_recurring_rules( $post_id ) {

        $result_arr = [
            'is_recurring' => false,
        ];

        if ( $this->is_recurring( $post_id ) ) {
            $freq             = get_post_meta( $post_id, 'etn_event_recurrence', true );
            $frequency        = ( !empty( $freq['recurrence_freq'] ) && 'no' != $freq['recurrence_freq'] ) ? $freq['recurrence_freq'] : '';
            $event_start_time = !empty( get_post_meta( $post_id, 'etn_start_time', true ) ) ? get_post_meta( $post_id, 'etn_start_time', true ) : '';
            $event_end_date   = !empty( get_post_meta( $post_id, 'etn_end_date', true ) ) ? get_post_meta( $post_id, 'etn_end_date', true ) : '';
            // get start and end date
            $event_range = $this->etn_event_range( $post_id );

            if ( !empty( $frequency ) ) {
                $result_arr['is_recurring'] = true;

                if ( !empty( $freq['span_type'] ) && $freq['span_type'] == "single" ) {
                    $result_arr['recurrence_span'] = 0;
                } else {
                    $result_arr['recurrence_span'] = !empty( $freq['recurrence_span'] ) ? $freq['recurrence_span'] : 1;
                }

                switch ( $freq['recurrence_freq'] ) {
                case 'day':
                    $daily_interval                = !empty( $freq['recurrence_daily_interval'] ) ? intval( $freq['recurrence_daily_interval'] ) : 1;
                    $result_arr['matching_events'] = $this->daily_recurrence_calculation( $freq, $daily_interval, $event_range['start_date'], $event_range['end_date'], $event_range['etn_start_range'] );
                    break;

                case 'week':
                    $recurrence_weekly_day         = ( isset( $freq['recurrence_weekly_day'] ) && is_array( $freq['recurrence_weekly_day'] ) ) ? $freq['recurrence_weekly_day'] : [];
                    $result_arr['matching_events'] = $this->weekly_recurrence_calculation( $freq, $recurrence_weekly_day, $event_range['start_date'], $event_range['end_date'], $event_range['etn_start_range'] );
                    break;

                case 'month':
                    $monthly_date                  = !empty( $freq['recurrence_monthly_date'] ) ? intval( $freq['recurrence_monthly_date'] ) : 1;
                    $result_arr['matching_events'] = $this->monthly_recurrence_calculation( $monthly_date, $event_range['start_date'], $event_range['end_date'], $event_range['etn_start_range'] );
                    break;

                case 'month-advanced':
                    $monthly_advanced_interval     = !empty( $freq['recurrence_monthly_advanced_interval'] ) ? intval( $freq['recurrence_monthly_advanced_interval'] ) : 1;
                    $monthly_advanced_week_no      = !empty( $freq['recurrence_monthly_advanced_week_no'] ) ? intval( $freq['recurrence_monthly_advanced_week_no'] ) : 1;
                    $monthly_advanced_weekday_no   = !empty( $freq['recurrence_monthly_advanced_weekday_no'] ) ? intval( $freq['recurrence_monthly_advanced_weekday_no'] ) : 0;
                    $result_arr['matching_events'] = $this->monthly_advanced_recurrence_calculation( $freq, $monthly_advanced_interval, $monthly_advanced_week_no, $monthly_advanced_weekday_no, $event_range['start_date'], $event_range['end_date'], $event_range['etn_start_range'] );
                    break;

                case 'year':
                    $recurrence_yearly_month       = !empty( $freq['recurrence_yearly_month'] ) ? $freq['recurrence_yearly_month'] : 1;
                    $recurrence_yearly_date        = !empty( $freq['recurrence_yearly_date'] ) ? $freq['recurrence_yearly_date'] : 1;
                    $result_arr['matching_events'] = $this->yearly_recurrence_calculation( $freq, $recurrence_yearly_month, $recurrence_yearly_date, $event_range['start_date'], $event_range['end_date'], $event_range['etn_start_range'] );
                    break;

                default:
                    break;
                }

            }

        }

        return $result_arr;
    }

    /**
     * Process Parent Post Meta & Prepare For Child Post
     *
     * @param [type] $parent_post_meta
     * @return void
     */
    public function process_child_post_meta_from_parent_meta( $parent_post_meta = [] ) {

		// remove recurrence data for child post's
        if ( isset( $parent_post_meta['recurring_enabled'] ) ) {
            unset( $parent_post_meta['recurring_enabled'] );
        }

        if ( isset( $parent_post_meta['etn_event_recurrence'] ) ) {
            unset( $parent_post_meta['etn_event_recurrence'] );
        }

        if ( isset( $parent_post_meta['etn_sold_tickets'] ) ) {
            unset( $parent_post_meta['etn_sold_tickets'] );
        }

        if ( isset( $parent_post_meta['etn_start_date'] ) ) {
            unset( $parent_post_meta['etn_start_date'] );
        }

        if ( isset( $parent_post_meta['etn_end_date'] ) ) {
            unset( $parent_post_meta['etn_end_date'] );
        }

        if ( isset( $parent_post_meta['etn_total_sold_tickets'] ) ) {
            unset( $parent_post_meta['etn_total_sold_tickets'] );
        }

        return $parent_post_meta;
    }

    /**
     * Create Recurring Event For Each Possible Recurrence
     *
     * @param [type] $args
     * @param [type] $post_meta
     * @return void
     */
    public function create_recurring_event( $args, $post_meta ) {

        remove_action( 'wp_insert_post', [$this, 'create_recurring_event'] );
        $post = get_post( $args['post_id'] );

        $parent_event           = new Event_Model( $args['post_id'] );
        $data                   = $parent_event->get_data();
        $data['post_title']     = get_the_title($parent_event->id );
        $data['post_content']   = get_post( $parent_event->id )->post_content;
        $data['post_status']    = get_post_status($parent_event->id );
        $data['post_parent']    = $args['post_id'];
        $data['etn_start_date'] = $args['etn_start_date'];
        $data['etn_end_date']    = $args['etn_end_date'];
        $data['event_banner_id'] = get_post_meta( $parent_event->id, 'event_banner_id', true );
        $data['event_banner']    = get_post_meta( $parent_event->id, 'event_banner', true );

        unset( $data['recurring_enabled'] );
        unset( $data['event_recurrence'] );

        // Get parent categories.
        $taxonomy        = 'etn_category';
        $categories      = $parent_event->get_term_ids( $taxonomy );

        // Get parent tags.
        $taxonomy = 'etn_tags';
        $tags     = $parent_event->get_term_ids( $taxonomy );

		// insert child post if not exist and get child post id
        if ( !Helper::the_slug_exists( $args['post_name'] ) ) {
            $child_event = new Event_Model();
            $child_event->create( $data );

            $recurrence_id = $child_event->id;
            set_post_thumbnail( $recurrence_id, $data['event_banner_id'] );

            if ( $recurrence_id ) {
                foreach ( $post_meta as $meta_key => $meta_value ) {
                    $meta_value = is_array( $meta_value ) ? $meta_value[0] : $meta_value;

                    if ( is_serialized( $meta_value ) ) {
                        $meta_value = maybe_unserialize( $meta_value );
                    }

                    update_post_meta( $recurrence_id, $meta_key, $meta_value );
                }

                update_post_meta( get_the_ID(), "_price", 0 );
                update_post_meta( get_the_ID(), "_regular_price", 0 );
                update_post_meta( get_the_ID(), "_sale_price", 0 );
                update_post_meta( get_the_ID(), "_stock", 0 );
            }

            // Update category.
            $child_event->assign_post_terms( 'etn_category', $categories );

            // Update tags.
            $child_event->assign_post_terms( 'etn_tags', $tags );

        }

        add_action( 'wp_insert_post', [$this, 'create_recurring_event'] );

        return;
    }

    /**
     * Return events start and date
     */
    public function etn_event_range( $post_id ) {
        $event_start_date = !empty( get_post_meta( $post_id, 'etn_start_date', true ) ) ? get_post_meta( $post_id, 'etn_start_date', true ) : '';
        $event_end_date   = !empty( get_post_meta( $post_id, 'etn_end_date', true ) ) ? get_post_meta( $post_id, 'etn_end_date', true ) : '';

        $current_date = ( new \DateTime( date( 'Y-m-d H:i:s', strtotime( $event_start_date ) ) ) )->setTime( 0, 0, 0 );
        $start_date   = $current_date->getTimestamp();

        $final_date = ( new \DateTime( date( 'Y-m-d H:i:s', strtotime( $event_end_date ) ) ) )->setTime( 0, 0, 0 );
        $end_date   = $final_date->getTimestamp();

        return [ 'start_date' => $start_date, 'end_date' => $end_date, 'etn_start_range' => $event_start_date ];
    }

    /**
     * Recurring event monthly calculation
     *
     * @param [type] $monthly_date
     * @param [type] $event_start_date
     * @param [type] $event_end_date
     * @param [type] $event_start_range
     * @return void
     */
    public function monthly_advanced_recurrence_calculation( $freq, $monthly_advanced_interval, $monthly_advanced_week_no, $monthly_advanced_weekday_no, $event_start_date, $event_end_date, $event_start_range ) {
        $current_date = ( new \DateTime( date( 'Y-m-d H:i:s', strtotime( $event_start_range ) ) ) )->setTime( 0, 0, 0 );
        $current_date->modify( $current_date->format( 'Y-m-1 00:00:00' ) ); //Start date on first day of month

        $matching_days = [];

        while ( $current_date->getTimestamp() <= $event_end_date ) {

            $last_day_of_month   = $current_date->format( 't' ); // Total number of days in this month, e.g. 28 through 31
            $current_week_day    = $current_date->format( 'w' ); // Numeric representation of the current day of the week, e.g. 0 (for Sunday) through 6 (for Saturday)
            $matching_month_days = [];

			// Loop through days of this month and save matching days to temp array
            for ( $day = 1; $day <= $last_day_of_month; $day++ ) {
                if ( (int) $current_week_day == $monthly_advanced_weekday_no ) {
                    $matching_month_days[] = $day;
                }

                $current_week_day = ( $current_week_day < 6 ) ? $current_week_day + 1 : 0;
            }

            // Now grab from the matching day of the month
            $matching_day = false;

            if ( $monthly_advanced_week_no > 0 ) {
				//date might not exist (e.g. fifth Friday of a month), so only add if it exists
                if ( !empty( $matching_month_days[$monthly_advanced_week_no - 1] ) ) {
                    $matching_day = $matching_month_days[$monthly_advanced_week_no - 1];
                }

            } else {
                //last matching of month, so we pop the last matching day from the matching day array
                $matching_day = array_pop( $matching_month_days );
            }

			// if we have a matching day, get the timestamp
			// make sure it's within our start/end dates for the event
            if ( !empty( $matching_day ) ) {
                $matching_date = $current_date->setDate( $current_date->format( 'Y' ), $current_date->format( 'm' ), $matching_day )->getTimestamp();
                if ( $matching_date >= $event_start_date && $matching_date <= $event_end_date ) {
                    $matching_days[] = $matching_date;
                }

            }

            // set current date to the 1st day of current month, then add the monthly interval to the current date and start checking from there
            $current_date->modify( $current_date->format( 'Y-m-01' ) );
            $current_date->add( new \DateInterval( 'P' . $monthly_advanced_interval . 'M' ) );
        }

        sort( $matching_days );

        return $matching_days;
    }

    /**
     * Recurring event monthly calculation
     *
     * @param [type] $monthly_date
     * @param [type] $event_start_date
     * @param [type] $event_end_date
     * @param [type] $event_start_range
     * @return void
     */
    public function monthly_recurrence_calculation( $monthly_date, $event_start_date, $event_end_date, $event_start_range ) {
        $current_date = ( new \DateTime( date( 'Y-m-d H:i:s', strtotime( $event_start_range ) ) ) )->setTime( 0, 0, 0 );
        $current_date->modify( $current_date->format( 'Y-m-' . $monthly_date . ' 00:00:00' ) ); //Start date on first day of month, done this way to avoid 'first day of' issues in PHP < 5.6
        $current_date->getTimestamp();

        $matching_days = [];

        while ( $current_date->getTimestamp() <= $event_end_date ) {

            $matching_days[] = $current_date->getTimestamp();

			//if we have a matching day, get the timestamp, make sure it's within our start/end dates for the event, and add to array if it is
            if ( !empty( $matching_day ) ) {
                $matching_date = $current_date->setDate( $current_date->format( 'Y' ), $current_date->format( 'm' ), $matching_day )->getTimestamp();
                if ( $matching_date >= $event_start_date && $matching_date <= $event_end_date ) {
                    $matching_days[] = $matching_date;
                }

            }

            $current_date->modify( $current_date->format( 'Y-m-' . $monthly_date . '' ) );
            $current_date->add( new \DateInterval( 'P1M' ) ); // Every month
        }

        sort( $matching_days );

        return $matching_days;
    }

    /**
     * Weekly Recurrence Calculation
     *
     * @param [type] $freq
     * @param [type] $recurrence_weekly_day
     * @param [type] $event_start_date
     * @param [type] $event_end_date
     * @return void
     */
    public function weekly_recurrence_calculation( $freq, $recurrence_weekly_day, $event_start_date, $event_end_date, $event_start_range ) {

        $weekdays     = $recurrence_weekly_day;
        $current_date = ( new \DateTime( date( 'Y-m-d H:i:s', strtotime( $event_start_range ) ) ) )->setTime( 0, 0, 0 );
        $start_date   = $event_start_date;
        $end_date     = $event_end_date;

        $start_of_week          = get_option( 'start_of_week' );
        $start_weekday_dates    = [];
        $start_date_day_of_week = $current_date->format( 'w' );

        for ( $i = 0; $i < 7; $i++ ) {

            if ( in_array( $current_date->format( 'w' ), $weekdays ) ) {
                $start_weekday_dates[] = $current_date->getTimestamp();
            }

            $date_interval = new \DateInterval( 'P1D' );
            $current_date->add( $date_interval );
        }

        $matching_days = [];

        foreach ( $start_weekday_dates as $weekday_date ) {
            $current_date->setTimestamp( $weekday_date );

            while ( $current_date->getTimestamp() <= $end_date ) {

                if ( $current_date->getTimestamp() >= $start_date && $current_date->getTimestamp() <= $end_date ) {
                    $matching_days[] = $current_date->getTimestamp();
                }

                $date_interval = new \DateInterval( 'P7D' );
                $current_date->add( $date_interval ); // add 7 days interval
            }

        }

        sort( $matching_days );

        return $matching_days;
    }

    /**
     * Weekly Recurrence Calculation
     *
     * @param [type] $freq
     * @param [type] $recurrence_weekly_day
     * @param [type] $event_start_date
     * @param [type] $event_end_date
     * @return void
     */
    public function yearly_recurrence_calculation( $freq, $recurrence_yearly_month, $recurrence_yearly_date, $event_start_date, $event_end_date, $event_start_range ) {
        $selected_recur_date = date( 'Y', strtotime( $event_start_range ) ) . '-' . $recurrence_yearly_month . '-' . $recurrence_yearly_date;
        $current_date        = ( new \DateTime( date( 'Y-m-d H:i:s', strtotime( $selected_recur_date ) ) ) )->setTime( 0, 0, 0 );
        $matching_days       = [];

        while ( $current_date->getTimestamp() <= $event_end_date ) {

            if ( $current_date->getTimestamp() >= $event_start_date && $current_date->getTimestamp() <= $event_end_date ) {
                $matching_days[] = $current_date->getTimestamp();
            }

            $date_interval = new \DateInterval( 'P1Y' );
            $current_date->add( $date_interval ); // add 1 year interval
        }

        if ( !empty( $matching_days ) ) {
            sort( $matching_days );
        }

        return $matching_days;
    }

    /**
     * Process Existing Recurrences That Are Not Needed Anymore
     *
     * @param array $events_to_be_removed
     * @return void
     */
    public function process_existing_junk_recurrences( $parent_post_id, $parent_post_slug, $events_to_be_removed = [] ) {

        if ( empty( $events_to_be_removed ) ) {
            return;
        }

        foreach ( $events_to_be_removed as $single_match_day ) {
            $recurrence_start_date = gmdate( "Y-m-d", $single_match_day );
            $recurrence_event_slug = Helper::sanitize_recurring_event_slug( $parent_post_slug, $recurrence_start_date );
            $args                  = [
                'name'        => $recurrence_event_slug,
                'post_type'   => "etn",
                'post_parent' => $parent_post_id,
                'numberposts' => 1,
            ];
            $single_recurrence = get_posts( $args );

            if ( $single_recurrence ) {
                $recurrence_id = $single_recurrence[0]->ID;
                wp_delete_post( $recurrence_id );
            }

        }

    }

    /**
     * Daily Recurrence Calculation
     *
     * @param [type] $freq
     * @param [type] $recurrence_weekly_day
     * @param [type] $event_start_date
     * @param [type] $event_end_date
     * @return void
     */
    public function daily_recurrence_calculation( $freq, $daily_interval, $event_start_date, $event_end_date, $event_start_range ) {
        $interval = $daily_interval;

        $current_date  = ( new \DateTime( $event_start_range ) )->setTime( 0, 0, 0 );
        $matching_days = [];

        while ( $current_date->getTimestamp() <= $event_end_date ) {

            if ( $current_date->getTimestamp() >= $event_start_date ) {
                $matching_days[] = $current_date->getTimestamp();
            }

            $date_interval = new \DateInterval( 'P' . $interval . 'D' );
            $current_date->add( $date_interval ); // add 1 year interval
        }

        if ( !empty( $matching_days ) ) {
            sort( $matching_days );
        }

        return $matching_days;
    }

    /**
     * Add admin notice about recurring event warning
     *
     * @return void
     */
    public function admin_notices() {
        //When editing
        global $post, $EM_Event, $pagenow;

        if ( $pagenow == 'post.php' && ( $post->post_type == 'etn' ) && ( false !== Helper::get_child_events( $post->ID ) ) && ( !empty( Helper::get_child_events( $post->ID ) ) ) ) {
            $view_all_recurrences = admin_url( 'admin.php?page=view_recurrences&id=' . absint( $post->ID ) );
            $all_links            = wp_nonce_url( add_query_arg( [ 'action' => 'view_recurrences' ], $view_all_recurrences ), 'recurr_nonce' );
            ?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong> <?php echo esc_html__( 'WARNING: This is a recurring event.', 'eventin' ); ?> </strong>
				</p>
				<p>
					<?php echo esc_html__( 'Any kind of change / modification to this event will be applied to all recurrences attached to this event and will overwrite any changes that has been made to those individual recurrences.', 'eventin' ); ?>
				</p>
				<p>
					<?php echo esc_html__( 'Individual recurrences and their purchase data will be preserved if event start time, event end time or recurrence settings are not changed.', 'eventin' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( $all_links ); ?>" target='_blank' rel="noopener"><?php echo esc_html__( 'You can edit individual recurrences and detach them from this recurring event. It won\'t effect any other recurrence', 'eventin' ); ?></a>
				</p>
			</div>
			<?php
		}

    }

	/**
	 * Remove Child Events From parent
	 *
	 * @param [type] $parent_post_id
	 * @return void
	 */
    public function remove_all_child_events_of_parent( $parent_post_id ) {
        $has_recurring_children = Helper::get_child_events( $parent_post_id );

        if ( $has_recurring_children && is_array( $has_recurring_children ) && !empty( $has_recurring_children ) ) {

            foreach ( $has_recurring_children as $single_child ) {
                $child_event_id = $single_child->ID;
                $this->detach_this_child_event_from_parent( $child_event_id );
            }

        }

    }

    /**
     * Detach Child Event From Parent & Make This As A Single Event
     *
     * @param [type] $child_event_id
     * @return void
     */
    public function detach_this_child_event_from_parent( $child_event_id ) {

        $event_content = get_post( $child_event_id ); // child event post details
		
        if ( !empty( $event_content ) && 0 !== wp_get_post_parent_id( $event_content ) ) {
            // So it is a child event , Change parent of child post
            wp_update_post( ['ID' => $child_event_id, 'post_parent' => 0] );
        }

    }

    /**
     * Prepare recurring child ticket variations
     *
     * @param   integer  $post_id
     * @param   array  $ticket_variations
     *
     * @return  array
     */
    private function prepare_child_ticket_data( $post_id, $ticket_variations ) {
        

        $modified_tickets = [];
        $post_start_date   = get_post_meta( $post_id, 'etn_start_date', true );
        $post_publish_date = get_the_date( 'Y-m-d', $post_id );

        if ( $ticket_variations ) {
            foreach( $ticket_variations as $ticket ) {
                $ticket['start_date'] = $post_publish_date;
                $ticket['end_date']   = $post_start_date;

                $modified_tickets[] = $ticket;
            }
        }

        return $modified_tickets;
    }

}
