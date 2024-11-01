<?php
if (!defined('ABSPATH')) exit;
use \Etn\Utils\Helper as Helper;

if ( !empty( $show_child_event ) && 'yes' == $show_child_event ) {
    $has_child_events = Helper::get_child_events( $value->ID );
    if ( !empty($has_child_events)) {
        foreach ( (array) $has_child_events as $key => $item ) {
            $recur_category   = Helper::cate_with_link($item->ID, 'etn_category');
            ?>
                <div class="etn-col-md-6 etn-col-lg-<?php echo esc_attr($etn_event_col); ?>">
                    <div class="etn-event-item">
                        <!-- thumbnail -->
                        <?php if ( get_the_post_thumbnail_url( $item->ID ) ) : ?>
                            <div class="etn-event-thumb">
                                <a 
                                    href="<?php echo esc_url(get_the_permalink($item->ID)); ?>" 
                                    aria-label="<?php echo get_the_title(); ?>"
                                >
                                    <?php echo get_the_post_thumbnail( $item->ID, 'large' );  ?>
                                </a>
                                <div class="etn-event-category">
                                        <?php echo  Helper::kses($recur_category); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <!-- Thumbnail end -->
                        <!-- content start-->
                        <div class="etn-event-content">
                            <?php $location = \Etn\Core\Event\Helper::instance()->display_event_location($item->ID); ?>
                            <?php if (!empty($location)) : ?>
                                    <div class="etn-event-location"><i class="etn-icon etn-location"></i> <?php echo esc_html($location); ?></div>
                            <?php endif; ?>
                            <h3 class="etn-title etn-event-title">
                                <a href="<?php echo esc_url(get_the_permalink($item->ID)); ?>"> <?php echo esc_html(get_the_title($item->ID)); ?></a>
                            </h3>
                            <p><?php echo esc_html(Helper::trim_words(get_the_excerpt($item->ID), $etn_desc_limit)); ?></p>
                            <div class="etn-event-footer">
                                <div class="etn-event-date">
                                <?php 
                                    $show_end_date = !empty($show_end_date) ? $show_end_date : 'no';
                                    echo esc_html(Helper::etn_display_date($item->ID, 'yes', $show_end_date));
                                  ?>
                                </div>
                                <div class="etn-atend-btn">
                                    <?php
                                        $show_form_button = apply_filters("etn_form_submit_visibility", true, $item->ID);
                                        if ($show_form_button === false) {
                                            ?>
                                            <a href="#" class="etn-btn etn-btn-border"><?php echo esc_html__('Expired!', "eventin"); ?> </a>
                                            <?php
                                        } else {
                                            ?>
                                            <a href="<?php echo esc_url(get_the_permalink($item->ID)); ?>" class="etn-btn etn-btn-border" title="<?php echo get_the_title($item->ID); ?>"><?php echo esc_html__('Attend', 'eventin') ?> <i class="etn-icon etn-arrow-right"></i></a>
                                            <?php
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <!-- content end-->
                    </div>
                </div>
            <?php
        }
    }
}