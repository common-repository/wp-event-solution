<?php
defined( 'ABSPATH' ) || exit;

use \Etn\Utils\Helper;
$author_id = get_queried_object_id();
$etn_speaker_designation = get_user_meta( $author_id , 'etn_speaker_designation', true);
?>
    <p class="etn-speaker-designation"><?php echo Helper::kses( $etn_speaker_designation ); ?></p>	