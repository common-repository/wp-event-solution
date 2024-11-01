<!--Email Content Area -->
<h1
    style="
    font-size: 24px;
    margin: 30px 0;
    font-weight: 600;
    color: #020617;
    "
>
    <?php echo esc_html_e( 'It\'s Only a Day Away', 'eventin' ); ?>
</h1>

<p
    style="
    font-size: 14px;
    line-height: 22px;
    color: #556880;
    margin-bottom: 24px;
    "
>
    <?php echo wp_kses_post( $content ); ?>
</p>
<!-- Separator -->
<div
    style="margin: 30px 0; background-color: #c9c9c9; height: 1px"
></div>

<!-- Event Details Area -->
<div style="margin-top: 20px">
    <h3
    style="font-size: 18px; font-weight: 600; margin-bottom: 24px"
    >
    <?php echo esc_html( $event->get_title() ); ?>
    </h3>

    <div style="display: flex; gap: 30px">
    <div style="flex: 1 1 50%; display: flex; gap: 8px">
        <svg
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        >
        <path
            d="M7 2C7 1.44772 6.55228 1 6 1C5.44772 1 5 1.44772 5 2V2.44885C5.38032 2.32821 5.78554 2.24208 6.21533 2.17961C6.46328 2.14357 6.72472 2.11476 7 2.09173V2Z"
            fill="#141B34"
        />
        <path
            d="M19 2.44885C18.6197 2.32821 18.2145 2.24208 17.7847 2.17961C17.5367 2.14357 17.2753 2.11476 17 2.09173V2C17 1.44772 17.4477 1 18 1C18.5523 1 19 1.44772 19 2V2.44885Z"
            fill="#141B34"
        />
        <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M13.0288 2H10.9712C9.02294 1.99997 7.45141 1.99994 6.21533 2.17961C4.92535 2.3671 3.8568 2.76781 3.01802 3.6746C2.18949 4.57031 1.83279 5.69272 1.66416 7.04866C1.49997 8.36894 1.49998 10.0541 1.5 12.1739V12.8261C1.49998 14.9459 1.49997 16.6311 1.66416 17.9513C1.83279 19.3073 2.18949 20.4297 3.01802 21.3254C3.8568 22.2322 4.92535 22.6329 6.21533 22.8204C7.45142 23.0001 9.02293 23 10.9712 23H13.0288C14.9771 23 16.5486 23.0001 17.7847 22.8204C19.0747 22.6329 20.1432 22.2322 20.982 21.3254C21.8105 20.4297 22.1672 19.3073 22.3358 17.9513C22.5 16.6311 22.5 14.9459 22.5 12.8261V12.1739C22.5 10.0541 22.5 8.36895 22.3358 7.04866C22.1672 5.69272 21.8105 4.57031 20.982 3.6746C20.1432 2.76781 19.0747 2.3671 17.7847 2.17961C16.5486 1.99994 14.9771 1.99997 13.0288 2ZM4.49783 9C4.03921 9 3.8099 9 3.66385 9.14417C3.51781 9.28833 3.51487 9.51472 3.509 9.96751C3.50027 10.6407 3.5 11.3942 3.5 12.2432V12.7568C3.5 14.9616 3.50182 16.5221 3.64887 17.7045C3.79327 18.8656 4.06263 19.5094 4.48622 19.9673C4.89956 20.4142 5.4647 20.6903 6.503 20.8412C7.57858 20.9975 9.00425 21 11.05 21H12.95C14.9957 21 16.4214 20.9975 17.497 20.8412C18.5353 20.6903 19.1004 20.4142 19.5138 19.9673C19.9374 19.5094 20.2067 18.8656 20.3511 17.7045C20.4982 16.5221 20.5 14.9616 20.5 12.7568V12.2432C20.5 11.3942 20.4997 10.6407 20.491 9.96751C20.4851 9.51472 20.4822 9.28833 20.3362 9.14417C20.1901 9 19.9608 9 19.5022 9H4.49783Z"
            fill="#141B34"
        />
        </svg>
        <p
        style="
            font-size: 16px;
            color: #334155;
            line-height: 24px;
            margin-top: 0;
            margin-bottom: 10px;
            flex: 1 1 50%;
        "
        >
        
        <?php
        
            if ( $event->etn_start_date == $event->etn_end_date ) {
                printf( '%s from %s - %s %s', $event->get_start_datetime('l, F d, Y'), $event->get_start_datetime('h:i A'), $event->get_start_datetime('h:i A'), $event->get_timezone() );
            } else {
                printf( '%s at %s - %s at %s %s', $event->get_start_datetime('l, F d, Y'), $event->get_start_datetime('h:i A'), $event->get_end_datetime('l, F d, Y'), $event->get_end_datetime('h:i A'), $event->get_timezone() );
            }
        ?>
        </p>
    </div>
    <div style="flex: 1 1 50%; display: flex; gap: 8px">
        <svg
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        >
        <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M12 1.25C7.16043 1.25 3.25 5.21073 3.25 10.0807C3.25 12.8754 4.36442 15.0556 6.50258 16.9503C7.89367 18.183 10.3925 20.804 11.3596 22.3903C11.4936 22.61 11.7309 22.7457 11.9882 22.7498C12.2456 22.7538 12.487 22.6256 12.6278 22.4101C13.6574 20.8346 16.1198 18.1711 17.4974 16.9503C19.6356 15.0556 20.75 12.8754 20.75 10.0807C20.75 5.21073 16.8396 1.25 12 1.25ZM12.0352 13.5C13.9682 13.5 15.5352 11.933 15.5352 10C15.5352 8.067 13.9682 6.5 12.0352 6.5C10.1022 6.5 8.53516 8.067 8.53516 10C8.53516 11.933 10.1022 13.5 12.0352 13.5Z"
            fill="#141B34"
        />
        </svg>
        <p
        style="
            font-size: 16px;
            color: #334155;
            line-height: 24px;
            margin-top: 0;
            margin-bottom: 10px;
            flex: 1 1 50%;
        "
        >
        <?php 
            if ( $event->event_type == 'offline' ) {
                echo esc_html( $event->get_address() );
            } else {
                echo printf( 'Online meeting link: %s', $event->meeting_link );
            }
        ?>
        </p>
    </div>
    </div>
</div>
