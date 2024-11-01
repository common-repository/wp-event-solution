<?php
defined( 'ABSPATH' ) || exit;

$author_id = get_queried_object_id();
$etn_speaker_socials = get_user_meta( $author_id, 'etn_speaker_social', true);

?>

<div class="etn-social">
    <?php if (!empty($etn_speaker_socials) && is_array($etn_speaker_socials)) : ?>
        <?php foreach ($etn_speaker_socials as $social) : ?>
            <?php if (!empty($social)) : 
                $etn_social_class = 'etn-' . str_replace('etn-icon fa-', '', $social['icon']); 
            ?>
                <a 
                    href="<?php echo esc_url($social['etn_social_url']); ?>" 
                    target="_blank" 
                    class="<?php echo esc_attr($etn_social_class); ?>"
                    aria-label="<?php echo esc_attr($social['etn_social_title']); ?>"
                >
                    <i class="etn-icon <?php echo esc_attr($social['icon']); ?>" rel="noopener"></i> 
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
