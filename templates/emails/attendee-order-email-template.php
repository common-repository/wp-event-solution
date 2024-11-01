<!--Email Content Area -->
<h1
    style="
    font-size: 24px;
    margin: 30px 0;
    font-weight: 600;
    color: #020617;
    "
>
    <?php echo esc_html_e( 'Your Event booking is complete.', 'eventin' ); ?>
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
<!-- Ticket Details Area -->
    <div>
        <h2
        style="
            font-size: 16px;
            line-height: 24px;
            color: #020617;
            font-weight: 600;
            margin-top: 0;
        "
        >
        Download Ticket
        </h2>
        <!-- Single Ticket Information -->
        <div>
            <p style="margin: 8px 0px">Ticket name: <?php echo esc_html( $attendee->ticket_name ); ?></p>
            <p style="margin: 8px 0px">Attendee: <?php echo esc_html( $attendee->etn_name ); ?></p>
            <div
                style="
                display: flex;
                align-items: flex-start;
                gap: 8px;
                margin-top: 20px;
                "
            >
                <a
                href="<?php echo esc_url( site_url("etn-attendee?etn_action=download_ticket&attendee_id={$attendee->id}&etn_info_edit_token={$attendee->etn_info_edit_token}") ); ?>"
                style="
                    color: #6c2bd9;
                    font-size: 14px;
                    font-weight: 600;
                    text-decoration: underline;
                    display: flex;
                    gap: 5px;
                "
                >
                <span>
                    <svg
                    width="18"
                    height="18"
                    viewBox="0 0 18 18"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M9.75442 3.375C9.75442 2.96079 9.41857 2.625 9.00442 2.625C8.5902 2.625 8.25442 2.96079 8.25442 3.375V8.25H7.8087C7.677 8.24993 7.51522 8.24977 7.3829 8.26635L7.3804 8.26665C7.28556 8.2785 6.85353 8.33235 6.64776 8.75655C6.44154 9.18173 6.66799 9.5568 6.71698 9.63795L6.71881 9.64095C6.78796 9.75577 6.88858 9.88388 6.97133 9.98933L6.98914 10.012C7.21011 10.2939 7.49659 10.6571 7.78192 10.9503C7.92427 11.0966 8.08725 11.2475 8.26042 11.3667C8.41432 11.4726 8.67697 11.625 9 11.625C9.32302 11.625 9.58567 11.4726 9.73957 11.3667C9.91275 11.2475 10.0757 11.0966 10.2181 10.9503C10.5034 10.6571 10.7899 10.2939 11.0109 10.012L11.0287 9.98933C11.1114 9.88388 11.212 9.75577 11.2812 9.64095L11.283 9.63795C11.332 9.5568 11.5585 9.18173 11.3522 8.75655C11.1465 8.33235 10.7144 8.2785 10.6196 8.26665L10.6171 8.26635C10.4848 8.24977 10.323 8.24993 10.1913 8.25H9.75442V3.375Z"
                        fill="#6B2EE5"
                    />
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M3 11.625C3.41421 11.625 3.75 11.9608 3.75 12.375C3.75 12.8242 3.77442 13.133 3.82232 13.351C3.86836 13.5605 3.92529 13.6358 3.95727 13.6677C3.98924 13.6997 4.0645 13.7567 4.27404 13.8027C4.49208 13.8506 4.80085 13.875 5.25 13.875H12.75C13.1992 13.875 13.5079 13.8506 13.726 13.8027C13.9355 13.7567 14.0107 13.6997 14.0427 13.6677C14.0747 13.6358 14.1317 13.5605 14.1777 13.351C14.2256 13.133 14.25 12.8242 14.25 12.375C14.25 11.9608 14.5858 11.625 15 11.625C15.4142 11.625 15.75 11.9608 15.75 12.375C15.75 12.8566 15.7259 13.2944 15.6427 13.6728C15.5578 14.0596 15.4012 14.4305 15.1034 14.7284C14.8055 15.0262 14.4347 15.1828 14.0478 15.2678C13.6694 15.3509 13.2316 15.375 12.75 15.375H5.25C4.76839 15.375 4.33054 15.3509 3.95219 15.2678C3.56535 15.1828 3.19444 15.0262 2.8966 14.7284C2.59877 14.4305 2.44224 14.0596 2.35726 13.6728C2.27414 13.2944 2.25 12.8566 2.25 12.375C2.25 11.9608 2.58579 11.625 3 11.625Z"
                        fill="#6B2EE5"
                    />
                    </svg>
                </span>
                Download Ticket</a
                >
                <a
                href="<?php echo esc_url( site_url("etn-attendee?etn_action=edit_information&attendee_id={$attendee->id}&etn_info_edit_token={$attendee->etn_info_edit_token}") ); ?>"
                style="
                    font-size: 14px;
                    font-weight: 600;
                    text-decoration: underline;
                    border-left: 1.5px solid #334155;
                    padding-left: 8px;
                "
                >Edit Information</a
                >
            </div>
        </div>
            <!-- Separator -->
            <div
            style="margin: 30px 0; background-color: #c9c9c9; height: 1px"
            ></div>
        <div>

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
