<?php
// Ensure this file is not accessed directly
if ( !defined('ABSPATH') ) {
    exit;
}
// Check if the request is an AJAX call
if ( !wp_doing_ajax() ) {
    get_header();
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <section class="error-404 not-found">
            <div class="page-content" style=" text-align:center; margin: 0 auto;">

                <div class="svg-container">
                    
                    <?php
                    $image = plugin_basename( plugin_dir_url( __DIR__ ) ). '/assets/images/frame.svg'; 
                    ?>
                    <img src="<?php echo esc_url( $image )?>" alt="404 error">

                </div>

                <p><a href="<?php echo esc_url( home_url('/etn') ); ?>"><?php esc_html_e( 'Return to archive page', 'eventin' ); ?></a></p>
            </div><!-- .page-content -->
        </section><!-- .error-404 -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php
// Check if the request is an AJAX call
if ( !wp_doing_ajax() ) {
    get_header();
}
