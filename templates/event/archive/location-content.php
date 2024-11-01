<?php
use \Etn\Utils\Helper as Helper;

defined( 'ABSPATH' ) || die();
$etn_event_location = get_post_meta( get_the_ID(), 'etn_event_location', true );
$existing_location  = Helper::cate_with_link(get_the_ID(), 'etn_location');
$etn_event_location_type = get_post_meta(get_the_ID(), 'etn_event_location_type', true);
$location = \Etn\Core\Event\Helper::instance()->display_event_location(get_the_ID());
?>

<?php  if (!empty($location)) { ?>
    <div class="etn-event-location">
        <i class="etn-icon etn-location"></i>
        <?php
            echo esc_html($location); 
        ?>
    </div>
<?php } ?>
