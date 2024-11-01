<?php

namespace Etn\Core\Shortcodes;

use Etn\Core\Event\Event_Model;
use Etn\Utils\Helper as helper;

defined( 'ABSPATH' ) || exit;

class Hooks {

    use \Etn\Traits\Singleton;

    public function Init() { 
        //[events limit='1' event_cat_ids='1,2' event_tag_ids='1,2' /]
        add_shortcode( "events", [$this, "etn_events_widget"] );

        //[events_tab limit='1' event_cat_ids='1,2' event_tag_ids='1,2' /]
        add_shortcode( "events_tab", [$this, "etn_events_tab"] );

        //[events_calendar limit='1' event_cat_ids='1,2' event_tag_ids='1,2' /]
        add_shortcode( "events_calendar", [$this, "calendar_widget"] ); 

        //[speakers cat_id='19' limit='3'/]
        add_shortcode( "speakers", [$this, "etn_speakers_widget"] );

        //[schedules ids ='18,19'/]
        add_shortcode( "schedules", [$this, "etn_schedules_widget"] );

        //[event_search_filter/]
        add_shortcode( "event_search_filter", [$this, "etn_search_filter"] );

        add_shortcode( "schedules_list", [$this, "etn_schedules_list_widget"] );

        //[etn_zoom_api_link meeting_id ='123456789' link_only='no']
        add_shortcode( "etn_zoom_api_link", [$this, "etn_zoom_api_link"] );
        add_shortcode( "etn_event_meta_info", [$this, "etn_event_meta_info"] );
    }
    
    /**
     * Events Calendar
     */
    public function calendar_widget($attributes){
        wp_enqueue_style('etn-app-index');
        wp_enqueue_script('etn-app-index');
        $event_cat = null;

        if ( isset( $attributes['event_cat_ids'] ) && $attributes['event_cat_ids'] !== '' ) {
            $event_cat = explode( ',', $attributes['event_cat_ids'] );
        }

        $event_count        = isset( $attributes["limit"] ) && is_numeric( $attributes["limit"] ) && is_numeric( $attributes["limit"] ) <= 3 ? intval( $attributes["limit"] ) : 15;
        $show_desc          = !empty( $attributes["show_desc"] ) ? $attributes["show_desc"] : 'no';
        $calendar_show      = !empty( $attributes["calendar_show"] ) ? $attributes["calendar_show"] : 'left';
        $style              = !empty( $attributes["style"] ) ? $attributes["style"] : 'style-1';

        if(is_array($event_cat)){
            $cats = $event_cat;
        }else{
    
            $args_cat = array(
                'taxonomy'     => 'etn_category',
                'number' => 50,
            );
            $cats = get_categories( $args_cat );
        }
        $cat_json = [];
        if(!empty($cats)){
            foreach($cats as $value){
                $term = get_term($value,'etn_category');
                
                $info = [
                    'category' => $term->name, 
                ];
                array_push($cat_json,  $info );
            }
        }
    
        $show_parent_event = isset( $attributes['show_parent_event'] ) ? $attributes['show_parent_event'] : '';
        $show_child_event  = isset( $attributes['show_child_event'] ) ? $attributes['show_child_event'] : 'yes';
        $post_parent = Helper::show_parent_child( $show_parent_event , $show_child_event  );

        $extra_controls = [
            'limit' => $event_count, 
            'select_cat_text' => esc_html__('All Categories', 'eventin'),
            'show_desc' => $show_desc,
            'calendar_show' =>$calendar_show, 
            'style' =>$style, 
            'selected_date_text' => esc_html__('Showing events for', 'eventin'),
            'event_notice' => esc_html__('No event found on the selected date. Please select another date or month.', 'eventin'),
            'post_parent' => $post_parent
        ];
        $data_controls = json_encode( $extra_controls );
        $data_cat_list = json_encode( $cat_json );
        $data_selected_cats = json_encode( $cats );
        

        ob_start();
        ?>
          <div class="eventin-shortcode-wrapper">
              <div class="events_calendar_classic" data-cat="<?php echo esc_attr( $data_cat_list ); ?>" data-controls="<?php echo esc_attr( $data_controls ); ?>" data-selectedCats="<?php echo esc_attr( $data_selected_cats ); ?>"></div>
          </div>
        <?php

        return ob_get_clean(); 
    }

  

    /**
     * Events shortcode
     */
    public function etn_events_widget( $attributes ) {

        $event_cat = null;
        $event_tag = null;
        $style            = !empty( $attributes["style"] ) ? $attributes["style"] : 'event-1';

        if ( isset( $attributes['event_cat_ids'] ) && $attributes['event_cat_ids'] !== '' ) {
            $event_cat = explode( ',', $attributes['event_cat_ids'] );
        }

        if ( isset( $attributes['event_tag_ids'] ) && $attributes['event_tag_ids'] !== '' ) {
            $event_tag = explode( ',', $attributes['event_tag_ids'] );
        }

        $event_count    = isset( $attributes["limit"] ) && is_numeric( $attributes["limit"] ) && is_numeric( $attributes["limit"] ) <= 3 ? intval( $attributes["limit"] ) : 3;
        $order          = !empty( $attributes["order"] ) ? $attributes["order"] : 'DESC';
        $etn_desc_limit = !empty( $attributes["desc_limit"] ) ? $attributes["desc_limit"] : 20;
        $etn_event_col  = !empty( $attributes["etn_event_col"] ) ? $attributes["etn_event_col"] : 20;

        $show_parent_event = ! empty( $attributes['show_parent_event'] ) ? $attributes['show_parent_event'] : 'no';
        $show_child_event  = ! empty( $attributes['show_child_event'] ) ? $attributes['show_child_event'] : 'yes';
        $post_parent = Helper::show_parent_child( $show_parent_event , $show_child_event  );


        if ( $etn_event_col == 6 ) {
            $etn_event_col = 2;
        } else if ( $etn_event_col == 5 ) {
            $etn_event_col = 2;
        } else if ( $etn_event_col == 4 ) {
            $etn_event_col = 3;
        } else if ( $etn_event_col == 3 ) {
            $etn_event_col = 4;
        } else if ( $etn_event_col == 2 ) {
            $etn_event_col = 6;
        } else if ( $etn_event_col == 1 ) {
            $etn_event_col = 12;
        }


        $post_attributes    = ['title', 'ID', 'name', 'post_date'];
        $orderby            = !empty( $attributes["orderby"] ) ? $attributes["orderby"] : 'title';
        $orderby_meta       = in_array($orderby, $post_attributes) ? false : 'meta_value';
        $filter_with_status = !empty( $attributes["filter_with_status"] ) ? $attributes["filter_with_status"] : '';
        $show_end_date  = !empty( $attributes["show_end_date"] ) ? $attributes["show_end_date"] : 'no';
        $show_event_location  = !empty( $attributes["show_event_location"] ) ? $attributes["show_event_location"] : 'yes';

        $etn_desc_show      = (isset($attributes["etn_desc_show"]) ? $attributes["etn_desc_show"] : 'yes');


        ob_start();

        if ( file_exists( \Wpeventin::widgets_dir() . "events/style/{$style}.php" ) ) {
            include \Wpeventin::widgets_dir() . "events/style/{$style}.php";
        }

        return ob_get_clean();
    }
    /**
     * Events Tab shortcode
     */
    public function etn_events_tab( $attributes ) {

        $event_cats = null;
        $event_tag = null;
        $style            = !empty( $attributes["style"] ) ? $attributes["style"] : 'event-1';

        if ( isset( $attributes['event_cat_ids'] ) && $attributes['event_cat_ids'] !== '' ) {
            $event_cats = explode( ',', $attributes['event_cat_ids'] );
        }

        if ( isset( $attributes['event_tag_ids'] ) && $attributes['event_tag_ids'] !== '' ) {
            $event_tag = explode( ',', $attributes['event_tag_ids'] );
        }

        $event_count    = isset( $attributes["limit"] ) && is_numeric( $attributes["limit"] ) && is_numeric( $attributes["limit"] ) <= 3 ? intval( $attributes["limit"] ) : 3;
        $order          = !empty( $attributes["order"] ) ? $attributes["order"] : 'DESC';
        $etn_desc_limit = !empty( $attributes["desc_limit"] ) ? $attributes["desc_limit"] : 20;        
        $etn_event_col  = !empty( $attributes["etn_event_col"] ) ? $attributes["etn_event_col"] : 20;

        if ( $etn_event_col == 6 ) {
            $etn_event_col = 2;
        } else if ( $etn_event_col == 5 ) {
            $etn_event_col = 2;
        } else if ( $etn_event_col == 4 ) {
            $etn_event_col = 3;
        } else if ( $etn_event_col == 3 ) {
            $etn_event_col = 4;
        } else if ( $etn_event_col == 2 ) {
            $etn_event_col = 6;
        } else if ( $etn_event_col == 1 ) {
            $etn_event_col = 12;
        }
        $widget_id = uniqid();
        $post_attributes    = ['title', 'ID', 'name', 'post_date'];
        $orderby            = !empty( $attributes["orderby"] ) ? $attributes["orderby"] : 'title';
        $orderby_meta       = in_array($orderby, $post_attributes) ? false : 'meta_value';
        $filter_with_status = !empty( $attributes["filter_with_status"] ) ? $attributes["filter_with_status"] : '';
        $show_end_date  = !empty( $attributes["show_end_date"] ) ? $attributes["show_end_date"] : 'no';


        $show_parent_event = ! empty( $attributes['show_parent_event'] ) ? $attributes['show_parent_event'] : 'no';
        $show_child_event  = ! empty( $attributes['show_child_event'] ) ? $attributes['show_child_event'] : 'yes';
        $post_parent = Helper::show_parent_child( $show_parent_event , $show_child_event  );

        $show_event_location  = !empty( $attributes["show_event_location"] ) ? $attributes["show_event_location"] : 'yes';
        
        $etn_desc_show      = (isset($attributes["etn_desc_show"]) ? $attributes["etn_desc_show"] : 'yes');


        ob_start();

        if ( file_exists( \Wpeventin::widgets_dir() . "/events-tab/style/tab-1.php" ) ) {
            include \Wpeventin::widgets_dir() . "/events-tab/style/tab-1.php";

        }

        return ob_get_clean();
    }

    /**
     * Speakers shortcode
     */
    public function etn_speakers_widget( $attributes ) {

        $speakers_category  = isset( $attributes["cat_id"] ) && ( "" != $attributes["cat_id"] ) ? explode( ',', $attributes["cat_id"] ) : [];
        $etn_speaker_count  = isset( $attributes["limit"] ) && is_numeric( $attributes["limit"] ) ? $attributes["limit"] : 3;
        $etn_speaker_order  = !empty( $attributes["order"] ) ? $attributes["order"] : 'DESC';

        $etn_speaker_col  = !empty( $attributes["etn_speaker_col"] ) ? $attributes["etn_speaker_col"] : 3;

        if ( $etn_speaker_col == 6 ) {
            $etn_speaker_col = 2;
        } else if ( $etn_speaker_col == 5 ) {
            $etn_speaker_col = 2;
        } else if ( $etn_speaker_col == 4 ) {
            $etn_speaker_col = 3;
        } else if ( $etn_speaker_col == 3 ) {
            $etn_speaker_col = 4;
        } else if ( $etn_speaker_col == 2 ) {
            $etn_speaker_col = 6;
        } else if ( $etn_speaker_col == 1 ) {
            $etn_speaker_col = 12;
        }

        
        $post_attributes    = ['title', 'ID', 'name', 'post_date'];
        $orderby            = !empty( $attributes["orderby"] ) ? $attributes["orderby"] : 'title';
        $orderby_meta       = in_array($orderby, $post_attributes) ? false : 'meta_value';
        $style  = !empty( $attributes["style"] ) ? $attributes["style"] : 'speaker-1';

        
        ob_start();

        if ( file_exists( \Wpeventin::widgets_dir() . "speakers/style/speaker-2.php" ) && $style =='speaker-1' ) {
            include \Wpeventin::widgets_dir() . "speakers/style/speaker-2.php";
        }else if(file_exists( \Wpeventin::widgets_dir() . "speakers/style/speaker-3.php" ) && $style =='speaker-2'){
            include \Wpeventin::widgets_dir() . "speakers/style/speaker-3.php";
        }else{
            echo esc_html__('No style found', 'eventin');
        }

        return ob_get_clean();
    }

    /**
     * Schedule shortcode list
     */
    public function etn_schedules_list_widget( $attributes ) {
        $schedule_ids     = is_array( $attributes ) && isset( $attributes["ids"] ) ? $attributes["ids"] : "";
        $etn_schedule_id = explode( ",", $schedule_ids );
        $order            = isset( $attributes["order"] ) ? $attributes["order"] : 'asc';

        ob_start();

        if ( file_exists( \Wpeventin::widgets_dir() . "schedule-list/style/schedule-list-1.php" ) ) {
            include \Wpeventin::widgets_dir() . "schedule-list/style/schedule-list-1.php";
        }

        return ob_get_clean();
    }

    /**
     * Schedule shortcode tab
     */
    public function etn_schedules_widget( $attributes ) {
        $schedule_ids     = is_array( $attributes ) && isset( $attributes["ids"] ) ? $attributes["ids"] : "";
        $etn_schedule_ids = explode( ",", $schedule_ids );
        $order            = isset( $attributes["order"] ) ? $attributes["order"] : 'asc';

        ob_start();

        if ( file_exists( \Wpeventin::widgets_dir() . "schedule/style/schedule-1.php" ) ) {
            include \Wpeventin::widgets_dir() . "schedule/style/schedule-1.php";
        }

        return ob_get_clean();
    }

    /**
     * search filter widget
     */
    public function etn_search_filter(){
        ob_start();
        $widget_id = uniqid();
        ?>
            <div class="etn_search_<?php echo esc_attr( $widget_id ); ?> etn_search_wraper">

            <?php helper::get_event_search_form(); ?>
            <p class="etn_search_bottom_area_text"><?php echo esc_html__( "Discover ". count(helper::get_eventin_search_data()) ." Upcoming and Expire "._n( "Event", "Events", count(helper::get_eventin_search_data()), 'eventin' )."", 'eventin' ); ?></p>
            </div>
        <?php
        return ob_get_clean();
    }
     
    /**
     * Zoom meeting info function
     */
    public function etn_zoom_api_link( $atts, $content ) {
        extract( shortcode_atts( ['meeting_id' => 'javascript:void(0);', 'link_only' => 'no'], $atts ) );

        ob_start();

        $event = new Event_Model( $meeting_id );
        $location = $event->etn_event_location;

        $integration = ! empty( $location['integration'] ) ? $location['integration'] : '';
        $meeting_link = $event->meeting_link;

        if ( 'online' == $event->event_type && 'zoom' == $integration ) {
            ?>
                <div class="meeting-wrapper">
                    <div class="meeting-row">
                        <div class="meeting-info">
                            <p><?php echo esc_html__( 'Join url', 'eventin' ); ?></p>
                        </div>
                        <div class="meeting-info info-right">
                            <a href="<?php echo esc_url( $meeting_link ) ?>"><?php echo esc_html__( 'Join url', 'eventin' ); ?></a>
                        </div>
                    </div><!-- row end -->
                </div><!-- mettin-wrapper end -->
            <?php
        } else {
            ?>
                <p><?php echo esc_html__( 'This is not a zoom event', 'eventin' ); ?></p>
            <?php
        }
        

        return ob_get_clean();
    }

    /**
     * fetch meeting info function
     */
    private function fetch_meeting( $meeting_id ) {
        $meeting_info = \Etn\Core\Zoom_Meeting\Api_Handlers::instance()->meeting_info( $meeting_id );
        if ( count( $meeting_info ) === 0 ) {
            return false;
        }

        return $meeting_info;
    }

    /**
     * fetch event meta info function
     */

    public function etn_event_meta_info( $attributes ) {
        $single_event_id = null;
        $single_event_id     = is_array( $attributes ) && isset( $attributes["event_id"] ) ? $attributes["event_id"] : "";
        
        ?>
        <div class="etn-sidebar">
            <?php
                \Etn\Templates\Event\Parts\EventDetailsParts::event_single_sidebar_meta( $single_event_id );
            ?>
        </div>
        <?php
    }

}
