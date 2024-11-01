<?php
if (!defined('ABSPATH')) exit;

use \Etn\Utils\Helper as Helper;

$data           = Helper::post_data_query('etn', $event_count, $order, $event_cat,
'etn_category', null, null, $event_tag, $orderby_meta, $orderby, $filter_with_status, $post_parent );

?>
<div class='etn-row etn-event-wrapper etn-event-list2'>
		<?php
		if (!empty($data)) {
				foreach ($data as $value) {

						$social             = get_post_meta($value->ID, 'etn_event_socials', true);
						$etn_event_location = get_post_meta($value->ID, 'etn_event_location', true);
						$etn_start_date     = get_post_meta($value->ID, 'etn_start_date', true);
						$event_start_date   =  Helper::etn_date( $etn_start_date );
						$category           =  Helper::cate_with_link($value->ID, 'etn_category');
						$etn_ticket_price     = get_post_meta($value->ID, 'etn_ticket_price', true);
						$banner_image_url       = get_post_meta( $value->ID, 'event_banner', true );

						?>
						<div class="etn-col-md-6 etn-col-lg-<?php echo esc_attr($etn_event_col); ?>">
								<div class="etn-event-item">
										<!-- thumbnail -->
										<div class="etn-event-thumb">
											<?php if ( get_the_post_thumbnail_url($value->ID) ): ?>
												<a 
													href="<?php echo esc_url(get_the_permalink($value->ID)); ?>" 
													aria-label="<?php echo get_the_title(); ?>"
												>
													<?php echo get_the_post_thumbnail($value->ID, 'large');  ?>
												</a>
											<?php elseif( $banner_image_url ): ?>
												<a 
													href="<?php echo esc_url(get_the_permalink($value->ID)); ?>" 
													aria-label="<?php echo get_the_title(); ?>"
												>
													<img src="<?php echo esc_url($banner_image_url); ?>" alt="Image">
												</a>
											<?php endif; ?>
											
											<div class="etn-event-category">
												<?php echo  Helper::kses($category); ?>
											</div>
										</div>
										<!-- thumbnail start-->

										<!-- content start-->
										<div class="etn-event-content">
												<div class="event-top-meta">
														<?php $location = \Etn\Core\Event\Helper::instance()->display_event_location($value->ID); ?>
														<?php if (!empty($location)) { ?>
																<div class="etn-event-location"><i class="etn-icon etn-location"></i> <?php echo esc_html($location); ?></div>
														<?php } ?>

														<?php if (!empty($etn_ticket_price) && class_exists('woocommerce')){  ?>
																<div class='etn-ticket-price'>
																		<i class="etn-icon etn-money-bill"></i>
																		<?php echo get_woocommerce_currency_symbol(); ?><?php echo esc_html($etn_ticket_price); ?>
																</div>
																<?php } ?>
												</div>

												<h3 class="etn-title etn-event-title"><a href="<?php echo esc_url(get_the_permalink($value->ID)); ?>"> <?php echo esc_html(get_the_title($value->ID)); ?></a> </h3>
												<p><?php echo esc_html(Helper::trim_words($value->post_content, $etn_desc_limit)); ?></p>
												<div class="etn-event-footer">
														<div class="etn-event-date">
																<i class="etn-icon etn-calendar"></i>
																<?php echo esc_html($event_start_date); ?>
														</div>
														<div class="etn-atend-btn">
																<?php
																$show_form_button = apply_filters("etn_form_submit_visibility", true, $value->ID);
																if ($show_form_button === false) {
																		?>
																		<a href="#" class="etn-btn etn-btn-border"><?php echo esc_html__('Expired!', "eventin"); ?> </a>
																		<?php
																} else {
																		?>
																		<a href="<?php echo esc_url(get_the_permalink($value->ID)); ?>" class="etn-btn etn-btn-border" title="<?php echo get_the_title($value->ID); ?>"><?php echo esc_html__('Attend', 'eventin') ?> <i class="etn-icon etn-arrow-right"></i></a>
																		<?php
																}
																?>
														</div>
												</div>
										</div>
										<!-- content end-->
								</div>
								<!-- etn event item end-->
						</div>
						<?php
				}
		}else{
				?>
				<p class="etn-not-found-post"><?php echo esc_html__('No Post Found', 'eventin'); ?></p>
				<?php
		} ?>
</div>