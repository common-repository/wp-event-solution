<?php

defined('ABSPATH') || die();

if (has_post_thumbnail()) {
    ?>
    <!-- thumbnail -->
    <div class="etn-event-thumb">
    
        <?php do_action( 'etn_before_event_archive_thumbnail' ); ?>

        <a href="<?php the_permalink(); ?>" aria-label="<?php the_title(); ?>">
            <?php the_post_thumbnail(); ?>
        </a>

        <?php do_action( 'etn_after_event_archive_thumbnail' ); ?>

    </div>
    <?php
}