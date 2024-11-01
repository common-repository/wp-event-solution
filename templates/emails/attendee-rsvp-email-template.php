<table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto;">
    <tr>
        <td valign="top" class="bg_white" style="padding: 1em 2.5em 0 2.5em;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td class="logo" style="text-align: left;">
                    <h1><a href="#"><?php echo esc_html_e( 'RSVP Confirmation', 'eventin' ); ?></a></h1>
                </td>
            </tr>
        </table>
        </td>
        </tr><!-- end tr -->
        <tr>
        <td valign="middle" class="hero bg_white" style="padding: 2em 0 2em 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td style="padding: 0 2.5em; text-align: left;">
                    <div class="text">
                        <p><?php echo wp_kses_post( $content ); ?></p>
                    </div>
                </td>
            </tr>
        </table>
        </td>
    </tr><!-- end tr -->
    <!-- 1 Column Text + Button : END -->
</table>
