<?php
defined( 'ABSPATH' ) || exit;

$author_id = get_queried_object_id();
$etn_speaker_summary = get_user_meta( $author_id, 'etn_speaker_summery', true);
?>
<div class="etn-speaker-summery"> 
    <?php echo wpautop($etn_speaker_summary) ; ?>
</div>
