<?php
  
  if( function_exists('acf_add_options_page') ) {
  	
  	$websiteopties = array(
	
    	/* (string) The title displayed on the options page. Required. */
    	'page_title' => 'Options',
    	
    	/* (string) The title displayed in the wp-admin sidebar. Defaults to page_title */
    	'menu_title' => 'Thema Opties',
    	
    	/* (string) The URL slug used to uniquely identify this options page. 
    	Defaults to a url friendly version of menu_title */
    	'menu_slug' => '',
    	
    	/* (string) The icon class for this menu. Defaults to default WordPress gear.
    	Read more about dashicons here: https://developer.wordpress.org/resource/dashicons/ */
    	'icon_url' => 'dashicons-desktop',
    	
    	/* (boolean) If set to true, this options page will redirect to the first child page (if a child page exists). 
    	If set to false, this parent page will appear alongside any child pages. Defaults to true */
    	'redirect' => true,
    	
    	/* (int|string) The '$post_id' to save/load data to/from. Can be set to a numeric post ID (123), or a string ('user_2'). 
    	Defaults to 'options'. Added in v5.2.7 */
    	'post_id' => 'options',
    			
    );
    

  	acf_add_options_page($websiteopties);

  	add_filter( 'timber_context', 'mytheme_timber_context'  );
  
    function mytheme_timber_context( $context ) {
        $context['options'] = get_fields('option');
        $context['themeurl'] = get_template_directory_uri();
        return $context;
    }
  	
  }