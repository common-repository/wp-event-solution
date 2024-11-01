<?php
use \Etn\Utils\Helper as Helper;
$data = Helper::user_data_query( $etn_speaker_count, $etn_speaker_order, $speakers_category, $orderby );

if ( !empty( $data ) ) { 
    ?>
    <div class='etn-row etn-speaker-wrapper'>
        <?php
        foreach( $data as $value ) {
            $etn_speaker_designation = get_user_meta( $value->data->ID , 'etn_speaker_designation', true);
            $etn_speaker_image = get_user_meta( $value->data->ID, 'image', true);
            $social = get_user_meta( $value->data->ID, 'etn_speaker_social', true);
            $author_id = get_the_author_meta($value->data->ID);
            ?>
            <div class="etn-col-lg-<?php echo esc_attr($etn_speaker_col); ?> etn-col-md-6">
                <div class="etn-speaker-item style-3">
                    <div class="etn-speaker-thumb">
                        <a href="<?php echo esc_url( get_the_permalink( $value->data->ID ) ); ?>" class="etn-img-link" aria-label="<?php echo esc_html($value->data->display_name); ?>">
                            <img src="<?php echo esc_url($etn_speaker_image); ?>" alt="">
                        </a>
                    </div>
                    <div class="etn-speaker-content">
                        <h3 class="etn-title etn-speaker-title">
                            <a href="<?php echo Helper::get_author_page_url_by_id($value->data->ID); ?>"> <?php echo esc_html($value->data->display_name); ?></a>
                        </h3>
                        <p>
                            <?php echo Helper::kses($etn_speaker_designation); ?>
                        </p>
                        <div class="etn-speakers-social">
                            <?php
                            if (is_array($social)  & !empty( $social )) {
                                ?>
                                <?php
                              if(!empty($social_value)){
                                    ?>
                                    <a href="<?php echo esc_url($social_value["etn_social_url"]); ?>" title="<?php echo esc_attr($social_value["etn_social_title"]); ?>">
                                        <i class="etn-icon <?php echo esc_attr($social_value["icon"]); ?>"></i>
                                    </a>
                                    <?php  
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php 
} else { 
    ?>
    <p class="etn-not-found-post"><?php echo esc_html__('No Post Found', 'eventin'); ?></p>
    <?php
}