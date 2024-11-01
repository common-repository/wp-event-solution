<?php

namespace Etn\Templates\Event\Parts;

use Etn\Utils\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Event details class.
 *
 * @since 3.3.9
 */
class EventDetailsParts {

	/**
	 * Process event single tag lists
	 *
	 * @param [type] $event_id
	 *
	 * @return void
	 */
	public static function event_single_tag_list( $single_event_id ) {
		?>
        <div class="etn-event-tag-list">
			<?php
			global $post;
			$etn_terms = wp_get_post_terms( $single_event_id, 'etn_tags' );
			if ( $etn_terms ) {
				?>
                <h4 class="etn-tags-title">
					<?php
					$tag_title = apply_filters( 'etn_event_tag_list_title', esc_html__( 'Tags', "eventin" ) );
					echo esc_html( $tag_title );
					?>
                </h4>
				<?php
				$output = array();

				if ( is_array( $etn_terms ) ) {
					foreach ( $etn_terms as $term ) {
						$term_link = get_term_link( $term->slug, 'etn_tags' );
						$output[]  = '<a href="' . $term_link . '">' . $term->name . '</a>';
					}
				}
				echo Helper::kses( join( ' ', $output ) );
			}
			?>
        </div>
		<?php
	}

	/**
	 * Process event single tag lists
	 *
	 * @param [type] $event_id
	 *
	 * @return void
	 */
	public static function event_single_organizers( $etn_organizer_events ) {
		$args = [
			'include' => $etn_organizer_events
		];
        
		$data = get_users( $args );
        
		if ( $data && $etn_organizer_events ) :
			?>
            <div class="etn-widget etn-event-organizers">
                <h4 class="etn-widget-title etn-title">
					<?php
					$event_organizers_title = apply_filters( 'etn_event_organizers_title', esc_html__( "Organizers", "eventin" ) );
					echo esc_html( $event_organizers_title );
					?>
                </h4>
				<?php
				foreach ( $data as $value ) {
					$social                   = get_user_meta( $value->ID, 'etn_speaker_socials', true );
					$email                    = get_user_meta( $value->ID, 'etn_speaker_website_email', true );
					$etn_speaker_company_logo = get_user_meta( $value->ID, 'image_id', true );
					$etn_company_logo         = get_user_meta( $value->ID, 'etn_speaker_company_logo', true );
					$logo                     = wp_get_attachment_image_src( $etn_speaker_company_logo, 'full' );
					?>
                    <div class="etn-organaizer-item">
						<?php if ( isset( $logo[0] ) ) { ?>
                            <div class="etn-organizer-logo">
								<?php echo wp_get_attachment_image( $etn_speaker_company_logo, 'full' ); ?>
                            </div>
						<?php } else {
							?>
							<div class="etn-organizer-logo">
								<img src="<?php echo esc_url( $etn_company_logo); ?>">
							</div>
						<?php
						} 
						?>
                        <h4 class="etn-organizer-name">
							<?php echo esc_html( get_user_meta( $value->ID, 'first_name', true ) ); ?>
                        </h4>

						<?php if ( $email ) { ?>
                            <div class="etn-organizer-email">
                                <span class="etn-label-name"><?php echo esc_html__( 'Email :', "eventin" ); ?></span>
                                <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
                            </div>
						<?php } ?>
						<?php if ( is_array( $social ) && ! empty( $social ) ) { ?>
                            <div class="etn-social">
                                <span class="etn-label-name"><?php echo esc_html__( 'Social :', "eventin" ); ?></span>
								<?php foreach ( $social as $social_value ) { ?>
									<?php $etn_social_class = 'etn-' . str_replace( 'fab fa-', '', $social_value['icon'] ); ?>

                                    <a href="<?php echo esc_url( $social_value["etn_social_url"] ); ?>"
                                       target="_blank" class="<?php echo esc_attr( $etn_social_class ); ?>"
                                       title="<?php echo esc_attr( $social_value["etn_social_title"] ); ?>">
                                        <i class="etn-icon <?php echo esc_attr( $social_value["icon"] ); ?>"
                                           rel="noopener"></i>
                                    </a>
								<?php } ?>
                            </div>
						<?php } ?>
                    </div>
				<?php }
				?>
            </div>
		<?php endif;
	}

	/**
	 * Process event single category list
	 *
	 * @param [type] $single_event_id
	 *
	 * @return void
	 */
	public static function event_single_category( $single_event_id ) {
		global $post;
		$data              = Helper::single_template_options( $single_event_id );
		$etn_event_socials = isset( $data['etn_event_socials'] ) ? $data['etn_event_socials'] : [];
		$etn_cat_terms     = wp_get_post_terms( $single_event_id, 'etn_category' );
		$is_hide_social    = etn_get_option( 'hide_social_from_details' );
		?>

		<?php if( !empty( $etn_event_socials ) || !empty( $etn_cat_terms ) ) : ?>
			<div class="etn-event-meta">
				<?php if( !empty( $etn_cat_terms ) ) : ?>
					<div class="etn-event-category">
						<?php
							$output = array();
							if ( is_array( $etn_cat_terms ) ) {
								foreach ( $etn_cat_terms as $term ) {
									$term_link = get_term_link( $term->slug, 'etn_category' );
									$output[]  = '<a  href="' . $term_link . '">' . $term->name . '</a>';
								}
							}
							echo "<span>" . Helper::kses( join( ' ', $output ) ) . "</span>";
						?>
					</div>
				<?php endif ;?>
				<?php if( !empty( $etn_event_socials ) && 'on' === $is_hide_social ) : ?>
					<div class="etn-event-social-wrap">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18">
							<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
						</svg>
						<div class="etn-social">
							<?php if ( is_array( $etn_event_socials ) ) : ?>
								<?php foreach ( $etn_event_socials as $social ) : ?>
									<?php $etn_social_class = 'etn-' . str_replace( 'etn-icon fa-', '', $social['icon'] ); ?>
									<a
											href="<?php echo esc_url( $social['etn_social_url'] ); ?>"
											target="_blank"
											rel="noopener"
											aria-label="<?php echo esc_attr( $social["etn_social_title"] ); ?>"
									>
										<i class="etn-icon <?php echo esc_attr( $social['icon'] ); ?>"></i>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endif ;?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Process event single category list
	 *
	 * @param [type] $single_event_id
	 *
	 * @return void
	 */
	public static function event_single_sidebar_meta( $single_event_id ) {
		$event_options = get_option( "etn_event_options" );
		$hide_location = ! empty( $event_options["etn_hide_location_from_details"] ) ? $event_options["etn_hide_location_from_details"] : '';
		$data          = Helper::single_template_options( $single_event_id );
		if ( ! empty( $data['event_start_date'] ) || ! empty( $data['event_start_time'] ) || ! empty( $data['etn_event_location'] )) :
			?>
            <div class="etn-event-meta-info etn-widget">
                <ul>
					<?php
					// event date
					if ( ! isset( $event_options["etn_hide_date_from_details"] ) && ! empty( $data['event_start_date'] ) ) {
						$separate = ! empty( $data['event_end_date'] ) ? ' - ' : '';
						?>
                        <li>
							<?php if ( $data['event_start_date'] !== $data['event_end_date']): ?>
                            <span> <?php echo esc_html__( 'Date : ', "eventin" ); ?></span>
							<?php echo esc_html( $data['event_start_date'] . $separate . $data['event_end_date'] ); ?>

							<?php else: ?>
								<span> <?php echo esc_html__( 'Date : ', "eventin" ); ?></span>
							<?php echo esc_html( $data['event_start_date'] ); ?>
							<?php endif; ?>
                        </li>
						<?php
					}
					?>
					<?php
					// event time
					if ( ! isset( $event_options["etn_hide_time_from_details"] ) && ( ! empty( $data['event_start_time'] ) || ! empty( $data['event_end_time'] ) ) ) {
						$separate = ! empty( $data['event_end_time'] ) ? ' - ' : '';
						?>
                        <li>
                            <span><?php echo esc_html__( 'Time : ', "eventin" ); ?></span>
							<?php echo esc_html( $data['event_start_time'] . $separate . $data['event_end_time'] ); ?>
                            <span class="etn-event-timezone">
                            <?php
                            if ( ! empty( $data['event_timezone'] ) && ! isset( $event_options["etn_hide_timezone_from_details"] ) ) {
	                            ?>
                                (<?php echo esc_html( $data['event_timezone'] ); ?>)
	                            <?php
                            }
                            ?>
                        </span>
                        </li>
						<?php
					}
					?>
					
					<?php
					$location = \Etn\Core\Event\Helper::instance()->display_event_location( $single_event_id );
					$location = etn_prepare_address( $location );
					$event_location_type = $data['etn_event_location_type'];
					if ( ! empty( $location) ) {
						?>
                        <li>
                            <span><?php echo esc_html__( 'Venue : ', "eventin" ) ?></span>
							<?php  
								echo esc_html( $location ); 
							?>
                        </li>
						<?php
					}

					?>
					<?php if ( $event_location_type === 'new_location' && class_exists( 'Wpeventin_Pro' ) && empty( $hide_location ) ): ?>
						<?php
							$event_terms = ! empty( get_the_terms( $single_event_id, 'etn_location' ) ) ? get_the_terms( $single_event_id, 'etn_location' ) : [];
							if( ! empty( $event_terms ) ) :
						?>
							<li>
								<span><?php echo esc_html__( 'Venue : ', "eventin" ) ?></span>
								<?php foreach ( $event_terms as $term ) : ?>
									<span class="etn-location-name"><?php echo esc_html( $term->name ); ?></span>
								<?php endforeach; ?>
							</li>
						<?php endif; ?>
					<?php endif; ?>
                </ul>
				<?php
				?>
            </div>
		<?php endif; ?>
		<?php
	}

}