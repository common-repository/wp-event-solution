<?php
if (!defined('ABSPATH')) exit;

use \Etn\Utils\Helper as Helper;

$data           = Helper::post_data_query('etn', $event_count, $order, $event_cat, 'etn_category',
null, null, $event_tag, $orderby_meta, $orderby, $filter_with_status, $post_parent);

?>
<div class='etn-row etn-event-wrapper'>
    <?php
	if ( !empty( $data ) ) {
		foreach ( $data as $value ) {
			$social             = get_post_meta($value->ID, 'etn_event_socials', true);
			$etn_event_location = get_post_meta($value->ID, 'etn_event_location', true);
			$category           = Helper::cate_with_link($value->ID, 'etn_category');
			$existing_location  = Helper::cate_with_link($value->ID, 'etn_location');

            $etn_event_location_type = get_post_meta($value->ID, 'etn_event_location_type', true);
            $banner_image_url       = get_post_meta( $value->ID, 'event_banner', true );

		?>
    <div class="etn-col-md-6 etn-col-lg-<?php echo esc_attr($etn_event_col); ?>">
        <div class="etn-event-item">
            <!-- thumbnail -->
            <div class="etn-event-thumb">
                <?php if ( $banner_image_url ): ?>
                    <a 
                        href="<?php echo esc_url(get_the_permalink($value->ID)); ?>" 
                        aria-label="<?php echo get_the_title(); ?>"
                    >
                        <img src="<?php echo esc_url($banner_image_url); ?>" alt="Image">
                    </a>
                <?php elseif ( get_the_post_thumbnail_url($value->ID) ): ?>
                    <a 
                        href="<?php echo esc_url(get_the_permalink($value->ID)); ?>" 
                        aria-label="<?php echo get_the_title(); ?>"
                    >
                        <?php echo get_the_post_thumbnail($value->ID, 'large');  ?>
                    </a>
                <?php endif; ?>
                
                <div class="etn-event-category">
                    <?php echo  Helper::kses($category); ?>
                </div>
                <?php Helper::event_recurring_status($value); ?>
            </div>
            <!-- thumbnail start-->

            <!-- content start-->
            <div class="etn-event-content">
                <?php if($show_event_location == 'yes'): 
                    $location = \Etn\Core\Event\Helper::instance()->display_event_location($value->ID);
                    ?>
                    <?php if (!empty($location)) { ?>
                        <div class="etn-event-location">
                            <i class="etn-icon etn-location"></i>
                            <?php echo esc_html($location); ?>
                        </div>
                    <?php } ?>
               
                <?php endif; ?>
                
                <h3 class="etn-title etn-event-title"><a href="<?php echo esc_url(get_the_permalink($value->ID)); ?>">
                    <?php echo esc_html(get_the_title($value->ID)); ?></a>
                </h3>
                <?php
                    if($etn_desc_show =='yes'):
                ?>
                    <p>
                        <?php echo esc_html(Helper::trim_words(get_the_excerpt($value->ID), $etn_desc_limit)); ?>
                    </p>
                <?php endif; ?>
                
                <div class="etn-event-footer">
                    <div class="etn-event-date">
                        <?php
                            $show_end_date = !empty($show_end_date) ? $show_end_date : 'no';
                            echo esc_html(Helper::etn_display_date($value->ID, 'yes', $show_end_date)); 
                        ?>
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
                        <a href="<?php echo esc_url(get_the_permalink($value->ID)); ?>" class="etn-btn etn-btn-border"
                            title="<?php echo get_the_title($value->ID); ?>"><?php echo esc_html__('Attend', 'eventin') ?>
                            <i class="etn-icon etm-arrow-right"></i></a>
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