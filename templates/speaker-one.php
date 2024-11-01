<?php
/**
 * The Template for displaying single speaker
 *
 * @see         
 * @package     Eventin\Templates
 * @version     2.3.2
 */

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
 

    $author_id = get_queried_object_id();
    // Get author name
    $author_name = get_the_author_meta( 'display_name', $author_id );
    // Get author email
    $author_email = get_the_author_meta( 'user_email', $author_id );
    // Get author bio/description
    $author_bio = get_the_author_meta( 'description', $author_id );
    // Get other custom fields or meta
    $author_website = get_the_author_meta( 'user_url', $author_id );
    $speaker_thumbnail = get_user_meta( $author_id, 'image', true);
		/**
		 * etn_before_single_speaker_content hook.
		 */
         // do_action( "etn_speaker_content_before" );
    ?>

    <div class="etn-speaker-page-container">
        <div class="etn-container">
            <div class="etn-single-speaker-wrapper">
                <div class="etn-row">
                    <div class="etn-col-lg-5">
                        <div class="etn-speaker-info">
                            <?php 
                            $speaker_avatar = apply_filters("etn/speakers/avatar", \Wpeventin::assets_url() . "images/avatar.jpg");
                            $speaker_thumbnail = $speaker_thumbnail ? $speaker_thumbnail : $speaker_avatar;
                            ?>
                            <div class="etn-speaker-thumb">
                                <img src="<?php echo esc_url( $speaker_thumbnail ); ?>" height="150" width="150" alt="<?php echo esc_attr($author_name); ?>"/>
                            </div>
                            <?php 
        
                                    /**
                            * Speaker meta hook.
                            *
                            * @hooked etn_speaker_company_logo - 12
                            */
                            do_action('etn_speaker_company_logo', $author_id);
                            
        
                            /**
                            * Speaker title before hook.
                            *
                            * @hooked speaker_title_before - 10
                            */
                            do_action('etn_speaker_title_before');
                            ?>
        
                            <h3 class="etn-title etn-speaker-name"> 
                                <?php echo esc_html($author_name); ?> 
                            </h3>
        
                            <?php
                            /**
                            * Speaker title after hook.
                            *
                            * @hooked speaker_name - 12
                            */
                            do_action('etn_speaker_title_after');
        
                            /**
                            * Speaker designation hook.
                            *
                            * @hooked speaker_designation - 13
                            */
                            do_action( "etn_speaker_designation" ); 
        
                                /**
                            * Speaker meta hook.
                            *
                            * @hooked speaker_meta - 12
                            */
                            do_action('etn_speaker_meta');
        
                    
        
                                
                            
                            /**
                            * Speaker summary hook.
                            *
                            * @hooked speaker_summary - 14
                            */
                            do_action( "etn_speaker_summary" ); 
        
                            /**
                            * Speaker social links.
                            *
                            * @hooked speaker_socials - 15
                            */
                            do_action( "etn_speaker_socials" ); 
        
                            ?>
                        </div>
                    </div>
                    <div class="etn-col-lg-7">
                        <div class="etn-schedule-wrap">
                            <?php
                                $author_id = get_queried_object_id();
                                $orgs = \Etn\Utils\Helper::speaker_sessions( $author_id);

                                if( is_array( $orgs ) && !empty( $orgs ) ) {
        
                                    foreach ( $orgs as $org ) {
                                        $etn_schedule_meta_value = get_post_meta( $org, 'etn_schedule_topics', true);
                                        foreach ($etn_schedule_meta_value as $single_meta) {
                                            $speaker_schedules = isset($single_meta["speakers"]) && is_array($single_meta["speakers"]) ? $single_meta["speakers"]: [];
                                            
                                            $start_time = isset($single_meta["etn_shedule_start_time"]) ? $single_meta["etn_shedule_start_time"] : "";
                                            $end_time   = isset($single_meta["etn_shedule_end_time"]) ? $single_meta["etn_shedule_end_time"] : "";
                                            $room       = isset($single_meta["etn_shedule_room"]) ? $single_meta["etn_shedule_room"] : "";
                                            $topics     = isset($single_meta["etn_schedule_topic"]) ? $single_meta["etn_schedule_topic"] : "";
                                            $desc       = isset($single_meta["etn_shedule_objective"]) ? $single_meta["etn_shedule_objective"] : "";

                                            if ( in_array( $author_id, $speaker_schedules ) ) {
    
                                                /**
                                                * Speaker schedule details before.
                                                *
                                                * @hooked speaker_details_before - 16
                                                */
                                                do_action( 'etn_speaker_details_before' );
    
                                                ?>
                                                <div class="etn-single-schedule-item etn-row">
                                                    <div class="etn-schedule-info etn-col-lg-4">
                                                        <?php 
    
                                                            /**
                                                            * Speaker schedule time hook.
                                                            *
                                                            * @hooked schedule_time - 17
                                                            */
                                                            do_action('etn_schedule_time', $start_time , $end_time);
                                                           
     
                                                            /**
                                                            * Speaker schedule location hook.
                                                            *
                                                            * @hooked schedule_locations - 18
                                                            */
                                                            do_action( 'etn_schedule_locations' , $room  );
                                                        ?>
                                                    </div>
                                                    <div class="etn-schedule-content etn-col-lg-8">
                                                        <?php
    
                                                            /**
                                                            * Speaker topic hook.
                                                            *
                                                            * @hooked speaker_topic - 19
                                                            */
                                                            do_action( 'etn_speaker_topic' , $topics  );
    
                                                            /**
                                                            * Speaker objective hook.
                                                            *
                                                            * @hooked speaker_objective - 20
                                                            */
                                                            do_action( 'etn_speaker_objective' , $desc  );
                                                        ?>
                                                    </div>
                                                </div>
                                                
                                                <?php
                                                    /**
                                                    * Speaker details after hook.
                                                    *
                                                    * @hooked speaker_details_after - 21
                                                    */
                                                    do_action( 'etn_speaker_details_after' );
                                            }
                                        }
                                        
                                    }
                                }?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

 