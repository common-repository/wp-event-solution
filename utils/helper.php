<?php

namespace Etn\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Global helper class.
 *
 * @since 1.0.0
 */

use DateTime;
use Etn\Core\Event\Event_Model;
use Eventin\Speaker\Api\SpeakerController;
use WP_Query;
use WP_User_Query;

class Helper {

	use \Etn\Traits\Singleton;

	private static $settings_key = 'etn_event_options';

	/**
	 * Auto generate classname from path.
	 */
	public static function make_classname( $dirname ) {
		$dirname    = pathinfo( $dirname, PATHINFO_FILENAME );
		$class_name = explode( '-', $dirname );
		$class_name = array_map( 'ucfirst', $class_name );
		$class_name = implode( '_', $class_name );

		return $class_name;
	}

	/**
	 * Renders provided markup
	 */
	public static function render( $content ) {
		return $content;
	}

	/**
	 * Filters only accepted kses
	 */
	public static function kses( $raw ) {
		$allowed_tags = [
			'a'                             => [
				'class'  => [],
				'href'   => [],
				'rel'    => [],
				'title'  => [],
				'target' => [],
			],
			'input'                         => [
				'value'              => [],
				'type'               => [],
				'size'               => [],
				'name'               => [],
				'checked'            => [],
				'data-value'         => [],
				'data-default-color' => [],
				'placeholder'        => [],
				'id'                 => [],
				'class'              => [],
				'min'                => [],
				'step'               => [],
				'readonly'           => 'readonly',
			],
			'button'                        => [
				'type'    => [],
				'name'    => [],
				'id'      => [],
				'class'   => [],
				'onclick' => [],
			],
			'select'                        => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => [],
				'option'      => [
					'value'   => [],
					'checked' => [],
				],
			],
			'textarea'                      => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'rows'        => [],
				'cols'        => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => [],
			],
			'abbr'                          => [
				'title' => [],
			],
			'b'                             => [],
			'blockquote'                    => [
				'cite' => [],
			],
			'cite'                          => [
				'title' => [],
			],
			'code'                          => [],
			'del'                           => [
				'datetime' => [],
				'title'    => [],
			],
			'dd'                            => [],
			'div'                           => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'dl'                            => [],
			'dt'                            => [],
			'em'                            => [],
			'h1'                            => [
				'class' => [],
			],
			'h2'                            => [
				'class' => [],
			],
			'h3'                            => [
				'class' => [],
			],
			'h4'                            => [
				'class' => [],
			],
			'h5'                            => [
				'class' => [],
			],
			'h6'                            => [
				'class' => [],
			],
			'i'                             => [
				'class' => [],
			],
			'img'                           => [
				'alt'    => [],
				'class'  => [],
				'height' => [],
				'src'    => [],
				'width'  => [],
			],
			'li'                            => [
				'class' => [],
			],
			'ol'                            => [
				'class' => [],
			],
			'p'                             => [
				'class' => [],
			],
			'q'                             => [
				'cite'  => [],
				'title' => [],
			],
			'span'                          => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'iframe'                        => [
				'width'       => [],
				'height'      => [],
				'scrolling'   => [],
				'frameborder' => [],
				'allow'       => [],
				'src'         => [],
			],
			'strike'                        => [],
			'br'                            => [],
			'strong'                        => [],
			'data-wow-duration'             => [],
			'data-wow-delay'                => [],
			'data-wallpaper-options'        => [],
			'data-stellar-background-ratio' => [],
			'ul'                            => [
				'class' => [],
			],
			'label'                         => [
				'class'      => [],
				'for'        => [],
				'data-left'  => [],
				'data-right' => [],
			],
			'form'                          => [
				'class'  => [],
				'id'     => [],
				'role'   => [],
				'action' => [],
				'method' => [],
			],
		];

		if ( function_exists( 'wp_kses' ) ) { // WP is here
			return wp_kses( $raw, $allowed_tags );
		} else {
			return $raw;
		}

	}

	/**
	 * internal
	 *
	 * @param [type] $text
	 *
	 * @return void
	 */
	public static function kspan( $text ) {
		return str_replace( [ '{', '}' ], [ '<span>', '</span>' ], self::kses( $text ) );
	}

	/**
	 * retuns trimmed word
	 */
	public static function trim_words( $text, $num_words ) {
		return wp_trim_words( $text, $num_words, '' );
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $id
	 *	 */
	public static function img_meta( $id ) {
		$attachment = get_post( $id );

		if ( $attachment == null || $attachment->post_type != 'attachment' ) {
			return null;
		}

		return [
			'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'caption'     => $attachment->post_excerpt,
			'description' => $attachment->post_content,
			'href'        => get_permalink( $attachment->ID ),
			'src'         => $attachment->guid,
			'title'       => $attachment->post_title,
		];
	}

	/**
	 * Date format
	 *
	 */
	public static function get_date_formats() {
		return [
			'Y-m-d',
			'm/d/Y',
			'd/m/Y',
			'm-d-Y',
			'd-m-Y',
			'Y.m.d',
			'm.d.Y',
			'd.m.Y',
			'd M Y',
			'j F Y',
		];
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $path
	 *
	 * @return void
	 */
	public static function safe_path( $path ) {
		$path = str_replace( [ '//', '\\\\' ], [ '/', '\\' ], $path );

		return str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $path );
	}

	/**
	 * Convert a multi-dimensional array into a single-dimensional array.
	 *
	 * @param array $array The multi-dimensional array.
	 *
	 * @return array
	 * @author Sean Cannon, LitmusBox.com | seanc@litmusbox.com
	 */
	public static function array_flatten( $array ) {

		if ( ! is_array( $array ) ) {
			return false;
		}

		$result = [];

		foreach ( $array as $key => $value ) {

			if ( is_array( $value ) ) {
				$result = array_merge( $result, self::array_flatten( $value ) );
			} else {
				$result = array_merge( $result, [ $key => $value ] );
			}

		}

		return $result;
	}

	public static function show_parent_child( $show_parent_event, $show_child_event ) {
		if( 'yes' === $show_parent_event ) {
			return 'yes' === $show_child_event ? 'show_both' : 'parent';
		} else {
			return 'yes' === $show_child_event ? 'child' : 'hide_both';
		}
	}

 
	/**
	 * User query to get data for widget and shortcode
	 */
	public static function user_data_query( $count = '-1', $order = 'DESC', $term_arr = null, $orderby = 'ID' ) {

		$user_data = [];

		// Ensure $term_arr is a string before using explode
		if (is_array($term_arr)) {
			$term_arr = implode(',', $term_arr);
		}

		$term_arrr = explode(',', $term_arr);

		if( !empty( $term_arrr ) ) {
			foreach( $term_arrr as $group_id ) {
				$args = [
					'number'     => $count,
					'role__in'   => array( 'etn-speaker', 'etn-organizer' ),
					'order'      => $order,
					'orderby'    => $orderby,
					'meta_query' => [
						'relation' => 'AND',
						[
							'key'     => 'etn_speaker_group',
							'value'   => strval($group_id),
							'compare' => 'LIKE',
						],
					],
				];

				// Check if the get_users() function returns an array
				if ( is_array( get_users( $args ) ) ) {
					$user_data = array_merge( $user_data, get_users( $args ) );
				}
			}
		}
		
		// Remove duplicates
		$unique_users = array_unique($user_data, SORT_REGULAR);

		// Sort the users by name if 'title' is passed as the orderby argument
		if ($orderby === 'title' || $orderby === 'name') {
			usort($unique_users, function($a, $b) use ($order) {
				return $order === 'ASC' ? strcmp($a->display_name, $b->display_name) : strcmp($b->display_name, $a->display_name);
			});
		}

		return $unique_users;
	}



	/**
	 * Post query to get data for widget and shortcode
	 */
	public static function post_data_query( $post_type, $count = null, $order = 'DESC', $term_arr = null, $taxonomy_slug = null, $post__in = null, $post_not_in = null, $tag__in = null, $orderby_meta = null, $orderby = 'post_date', $filter_with_status = null, $post_parent = '0', $post_author = '' ) {

		$data = [];
		$args = [
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'suppress_filters' => false,
			'tax_query'        => [
				'relation' => 'AND',
			],
		];

		if ( $post_author != '' ) {
			$args['author'] = $post_author;
		}

		if ( $order != null ) {

			if ( $orderby_meta == null ) {
				$args['orderby'] = $orderby;
			} else {
				$args['meta_key'] = $orderby;
				$args['orderby']  = $orderby_meta;
			}

			$args['order'] = strtoupper( $order );
		}

		if ( $post_not_in != null ) {
			$args['post__not_in'] = $post_not_in;
		}

		if ( $count != null ) {
			$args['posts_per_page'] = $count;
		}

		if ( $post__in != null ) {
			$args['post__in'] = $post__in;
		}

		// Elementor::If categories selected, add them to tax_query
		if ( is_array( $term_arr ) && ! empty( $term_arr ) ) {
			$categories = [
				'taxonomy'         => $taxonomy_slug,
				'terms'            => $term_arr,
				'field'            => 'id',
				'include_children' => true,
				'operator'         => 'IN',
			];
			array_push( $args['tax_query'], $categories );
		}

		// Elementor::If tags selected, add them to tax_query
		if ( ! empty( $tag__in ) && is_array( $tag__in ) ) {
			$tags = [
				'taxonomy'         => 'etn_tags',
				'terms'            => $tag__in,
				'field'            => 'id',
				'include_children' => true,
				'operator'         => 'IN',
			];
			array_push( $args['tax_query'], $tags );
		}

		// Elementor::If select upcoming  event , filter out the upcoming events
		if ( $post_type == "etn" ) {

			if ( $filter_with_status == 'upcoming' ) {

				$args['meta_query'] = [
					[
						'key'     => 'etn_start_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					],
				];
			}

			if ( $filter_with_status == 'expire' ) {

				$args['meta_query'] = [
					'relation' => 'AND',
					[
						'relation' => 'OR',
						[
							'key'     => 'etn_end_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '<',
							'type'    => 'DATE',

						],
						[
							'key'     => 'etn_end_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '=',
							'type'    => 'DATE',
						],
						[
							'key'     => 'etn_end_date',
							'value'   => '',
							'compare' => '=',
						],
					],
					[
						'key'     => 'etn_start_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '<',
						'type'    => 'DATE',
					],
				];
			}

		}

		if ( 'child' !== $post_parent || 'hide_both' == $post_parent ) {
			$parent_id           = ($post_parent == 'hide_both' || $post_parent == 'parent' ) ? '0' : $post_parent;
			$args['post_parent'] = $parent_id;
		}

		$data = get_posts( $args );
		// adding recurring tag
		$data = \Etn\Core\Event\Helper::instance()->recurring_tag( $data );

		if ( ( 'child' == $post_parent || 'hide_both' == $post_parent ) && ( is_array( $data ) && count( $data ) > 0 ) ) {
			// Delete all the Parent recurring event
			foreach ( $data as $index => $post ) {
				$post_id             = $post->ID;
				$is_recurring_parent = Helper::get_child_events( $post_id );
				if ( $is_recurring_parent ) {
					unset( $data[ $index ] );
				}
			}
		}

		return $data;
	}

	/**
	 * returns list of all speaker
	 * returns single speaker if speaker id is provuded
	 */
	public static function get_speakers() {
		$return_organizers = [];
		$user_ids = new SpeakerController();
		$users = $user_ids::get_etn_user_role();

		$args = [
			'include' => $users,
		];
		$organizers = new WP_User_Query( $args );

		foreach ( $organizers->results as $value ) {
			$return_organizers[ $value->data->ID ] = $value->data->display_name;
		}

		return $return_organizers;
	}


	/**
	 * Author page URL by ID
	 */
	public static function get_author_page_url_by_id($author_id) {
		// Get the author's user object to retrieve the user_nicename (slug)
		$author = get_user_by('id', $author_id);
		
		// Ensure the author exists
		if ($author) {
			// Get the author URL
			$author_url = get_author_posts_url($author_id, $author->user_nicename);
			return $author_url;
		}
		
		// Return false if the author is not found
		return false;
	}


	/**
	 * get all settings options
	 *
	 * @return void
	 */
	public static function get_settings() {
		return get_option( "etn_event_options" );
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $key
	 * @param string $default
	 *
	 * @return void
	 */
	public static function get_option( $key, $default = '' ) {
		$all_settings = get_option( self::$settings_key );

		return ( isset( $all_settings[ $key ] ) && $all_settings[ $key ] != '' ) ? $all_settings[ $key ] : $default;
	}

	/**
	 * get single data by meta
	 */
	public static function get_single_data_by_meta( $post_type, $limit, $key, $value, $sign = "=" ) {
		$args         = [
			'post_type'      => $post_type,
			'posts_per_page' => $limit,
			'meta_query'     => [
				[
					'key'     => $key,
					'value'   => $value,
					'compare' => $sign,
				],
			],
		];
		$query_result = get_posts( $args );

		return $query_result;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $key
	 * @param string $value
	 *
	 * @return void
	 */
	public static function update_option( $key, $value = '' ) {
		$all_settings         = get_option( self::$settings_key );
		$all_settings[ $key ] = $value;
		update_option( self::$settings_key, $all_settings );

		return true;
	}

	/**
	 * sanitizes given input
	 *
	 * @param string $data
	 *
	 * @return void
	 */
	public static function sanitize( string $data ) {
		return strip_tags(
			stripslashes(
				sanitize_text_field(
					filter_input( INPUT_POST, $data )
				)
			)
		);
	}

	/**
	 * returns category of a speaker
	 */
	public static function get_speakers_category( $id = null ) {
		$speaker_category = [];
		try {

			if ( is_null( $id ) ) {
				$terms = get_terms( [
					'taxonomy'   => 'etn_speaker_category',
					'hide_empty' => false,
				] );

				foreach ( $terms as $speakers ) {
					$speaker_category[ $speakers->term_id ] = $speakers->name;
				}

				return $speaker_category;
			} else {
				// return single speaker
				return get_post( $id );
			}

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * returns category of an event
	 *
	 * @param [type] $id
	 *
	 * @return array
	 */
	public static function get_event_category( $id = null ) {
		$event_category = [];
		$terms          = [];

		try {
			if ( is_null( $id ) || ! is_numeric( $id ) ) {
				$terms = get_terms( [
					'taxonomy'   => 'etn_category',
					'hide_empty' => false,
				] );
			} else {
				$terms = wp_get_post_terms( $id, 'etn_category' );
			}

			foreach ( $terms as $event ) {
				$event_category[ $event->term_id ] = $event->name;
			}

			return $event_category;

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * returns tag of an event
	 */
	public static function get_event_tag( $id = null ) {
		$event_tag = [];
		$terms     = [];

		try {
			if ( is_null( $id ) || ! is_numeric( $id ) ) {
				$terms = get_terms( [
					'taxonomy'   => 'etn_tags',
					'hide_empty' => false,
				] );
			} else {
				$terms = wp_get_post_terms( $id, 'etn_tags' );
			}

			// return $terms;

			foreach ( $terms as $event ) {
				$event_tag[ $event->term_id ] = $event->name;
			}

			return $event_tag;

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * returns event locations ids
	 *
	 * @param [type] $id
	 *
	 * @return array
	 */
	public static function get_event_locations( $id = null ) {
		$event_location = [];
		$terms          = [];

		try {
			if ( is_null( $id ) || ! is_numeric( $id ) ) {
				$terms = get_terms( [
					'taxonomy'   => 'etn_location',
					'hide_empty' => false,
				] );
			} else {
				$terms = wp_get_post_terms( $id, 'etn_location' );
			}

			foreach ( $terms as $event ) {
				$event_location[ $event->term_id ] = $event->name;
			}

			return $event_location;

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $id
	 *
	 * @return void
	 */
	public static function get_schedules( $id = null ) {
		$return_schedules = [];
		try {

			if ( is_null( $id ) ) {
				$args      = [
					'post_type'        => 'etn-schedule',
					'post_status'      => 'publish',
					'posts_per_page'   => - 1,
					'suppress_filters' => false,
				];
				$schedules = get_posts( $args );

				foreach ( $schedules as $value ) {
					$schedule_date                  = get_post_meta( $value->ID, 'etn_schedule_date', true );
					$return_schedules[ $value->ID ] = $value->post_title . " ($schedule_date)";
				}

				return $return_schedules;
			} else {
				// return single speaker
				return get_post( $id );
			}

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $id
	 *
	 * @return void
	 */
	public static function get_events( $id = null, $allow_child = false, $return_recurring_only = false, $upcoming = false, $seat_plan = 1 ) {
		$return_events = [];
		try {

			if ( is_null( $id ) ) {
				$args = [
					'post_type'      => 'etn',
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
				];

				if ( ! $allow_child ) {
					$args['post_parent'] = 0;
				}

				if ( $upcoming ) {
					$args['meta_query'] = array(
						array(
							'key'     => 'etn_end_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '>=',
							'type'    => 'DATE',
						),
					);
				}

				

				$events = get_posts( $args );

				foreach ( $events as $value ) {

					if ( $return_recurring_only ) {
						$args = array(
							'post_parent' => $value->ID,
							'post_type'   => 'etn',
						);
						$children = get_children( $args );

						if ( empty( $children ) ) {
							continue;
						}
					}
					$seat_plan_module = get_post_meta($value->ID,'seat_plan_module_enable',true);

					if ( $seat_plan == 1 ) {
						$return_events[ $value->ID ] = $value->post_title;
					}else if( $seat_plan == 0 && $seat_plan_module !== "yes"){
						$return_events[ $value->ID ] = $value->post_title;
					}
				}

				return $return_events;
			} else {
				// return single speaker
				return get_post( $id );
			}

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $id
	 *
	 * @return void
	 */
	public static function get_users( $id = null ) {
		$return_organizers = [ '' => esc_html__( 'select organizer', 'eventin' ) ];
		try {
			$blogusers = get_users(
				[
					'order'    => 'DESC',
					'role__in' => [ 'etn_organizer', 'administrator' ],
				]
			);

			foreach ( $blogusers as $user ) {
				$name                           = isset( $user->display_name ) ? $user->display_name : $user->user_nicename;
				$return_organizers[ $user->ID ] = $name . ' - ' . $user->user_email;
			}

			return $return_organizers;
		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * Undocumented function
	 *
	 * @param string $prefix
	 *
	 * @return void
	 */
	public static function etn_event_manager_fontawesome_icons( $prefix = 'etn-icon' ) {
		$prefix       = apply_filters( 'etn_event_social_icons_prefix', $prefix );
		$social_icons = [
			"$prefix fa-facebook"           => esc_html__( 'facebook', 'eventin' ),
			"$prefix fa-facebook-f"         => esc_html__( 'facebook-f', 'eventin' ),
			"$prefix fa-facebook-messenger" => esc_html__( 'facebook-messenger', 'eventin' ),
			"$prefix fa-facebook-square"    => esc_html__( 'facebook-square', 'eventin' ),
			"$prefix fa-linkedin"           => esc_html__( 'linkedin', 'eventin' ),
			"$prefix fa-linkedin-in"        => esc_html__( 'linkedin-in', 'eventin' ),
			"$prefix fa-twitter"            => esc_html__( 'twitter', 'eventin' ),
			"$prefix fa-twitter-square"     => esc_html__( 'twitter-square', 'eventin' ),
			"$prefix fa-uber"               => esc_html__( 'uber', 'eventin' ),
			"$prefix fa-google"             => esc_html__( 'google', 'eventin' ),
			"$prefix fa-google-drive"       => esc_html__( 'google-drive', 'eventin' ),
			"$prefix fa-google-play"        => esc_html__( 'google-play', 'eventin' ),
			"$prefix fa-google-wallet"      => esc_html__( 'google-wallet', 'eventin' ),
			"$prefix fa-linkedin"           => esc_html__( 'linkedin', 'eventin' ),
			"$prefix fa-linkedin-in"        => esc_html__( 'linkedin-in', 'eventin' ),
			"$prefix fa-whatsapp"           => esc_html__( 'whatsapp', 'eventin' ),
			"$prefix fa-whatsapp-square"    => esc_html__( 'whatsapp-square', 'eventin' ),
			"$prefix fa-wordpress"          => esc_html__( 'wordpress', 'eventin' ),
			"$prefix fa-wordpress-simple"   => esc_html__( 'wordpress-simple', 'eventin' ),
			"$prefix fa-youtube"            => esc_html__( 'youtube', 'eventin' ),
			"$prefix fa-youtube-square"     => esc_html__( 'youtube-square', 'eventin' ),
			"$prefix fa-xbox"               => esc_html__( 'xbox', 'eventin' ),
			"$prefix fa-vk"                 => esc_html__( 'vk', 'eventin' ),
			"$prefix fa-vnv"                => esc_html__( 'vnv', 'eventin' ),
			"$prefix fa-instagram"          => esc_html__( 'instagram', 'eventin' ),
			"$prefix fa-reddit"             => esc_html__( 'reddit', 'eventin' ),
			"$prefix fa-reddit-alien"       => esc_html__( 'reddit-alien', 'eventin' ),
			"$prefix fa-reddit-square"      => esc_html__( 'reddit-square', 'eventin' ),
			"$prefix fa-pinterest"          => esc_html__( 'pinterest', 'eventin' ),
			"$prefix fa-pinterest-p"        => esc_html__( 'pinterest-p', 'eventin' ),
			"$prefix fa-pinterest-square"   => esc_html__( 'pinterest-square', 'eventin' ),
			"$prefix fa-tumblr"             => esc_html__( 'tumblr', 'eventin' ),
			"$prefix fa-tumblr-square"      => esc_html__( 'tumblr-square', 'eventin' ),
			"$prefix fa-flickr"             => esc_html__( 'flickr', 'eventin' ),
			"$prefix fa-meetup"             => esc_html__( 'meetup', 'eventin' ),
			"$prefix fa-vimeo-v"            => esc_html__( 'vimeo', 'eventin' ),
			"$prefix fa-weixin"             => esc_html__( 'Wechat', 'eventin' ),
			"$prefix x-twitter"             => esc_html__( 'Twitter', 'eventin' ),
		];

		return apply_filters( 'etn_social_icons', $social_icons );
	}

	/**
	 * returns all organizers list
	 */
	public static function get_orgs() {
		$return_organizers = [
			'' => esc_html__( 'Select Category', 'eventin' ),
		];

		try {
			$terms = get_terms( [
				'taxonomy'   => 'etn_speaker_category',
				'orderby'    => 'count',
				'hide_empty' => false,
				'fields'     => 'all',
			] );

			foreach ( $terms as $term ) {
				$return_organizers[ $term->slug ] = $term->name;
			}

			return $return_organizers;
		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * returns all categories of an event
	 */
	public static function cate_with_link( $post_id = null, $category = '', $single = false ) {
		$terms         = get_the_terms( $post_id, $category );
		$category_name = '';

		if ( is_array( $terms ) ):

			foreach ( $terms as $tkey => $term ):
				$cat = $term->name;

				$category_name .= sprintf( "<span>%s</span> ", $cat );

				if ( $single ) {
					break;
				}

				if ( $tkey == 1 ) {
					break;
				}

			endforeach;
		endif;

		return $category_name;
	}

	/**
	 * validation for nonce
	 */
	public static function is_secured( $nonce_field, $action, $post_id = null, $post = [] ) {

		$nonce = ! empty( $post[ $nonce_field ] ) ? sanitize_text_field( $post[ $nonce_field ] ) : '';

		if ( $nonce == '' ) {
			return false;
		}

		if ( null !== $post_id ) {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return false;
			}

			if ( wp_is_post_autosave( $post_id ) ) {
				return false;
			}

			if ( wp_is_post_revision( $post_id ) ) {
				return false;
			}

		}

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Single page settings option
	 */
	public static function single_template_options( $single_event_id ) {
		$data                     = [];
		$date_options             = Helper::get_date_formats();
		$text_domain              = 'eventin';
		$etn_start_date           = get_post_meta( $single_event_id, 'etn_start_date', true );
		$etn_end_date             = get_post_meta( $single_event_id, 'etn_end_date', true );
		$etn_start_time           = strtotime( get_post_meta( $single_event_id, 'etn_start_time', true ) );
		$etn_end_time             = strtotime( get_post_meta( $single_event_id, 'etn_end_time', true ) );
		$etn_event_location_type  = get_post_meta( $single_event_id, 'etn_event_location_type', true );
		$etn_event_location       = get_post_meta( $single_event_id, 'etn_event_location', true );
		$event_timezone           = get_post_meta( $single_event_id, 'event_timezone', true );
		$etn_event_tags           = get_post_meta( $single_event_id, 'etn_event_tags', true );
		$etn_event_description    = get_post_meta( $single_event_id, 'etn_event_description', true );
		$etn_event_schedule       = get_post_meta( $single_event_id, 'etn_event_schedule', true );
		$etn_online_event         = get_post_meta( $single_event_id, 'etn_online_event', true );
		$etn_es_event_feature     = get_post_meta( $single_event_id, 'etn_es_event_feature', true );
		$etn_event_banner         = get_post_meta( $single_event_id, 'etn_event_banner', true );
		$etn_event_banner_url     = wp_get_attachment_image_src( $etn_event_banner );
		$etn_organizer_banner     = get_post_meta( $single_event_id, 'etn_organizer_banner', true );
		$etn_organizer_banner_url = wp_get_attachment_image_src( $etn_organizer_banner );
		$etn_event_socials        = get_post_meta( $single_event_id, 'etn_event_socials', true );
		$etn_event_page           = get_post_meta( $single_event_id, 'etn_event_page', true );
		$etn_organizer_events     = get_post_meta( $single_event_id, 'etn_event_organizer', true );
		$etn_avaiilable_tickets   = get_post_meta( $single_event_id, 'etn_avaiilable_tickets', true );
		$etn_avaiilable_tickets   = isset( $etn_avaiilable_tickets ) ? ( intval( $etn_avaiilable_tickets ) ) : 0;
		$etn_ticket_unlimited     = get_post_meta( $single_event_id, 'etn_ticket_availability', true );

		$cart_product_id = get_post_meta( $single_event_id, 'link_wc_product', true ) ? esc_attr( get_post_meta( $single_event_id, 'link_wc_product', true ) ) : esc_attr( $single_event_id );

		$etn_sold_tickets = get_post_meta( $single_event_id, 'etn_sold_tickets', true );

		if ( ! $etn_sold_tickets ) {
			$etn_sold_tickets = 0;
		}

		$etn_ticket_price   = get_post_meta( $single_event_id, 'etn_ticket_price', true );
		$etn_ticket_price   = isset( $etn_ticket_price ) ? ( floatval( $etn_ticket_price ) ) : 0;
		$etn_left_tickets   = $etn_avaiilable_tickets - $etn_sold_tickets;
		$event_options      = get_option( "etn_event_options" );
		$event_time_format  = empty( $event_options["time_format"] ) ? '12' : $event_options["time_format"];
		$event_start_time   = empty( $etn_start_time ) ? '' : ( ( $event_time_format == "24" ) ? date( 'H:i', $etn_start_time ) : date( 'g:i a', $etn_start_time ) );
		$event_end_time     = empty( $etn_end_time ) ? '' : ( ( $event_time_format == "24" ) ? date( 'H:i', $etn_end_time ) : date( 'g:i a', $etn_end_time ) );
		$event_start_date   = self::etn_date( $etn_start_date );
		$event_end_date     = self::etn_date( $etn_end_date );
		$etn_deadline       = get_post_meta( $single_event_id, 'etn_registration_deadline', true );
		$etn_deadline_value = self::etn_date_with_time( $etn_deadline );

		$category = self::cate_with_link( $single_event_id, 'etn_category' );

		$data['category']                = $category;
		$data['etn_event_schedule']      = $etn_event_schedule;
		$data['event_options']           = $event_options;
		$data['text_domain']             = $text_domain;
		$data['event_start_date']        = $event_start_date;
		$data['event_end_date']          = $event_end_date;
		$data['event_start_time']        = $event_start_time;
		$data['event_end_time']          = $event_end_time;
		$data['etn_deadline_value']      = $etn_deadline_value;
		$data['etn_event_location_type'] = $etn_event_location_type;
		$data['etn_event_location']      = $etn_event_location;
		$data['etn_left_tickets']        = $etn_left_tickets;
		$data['etn_organizer_events']    = $etn_organizer_events;
		$data['date_options']            = $date_options;
		$data['etn_event_socials']       = $etn_event_socials;
		$data['etn_ticket_price']        = $etn_ticket_price;
		$data['etn_ticket_unlimited']    = $etn_ticket_unlimited;
		$data['event_timezone']          = $event_timezone;

		return $data;
	}

	/**
	 * Single page organizer
	 */
	public static function single_template_organizer_free( $etn_organizer_events ) {

		if ( file_exists( get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/event-organizers-free.php' ) ) {
			require_once get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/event-organizers-free.php';
		} elseif ( file_exists( get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/event-organizers-free.php' ) ) {
			require_once get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/event-organizers-free.php';
		} else {
			require_once \Wpeventin::templates_dir() . 'event/event-organizers-free.php';
		}

	}

	/**
 * Get Schedule Topics by User ID
 *
 * @param int $user_id The ID of the user.
 * @return array The schedule topics related to the user.
 */
	public static function speaker_sessions($user_id) {
		// Initialize an array to hold matched post IDs
		$matched_posts = [];

		// Get posts of the custom post type 'etn_schedule'
		$args = [
			'post_type' => 'etn-schedule',
			'post_status' => 'publish',
			'posts_per_page' => -1 // Get all posts
		];
		$schedule_query = new WP_Query($args);

		// Loop through each post
		if ($schedule_query->have_posts()) {
			while ($schedule_query->have_posts()) {
				$schedule_query->the_post();
				$post_id = get_the_ID();

				// Get the 'etn_schedule_topics' meta value
				$schedule_topics = get_post_meta($post_id, 'etn_schedule_topics', true);
				if(!empty($schedule_topics)){
					foreach($schedule_topics as $key => $value){
						if (is_array($value['speakers']) && in_array($user_id, $value['speakers'])) {
							// Add the post ID to the matched posts array
							$matched_posts[] = $post_id;
						}
					}
				}
			
			}
			// Restore original Post Data
			wp_reset_postdata();
		}

		// Return the matched post IDs
		
		return array_unique($matched_posts);
	}
	
	/**
	 * Remove attendee data when status failed
	 */
	public static function remove_attendee_data() {
		global $wpdb;
		$query = $wpdb->query(
			"UPDATE $wpdb->posts posts
            INNER JOIN $wpdb->postmeta postmeta
            ON posts.ID = postmeta.post_id
            SET posts.post_status = 'etn-trashed-attendee'
            WHERE postmeta.meta_key = 'etn_status'
            AND postmeta.meta_value = 'failed'
            AND posts.post_type = 'etn-attendee'"
		);

		return $query;
	}

	/**
	 * get  corn schedule days for remove attendee
	 */
	public static function get_schedule_days() {
		$event_options   = get_option( "etn_event_options" );
		$attendee_remove = isset( $event_options['attendee_remove'] ) && $event_options['attendee_remove'] !== "" ? $event_options['attendee_remove'] : 30;

		return 60 * 60 * 24 * $attendee_remove;
	}

	/**
	 * Send email function
	 */
	public static function send_email( $to, $subject, $mail_body, $from, $from_name ) {
		$body    = html_entity_decode( $mail_body );
		$headers = [ 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from . '>' ];
		$result  = wp_mail( $to, $subject, $body, $headers );

		return $result;
	}

	/**
	 * Get all sales history of event
	 */
	public static function get_tickets_by_event( $current_post_id, $report_sorting ) {
		global $wpdb;
		$response_data = [];
		$data          = [];

		$table_etn_events = ETN_EVENT_PURCHASE_HISTORY_TABLE;
		$data             = $wpdb->get_results( "SELECT * FROM $table_etn_events WHERE post_id = $current_post_id ORDER BY event_id $report_sorting" );

		if ( is_array( $data ) && count( $data ) > 0 ) {
			$total_sale_price = 0;

			$trans_history_meta_table = ETN_EVENT_PURCHASE_HISTORY_META_TABLE;

			foreach ( $data as &$single_sale ) {
				$total_sale_price += $single_sale->event_amount;
				$single_sale_meta = $wpdb->get_results( "SELECT * FROM $trans_history_meta_table WHERE event_id = $single_sale->event_id AND meta_key = '_etn_order_qty'" );
				$single_sale->{'single_sale_meta'}

				                  = $single_sale_meta[0]->meta_value;
			}

		}

		$response_data['all_sales']        = $data;
		$response_data['total_sale_price'] = isset( $total_sale_price ) ? $total_sale_price : 0;

		return $response_data;
	}

	/**
	 * module for related events
	 *
	 * @param [type] $single_event_id
	 *
	 * @return void
	 */
	public static function related_events_widget( $single_event_id, $configs = [] ) {

		$etn_terms    = wp_get_post_terms( $single_event_id, 'etn_tags' );
		$etn_term_ids = [];

		if ( $etn_terms ) {

			foreach ( $etn_terms as $terms ) {
				array_push( $etn_term_ids, $terms->term_id );
			}

		}

		$event_options = get_option( "etn_event_options" );
		$date_options  = self::get_date_formats();
		$related_events_per_page = isset( $event_options['related_events_per_page'] ) && $event_options['related_events_per_page'] !== "" ? $event_options['related_events_per_page'] : 6;
		$data                    = self::post_data_query( 'etn', $related_events_per_page, null, $etn_term_ids, "etn_tags", null, [ $single_event_id ], null, null, 'post_date', 'upcoming' );

		$column = "4";

		if ( ! empty( $configs ) && ! empty( $configs["column"] ) ) {
			$column = $configs["column"];
		}

		$title = ( is_array( $configs ) && ! empty( $configs["title"] ) ) ? $configs["title"] : esc_html__( 'Related Events', 'eventin' );

		if ( file_exists( get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/related-events-free.php' ) ) {
			$template = get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/related-events-free.php';
		} elseif ( file_exists( get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/related-events-free.php' ) ) {
			$template = get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/related-events-free.php';
		} elseif ( file_exists( \Wpeventin::templates_dir() . 'event/related-events-free.php' ) ) {
			$template = \Wpeventin::templates_dir() . 'event/related-events-free.php';
		}

		include $template;

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $key
	 * @param [type] $value
	 *
	 * @return void
	 */
	public static function get_attendee_by_token( $key, $value ) {
		global $wpdb;
		$query_result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='$key' AND meta_value='$value'" );

		return $query_result;
	}

	/**
	 * Sorting Schedule repeater data
	 */
	public static function sort_schedule_items( $post_id, $etn_rep_key ) {
		$new_order = sanitize_text_field( stripslashes( $_POST['etn_schedule_sorting'] ) );
		$order     = json_decode( $new_order, true );
		$order     = array_values( $order );

		if ( is_array( $order ) && ! empty( $order ) ) {
			$schedules = $etn_rep_key;
			$new_arr   = [];
			$sort_arr  = [];

			foreach ( $order as $key => $value ) {
				$new_arr[ $key ]  = $schedules[ $value ];
				$sort_arr[ $key ] = $key;
			}

			$new_sort = json_encode( $sort_arr );
			update_post_meta( $post_id, 'etn_schedule_topics', $new_arr );
			update_post_meta( $post_id, 'etn_schedule_sorting', $new_sort );
		}

	}

	public static function generate_name_from_label( $prefix, $label ) {
		return $prefix . self::get_name_structure_from_label( $label );
	}

	public static function get_name_structure_from_label( $label ) {
		return strtolower( preg_replace( "/[^a-zA-Z0-9]/", "_", $label ) );
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $default_template_name
	 * @param [type] $template_name
	 *
	 * @return void
	 */
	public static function prepare_event_template_path( $default_template_name, $template_name ) {

		if ( "event-one" !== $template_name && class_exists( 'Etn_Pro\Bootstrap' ) ) {
			$single_template_path = \Wpeventin_Pro::templates_dir() . $template_name . ".php";
		} else {
			$single_template_path = \Wpeventin::templates_dir() . $default_template_name . ".php";
		}

		$single_template_path = apply_filters( "etn_event_content_template_path", $single_template_path );

		return $single_template_path;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $default_template_name
	 * @param [type] $template_name
	 *
	 * @return void
	 */
	public static function prepare_speaker_template_path( $default_template_name, $template_name ) {
		$arr = [
			'speaker-one',
			'speaker-two-lite',
		];

		if ( ! in_array( $template_name, $arr ) && class_exists( 'Etn_Pro\Bootstrap' ) ) {
			$single_template_path = \Wpeventin_Pro::templates_dir() . $template_name . ".php";
		} else {
			$single_template_path = \Wpeventin::templates_dir() . $template_name . ".php";
		}

		$single_template_path = apply_filters( "etn_speaker_content_template_path", $single_template_path );

		return $single_template_path;
	}

	public static function get_attendee_by_woo_order( $order_id ) {
		$all_attendee = [];
		global $wpdb;
		$table_name = $wpdb->prefix . "postmeta";
		$sql        = "SELECT post_id FROM $table_name WHERE meta_key='etn_attendee_order_id' AND meta_value=$order_id";
		$results    = $wpdb->get_results( $sql );

		if ( is_array( $results ) && ! empty( $results ) ) {

			foreach ( $results as $result ) {
				array_push( $all_attendee, $result->post_id );
			}

		}

		return $all_attendee;
	}

	public static function update_attendee_payment_status( $attendee_id, $order_status ) {
		$payment_success_status_array = [
			// 'pending',
			'processing',
			// 'on-hold',
			'completed',
			// 'cancelled',
			// 'failed',
			'partial-payment',
			'stripe-pending',
		];

		if ( in_array( $order_status, $payment_success_status_array ) ) {
			//payment complete, update payment status to success
			update_post_meta( $attendee_id, 'etn_status', 'success' );
		} else {
			//payment failed, update payment status to falied
			update_post_meta( $attendee_id, 'etn_status', 'failed' );
		}

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $attendee_id
	 * @param [type] $check_info_edit_token
	 *
	 * @return void
	 */
	public static function verify_attendee_edit_token( $attendee_id, $check_info_edit_token ) {
		$post_status = get_post_status( $attendee_id );


		if ( "publish" !== $post_status || empty( $attendee_id ) || empty( $check_info_edit_token ) ) {
			return false;
		}

		$stored_edit_token = get_post_meta( $attendee_id, "etn_info_edit_token", true );

		if ( $stored_edit_token == $check_info_edit_token ) {
			return true;
		}

		return false;

	}

	/**
	 * Show Invalid Data Page
	 *
	 * @return html
	 */
	public static function show_attendee_pdf_invalid_data_page() {
		wp_head();
		?>
        <div class="section-inner">
            <h3 class="entry-title">
				<?php echo esc_html__( "Invalid data. ", "eventin" ); ?>
            </h3>
            <div class="intro-text">
                <a href="<?php echo esc_url( home_url() ); ?>"><?php echo esc_html__( "Return to homepage", "eventin" ); ?></a>
            </div>
        </div>
		<?php
		wp_footer();
	}

	/************************
	 *advanced search
	 *******************************/

	// get event data
	public static function get_eventin_search_data( $posts_per_page = - 1 ) {
		$etn_event_location = "";

		if ( isset( $_GET['etn_event_location'] ) ) {
			$etn_event_location = $_GET['etn_event_location'];
		}

		$event_cat = "";

		if ( isset( $_GET['etn_categorys'] ) ) {
			$event_cat = $_GET['etn_categorys'];
		}

		$keyword = "";

		if ( isset( $_GET['s'] ) ) {
			$keyword = $_GET['s'];
		}

		$data_query_args = [
			'post_type'      => 'etn',
			'post_status'    => 'publish',
			's'              => $keyword,
			'posts_per_page' => isset( $posts_per_page ) ? $posts_per_page : - 1,
		];

		if ( ! empty( $event_cat ) ) {
			$data_query_args['tax_query'] = [
				[
					'taxonomy'         => 'etn_category',
					'terms'            => [ $event_cat ],
					'field'            => 'id',
					'include_children' => true,
					'operator'         => 'IN',
				],
			];
		}

		if ( ! empty( $etn_event_location ) ) {

			$term_details = get_term_by( 'slug', $etn_event_location, 'etn_location' );
			if( ! empty ( $term_details ) ){
				$data_query_args['tax_query'] = [
					[
						'taxonomy'         => 'etn_location',
						'terms'            => [ $term_details->term_id ],
						'field'            => 'id'
					],
				];
			} else {
				$data_query_args['meta_query'] = [
					[
						'key'     => 'etn_event_location',
						'value'   => $etn_event_location,
						'compare' => 'LIKE',
					],
				];
			}
			
		}


		$query_data = get_posts( $data_query_args );

		return $query_data;
	}

	// get event location
	public static function get_event_location() {
		$location_args       = [
			'post_type'   => [ 'etn' ],
			'numberposts' => - 1,
			'meta_query'  => [
				[
					'key'     => 'etn_event_location',
					'compare' => 'EXISTS',
				],
				[
					'key'     => 'etn_event_location',
					'value'   => [ '' ],
					'compare' => 'NOT IN',
				],
			],
		];
		$location_query_data = get_posts( $location_args );
		$location_data[]     = esc_html__( "Select Location", 'eventin' );

		if ( ! empty( $location_query_data ) ) {
			foreach ( $location_query_data as $value ) {
				$event_type = get_post_meta( $value->ID, 'event_type', true );
				$location   = get_post_meta( $value->ID, 'etn_event_location', true );
		
				if ( 'offline' === $event_type && is_array($location) && isset($location['address']) && !empty($location['address']) && is_string($location['address']) ) {
					// Ensure $location['address'] is a string and is set before using it as a key
					$location_data[$location['address']] = $location['address'];
				}
			}
		}

		/**
		 * get all the existing locations which are assigned
		 */
		// $terms = get_terms( array(
		// 	'taxonomy'   => 'etn_location',
		// 	'hide_empty' => true,
		// ) );

		// if ( ! empty( $terms ) ) {

		// 	foreach ( $terms as $value ) {
		// 		$location_data[ $value->slug ] = $value->name;
		// 	}

		// }

		return $location_data;

	}

	// get event search form
	public static function get_event_search_form( $etn_event_input_filed_title = "Find your next event", $etn_event_category_filed_title = "Event Category", $etn_event_location_filed_title = "Event Location", $etn_event_button_title = "Search Now" ) {

		$category_data = Helper::get_event_category();
		$location_data = [];
		$location_data = self::get_event_location();

		?>
        <form method="GET" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="etn_event_inline_form">
            <div class="etn-event-search-wrapper">
                <div class="input-group">
                    <div class="input-group-prepend">
						<span class="input-group-text">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="feather feather-search">
								<circle cx="11" cy="11" r="8"></circle>
								<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
							</svg>
						</span>
                    </div>
                    <input
                            type="search"
                            name="s"
                            value="<?php echo get_search_query() ?>"
                            placeholder="<?php echo esc_html__( $etn_event_input_filed_title, 'eventin' ) ?>"
                            class="form-control"
                    >
                </div>
                <!-- // Search input filed -->
                <div class="input-group">
                    <div class="input-group-prepend">
						<span class="input-group-text">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="feather feather-map-pin"><path
                                        d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12"
                                                                                                          cy="10"
                                                                                                          r="3"></circle></svg>
						</span>
                    </div>
                    <select name="etn_event_location" class="etn_event_select2 etn_event_select">
                        <option value><?php echo esc_html__( $etn_event_location_filed_title, 'eventin' ) ?></option>
						<?php
						if ( is_array( $location_data ) && ! empty( $location_data ) ) {
							$modify_array_data = array_shift( $location_data );

							foreach ( $location_data as $key => $value ) {
								$select_value = "";

								if ( isset( $_GET['etn_event_location'] ) ) {
									$select_value = $_GET['etn_event_location'];
								}

								?>
                                <option <?php

								if ( ! empty( $select_value ) && $select_value === $key ) {
									echo ' selected="selected"';
								}

								?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
								<?php
							}

						}

						?>
                    </select>
                </div>
                <!-- // location -->
                <div class="input-group">
                    <div class="input-group-prepend">
						<span class="input-group-text">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="feather feather-layers"><polygon
                                        points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline
                                        points="2 17 12 22 22 17"></polyline><polyline
                                        points="2 12 12 17 22 12"></polyline></svg>
						</span>
                    </div>
                    <select name="etn_categorys" class="etn_event_select2 etn_event_select">
                        <option value><?php echo esc_html__( $etn_event_category_filed_title, 'eventin' ) ?></option>
						<?php

						if ( ! empty( $category_data ) && is_array( $category_data ) ) {
							$select_cat_value = '';

							if ( isset( $_GET['etn_categorys'] ) ) {
								$select_cat_value = $_GET['etn_categorys'];
							}

							foreach ( $category_data as $key => $value ) {
								?>
                                <option
									<?php

									if ( ! empty( $select_cat_value ) && $select_cat_value == $key ) {
										echo ' selected="selected"';
									}

									?>
                                        value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
								<?php
							}

						}

						?>
                    </select>
                </div>

                <!-- // cat -->
                <div class="search-button-wrapper">
                    <input type="hidden" name="post_type" value="etn"/>
					<?php if ( defined( 'ETN_PRO_FILES_LOADED' ) ) : ?>
                        <button
                                type="button"
                                class="etn-btn etn-filter-icon"
                                aria-label="<?php echo __( 'Event filter button', 'eventin' ); ?>"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="feather feather-sliders">
                                <line x1="4" y1="21" x2="4" y2="14"></line>
                                <line x1="4" y1="10" x2="4" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12" y2="3"></line>
                                <line x1="20" y1="21" x2="20" y2="16"></line>
                                <line x1="20" y1="12" x2="20" y2="3"></line>
                                <line x1="1" y1="14" x2="7" y2="14"></line>
                                <line x1="9" y1="8" x2="15" y2="8"></line>
                                <line x1="17" y1="16" x2="23" y2="16"></line>
                            </svg>
                        </button>
					<?php endif; ?>
                    <button
                            type="submit"
                            class="etn-btn etn-btn-primary"
                    >
						<?php echo esc_html__( $etn_event_button_title, 'eventin' ) ?>
                    </button>
                </div>
            </div>
			<?php 
			do_action( 'etn_advanced_search' );
			 ?>
        </form>
		<?php
	}

	/**
	 * event normal search filter
	 * Attendee list filter by event id
	 */
	public static function event_etn_search_filter( $query ) {
		if ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] === "etn" ) && $query->is_search && ! is_admin() ) {

			$prev_date = date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . ' -1 day' ) );
			$next_date = date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . ' +1 day' ) );

			$week_date = date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . ' +7 day' ) );

			$week_start = strtotime( "last monday" );
			$week_start = date( 'w', $week_start ) == date( 'w' ) ? $week_start + 7 * 86400 : $week_start;
			$weekend    = date( 'Y-m-d', strtotime( date( "Y-m-d", $week_start ) . " +6 days" ) );

			$month_start_date = date( 'Y-m-d', strtotime( date( 'Y-m' ) ) );
			$month_end_date   = date( 'Y-m-d', strtotime( date( "Y-m-t", strtotime( $month_start_date ) ) ) );

			$etn_event_location = "";

			if ( isset( $_GET['etn_event_location'] ) ) {
				$etn_event_location = $_GET['etn_event_location'];
			}

			$etn_event_date_range = "";

			if ( isset( $_GET['etn_event_date_range'] ) ) {
				$etn_event_date_range = $_GET['etn_event_date_range'];
			}

			$event_cat = "";

			if ( isset( $_GET['etn_categorys'] ) ) {
				$event_cat = $_GET['etn_categorys'];
			}

			$etn_event_will_happen = "";

			if ( isset( $_GET['etn_event_will_happen'] ) ) {
				$etn_event_will_happen = $_GET['etn_event_will_happen'];
			}

			$keyword = "";

			if ( isset( $_GET['s'] ) ) {
				$keyword = $_GET['s'];
			}

			$meta_location_query = [];

			if ( ! empty( $etn_event_location ) ) {

				$meta_location_query = [
					'key'     => 'etn_event_location',
					'value'   => self::get_event_location_value( $etn_event_location ),
					'compare' => 'LIKE'
				];
				
			}

			$meta_date_query = [];

			if ( ! empty( $etn_event_date_range ) ) {

				if ( $etn_event_date_range === "today" ) {
					$meta_date_query = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '>=',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "tomorrow" ) {
					$meta_date_query = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => $next_date,
							'compare' => '>=',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => $next_date,
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "yesterday" ) {
					$yesterday = date('Y-m-d', strtotime('-1 day'));
					$meta_date_query = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => $prev_date,
							'compare' => '>=',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => $prev_date,
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "this-weekend" ) {
					$meta_date_query = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => $weekend,
							'compare' => '>=',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => $weekend,
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "this-week" ) {
					$meta_date_query = [
						'relation' => 'OR',
						[
							'key'     => 'etn_start_date',
							'value'   => [ $week_start, $weekend ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
						[
							'key'     => 'etn_end_date',
							'value'   => [ $week_start, $weekend ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
					];
				} elseif ( $etn_event_date_range === "this-month" ) {
					$meta_date_query = [
						'relation' => 'OR',
						[
							'key'     => 'etn_start_date',
							'value'   => [ $month_start_date, $month_end_date ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
						[
							'key'     => 'etn_end_date',
							'value'   => [ $month_start_date, $month_end_date ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
					];
				} elseif ( $etn_event_date_range === "upcoming" ) {
					$meta_date_query = [
						'relation' => 'AND',
						[
							'relation' => 'OR',
							[
								'key'     => 'etn_start_date',
								'value'   => date( 'Y-m-d' ),
								'compare' => '>=',
								'type'    => 'DATE',
							],
							[
								'key'     => 'etn_start_date',
								'value'   => '',
								'compare' => '=',
							],
						],
						[
							'relation' => 'OR',
							[
								'key'     => 'etn_end_date',
								'value'   => date( 'Y-m-d' ),
								'compare' => '>',
								'type'    => 'DATE',
							],
							[
								'key'     => 'etn_end_date',
								'value'   => '',
								'compare' => '=',
							],
						],
					];
				} elseif ( $etn_event_date_range === "expired" ) {
					$meta_date_query = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '<',
							'type'    => 'DATE',
						],
						[
							'relation' => 'OR',
							[
								'key'     => 'etn_start_date',
								'value'   => date( 'Y-m-d' ),
								'compare' => '<=',
								'type'    => 'DATE',
							],
							[
								'key'     => 'etn_start_date',
								'value'   => '',
								'compare' => '=',
							],
						],
					];
				}

			}

			$meta_event_happen_query = [];

			if ( ! empty( $etn_event_will_happen ) ) {
				$meta_event_happen_query = [
					[
						'key'     => 'etn_zoom_event',
						'value'   => $etn_event_will_happen,
						'compare' => "EXSISTS",
					],
				];
			}

			$meta_query = ['relation' => 'AND'];

			if ( ! empty( $meta_location_query ) ) {
				$meta_query[] = $meta_location_query;
			}
			
			if ( ! empty( $meta_date_query ) ) {
				$meta_query[] = $meta_date_query;
			}
			
			if ( ! empty( $meta_event_happen_query ) ) {
				$meta_query[] = $meta_event_happen_query;
			}
			
			$query->set('meta_query', $meta_query);
			if ( ! empty( $keyword ) ) {
				$query->set( 's', $keyword );
			}

			if ( ! empty( $event_cat ) ) {
				$taxquery = [
					[
						'taxonomy' => 'etn_category',
						'terms'    => [ $event_cat ],
						'field'    => 'id',
					],
				];
				$query->set( 'tax_query', $taxquery );
			}			

			$query->set( 'post_type', [ 'etn' ] );

			// Archive page event sort by event start date
			if ( is_archive() ) {
				$query->set( 'order', 'DESC' );
				$query->set( 'meta_key', 'etn_start_date' );
				$query->set( 'orderby', 'meta_value' );
			}
		}

		// Attendee by event id
		\Etn\Core\Event\Helper::instance()->attendee_by_events( $query );

		return $query;
	}

	// ajax event filter in event archive
	public static function etn_event_ajax_get_data() {
		$post_arr  = filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS );
		$prev_date = date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . ' -1 day' ) );
		$next_date = date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . ' +1 day' ) );

		$week_date = date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . ' +7 day' ) );

		$week_start = strtotime( "last monday" );
		$week_start = date( 'w', $week_start ) == date( 'w' ) ? $week_start + 7 * 86400 : $week_start;
		$weekend    = date( 'Y-m-d', strtotime( date( "Y-m-d", $week_start ) . " +6 days" ) );

		$month_start_date = date( 'Y-m-d', strtotime( date( 'Y-m' ) ) );
		$month_end_date   = date( 'Y-m-d', strtotime( date( "Y-m-t", strtotime( $month_start_date ) ) ) );

		$keyword = "";

		if ( isset( $post_arr['s'] ) ) {
			$keyword = $post_arr['s'];
		}

		$event_cat = "";

		if ( isset( $post_arr['etn_categorys'] ) ) {
			$event_cat = $post_arr['etn_categorys'];
		}

		$etn_event_location = "";

		if ( isset( $post_arr['etn_event_location'] ) ) {
			$etn_event_location = $post_arr['etn_event_location'];
		}

		$etn_event_date_range = "";

		if ( isset( $post_arr['etn_event_date_range'] ) ) {
			$etn_event_date_range = $post_arr['etn_event_date_range'];
		}

		$etn_event_will_happen = "";

		if ( isset( $post_arr['etn_event_will_happen'] ) ) {
			$etn_event_will_happen = $post_arr['etn_event_will_happen'];
		}

		if (  isset( $post_arr['etn_event_location'] ) && !empty( $post_arr['etn_event_location'] ) || isset( $post_arr['etn_categorys'] ) && !empty( $post_arr['etn_categorys'] ) || isset( $post_arr['s'] ) && !empty( $post_arr['s'] )  || isset( $post_arr['etn_event_date_range'] ) && !empty( $post_arr['etn_event_date_range'] ) || isset( $post_arr['etn_event_will_happen'] ) ) {
			$query_string = [
				'post_type'   => 'etn',
				'post_status' => 'publish',
			];

			if ( isset( $post_arr['type'] ) ) {
				$id                           = $post_arr['id'];
				$query_string['post__not_in'] = explode( ',', $id );
			}

			if ( ! empty( $keyword ) ) {
				$query_string['s'] = $keyword;
			}

			if ( ! empty( $event_cat ) ) {
				$query_string['tax_query'] = [
					[
						'taxonomy' => 'etn_category',
						'terms'    => [ $event_cat ],
						'field'    => 'id',
					],
				];
			}

			$meta_location_query_string = [];


			if ( ! empty( $etn_event_location ) ) {

				$meta_location_query_string = [
					'key'     => 'etn_event_location',
					'value'   => self::get_event_location_value( $etn_event_location ),
					'compare' => 'LIKE'
				];
				
			}


			$meta_event_happen_query = [];

			if ( ! empty( $etn_event_will_happen ) ) {
				$meta_event_happen_query = [
					'key'     => 'etn_zoom_event',
					'value'   => $etn_event_will_happen,
					'compare' => 'LIKE',
				];
			}

			$meta_event_date_query_string = [];

			if ( ! empty( $etn_event_date_range ) ) {

				if ( $etn_event_date_range === "today" ) {
					$meta_event_date_query_string = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '>=',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "tomorrow" ) {
					$tomorrow = date('Y-m-d', strtotime('+1 day'));
					$meta_event_date_query_string = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => $tomorrow,
							'compare' => '=>',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => $tomorrow,
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "yesterday" ) {
					$yesterday = date('Y-m-d', strtotime('-1 day'));
					$meta_event_date_query_string = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => $yesterday,
							'compare' => '>=',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => $yesterday,
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "this-weekend" ) {
					$meta_event_date_query_string = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => $weekend,
							'compare' => '>=',
						],
						[
							'key'     => 'etn_start_date',
							'value'   => $weekend,
							'compare' => '<=',
						],
					];
				} elseif ( $etn_event_date_range === "this-week" ) {
					$meta_event_date_query_string = [
						'relation' => 'OR',
						[
							'key'     => 'etn_start_date',
							'value'   => [ $week_start, $weekend ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
						[
							'key'     => 'etn_end_date',
							'value'   => [ $week_start, $weekend ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
					];
				} elseif ( $etn_event_date_range === "this-month" ) {
					$meta_event_date_query_string = [
						'relation' => 'OR',
						[
							'key'     => 'etn_start_date',
							'value'   => [ $month_start_date, $month_end_date ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
						[
							'key'     => 'etn_end_date',
							'value'   => [ $month_start_date, $month_end_date ],
							'type'    => 'date',
							'compare' => 'BETWEEN',
						],
					];
				} elseif ( $etn_event_date_range === "upcoming" ) {
					$meta_event_date_query_string = [
						'relation' => 'AND',
						[
							'relation' => 'OR',
							[
								'key'     => 'etn_start_date',
								'value'   => date( 'Y-m-d' ),
								'compare' => '>=',
								'type'    => 'DATE',
							],
							[
								'key'     => 'etn_start_date',
								'value'   => '',
								'compare' => '=',
							],
						],
						[
							'relation' => 'OR',
							[
								'key'     => 'etn_end_date',
								'value'   => date( 'Y-m-d' ),
								'compare' => '>',
								'type'    => 'DATE',
							],
							[
								'key'     => 'etn_end_date',
								'value'   => '',
								'compare' => '=',
							],
						],
					];
				} elseif ( $etn_event_date_range === "expired" ) {
					$meta_event_date_query_string = [
						'relation' => 'AND',
						[
							'key'     => 'etn_end_date',
							'value'   => date( 'Y-m-d' ),
							'compare' => '<',
							'type'    => 'DATE',
						],
						[
							'relation' => 'OR',
							[
								'key'     => 'etn_start_date',
								'value'   => date( 'Y-m-d' ),
								'compare' => '<=',
								'type'    => 'DATE',
							],
							[
								'key'     => 'etn_start_date',
								'value'   => '',
								'compare' => '=',
							],
						],
					];
				}

			}

			$query_string['meta_query'] = [
				'relation' => 'AND',
				[
					$meta_location_query_string,
					$meta_event_date_query_string,
					$meta_event_happen_query,
				],
			];
			$search                     = new \WP_Query( $query_string );
			$newdata                    = '';
			$ids                        = [];

			if ( $search->have_posts() ) {
				while ( $search->have_posts() ) {
					$search->the_post();
					$etn_event_location         = get_post_meta( get_the_ID(), 'etn_event_location', true );
					$existing_location          = self::cate_with_link( get_the_ID(), 'etn_location' );
					$etn_event_location_type    = get_post_meta( get_the_ID(), 'etn_event_location_type', true );
					$location                   = \Etn\Core\Event\Helper::instance()->display_event_location( get_the_ID() );
					$permalink                  = get_the_permalink();
					$post_thumbnail             = get_the_post_thumbnail( get_the_ID() );
					$title                      = get_the_title();
					$excerpt                    = get_the_excerpt();
					$etn_start_date             = get_post_meta( get_the_ID(), 'etn_start_date', true );
					?>
                    <div class="etn-col-md-6 etn-col-lg-<?php echo esc_attr( apply_filters( 'etn_event_archive_column', '4' ) ); ?>">
						<div class="etn-event-item">
							<?php
								if ( has_post_thumbnail() ) :
							?>
									<!-- thumbnail -->
									<div class="etn-event-thumb">
										<a href=" <?php echo esc_url( $permalink ) ; ?> " aria-label="<?php esc_html( $title ); ?> ">
											<?php echo $post_thumbnail  ; ?>
										</a>
									</div>
							<?php
							endif;
								?>
                            <!-- content start-->
                            <div class="etn-event-content">
							<?php  
							if ( ! empty( $location ) ) : ?>
								<div class="etn-event-location">
									<i class="etn-icon etn-location"></i>
									<?php
										echo esc_html( $location ); 
									?>
								</div>
							<?php 
							endif;
							?>
                                <h3 class="etn-title etn-event-title">
                                    <a href="<?php echo esc_url( $permalink ) ?>">
										<?php echo esc_html( $title ); ?>
                                    </a>
                                </h3>

								<p><?php echo apply_filters( 'etn_event_archive_content', wp_trim_words( $excerpt, 15 , '' ) ); ?></p>
                            </div>
							<div class="etn-event-footer">
								<div class="etn-event-date">
									<i class="etn-icon etn-calendar"></i>
									<?php echo esc_html( self::etn_date( $etn_start_date ) ); ?>
								</div>
							</div>
                            <!-- content end-->
						</div>

                    </div>
                        <!-- etn event item end-->
					<?php
				}

			}else{
				status_header( 404 );
				include_once  ETN_PLUGIN_TEMPLATE_DIR . 'etn-404.php';
			}

			wp_reset_postdata();

		}else{
			$query_string = [
				'post_type'   => 'etn',
				'post_status' => 'publish',
			];
			$search = new \WP_Query( $query_string );

			if ( $search->have_posts() ) {
				while ( $search->have_posts() ) {
					$search->the_post();
					?>
                    <div class="etn-col-md-6 etn-col-lg-<?php echo esc_attr( apply_filters( 'etn_event_archive_column', '4' ) ); ?>">

                        <div class="etn-event-item">

							<?php do_action( 'etn_before_event_archive_content', get_the_ID() ); ?>

                            <!-- content start-->
                            <div class="etn-event-content">

								<?php do_action( 'etn_before_event_archive_title', get_the_ID() ); ?>

                                <h3 class="etn-title etn-event-title">
                                    <a href="<?php echo esc_url( get_the_permalink() ) ?>">
										<?php echo esc_html( get_the_title() ); ?>
                                    </a>
                                </h3>

								<?php do_action( 'etn_after_event_archive_title', get_the_ID() ); ?>
                            </div>
                            <!-- content end-->

							<?php do_action( 'etn_after_event_archive_content', get_the_ID() ); ?>

                        </div>
                        <!-- etn event item end-->
                    </div>

					<?php
				}
			}
			wp_reset_postdata();

		}

		wp_die();
	}

	/**
	 * get_category id
	 *
	 * @param [type] $order_id
	 * @param [type] $order
	 *
	 * @return void
	 * @since 2.4.1
	 *
	 */
	public static function get_etn_taxonomy_ids( $taxonomy = 'etn_category', $shortcode_cat = "cat_id", $multiple = true ) {
		$multiple_val   = '';
		$default_select = '<option value="">' . esc_html__( 'Select a Category', 'eventin' ) . '</option>';
		if ( $multiple ) {
			$multiple_val   = 'multiple';
			$default_select = '';
		}
		$taxonomy = $taxonomy;
		$args_cat = [
			'taxonomy'   => $taxonomy,
			'number'     => 50,
			'hide_empty' => 0,
		];
		$cats     = get_categories( $args_cat );
		?>
        <select data-cat="<?php echo esc_attr( $shortcode_cat ); ?>"
                class="etn-shortcode-select etn-setting-input" <?php echo esc_attr( $multiple_val ); ?>>
			<?php echo Helper::render( $default_select ); ?>
			<?php foreach ( $cats as $item ): ?>
				<?php echo '<option value="' . esc_attr( $item->term_id ) . '">' . ( esc_html( $item->name ) ) . '</option>'; ?>
			<?php endforeach; ?>
        </select>
		<?php
	}

	/**
	 * returns list of all speaker
	 * returns single speaker if speaker id is provuded
	 */
	public static function get_posts_ids( $post_type = 'etn-schedule', $shortcode_ids = "ids", $multiple = 'multiple', $query_args = [] ) {
		$args = [
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'posts_per_page'   => - 1,
			'post_parent'      => 0,
			'suppress_filters' => false,
		];

		if ( ! empty( $query_args ) ) {
			$args = $query_args;
		}

		$schedules = get_posts( $args );
		?>
        <select data-cat="<?php echo esc_attr( $shortcode_ids ); ?>"
                class="etn-shortcode-select etn-setting-input" <?php echo esc_attr( $multiple ) ?>>
			<?php foreach ( $schedules as $item ): ?>
				<?php if ( $post_type === 'etn-zoom-meeting' ) {
					$post_item_id = get_post_meta( $item->ID, 'zoom_meeting_id', true );
				} else {
					$post_item_id = $item->ID;
				}

				?>
				<?php echo '<option  value="' . esc_attr( $post_item_id ) . '">' . ( esc_html( $item->post_title ) ) . '</option>'; ?>
			<?php endforeach; ?>
        </select>
		<?php
	}

	/**
	 * returns modified posts_per_page for event archive page
	 */
	public static function etn_event_archive_pagination_per_page( $query ) {
		if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'etn' ) ) {
			$settings        = etn_get_option();
			$events_per_page = ! empty( $settings['events_per_page'] ) ? $settings['events_per_page'] : 10;
			$event_sorting   = ! empty( $settings['archive_event_sorting'] ) ? $settings['archive_event_sorting'] : "";
			$event_sorting_order = ! empty( $settings['archive_event_sorting_order'] ) ? $settings['archive_event_sorting_order'] : "";
			$meta_query      = [];
			// upcoming event query
			if ( $event_sorting === 'upcoming' ) {
				$meta_query = array(
					array(
						'key'     => 'etn_start_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					)
				);
			}
			// expire events query
			if ( $event_sorting == 'expire' ) {
				$meta_query = [
					'relation' => 'OR',
					[
						'key'     => 'etn_end_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '<',
						'type'    => 'DATE',

					],
					[
						'key'     => 'etn_end_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '=',
						'type'    => 'DATE',
					],
				];
			}

			$query->set( 'meta_query', $meta_query );

			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', 'etn_start_date' );
			$query->set( 'order', $event_sorting_order );

			$query->set( 'posts_per_page', $events_per_page );

			return $query;
		}

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $attendee_id
	 *
	 * @return void
	 */
	public static function generate_unique_ticket_id_from_attendee_id( $attendee_id ) {
		$info_edit_token = get_post_meta( $attendee_id, 'etn_info_edit_token', true );
		$ticket_id       = substr( strtoupper( md5( $info_edit_token ) . $attendee_id ), - 10 );

		return $ticket_id;
	}

	/**
	 * shortcode builder option range
	 */
	public static function get_option_range( $arr = [], $class = "", $selected_index = 2 ) {
		?>
        <select class="etn-setting-input <?php echo esc_attr( $class ); ?>">
			<?php
			$i = 0;
			foreach ( $arr as $key => $value ) {
				$i ++;
				$selected = ( $i === $selected_index ) ? 'selected' : '';
				?>
                <option value="<?php echo esc_html( $key ); ?>" <?php echo esc_attr( $selected ); ?>> <?php echo esc_html( $value ); ?> </option>
			<?php }

			?>
        </select>
		<?php
		return;
	}

	/**
	 * shortcode builder hide empty
	 */
	public static function get_show_hide( $key ) {
		$hide_empty = [
			"$key='yes'" => esc_html__( 'Yes', 'eventin' ),
			"$key='no'"  => esc_html__( 'No', 'eventin' ),
		];

		return self::get_option_range( $hide_empty, '' );
	}

	/**
	 * shortcode builder hide empty
	 */
	public static function get_show_hide_recurring( $key, $selected_index = '' ) {
		$selected_index = 1;
		$hide_empty     = [
			"$key='yes'" => esc_html__( 'Yes', 'eventin' ),
			"$key='no'"  => esc_html__( 'No', 'eventin' ),
		];

		return self::get_option_range( $hide_empty, '', $selected_index );
	}

	/**
	 * shortcode builder hide empty
	 */
	public static function get_order( $key ) {
		$order = [
			"$key='ASC'"  => esc_html__( 'ASC', 'eventin' ),
			"$key='DESC'" => esc_html__( 'DESC', 'eventin' ),
		];

		return self::get_option_range( $order, '' );
	}

	/**
	 * shortcode builder hide empty
	 */
	public static function get_event_status( $key ) {
		$event_status = [
			"$key=''"         => esc_html__( 'All', 'eventin' ),
			"$key='upcoming'" => esc_html__( 'Upcoming', 'eventin' ),
			"$key='expire'"   => esc_html__( 'Expire', 'eventin' ),
		];

		return self::get_option_range( $event_status, '' );
	}

	/**
	 * shortcode builder style
	 */
	public static function get_option_style( $limit, $value_name, $option_name = "", $display_name = "" ) {
		?>
        <select class="etn-setting-input">
			<?php for ( $i = 1; $i <= $limit; $i ++ ) { ?>
                <option value="<?php echo esc_html( $value_name ); ?> ='<?php echo esc_html( $option_name . $i ); ?>'"> <?php echo esc_html( $display_name . $i, 'eventin' ); ?> </option>
			<?php }

			?>
        </select>
		<?php
		return;
	}

	/**
	 * Check If Attendee Exists For A Specific Event Of A Specific Order
	 *
	 * @param [type] $order_id
	 * @param [type] $id
	 *
	 * @return void
	 * @since 2.4.6
	 *
	 */
	public static function check_if_attendee_exists_for_ordered_event( $order_id ) {
		$args               = [
			'post_type'   => 'etn-attendee',
			'post_status' => 'publish',
		];
		$args['meta_query'] = [
			'relation' => "AND",
			[
				'key'     => 'etn_attendee_order_id',
				'value'   => $order_id,
				'compare' => '=',
			],
		];
		$data               = get_posts( $args );

		return $data;
	}

	/**
	 * Send Attendee Tickets Email For Specific Woocommerce Order
	 *
	 * @param [type] $order_id
	 * @param [type] $report_event_id
	 *
	 * @return void
	 */
	public static function send_attendee_ticket_for_woo_order( $order_id, $report_event_id = null, $gateway = 'woocommerce' ) {
		if ( $gateway == 'woocommerce' ) {
			$order = wc_get_order( $order_id );

			foreach ( $order->get_items() as $item_id => $item ) {
				// Get the product name
				$product_name = $item->get_name();
				$product_id   = ! is_null( $item->get_meta( 'event_id', true ) ) ? $item->get_meta( 'event_id', true ) : "";

				if ( ! empty( $product_id ) ) {
					$event_object = get_post( $product_id );
				} else {

					$event_object = \Etn\Core\Event\Helper::instance()->get_etn_object( $product_name );;

				}

				if ( ! empty( $event_object->post_type ) && ( 'etn' == $event_object->post_type ) && ( $event_object->ID == $report_event_id ) ) {
					$event_id   = $event_object->ID;
					$update_key = ! is_null( $item->get_meta( 'etn_status_update_key', true ) ) ? $item->get_meta( 'etn_status_update_key', true ) : "";
					self::mail_info_data( $event_id, $order_id, $product_name, $update_key );
				}
			}
		} else {
			$product_name = get_the_title( $report_event_id );
			$update_key   = get_post_meta( $order_id, 'etn_status_update_key', true );
			self::mail_info_data( $report_event_id, $order_id, $product_name, $update_key, $gateway );
		}
	}

	public static function mail_info_data( $event_id = null, $order_id = null, $product_name = '', $update_key = '', $gateway = 'woocommerce' ) {
		
		if( class_exists( 'Woocommerce' ) ){
			$order = wc_get_order( $order_id );
		}
		 
		// update attendee status and send ticket to email
		$event_location   = ! is_null( get_post_meta( $event_id, 'etn_event_location', true ) ) ? get_post_meta( $event_id, 'etn_event_location', true ) : "";
		$etn_ticket_price = ! is_null( get_post_meta( $event_id, 'etn_ticket_price', true ) ) ? get_post_meta( $event_id, 'etn_ticket_price', true ) : "";
		$etn_start_date   = ! is_null( get_post_meta( $event_id, 'etn_start_date', true ) ) ? get_post_meta( $event_id, 'etn_start_date', true ) : "";
		$etn_end_date     = ! is_null( get_post_meta( $event_id, 'etn_end_date', true ) ) ? get_post_meta( $event_id, 'etn_end_date', true ) : "";
		$etn_start_time   = ! is_null( get_post_meta( $event_id, 'etn_start_time', true ) ) ? get_post_meta( $event_id, 'etn_start_time', true ) : "";
		$etn_end_time     = ! is_null( get_post_meta( $event_id, 'etn_end_time', true ) ) ? get_post_meta( $event_id, 'etn_end_time', true ) : "";

		$pdf_data = [
			'order_id'         => $order_id,
			'event_name'       => $product_name,
			'update_key'       => $update_key,
			'user_email'       => !empty($order) ? $order->get_billing_email() : get_post_meta( $order_id, '_billing_email', true ),
			'user_name'       => !empty($order) ? $order->get_billing_first_name() : get_post_meta( $order_id, '_billing_first_name', true ),
			'event_location'   => $event_location,
			'etn_ticket_price' => $etn_ticket_price,
			'etn_start_date'   => $etn_start_date,
			'etn_end_date'     => $etn_end_date,
			'etn_start_time'   => $etn_start_time,
			'etn_end_time'     => $etn_end_time,
			'event_id'		   => $event_id
		];

		self::mail_attendee_report( $pdf_data, false, false, $gateway );
		// ========================== Attendee related works start ========================= //
	}

	/**
	 * markup for attendee ticket send in mail
	 */
	public static function generate_attendee_ticket_email_markup( $attendee_id ) {
		$attendee_name = get_the_title( $attendee_id );
		$ticket_name   = ! empty( get_post_meta( $attendee_id, 'ticket_name', true ) ) ? get_post_meta( $attendee_id, 'ticket_name', true ) : ETN_DEFAULT_TICKET_NAME;
		$edit_token    = get_post_meta( $attendee_id, 'etn_info_edit_token', true );

		$base_url              = home_url();
		$attendee_cpt          = new \Etn\Core\Attendee\Cpt();
		$attendee_endpoint     = $attendee_cpt->get_name();
		$action_url            = $base_url . "/" . $attendee_endpoint;
		$ticket_download_link  = $action_url . "?etn_action=" . urlencode( 'download_ticket' ) . "&attendee_id=" . urlencode( $attendee_id ) . "&etn_info_edit_token=" . urlencode( $edit_token );
		$edit_information_link = $action_url . "?etn_action=" . urlencode( 'edit_information' ) . "&attendee_id=" . urlencode( $attendee_id ) . "&etn_info_edit_token=" . urlencode( $edit_token );

		$event_id 				= get_post_meta( $attendee_id, 'etn_event_id', true );
		$meeting_link 		    = get_post_meta( $event_id, 'meeting_link', true );
		$event_type 			= get_post_meta( $event_id, 'event_type', true );
		$event_location 		= get_post_meta( $event_id, 'etn_event_location', true );
		$platform 				= ! empty( $event_location['integration'] ) ? $event_location['integration'] : '';

		$platforms = [
			'zoom' 		  => __( 'Zoom Link', 'eventin' ),
			'google_meet' => __( 'Google meet Link', 'eventin' ),
			'custom_url'  => __( 'Custom URL', 'eventin' ),
		];

		?>
		<?php if ( 'online' === $event_type && $meeting_link ): ?>
			<div><?php echo esc_html( $platforms[$platform] ); ?> :  <a href="<?php echo esc_url( $meeting_link ); ?>" target="_blank" class="" style="margin-right: 10px"><?php echo $meeting_link ?></a></div>
		<?php endif; ?>
        <div class="etn-attendee-details-button-parent">
            <div class="etn-attendee-details-name"><?php echo esc_html__( 'Ticket name: ', 'eventin' ) . esc_html( $ticket_name ); ?></div>
            <div class="etn-attendee-details-name"><?php echo esc_html__( 'Attendee: ', 'eventin' ) . esc_html( $attendee_name ); ?></div>
            <div class="etn-attendee-details-button-download">
                <a class="etn-btn etn-success download-details" target="_blank"
                   href="<?php echo esc_url( $ticket_download_link ); ?>"><?php echo esc_html__( 'Download Ticket', 'eventin' ); ?></a>
                |
                <a class="etn-btn etn-success edit-information" target="_blank"
                   href="<?php echo esc_url( $edit_information_link ); ?>"><?php echo esc_html__( 'Edit Information', 'eventin' ); ?></a>
            </div>
        </div><br>
		<?php
	}

	/**
	 * update attendee status and send ticket to email
	 */
	public static function mail_attendee_report( $pdf_data, $checkout = false, $update_payment_status = true, $gateway = 'woocommerce' ) {

		global $wpdb;

		if ( is_array( $pdf_data ) && ! empty( $pdf_data['update_key'] ) ) {
			$prepare_guery           = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta where meta_key ='etn_status_update_token' and meta_value = '%s' ", $pdf_data['update_key'] );
			$current_event_attendees = $wpdb->get_col( $prepare_guery );
			$event_name              = $pdf_data['event_name'];

			if ( $gateway == 'woocommerce' ) {
				$order        = wc_get_order( $pdf_data['order_id'] );
				$order_status = $order->get_status();
			} else {
				$order_status = get_post_meta( $pdf_data['order_id'], 'etn_payment_status', true );
			}
			
			// update attendee payment status
			if ( $update_payment_status ) {
				$special_status = $order_status;
				if ( $gateway == 'stripe' ) {
					$special_status = 'stripe-pending';
				}
				self::process_attendee_payment_status( $current_event_attendees, $pdf_data, $special_status );
			}

			if ( ! $checkout ) {
				// don't attempt to send ticket email while creating an order
				// send attendee ticket email
				self::process_attendee_ticket_email( $current_event_attendees, $event_name, $pdf_data, $order_status );
			}
		}
	}

	public static function process_attendee_payment_status( $current_order_attendees, $pdf_data, $order_status ) {

		if ( is_array( $current_order_attendees ) && ! empty( $current_order_attendees ) ) {

			foreach ( $current_order_attendees as $key => $value ) {
				$attendee_id = intval( $value );

				//update attendee status
				update_post_meta( $attendee_id, 'etn_attendee_order_id', $pdf_data['order_id'] );
				Helper::update_attendee_payment_status( $attendee_id, $order_status );
			}

		}

	}

	public static function process_attendee_ticket_email( $current_order_attendees, $event_name, $pdf_data, $order_status ) {

		$event_id = ! empty( $pdf_data['event_id'] ) ? intval( $pdf_data['event_id'] ) : 0;
		$event 	  = new Event_Model( $event_id );
		$post	  = get_post( $event_id );
		$location = get_post_meta( $event_id, 'etn_event_location', true );
		$address  = ! empty( $location['address'] ) ? $location['address'] : '';

		$admins 	= get_users(array('role' => 'administrator'));
		$host_name 	= '';
		$host_email = '';

		// Check if there are any administrators
		if (!empty($admins)) {
			// Get the first administrator's data (assuming the first one is the main admin)
			$admin = $admins[0];

			// Get the admin's email and display name
			$host_email = $admin->user_email;
			$host_name = $admin->display_name;
		}
		
		$placeholder = [
			'{%site_name%}' 	 => get_bloginfo( 'name' ),
			'{%site_link%}' 	 => site_url(),
			'{%site_logo%}' 	 => get_bloginfo('logo'),
			'{%event_title%}'    => $post->post_title,
			'{%event_date%}' 	 => $event->etn_start_date,
			'{%event_time%}' 	 => $event->etn_start_time,
			'{%event_location%}' => $address,
			'{%host_name%}' 	 => $host_name,
			'{%host_email%}'	 => $host_email,
			'{%customer_name%}'  => ! empty( $pdf_data['user_name'] ) ? $pdf_data['user_name'] : '',
			'{%customer_email%}' => $pdf_data['user_email'],
		];

		ob_start();

		// Get email message
        $purchase_email         = etn_get_email_settings( 'purchase_email' );
		$purchase_email_message = ! empty( $purchase_email['body'] ) ? $purchase_email['body'] : esc_html__( "You have purchased ticket(s) for %s. Attendee ticket details are as follows.", "eventin" );

		$purchase_email_message = strtr( $purchase_email_message, $placeholder );
		?>
        <div>
			<?php echo sprintf( $purchase_email_message, $event_name ); ?>
        </div>
        <br><br>
		<?php

		if ( is_array( $current_order_attendees ) && ! empty( $current_order_attendees ) ) {

			foreach ( $current_order_attendees as $key => $value ) {
				$attendee_id = intval( $value );

				//generate email content markup
				Helper::generate_attendee_ticket_email_markup( $attendee_id );
			}

		}

		$mail_content = ob_get_clean();
		$mail_content = self::filter_template_tags( $mail_content, $event_name );


		$settings_options     = Helper::get_settings();
		$disable_ticket_email = ! empty( Helper::get_option( 'disable_ticket_email' ) ) ? true : false;

		// send email with attendee tickets
		if ( ( ! $disable_ticket_email ) && is_array( $pdf_data ) && ! empty( $pdf_data['user_email'] ) ) {
			$to        = $pdf_data['user_email'];
			$subject   = ! empty( $purchase_email['subject'] ) ? $purchase_email['subject'] : esc_html__( 'Event Ticket', "eventin" );
			$from      = ! empty( $purchase_email['from'] ) ? $purchase_email['from'] : $settings_options['admin_mail_address'];
			$from_name = self::retrieve_mail_from_name();

			$proceed_ticket_mail = true;

			$order_status = strtolower( $order_status );

			// if checkout time and order_status is processing/completed then ticket mail will sent
			if ( ! ( $order_status == 'processing' || $order_status == 'completed' ) ) {
				$proceed_ticket_mail = false;
			}
			$proceed_ticket_mail = true;
			if ( $proceed_ticket_mail ) {
				Helper::send_email( $to, $subject, $mail_content, $from, $from_name );

				$reg_require_email = ! empty( Helper::get_option( 'reg_require_email' ) ) ? 'checked' : '';

				if ( is_array( $current_order_attendees ) && ! empty( $current_order_attendees ) && $reg_require_email == 'checked' ) {
					foreach ( $current_order_attendees as $key => $value ) {
						$attendee_id   = intval( $value );
						$attendee_to   = ! empty( get_post_meta( $attendee_id, 'etn_email', true ) ) ? get_post_meta( $attendee_id, 'etn_email', true ) : '';

						//generate email content markup
						ob_start();
						?>
						<div>
							<?php echo sprintf( $purchase_email_message, $event_name ); ?>
						</div>
						<br><br>
						<?php
						Helper::generate_attendee_ticket_email_markup( $attendee_id );

						$mail_attendee_content = ob_get_clean();
						$mail_attendee_content = self::filter_template_tags( $mail_attendee_content, $event_name );

						if( !empty( $attendee_to ) ){
							Helper::send_email( $attendee_to, $subject, $mail_attendee_content, $from, $from_name );
						}
							
					}
				}
			}

		}
	}

	/**
	 * get decoded version of special character to show
	 *
	 * @return string
	 */
	public static function retrieve_mail_from_name() {
		add_filter( 'wp_mail_from_name', function () {
			return html_entity_decode( get_bloginfo( "name" ), ENT_QUOTES );
		} );
	}


	/**
	 * Sanitize Recurring Event Slug Name
	 *
	 * @param [type] $post_slug
	 * @param [type] $post_slug_postfix
	 *
	 * @return void
	 */
	public static function sanitize_recurring_event_slug( $post_slug, $post_slug_postfix ) {

		if ( strlen( $post_slug . '-' . $post_slug_postfix ) > 200 ) {
			if ( preg_match( '/^(.+)(\-[0-9]+)$/', $post_slug, $post_slug_parts ) ) {
				$post_slug_decoded = urldecode( $post_slug_parts[1] );
				$post_slug_suffix  = $post_slug_parts[2];
			} else {
				$post_slug_decoded = urldecode( $post_slug );
				$post_slug_suffix  = '';
			}

			$post_slug_maxlength = 200 - strlen( $post_slug_suffix . '-' . $post_slug_postfix );
			if ( $post_slug_parts[0] === $post_slug_decoded . $post_slug_suffix ) {
				$post_slug = substr( $post_slug_decoded, 0, $post_slug_maxlength );
			} else {
				$post_slug = utf8_uri_encode( $post_slug_decoded, $post_slug_maxlength );
			}

			$post_slug = rtrim( $post_slug, '-' ) . $post_slug_suffix;
		} else {
			$post_slug = rtrim( $post_slug . '-' ) . $post_slug_postfix;
		}

		return apply_filters( 'etn_sanitize_recurring_event_slug', $post_slug, $post_slug_postfix );
	}

	/**
	 * Check if slug exists
	 *
	 * @param [type] $post_name
	 *
	 * @return void
	 */
	public static function the_slug_exists( $post_name ) {
		global $wpdb;
		if ( $wpdb->get_row( "SELECT post_name FROM $wpdb->posts WHERE post_name = '" . $post_name . "'", 'ARRAY_A' ) ) {
			return true;
		} else {
			return false;
		}

	}

	/*
     * get all posts which are shop_order
     */
	public static function get_order_posts() {
		global $wpdb;
		$order_posts = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_type = 'shop_order' ORDER BY id DESC", ARRAY_A );

		return $order_posts;
	}

	/**
	 * Checks If An Event Has Recurring Events
	 *
	 * @param [type] $single_event_id
	 *
	 * @return void
	 */
	public static function get_child_events( $single_event_id ) {
		//if post type is etn and post has this is as parent-id
		$args     = [
			'post_parent' => $single_event_id,
			'post_type'   => 'etn',
		];
		$children = get_children( $args );

		if ( ! empty( $children ) ) {
			return $children;
		}

		return false;
	}

	/**
	 * All data for recurrence details table
	 */
	public static function get_all_data( $id ) {
		$events_data = [];

		$events = get_posts( [
			'post_parent'    => $id,
			'posts_per_page' => - 1,
			'post_type'      => 'etn',
		] );

		if ( ! empty( $events ) ) {

			foreach ( $events as $key => $post ) {
				$freq                            = get_post_meta( $id, 'etn_event_recurrence', true );
				$recurr_type                     = ! empty( $freq['recurrence_freq'] ) ? ucfirst( $freq['recurrence_freq'] ) : "";
				$events_data[ $key ]['ID']       = $post->ID;
				$events_data[ $key ]['name']     = $post->post_title;
				$events_data[ $key ]['location'] = get_post_meta( $post->ID, 'etn_event_location', true );

				$events_data[ $key ]['schedule'] = esc_html__( 'Date: ', 'eventin' ) . get_post_meta( $post->ID, 'etn_start_date', true ) . esc_html__( ' to ', 'eventin' ) . get_post_meta( $post->ID, 'etn_end_date', true ) .
				                                   esc_html__( ' Time: ', 'eventin' ) . get_post_meta( $post->ID, 'etn_start_time', true ) . esc_html__( ' - ', 'eventin' ) . get_post_meta( $post->ID, 'etn_end_time', true );
			}

		}

		return $events_data;
	}

	public static function total_data( $id ) {
		$events = get_posts( [
			'post_parent'    => $id,
			'posts_per_page' => - 1,
			'post_type'      => 'etn',
		] );

		return count( $events );
	}

	/**
	 * Check If An Event Is A Recurrence Child
	 *
	 * @param [type] $event_id
	 *
	 * @return boolean
	 */
	public static function is_recurrence( $event_id ) {

		if ( 'etn' == get_post_type( $event_id ) && '0' != wp_get_post_parent_id( $event_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return day name
	 *
	 * @return void
	 */
	public static function day_name() {
		return [
			'Sun' => 'Sun',
			'Mon' => 'Mon',
			'Tue' => 'Tue',
			'Wed' => 'Wed',
			'Thu' => 'Thu',
			'Fri' => 'Fri',
			'Sat' => 'Sat'
		];
	}

	/**
	 * Create page
	 *
	 * @param string $title_of_the_page
	 * @param string $content
	 * @param [type] $parent_id
	 *
	 * @return void
	 */
	public static function create_page( $title_of_the_page, $content = '', $parent_id = null, $replace = '_' ) {

		$objPage = get_page_by_path( $title_of_the_page );

		if ( empty( $objPage ) ) {

			$page_id = wp_insert_post(
				[
					'comment_status' => 'close',
					'ping_status'    => 'close',
					'post_author'    => 1,
					'post_title'     => ucwords( str_replace( $replace, ' ', trim( $title_of_the_page ) ) ),
					'post_name'      => $title_of_the_page,
					'post_status'    => 'publish',
					'post_content'   => $content,
					'post_type'      => 'page',
					'post_parent'    => $parent_id,
				]
			);

		} else {
			$page_id = $objPage->ID;
		}

		return $page_id;
	}

	public static function send_attendee_ticket_email_on_order_status_change( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );


		foreach ( $order->get_items() as $item_id => $item ) {

			// Get the product name
			$product_name = $item->get_name();
			$event_id     = ! is_null( $item->get_meta( 'event_id', true ) ) ? $item->get_meta( 'event_id', true ) : "";

			if ( ! empty( $event_id ) ) {
				$event_object = get_post( $event_id );
			} else {
				$event_object = \Etn\Core\Event\Helper::instance()->get_etn_object( $product_name );
			}

			if ( ! empty( $event_object ) && ( 'etn' == $event_object->post_type ) ) {

				// ========================== Attendee related works start ========================= //
				$settings            = Helper::get_settings();
				$attendee_reg_enable = ! empty( $settings["attendee_registration"] ) ? true : false;

				if ( $attendee_reg_enable ) {
					// update attendee status and send ticket to email
					$update_key = ! is_null( $item->get_meta( 'etn_status_update_key', true ) ) ? $item->get_meta( 'etn_status_update_key', true ) : "";
					self::mail_info_data( $event_id, $order_id, $product_name, $update_key );
				}

				// ========================== Attendee related works end ========================= //
			}

		}

	}

	/**
	 * Input field escaping , sanitizing , validation
	 *
	 * @param array $request
	 * @param array $input_fields
	 *
	 * @return array
	 */
	public static function input_field_validation( $request, $input_fields ) {

		$response = [
			'status_code' => 1,
			'messages'    => [],
			'data'        => [],
		];

		if ( ! empty( $input_fields ) ) {
			$error_field = [];

			foreach ( $input_fields as $key => $value ) {

				if ( $value['required'] == true && empty( $request[ $value['name'] ] ) ) {
					$error_field[] = esc_html( ucfirst( str_replace( '_', ' ', $value['name'] ) ) . ' is empty', 'eventin' );
				}

			}

			if ( count( $error_field ) > 0 ) {
				$response = [
					'status_code' => 0,
					'messages'    => $error_field,
				];
			} else {

				$input_data = [];

				foreach ( $input_fields as $key => $value ) {
					$data                         = self::validate_param_data( $request, $value );
					$input_data[ $value['name'] ] = $data;
				}

				// pass sanitizing data
				$response = [
					'status_code' => 1,
					'messages'    => [],
					'data'        => $input_data,
				];
			}

		} else {
			$response = [
				'status_code' => 0,
				'messages'    => [
					'error' => esc_html__( 'Input field is empty', 'eventin' ),
				],
			];
		}

		return $response;
	}

	/**
	 * Sanitize and escaping data
	 *
	 * @param array $request
	 * @param array $input_fields
	 *
	 * @return mixed
	 */
	public static function validate_param_data( $request, $input_fields ) {
		$data = "";

		switch ( $input_fields['type'] ) {
			case "email":
				$data = sanitize_email( $request[ $input_fields['name'] ] );
				break;
			case "text":
				$data = sanitize_text_field( $request[ $input_fields['name'] ] );
				break;
			case "richeditor":
				$data = self::kses( $request[ $input_fields['name'] ] );
				break;
			case "number":
				$data = absint( $request[ $input_fields['name'] ] );
				break;
			default:
				break;
		}

		return $data;
	}

	/**
	 * Get All Events By Month of A Year
	 *
	 * @param [type] $month
	 * @param [type] $year
	 * @param array $params
	 *
	 * @return void
	 */
	public static function get_events_by_date( $month, $year, $display, $endDate, $startTime, $start_date = '', $end_date = '', $post_parent = '0', $post_id = '0', $selected_cats ='') {

		if ( empty( $month ) || empty( $year ) ) {
			return;
		}

		$all_events = [];

		if( class_exists('SitePress') && function_exists('icl_object_id') ){
			global $sitepress;
			$post_language_info = apply_filters( 'wpml_post_language_details', NULL, $post_id) ;
			$sitepress->switch_lang($post_language_info['language_code']);
		}

		$args = [
			'post_type'   => 'etn',
			'numberposts' =>  -1,
			'post_status' => 'publish',
			'fields'      => 'ids',
			'suppress_filters' => false,
			'meta_query'  =>
				array(
					'relation' => 'OR',
					array(
						'key'     => 'etn_end_date',
						'value'   => $start_date,
						'compare' => '>=',
						'type'    => 'DATE'
					),
					array(
						'key'     => 'etn_start_date',
						'value'   =>  $end_date,
						'compare' => '<=',
						'type'    => 'DATE'
					),
				)
			
		];
 
		if (!is_array($selected_cats) || empty($selected_cats)) {
			// If selected categories are not an array or empty, show all posts by default
			$selected_categories = [];
		} else {
			$selected_categories = explode(',', $selected_cats);
		}
		
		if (empty($selected_categories)) {
			// If no specific categories are selected, get all categories
			$terms = get_terms(array(
				'taxonomy' => 'etn_category',
				'hide_empty' => true
			));
			if (!empty($terms)) {
				$selected_categories = wp_list_pluck($terms, 'term_id');
			}
		}
		
		if (!empty($selected_categories)) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'etn_category',
					'field' => 'term_id',
					'terms' => $selected_categories
				)
			);
		}


		if ( 'child' !== $post_parent || 'hide_both' == $post_parent ) {
			$parent_id           = $post_parent == 'hide_both' ? '0' : $post_parent;
			$args['post_parent'] = $parent_id;
		}
		$post_ids = get_posts( $args );

		if ( ( 'child' == $post_parent || 'hide_both' == $post_parent ) && ( is_array( $post_ids ) && count( $post_ids ) > 0 ) ) {
			// Delete all the Parent recurring event
			foreach ( $post_ids as $index => $post_id ) {
				$is_recurring_parent = Helper::get_child_events( $post_id );
				if ( $is_recurring_parent ) {
					unset( $post_ids[ $index ] );
				}
			}
		}

		// if 'parent' === $post_parent then hide all the child events
		if ( 'parent' === $post_parent && ( is_array( $post_ids ) && count( $post_ids ) > 0 ) ) {
			foreach ( $post_ids as $index => $post_id ) {
				$is_recurring_child = Helper::is_recurrence( $post_id );
				if ( $is_recurring_child ) {
					unset( $post_ids[ $index ] );
				}
			}
		}

		foreach ( $post_ids as $post_id ) {
			$event_id              = $post_id;
			$event_cat             = wp_get_post_terms( $event_id, 'etn_category' );
			$cat_names             = wp_list_pluck( $event_cat, 'name' );
			$event                 = new \stdClass;
			$etn_ticket_variations = get_post_meta( $event_id, "etn_ticket_variations", true );

			$currency = '';

			if ( class_exists( 'woocommerce' ) && ! empty( $etn_ticket_variations ) ) {
				$currency = get_woocommerce_currency_symbol();
			} else if ( ! class_exists( 'woocommerce' ) && ! empty( $etn_ticket_variations ) ) {
				$currency = '$';
			}
			$min_price       = '';
			$max_price       = '';
			$variation_price = [];

			if ( ! empty( $etn_ticket_variations ) && is_array( $etn_ticket_variations ) ) {
				foreach ( $etn_ticket_variations as $index => $price ) {
					$variation_price[ $index ] = $price['etn_ticket_price'];
				}
				$min_price = min( $variation_price );
				$max_price = max( $variation_price );
			}

			if ( $min_price === $max_price ) {
				$price = $currency . $min_price;
			} else {
				$price = $currency . $min_price . "-" . $currency . $max_price;
			}

			if ( ! empty( $display ) ) {
				$event->display = $display;
			}

			$etn_start_time        = strtotime( get_post_meta( $event_id, 'etn_start_time', true ) );
			$etn_end_time          = strtotime( get_post_meta( $event_id, 'etn_end_time', true ) );

			// time stamp
			$start_date   = get_post_meta( $event_id, 'etn_start_date', true );
			$end_date     = get_post_meta( $event_id, 'etn_end_date', true ) == "" ? $start_date : get_post_meta( $event_id, 'etn_end_date', true );
			$date_format  = Helper::get_option( "date_format" );
			$date_options = Helper::get_date_formats();

			$etn_start_date   = get_post_meta( $event_id, 'etn_start_date', true );
			$event_start_date = ! empty( $date_format ) ? date( $date_options[ $date_format ], strtotime( $etn_start_date ) ) : date( get_option( "date_format" ), strtotime( $etn_start_date ) );


			$event_options      = get_option( "etn_event_options" );
			$event_time_format  = empty( $event_options["time_format"] ) ? '12' : $event_options["time_format"];
			$start_time   		= empty( $etn_start_time ) ? '' : ( ( $event_time_format == "24" ) ? date( 'H:i', $etn_start_time ) : date( 'g:i a', $etn_start_time ) );
			$end_time     		= empty( $etn_end_time ) ? '' : ( ( $event_time_format == "24" ) ? date( 'H:i', $etn_end_time ) : date( 'g:i a', $etn_end_time ) );

			if ( $start_date < $end_date ) {
				$start_date = $start_date;
				$end_date   = $end_date;
			} else {
				$start_date = $start_date . "" . $start_time;
				$end_date   = $end_date . "" . $end_time;
			}

			$event->className  = "has-event";
			$event->start_time = $start_time;
			$event->end_time   = $end_time;
			$event->id         = $event_id;
			$event->title      = get_the_title( $event_id );
			$event->date       = get_post_meta( $event_id, 'etn_start_date', true );

			if ( $startTime ) {
				$event->start = $start_date;
			}

			$endDatePlusOne = date( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );

			if ( $endDate ) {
				$event->end = $end_date;
			}

			$event->price       = $price;
			$event->description = get_post_field( 'post_content', $event_id );
			$event->thumbnail   = get_the_post_thumbnail_url( $event_id );
			$event->category    = $cat_names;
			$location           = get_post_meta( $event_id, 'etn_event_location', true
		 );

			// Show location based on location type.

			if ( class_exists( 'Wpeventin_Pro' ) ) {

				$location_tax_array    = array_column( wp_get_post_terms( $event_id, 'etn_location', [ 'fields' => 'all' ] ), 'name' );
				$selected_etn_location = implode( ', ', $location_tax_array );
				$location_type         = get_post_meta( $event_id, 'etn_event_location_type', true );
				$event->location       = 'new_location' === $location_type ? $selected_etn_location : $location;

			} else {

				$event->location = $location;
			}

			$event->url             = get_permalink( $event_id );
			$event->backgroundColor = get_post_meta( $event_id, 'etn_event_calendar_bg', true );


			$event->borderColor = get_post_meta( $event_id, 'etn_event_calendar_bg', true );
			$event->textColor   = get_post_meta( $event_id, 'etn_event_calendar_text_color', true );
			$event->dateFormat  = ! empty( $date_options[ $date_format ] ) ? $date_options[ $date_format ] : get_option( 'date_format' );
			$event->end_date    = get_post_meta( $event_id, 'etn_end_date', true );
			$all_events[]       = $event;
		}

		return $all_events;
	}

	public static function generate_unique_slug_from_ticket_title( $event_id, $event_ticket_variation_title ) {
		$ticket_title = $event_ticket_variation_title == "" ? esc_html__( "Default", "eventin" ) : $event_ticket_variation_title;

		return $event_id . "-" . sanitize_title_with_dashes( $ticket_title ) . "-" . substr( md5( time() ), 0, 5 );
	}

	/**
	 * returns list of all attendee
	 * returns single speaker if speaker id is provided
	 */
	public static function get_attendee( $id = null, $posts_per_page = - 1, $paged = 1 ) {
		try {
			if ( is_null( $id ) || ! is_numeric( $id ) ) {
				$args      = [
					'post_type'      => 'etn-attendee',
					'posts_per_page' => $posts_per_page,
					'paged'          => $paged,
				];
				$attendees = get_posts( $args );

				return $attendees;
			} else {
				// return single speaker
				$attendee = null;
				if ( 'etn-attendee' === get_post_type( $id ) ) {
					$attendee = get_post( $id );
				}

				return $attendee;
			}

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * calculate left ticket for individual variation ticket from total and sold quantity
	 *
	 * @param [array] $ticket_variation
	 *
	 * @return void
	 */
	public static function compute_left_tickets( $ticket_variation ) {
		$left_tickets = 0;

		if ( ! empty( $ticket_variation ) ) {
			$avaiilable_tickets = ! empty( $ticket_variation['etn_avaiilable_tickets'] ) ? absint( $ticket_variation['etn_avaiilable_tickets'] ) : 0;
			$sold_tickets       = ! empty( $ticket_variation['etn_sold_tickets'] ) ? absint( $ticket_variation['etn_sold_tickets'] ) : 0;
			$left_tickets       = $avaiilable_tickets - $sold_tickets;
		}

		return $left_tickets;
	}

	/**
	 * Ticket Form Widget For Single Events
	 *
	 * @param [type] $single_event_id
	 * @param string $class
	 *
	 * @return void
	 */
	public static function eventin_ticket_widget( $single_event_id, $class = "" ) { 

		// purchase module script
		wp_enqueue_script( 'etn-module-purchase');
	
		if ( file_exists( get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/single-event-variable-ticket.php' ) ) {
			$purchase_form_widget = get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/single-event-variable-ticket.php';
		} elseif ( file_exists( get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/single-event-variable-ticket.php' ) ) {
			$purchase_form_widget = get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/single-event-variable-ticket.php';
		} else {
			$purchase_form_widget = \Wpeventin::templates_dir() . 'event/purchase-form/single-event-variable-ticket.php';
		} 

		include $purchase_form_widget;
	}

	/**
	 * utc time calculation with event timezone
	 */
	public static function time_calculation( $event_timezone = '' ) {
		$utc_plus_minus = '+';
		if ( strpos( $event_timezone, 'UTC-' ) !== false ) {
			$utc_plus_minus = '-';
		}

		$utc_h_m = explode( $utc_plus_minus, $event_timezone )[1];

		$utc_h = $utc_h_m;
		$utc_m = 0;
		if ( strpos( $utc_h_m, '.' ) ) {
			$utc_h_m_again = explode( '.', $utc_h_m );
			$utc_h         = $utc_h_m_again[0];
			$utc_m         = $utc_h_m_again[1];

			if ( $utc_m == '5' ) {
				$utc_m = 30;
			} else if ( $utc_m == '75' ) {
				$utc_m = 45;
			}
		}

		$utc_cal = $utc_h * 60 * 60;
		if ( ! empty( $utc_m ) ) {
			$utc_cal += $utc_m * 60;
		}

		if ( $utc_plus_minus == '+' ) {
			$time_cal = time() + $utc_cal;
		} else {
			$time_cal = time() - $utc_cal;
		}

		return $time_cal;
	}

	/**
	 * Ticket Form Widget For Recurring Events
	 *
	 * @param [type] $single_event_id
	 * @param string $class
	 *
	 * @return void
	 */
	public static function woocommerce_recurring_events_ticket_widget( $parent_event_id, $recurring_event_ids, $class = "" ) {
		do_action( 'etn_before_recurring_event_form_content', $parent_event_id );

		if ( is_array( $recurring_event_ids ) && ! empty( $recurring_event_ids ) ) {

			asort( $recurring_event_ids );
			$i = 0;

			$events_with_start_date = [];
			foreach ( $recurring_event_ids as $key => $single_event_id ) {
				if ( ! empty( get_post_meta( $single_event_id, 'etn_start_date', true ) ) ) {
					$temp                     = [];
					$temp['id']               = $single_event_id;
					$temp['date']             = get_post_meta( $single_event_id, 'etn_start_date', true );
					$events_with_start_date[] = $temp;
				}
			}

			// sort events with start date
			usort( $events_with_start_date, function ( $a, $b ) {
				if ( $a == $b ) {
					return 0;
				}

				return strtotime( $a['date'] ) - strtotime( $b['date'] );
			} );

			if ( is_array( $events_with_start_date ) && ! empty( $events_with_start_date ) ) {
				foreach ( $events_with_start_date as $single_event ) {
					$single_event_id = intval( $single_event['id'] );
					$data            = self::single_template_options( $single_event_id );
					$ticket_list     = get_post_meta( $single_event_id, 'etn_ticket_variations', true );
					$unique_id       = md5( md5( microtime() ) );

					if ( is_array( $ticket_list ) && ! empty( $ticket_list ) ) {
						$event_options        = ! empty( $data['event_options'] ) ? $data['event_options'] : [];
						$etn_ticket_unlimited = ( isset( $data['etn_ticket_unlimited'] ) && $data['etn_ticket_unlimited'] == "no" ) ? true : false;
						$is_zoom_event        = get_post_meta( $single_event_id, 'etn_zoom_event', true );
						$event_title          = get_the_title( $single_event_id );
						$deadline             = get_post_meta( $single_event_id, 'etn_registration_deadline', true );
						$reg_deadline_expired = false;

						if ( ! empty( $deadline ) ) {
							$date_options    = \Etn\Utils\Helper::get_date_formats();
							$etn_date_format = ( isset( $event_options["date_format"] ) && $event_options["date_format"] != "" ) ? $date_options[ $event_options["date_format"] ] : get_option( "date_format" );

							$deadline_new = $deadline;
							$deadline_arr = date_parse_from_format( $etn_date_format, $deadline );
							if ( is_array( $deadline_arr ) && count( $deadline_arr ) > 0 ) {
								$deadline_new = $deadline_arr['year'] . '-' . $deadline_arr['month'] . '-' . $deadline_arr['day'];
							}

							$etn_start_time         = get_post_meta( $single_event_id, 'etn_start_time', true );
							$event_expire_date_time = trim( $deadline );

							$event_timezone = get_post_meta( $single_event_id, "event_timezone", true );
							if ( str_contains( $event_timezone, 'UTC+' ) || str_contains( $event_timezone, 'UTC-' ) ) {
								$time_cal = self::time_calculation( $event_timezone );

								$dt     = date_i18n( "Y-m-d H:i:s", strtotime( "$event_expire_date_time" ) );
								$now_dt = date_i18n( "Y-m-d H:i:s", $time_cal );
							} else {
								date_default_timezone_set( $event_timezone );
								$dt     = date_i18n( "Y-m-d H:i:s", strtotime( "$event_expire_date_time $event_timezone" ) );
								$now_dt = date_i18n( "Y-m-d H:i:s" );
							}

							$dt_int     = strtotime( $dt );
							$now_dt_int = strtotime( $now_dt );

							if ( $now_dt_int > $dt_int ) {
								$reg_deadline_expired = true;
							}

						}

						$event = new Event_Model( $single_event_id );

						$event_total_ticket = ! empty( get_post_meta( $single_event_id, "etn_total_avaiilable_tickets", true ) ) ? absint( get_post_meta( $single_event_id, "etn_total_avaiilable_tickets", true ) ) : 0;
						$event_sold_ticket  = ! empty( get_post_meta( $single_event_id, "etn_total_sold_tickets", true ) ) ? absint( get_post_meta( $single_event_id, "etn_total_sold_tickets", true ) ) : 0;
						$event_left_ticket  = intval($event->get_total_ticket()) - $event_sold_ticket;

						if ( file_exists( get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-variable-ticket.php' ) ) {
							$purchase_form_widget = get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-variable-ticket.php';
						} elseif ( file_exists( get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-variable-ticket.php' ) ) {
							$purchase_form_widget = get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-variable-ticket.php';
						} else {
							$purchase_form_widget = \Wpeventin::templates_dir() . 'event/purchase-form/recurring-event-variable-ticket.php';
						}

					} else {

						$etn_left_tickets     = ! empty( $data['etn_left_tickets'] ) ? $data['etn_left_tickets'] : 0;
						$etn_ticket_unlimited = ( isset( $data['etn_ticket_unlimited'] ) && $data['etn_ticket_unlimited'] == "no" ) ? true : false;
						$etn_ticket_price     = isset( $data['etn_ticket_price'] ) ? $data['etn_ticket_price'] : '';
						$ticket_qty           = get_post_meta( $single_event_id, "etn_sold_tickets", true );
						$total_sold_ticket    = isset( $ticket_qty ) ? intval( $ticket_qty ) : 0;
						$is_zoom_event        = get_post_meta( $single_event_id, 'etn_zoom_event', true );
						$event_options        = ! empty( $data['event_options'] ) ? $data['event_options'] : [];
						$event_title          = get_the_title( $single_event_id );
						$etn_min_ticket       = ! empty( get_post_meta( $single_event_id, 'etn_min_ticket', true ) ) ? get_post_meta( $single_event_id, 'etn_min_ticket', true ) : 1;
						$etn_max_ticket       = ! empty( get_post_meta( $single_event_id, 'etn_max_ticket', true ) ) ? get_post_meta( $single_event_id, 'etn_max_ticket', true ) : $etn_left_tickets;
						$etn_max_ticket       = min( $etn_left_tickets, $etn_max_ticket );

						if ( file_exists( get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-ticket.php' ) ) {
							$purchase_form_widget = get_stylesheet_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-ticket.php';
						} elseif ( file_exists( get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-ticket.php' ) ) {
							$purchase_form_widget = get_template_directory() . \Wpeventin::theme_templates_dir() . 'event/purchase-form/recurring-event-ticket.php';
						} else {
							$purchase_form_widget = \Wpeventin::templates_dir() . 'event/purchase-form/recurring-event-ticket.php';
						}

					}

					if ( file_exists( $purchase_form_widget ) ) {
						include $purchase_form_widget;
					}

					$i ++;
				}
			}

		}

		do_action( 'etn_after_recurring_event_form_content', $parent_event_id );

	}

	public static function convert_to_calender_date( $datetime ) {
		$time_string   = strtotime( $datetime );
		$date          = date_i18n( 'Ymd', $time_string );
		$time          = date( 'Hi', $time_string );
		$calendar_date = $date . "T" . $time . "00";

		return $calendar_date;
	}

	public static function content_to_html( $array_or_string ) {

		if ( is_string( $array_or_string ) ) {

			$array_or_string = sanitize_text_field( htmlentities( nl2br( $array_or_string ) ) );

		} elseif ( is_array( $array_or_string ) ) {

			foreach ( $array_or_string as $key => &$value ) {

				if ( is_array( $value ) ) {
					$value = self::content_to_html( $value );
				} else {
					$value = sanitize_text_field( htmlentities( nl2br( $value ) ) );
				}

			}

		}

		return $array_or_string;
	}

	public static function convert_to_calendar_title( $post_title ) {
		return str_replace( ' ', '+', $post_title );
	}

	/**
	 * check webinar plan is enabled for the user
	 */
	public static function is_webinar_user() {
		$webinar_enabled   = false;
		$user_settings_obj = json_decode( \Etn\Core\Zoom_Meeting\Api_Handlers::instance()->get_user_settings() );

		if ( is_object( $user_settings_obj ) && isset( $user_settings_obj->feature ) ) {
			$webinar_enabled = $user_settings_obj->feature->webinar;
		}

		return $webinar_enabled;
	}

	/**
	 * return settings set payment gateway like woocommerce, stripe etc.
	 */
	public static function retrieve_payment_gateway() {
		$payment_type = etn_get_option( 'etn_sells_engine_stripe', 'woocommerce' );

		return $payment_type;
	}

	/**
	 * ticket variations structure and value updation
	 *
	 * @param [type] $event_id
	 * @param array $ticket_variations
	 * @param [type] $from_multivendor
	 *
	 * @return array
	 */
	public static function get_ticket_variations_info( $event_id = null, $ticket_variations = [], $from_multivendor = false ) {
		$etn_total_created_tickets = 0;

		foreach ( $ticket_variations as $key => &$value ) {
			// will optimize
			if ( ! empty( $value['etn_ticket_name'] ) || ! empty( $value['etn_ticket_price'] ) || ! empty( $value['etn_avaiilable_tickets'] ) ||
			     ! empty( $value['etn_sold_tickets'] ) ) {
				if ( empty( $value['etn_ticket_name'] ) ) {
					$value['etn_ticket_name'] = ETN_DEFAULT_TICKET_NAME;
				}

				if ( empty( $value['etn_ticket_price'] ) ) {
					$value['etn_ticket_price'] = 0;
				}

				if ( ! isset( $value['etn_ticket_slug'] ) ) {
					$value['etn_ticket_slug'] = "";
				}

				if ( $value['etn_ticket_slug'] == "" ) {
					$value['etn_ticket_slug'] = Helper::generate_unique_slug_from_ticket_title( $event_id, $value['etn_ticket_name'] );
				}

				$etn_avaiilable_tickets          = ! empty( $value['etn_avaiilable_tickets'] ) ? intval( $value['etn_avaiilable_tickets'] ) : 100000;
				$value['etn_avaiilable_tickets'] = $etn_avaiilable_tickets;
				$etn_total_created_tickets       += $etn_avaiilable_tickets;

				$etn_min_ticket = ! empty( $value['etn_min_ticket'] ) ? intval( $value['etn_min_ticket'] ) : 0;
				$etn_max_ticket = ! empty( $value['etn_max_ticket'] ) ? intval( $value['etn_max_ticket'] ) : $etn_avaiilable_tickets;

				if ( $etn_min_ticket > $etn_avaiilable_tickets ) {
					$etn_min_ticket = 1;
				}

				if ( $etn_max_ticket > $etn_avaiilable_tickets ) {
					$etn_max_ticket = $etn_avaiilable_tickets;
				}

				if ( $etn_min_ticket > $etn_max_ticket ) {
					$swap           = $etn_min_ticket;
					$etn_min_ticket = $etn_max_ticket;
					$etn_max_ticket = $swap;
				}

				$value['etn_min_ticket'] = $etn_min_ticket;
				$value['etn_max_ticket'] = $etn_max_ticket;

			} else {
				if ( count( $ticket_variations ) > 1 ) {
					unset( $ticket_variations[ $key ] );
				}
			}
		}

		return [
			'ticket_variations'         => $ticket_variations,
			'etn_total_created_tickets' => $etn_total_created_tickets,
		];
	}

	/**
	 * Check conditions for ticket selling
	 */
	public static function check_sells_engine() {
		$settings                = etn_get_option();
		$sell_tickets            = ( ! empty( $settings['sell_tickets'] ) ? 'checked' : '' );
		$etn_sells_engine_stripe = ( ! empty( $settings['etn_sells_engine_stripe'] ) ? 'checked' : '' );

		if ( 'checked' == $etn_sells_engine_stripe && '' == $sell_tickets ) {
			$sells_engine = 'stripe';
		} elseif ( $sell_tickets == 'checked' ) {
			$sells_engine = 'woocommerce';
		} else {
			$sells_engine = '';
		}

		return $sells_engine;
	}

	/**
	 * Change default date format
	 */
	public static function etn_date( $get_date ) {
		$etn_date = '';
		
		if ( '' !== $get_date ) {
			$date_format  = Helper::get_option( "date_format" );
			
			$date_options = Helper::get_date_formats();
			date_default_timezone_set( 'UTC' );
			$new_date = str_replace( '/', '-', $get_date );
			$etn_date = ! empty( $date_format ) && ! empty( $date_options[ $date_format ] ) ? date_i18n( $date_options[ $date_format ], strtotime( $new_date ) ) : date_i18n( get_option( "date_format" ), strtotime( $new_date ) );
		}

		return $etn_date;
	}

	/**
	 * Change default date format with time
	 */
	public static function etn_date_with_time( $get_date ) {
		$etn_date = '';
		if ( '' !== $get_date ) {
			$date_format  	= Helper::get_option( "date_format" );
			$date_options 	= Helper::get_date_formats();
			date_default_timezone_set( 'UTC' );
			$new_date 	= str_replace( '/', '-', $get_date );
			$etn_date 	= ! empty( $date_format ) ? date_i18n( $date_options[ $date_format ] . ' '. get_option( 'time_format' ), strtotime( $new_date ) ) : date_i18n( get_option( "date_format" ) .' '. get_option( 'time_format' ), strtotime( $new_date ) );
		}

		return $etn_date;
	}

	// display event date
	public static function etn_display_date( $post_id = null, $show_start_date = 'yes', $show_end_date = 'no' ) {
		$start_date     = get_post_meta( $post_id, 'etn_start_date', true );
		$end_date       = get_post_meta( $post_id, 'etn_end_date', true );
		$etn_start_date = Helper::etn_date( $start_date );
		$etn_end_date   = Helper::etn_date( $end_date );

		if ( ! empty( $etn_start_date ) && $show_start_date == 'yes' ) {
			?>
            <span>
				<i class="etn-icon etn-calendar"></i>
                <?php echo esc_html( $etn_start_date ) ?>
            </span>
			<?php
		}
		if ( ( ! empty( $etn_end_date ) && $etn_start_date != $etn_end_date ) && $show_end_date == 'yes' ) {
			?>
            <span>
                <span><?php echo esc_html__( 'To', 'eventin' ) ?></span>
                <?php echo esc_html( $etn_end_date ) ?>
            </span>
			<?php
		}
	}

	/**
	 * Get Attendees AN Event
	 *
	 * @param [type] $event_id
	 * @param integer $posts_per_page
	 * @param integer $paged
	 *
	 * @return void
	 */
	public static function get_attendees_by_event( $event_id, $posts_per_page = - 1, $paged = 1 ) {

		if ( empty( $event_id ) || ! is_numeric( $event_id ) ) {
			return [];
		}

		global $wpdb;
		$query = "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='etn_event_id' AND meta_value=%d";
		if ( $posts_per_page !== - 1 ) {
			$start = ( $paged - 1 ) * $posts_per_page;
			$total = $posts_per_page;
			$query .= " LIMIT {$start},{$total}";
		}
		$event_attendees = $wpdb->get_results( $wpdb->prepare( $query, $event_id ) );

		foreach ( $event_attendees as $key => $attendee ) {
			if ( ( 'etn-attendee' !== get_post_type( $attendee->post_id ) )
			     || ( 'etn-attendee' == get_post_type( $attendee->post_id ) && get_post_status( $attendee->post_id ) !== "publish" ) ) {
				unset( $event_attendees[ $key ] );
			}

		}


		return $event_attendees;
	}

	/**
	 * Get Total Attendee Row Count
	 *
	 * @param [type] $event_id
	 *
	 * @return void
	 * @since 3.3.5
	 */
	public static function get_attendee_count( $event_id = null ) {
		if ( is_null( $event_id ) || ! is_numeric( $event_id ) ) {
			return wp_count_posts( 'etn-attendee' )->publish;
		} else {
			$attendees = self::get_attendees_by_event( $event_id );

			return count( $attendees );
		}
	}

	/**
	 * recurring tag add when available child event
	 */
	public static function event_recurring_status( $value = null ) {
		if ( ! empty( $value ) ) {
			if ( ( ! empty( $value->etn_recurring ) && true == $value->etn_recurring ) ) {
				?>
                <span class="more-event-tag">
                    <svg width="13" height="13" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 6C11 8.76 8.76 11 6 11C3.24 11 1.555 8.22 1.555 8.22M1.555 8.22H3.815M1.555 8.22V10.72M1 6C1 3.24 3.22 1 6 1C9.335 1 11 3.78 11 3.78M11 3.78V1.28M11 3.78H8.78"
                              stroke="white" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo esc_html_e( 'More Events', 'eventin' ) ?>
                </span>
				<?php
			}
		}
	}

	/**
	 * Get Orders By Event Id
	 *
	 * @param [type] $product_id
	 *
	 * @return void
	 */
	public static function get_orders_ids_by_event_id( $event_id ) {
		global $wpdb;

		// Define HERE the orders status to include in  <==  <==  <==  <==  <==  <==  <==
		$orders_statuses = "'wc-completed', 'wc-processing', 'wc-on-hold'";

		# Get All defined statuses Orders IDs for a defined product ID (or variation ID)
		return $wpdb->get_col(
			"SELECT DISTINCT woi.order_id
                FROM {$wpdb->prefix}woocommerce_order_itemmeta as woim, 
                    {$wpdb->prefix}woocommerce_order_items as woi, 
                    {$wpdb->prefix}posts as p
                WHERE woi.order_item_id = woim.order_item_id
                AND woi.order_id = p.ID
                AND p.post_status IN ( $orders_statuses )
                AND woim.meta_key IN ( '_product_id', '_variation_id', 'event_id' )
                AND woim.meta_value LIKE '$event_id'
                ORDER BY woi.order_item_id DESC"
		);
	}

	/**
	 * Show 404 Page From Theme
	 *
	 * @return void
	 */
	public static function show_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		include_once  ETN_PLUGIN_TEMPLATE_DIR . 'etn-404.php';
		// get_template_part( 404 );
		exit();
	}

	/**
	 * Convert reservation form email template tags
	 *
	 * @param [string] $content
	 * @param [string] $invoice
	 *
	 * @return string
	 */
	static function filter_template_tags( $content, $event_name ) {

		// Get custom logo
		$site_log_id = get_theme_mod( 'custom_logo' );
		$site_logo   = wp_get_attachment_image( $site_log_id, 'medium' );

		//List of template tags
		$etn_tag_arr = [
			'{site_name}',
			'{site_link}',
			'{site_logo}',
			'{event_title}',
		];

		// Replace template tags with data 
		$etn_value_arr = [
			get_bloginfo( 'name' ),
			get_option( 'home' ),
			$site_logo,
			$event_name
		];

		return str_replace( $etn_tag_arr, $etn_value_arr, $content );
	}

	
	/**
	 * Get page list for certificate
	 */
	public static function get_pages( $id = null ) {
		$return_pages = [ 'None' ];
		try {

			if ( is_null( $id ) ) {
				$args  = [
					'post_type'        => 'page',
					'post_status'      => 'publish',
					'posts_per_page'   => - 1,
					'suppress_filters' => false,
				];
				$pages = get_posts( $args );

				foreach ( $pages as $value ) {
					$return_pages[ $value->ID ] = $value->post_title;
				}

				return $return_pages;
			} else {
				// return single speaker
				return get_post( $id );
			}

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * Show notification for install Eventin PRO
	 * 
	 * @since 3.4.0
	 * return void
	 */
	public static function get_pro(){
		?>
		<div class="etn-pro">
			<a href="https://themewinter.com/eventin/#ts-pricing-list" target="_blank">
				<?php echo esc_html__('Upgrade to  Pro','eventin'); ?>
				<svg width="8" height="9" viewBox="0 0 8 9" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M1 7.5L7 1.5M7 1.5H1M7 1.5V7.5" stroke="#0A1018" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
		</div>
		<?php
	}

	/**
	 * Show notification for install Eventin ai
	 * 
	 * @since 3.4.0
	 * return void
	 */
	public static function get_eventin_ai(){
		?>
		<div class="etn-pro">
			<a href="#" target="_blank">
				<?php echo esc_html__('Install Eventin AI','eventin'); ?>
				<svg width="8" height="9" viewBox="0 0 8 9" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M1 7.5L7 1.5M7 1.5H1M7 1.5V7.5" stroke="#0A1018" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
		</div>
		<?php
	}



	/**
	 * Include the templates
	 * 
	 * @since 4.0.7
	 * return void
	 */
	public static function get_event_location_value( $value ) {
		global $wpdb;
		$sql = $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value LIKE %s", 'etn_event_location', '%address\";s:%');
		$post_ids = $wpdb->get_col($sql);
	
		foreach ($post_ids as $post_id) {
			$meta_value = get_post_meta($post_id, 'etn_event_location', true);
			$meta_value_array = maybe_unserialize($meta_value);
			if (isset($meta_value_array['address']) && $meta_value_array['address'] == esc_html( $value ) ) {
				return $meta_value_array['address'];
			}
		}
	
		return '';
	}
	/**
	 * Include the templates for single event
	 * 
	 * @since 4.0.7
	 * return void
	 */
	public static function etn_template_include() {

		include_once \Wpeventin::plugin_dir() . 'core/event/template-hooks.php';
		include_once \Wpeventin::plugin_dir() . 'core/event/template-functions.php';

		if ( class_exists( 'Wpeventin_Pro' ) ) { 
			include_once \Wpeventin_Pro::core_dir().'event/template-functions.php';
			include_once \Wpeventin_Pro::core_dir().'event/template-hooks.php';
		}
	}
		
}
