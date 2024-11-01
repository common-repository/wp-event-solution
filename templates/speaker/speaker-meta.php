<?php
defined( 'ABSPATH' ) || exit;

$author_id = get_queried_object_id();
$etn_speaker_website_url = get_user_meta( $author_id, 'etn_speaker_url', true);
$etn_company_name = get_user_meta( $author_id, 'etn_company_name', true);
 
$etn_speaker_website_email = get_the_author_meta(  'user_email', $author_id);
$settings                  = get_option( "etn_event_options" );
$email_show                = isset( $settings['show_speaker_email'] ) ? $settings['show_speaker_email'] : 'off';
 
?>
<ul class="etn-speaker-details-meta">
    <?php 
    if ( !empty($etn_speaker_website_url || !empty($etn_company_name )) ) { 
        ?>
        <li>
            <a href="<?php echo esc_url($etn_speaker_website_url ? $etn_speaker_website_url : '#'); ?>" target="_blank" rel="noopener">
                <i class="etn-icon etn-link"></i>
                <?php echo esc_html($etn_company_name ? $etn_company_name : $etn_speaker_website_url); ?>
            </a>
        </li>
        <?php 
    } 
    ?>

    <?php if (!empty($etn_speaker_website_email) && $email_show === 'on') { ?>
        <li>
            <a href="mailto:<?php echo esc_attr($etn_speaker_website_email); ?>">
                <i class="etn-icon etn-envelope"></i>
                <?php echo esc_html($etn_speaker_website_email); ?>
            </a>
        </li>
    <?php } ?>
</ul>