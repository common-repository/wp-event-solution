<?php

    use \Etn\Utils\Helper;

    $speaker_id              = $settings["speaker_id"];
    $etn_speaker_name        = get_the_author_meta( 'display_name', $speaker_id );
    $etn_speaker_designation = get_user_meta( $speaker_id , 'etn_speaker_designation', true);
    $social                  = get_user_meta( $speaker_id, 'etn_speaker_social', true);
    $summery                 = get_user_meta( $speaker_id , 'etn_speaker_summery', true);
    $all_logo                = get_user_meta( $speaker_id , 'etn_speaker_company_logo', true);
    $etn_speaker_image       = get_user_meta( $speaker_id, 'image', true);
    ?>
    
    <div class="etn-speaker-wrapper">
        <div class="etn-speaker-item">
            <div class="etn-speaker-thumb">
                <img src="<?php echo esc_url($etn_speaker_image); ?>" alt="">
                <div class="etn-speakers-social">
                    <?php if (!empty( $social )) { ?>
                        <?php foreach ($social as $social_value) {  ?>
                            <a href="<?php echo esc_url($social_value["etn_social_url"]); ?>" title="<?php echo esc_attr($social_value["etn_social_title"]); ?>"><i class="etn-icon <?php echo esc_attr($social_value["icon"]); ?>"></i></a>
                        <?php  } ?>
                    <?php } ?>
                </div>
            </div>
            <div class="etn-speaker-content">
                <h3 class="etn-title etn-speaker-title"><a href="<?php echo Helper::get_author_page_url_by_id($speaker_id); ?>"> <?php echo esc_html($etn_speaker_name); ?></a> </h3>
                <p>
                    <?php echo Helper::kses($etn_speaker_designation); ?>
                </p>
            </div>
        </div>
    </div>
