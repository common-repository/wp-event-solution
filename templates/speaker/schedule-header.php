<?php
defined( 'ABSPATH' ) || exit;
$author_id         = get_queried_object_id();
$author_name       = get_the_author_meta( 'display_name', $author_id );
?>
<h3 class="etn-title etn-schedule-wrap-title">
    <?php echo esc_html__('All Sessions by', 'eventin'); ?>
    <?php echo esc_html($author_name); ?>
</h3>