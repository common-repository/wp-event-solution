<?php
defined( 'ABSPATH' ) || exit;

$author_id                = get_queried_object_id();
$author_name              = get_the_author_meta( 'display_name', $author_id );
$etn_speaker_company_logo = get_user_meta( $author_id, 'etn_speaker_company_logo', true);

if ( !empty($etn_speaker_company_logo) && isset($etn_speaker_company_logo)) : ?>
    <div class="etn-speaker-logo">
        <img src="<?php echo esc_url($etn_speaker_company_logo); ?>" alt="<?php echo esc_attr($author_name); ?>">
    </div>
<?php endif; 