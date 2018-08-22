<?php
function add_theme_scripts() {
    wp_enqueue_style( 'styles', get_template_directory_uri() . '/assets/css/style.css');
    wp_enqueue_script( 'script', get_template_directory_uri() . '/assets/js/site-min.js', array ( 'jquery' ), 1.1, true);
}
  
add_action( 'wp_enqueue_scripts', 'add_theme_scripts' );

/**
 * Migrate from evenementen_datetime to evenementen array
 * 
 * @param unknown $events_datetimes
 * @return array
 */
function archive_agenda_list_helper( $events_datetimes ) {
    
    if( is_array( $events_datetimes ) && count( $events_datetimes ) > 0 ) {
        
        $tmp = wp_list_pluck( $events_datetimes, 'custom' );
        $events_datetimes = array();
        foreach( $tmp as $single_datetime ) {
            
            $single_id = $single_datetime['evenement'][0];
            $args_event = array( 'post_type' => 'evenementen', 'post__in' =>  array( $single_id  ) );
            $main_event = Timber::get_posts( $args_event );
            
            $events_datetimes[ $single_id ] = $main_event[0];
            
            $events_datetimes[ $single_id ]->custom = array_merge( $events_datetimes[ $single_id ]->custom, $single_datetime );
        }
        
        // Now sort by time ascending
        $events_datetimes_sorted = array();
        foreach( $events_datetimes as $single_datetime ) {
            $events_datetimes_sorted[$single_datetime->custom['begintijd'].$single_datetime->ID] = $single_datetime;
        }
            
        $events_datetimes = $events_datetimes_sorted;
        // Falltrough
    }
    
    return $events_datetimes;
}

define('DH_EVENTS_HOUR_OFFSET', 2);

function get_args_event_datetime_equals_date( $date ) {
    
    $args_evenementen_datetime = array(
        'post_type' => 'evenementen_datetime',
        'posts_per_page' => - 1,
        'meta_query' => array(
            'relation' => 'AND',
            'date1' => array(
                'key' => 'datum',
                'compare' => '=',
                'value' => $date
            ),
        ),
        'meta_key' => 'begintijd',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    );
    
    return $args_evenementen_datetime;
}

function get_args_event_datetime_greaterequals_date( $date ) {
    
    $args_evenementen_datetime = get_args_event_datetime_equals_date( $date );
    $args_evenementen_datetime['meta_query']['date1']['compare'] = '>=';
    
    return $args_evenementen_datetime;
    
}

function archive_agenda( $context, $tries = 0, $override_offset = false, $force_no_xhr = false ) {
    
    $context[ 'category' ] = Timber::get_term(['taxonomy'=>'categorie']);
    $context[ 'cat' ] = Timber::get_terms('categorie');
    
    /* get current category */
    $cat = get_category(get_query_var('categorie'));
    $cat_slug = $cat->slug;
    $context['currentcat'] = $cat_slug;
    
    $dh_is_ajax = false;
    if( isset( $_POST['offset'] ) ) {
        
        $dh_is_ajax = true;
        $offset = intval( $_POST['offset'] );
    }
    if( $override_offset ) {
        
        $dh_is_ajax = true;
        if( $force_no_xhr ) {
            $dh_is_ajax = false;
        }
        $offset = $override_offset;
    }
    
    if( $dh_is_ajax ) {
        $templates = 'partials/archive-evenementen-single.twig';
        $_GET['category']   = $_POST['category'];
        $_GET['date']       = $_POST['date'];
        $_GET['time']       = $_POST['time'];
        
    }
    
    $context['dh_agenda_ajaxurl']       = home_url( $_SERVER['REDIRECT_URL'] );
    $context['dh_agenda_ajaxoffset']    = $offset+DH_EVENTS_HOUR_OFFSET; //DH_EVENTS_HOUR_OFFSET;
    
    $context['selected_cats'] = array();
    $cats = $context[ 'cat' ];
    $context['date_sanitized'] = '';
    $context['time_sanitized'] = '';
    
    $cat_ids = array();
    if( isset( $_GET['category'] ) && is_array( $_GET['category'] ) && count( $_GET['category'] ) > 0 ) {
        foreach( $_GET['category'] as $request_cat ) {
            foreach( $cats as $cat ) {
                if( $cat->slug == $request_cat ) {
                    $cat_ids[] = $cat->term_id;
                    $context['selected_cats'][$cat->slug] = $cat->slug;
                }
            }
        }
    }
    
    $today = date('Ymd');
    $date = $today;
    
    $hasdate = false;
    if( isset( $_GET['date'] ) && $_GET['date'] ) {
        if( $_GET['date'] == 'tomorrow' ) {
            $_GET['date'] = date('d/m/Y', strtotime( 'tomorrow' ) );
        }
        
        $tmp = explode( '/', $_GET['date'] );
        if( count( $tmp ) == 3 && intval( $tmp[0] ) > 1 && intval( $tmp[1] ) > 0 && intval( $tmp[2] ) > 0 ) {
            $date = str_pad( intval( $tmp[2] ), 2, '0', STR_PAD_LEFT ).str_pad( intval( $tmp[1] ), 2, '0', STR_PAD_LEFT ).str_pad( intval( $tmp[0] ), 2, '0', STR_PAD_LEFT );
            $context['date_sanitized'] = str_pad( intval( $tmp[0] ), 2, '0', STR_PAD_LEFT ).'/'.str_pad( intval( $tmp[1] ), 2, '0', STR_PAD_LEFT ).'/'.str_pad( intval( $tmp[2] ), 2, '0', STR_PAD_LEFT );
            
            if(ICL_LANGUAGE_CODE==en){
                $context[ 'date_filter_full' ] = date('l d F', strtotime( $date ) );
                $context[ 'day_filter_full' ]  = date('l', strtotime( $date ) );
            }
            else {
                setlocale(LC_ALL, 'nl_NL');
                $context[ 'date_filter_full' ] = strftime('%A %e %B', strtotime( $date ) );
                $context[ 'day_filter_full' ]  = strftime('%A', strtotime( $date ) );
            }
            
            $context[ 'date_filter_short' ] = date('d.m.Y', strtotime( $date ));
            
            $hasdate = true;
        }
    }
    
    $timeday = '00:00:00';
    $timestart = $timeday;
    $hastime = false;
    if( isset( $_GET['time'] ) && $_GET['time'] ) {
        $tmp = explode( ':', $_GET['time'] );
        if( count( $tmp ) == 2 && (intval( $tmp[0] ) > 1 || $tmp[0] == '00') && (intval( $tmp[1] ) > 1 || $tmp[1] == '00') ) {
            // Discard minutes
            // $timestart = str_pad( intval( $tmp[0] ), 2, '0', STR_PAD_LEFT ).':'.str_pad( intval( $tmp[1] ), 2, '0', STR_PAD_LEFT ).':00';
            $timestart = str_pad( intval( $tmp[0] ), 2, '0', STR_PAD_LEFT ).':00:00';
            
            $context['time_sanitized'] = str_pad( intval( $tmp[0] ), 2, '0', STR_PAD_LEFT ).':'.str_pad( intval( $tmp[1] ), 2, '0', STR_PAD_LEFT );
            $hastime = true;
        }
    }
    
    if( $dh_is_ajax || $force_no_xhr ) {
        $first_slot = strtotime( $date. ' '.$timestart . ' + '.$offset.' hours');
        $date = date( 'Ymd', $first_slot);
        $timestart = date( 'H:i:00', $first_slot);
    }
    
    $args_evenementen = array(
        'post_type' => 'evenementen_datetime',
        'posts_per_page' => - 1,
        'meta_query' => array(
            'relation' => 'AND',
            'date1' => array(
                'key' => 'datum',
                'compare' => '=',
                'value' => $date
            ),
        ),
        'meta_key' => 'begintijd',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    );
    
    if( $hastime ) {
            
        $args_evenementen['meta_query']['hastime1'] =
                array(
                    'key' => 'begintijd',
                    'compare' => '>=',
                    'value' => $timestart
                );           
    }
    
    // Limit by DH_EVENTS_HOUR_OFFSET hours up front
    $next_slot = strtotime( $date. ' '.$timestart . ' + '.DH_EVENTS_HOUR_OFFSET.' hours');
    
    $args_evenementen['meta_query']['date2'] =
    array(
        'key' => 'datum',
        'compare' => '<=',
        'value' => date( 'Ymd', $next_slot),
    );
       
    $args_evenementen['meta_query']['hastime2'] =
        array(
            'key' => 'begintijd',
            'compare' => '<',
            'value' => date( 'H:i:00', $next_slot)
    );
    
    
    // Shift cats to upper
    if( is_array( $cat_ids ) && count( $cat_ids ) > 0 ) {
        $args_event = array( 'post_type' => 'evenementen', 'posts_per_page' => -1, 'fields' => 'ids' );
        $args_event['tax_query'] = array(
            'relation' => 'OR');
        
        foreach( $cat_ids as $cat_id ) {
            $args_event['tax_query'][] =
            array(
                'taxonomy' => 'categorie',
                'field'    => 'term_id',
                'terms'    => $cat_ids,
            );
        }
        
        $tax_query_results = new WP_Query( $args_event );
        
        $search_posts = array();
        foreach( $tax_query_results->posts as $parent_id ) {
            $search_posts[] = serialize(array("$parent_id"));
        }
        
        $args_evenementen['meta_query']['categories'] = 
            array(
                'key' => 'evenement',
                'compare' => 'IN',
                'value' => $search_posts,
            );
    }
    
    
    
    $args_evenementen_continuous = $args_evenementen;
    $args_evenementen_continuous['meta_query']['hastime1'] =
    array(
        'relation' => 'AND',
        array(
            'key' => 'begintijd',
            'compare' => '<',
            'value' => $timestart,
        ),
        array(
            'key' => 'doorlopend_event',
            'compare' => '=',
            'value' => 1
        ),
    );
    $args_evenementen_continuous['meta_query']['hastime2'] =
    array(
        'relation' => 'AND',
        array(
            'key' => 'eindtijd',
            'compare' => '>',
            'value' => date( 'H:i:00', $next_slot),
        ),
        array(
            'key' => 'doorlopend_event',
            'compare' => '=',
            'value' => 1
        ),
    );
    
    
    
    $events = Timber::get_posts( $args_evenementen );
    $events = archive_agenda_list_helper( $events );   
    
    $events_continuous = Timber::get_posts( $args_evenementen_continuous );
    $events_continuous = archive_agenda_list_helper( $events_continuous );
    
    $context['evenementen'] = array_merge( $events , $events_continuous);
    
    /*
    print_r($args_evenementen);
    //print_r( new WP_Query( $args_evenementen ));
    */
    
    if( $dh_is_ajax ) {
        
        // Recursive
        if( count( $events ) == 0 ) {
            $tries++;
            
            
            // Untill last hour this day
            if( date( 'H', $next_slot) < 22 ) {
                $context = archive_agenda( $context, $tries, ($offset+DH_EVENTS_HOUR_OFFSET) );
            }
        }
        
        // Do not cross date
        if( $_GET['date'] != date('d/m/Y', $next_slot ) ) {
            $context['evenementen'] = array();
        }
        $body = Timber::compile( $templates, $context  );
        
        
        echo json_encode( array('offsetvalue' => ($tries+1)*DH_EVENTS_HOUR_OFFSET, 'body' => $body ) );
        die();
    }
    
    return array(
        'context' => $context,
        'next_slot' => $next_slot,
        'count_events_single' => count( $events ),
        'count_events_continuous' => count( $events_continuous ),
        'offset' => $offset,
    )
    ;
}
?>