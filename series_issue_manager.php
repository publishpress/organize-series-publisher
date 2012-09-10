<?php
/*
Plugin Name: Organize Series Publisher
Plugin URI: http://unfoldingneurons.com/neurotic-plugins/organize-series-wordpress-plugin/
Description: Allows an editor to publish an "issue", which is to say, all pending posts with a given series. Until a series is published, all posts with that series will remain in the pending state.  Credit really needs to go to  <a href="http://xplus3.net">Jonathan Brinley</a> for his original Issue Manage plugin because all I did was modify it for use with series rather than categories.  Also NOTE that this REQUIRES Organize Series to be installed.
Version: 2.2.3
Author: Darren Ethier 
Author URI: http://unfoldingneurons.com
*/
 
// Register hooks for activation/deactivation.
register_activation_hook( __FILE__, 'series_issue_manager_activation' );
register_deactivation_hook( __FILE__, 'series_issue_manager_deactivation' );
add_action('plugins_loaded', 'orgseries_check');
add_action('init', 'org_pub_register_textdomain');

function org_pub_register_textdomain() {
	$dir = basename(dirname(__FILE__)).'/lang';
	load_plugin_textdomain('organize-series-publisher', false, $dir);
}

function orgseries_check() {
	if ( !class_exists('orgSeries') ) {
		add_action('admin_notices', 'orgseries_plugin_warning');
		add_action('admin_notices', 'orgseries_pub_deactivate');
	}
}

function orgseries_pub_deactivate() {
	deactivate_plugins('organize-series-publisher/series_issue_manager.php', true);
}

function orgseries_plugin_warning() {
	$msg = '<div id="wpp-message" class="error fade"><p>'.__('Organize Series Publisher requires the Organize Series plugin to be installed and activated in order to work.  Plugin won\'t activate until this condition is met.', 'organize-series-publisher').'</p></div>';
	echo $msg;
}

function series_issue_manager_manage_page() {
  if ( function_exists('add_submenu_page') ) {
    $page = add_submenu_page( 'edit.php', __('Manage Series Issues','organize-series-publisher'), __('Publish Series','organize-series-publisher'), 'publish_posts', 'manage-issues', 'series_issue_manager_admin' );
    add_action("admin_print_scripts-$page", 'series_issue_manager_scripts');
  }
}
function series_issue_manager_admin() {
  $published = get_option( 'im_published_series' );
  $unpublished = get_option( 'im_unpublished_series' );
  $series = get_series( 'orderby=name&hide_empty=0' );
  
  // Make sure the options exist
  if ( $published === FALSE ) { $published = array(); update_option( 'im_published_series', $published ); }
  if ( $unpublished === FALSE ) { $unpublished = array(); update_option( 'im_unpublished_series', $unpublished ); }
  
  // See if we have GET parameters
  $series_ID = isset($_GET['series_ID'])?$_GET['series_ID']:null;
  $action = isset($_GET['action'])?$_GET['action']:null;
    
  if ( $series_ID ) {
    $series_ID = (int)$series_ID;
    switch($action) {
      case "list":
        include_once('series_im_article_list.php');
        break;
      case "publish":
        $post_IDs = isset($_GET['posts'])?$_GET['posts']:null;
        $pub_time['mm'] = isset($_GET['mm'])?$_GET['mm']:null;
        $pub_time['jj'] = isset($_GET['jj'])?$_GET['jj']:null;
        $pub_time['aa'] = isset($_GET['aa'])?$_GET['aa']:null;
        $pub_time['hh'] = isset($_GET['hh'])?$_GET['hh']:null;
        $pub_time['mn'] = isset($_GET['mn'])?$_GET['mn']:null;
        if ( $post_IDs ) series_issue_manager_publish($series_ID, $post_IDs, $pub_time, $published, $unpublished);
        include_once('series_im_admin_main.php');
        break;
      case "unpublish":
        series_issue_manager_unpublish($series_ID, $published, $unpublished);
        include_once('series_im_admin_main.php');
        break;
      case "ignore":
        // stop tracking the series_ID
        $key = array_search($series_ID, $published);
        if ( FALSE !== $key ) {
          array_splice($published, $key, 1);
          update_option( 'im_published_series', $published );
        }
        $key = array_search($series_ID, $unpublished);
        if ( FALSE !== $key ) {
          array_splice($unpublished, $key, 1);
          update_option( 'im_unpublished_series', $unpublished );
        }
        include_once('series_im_admin_main.php');
        break;
      default:
        include_once('series_im_admin_main.php');
        break;
    }
  } else {
    include_once('series_im_admin_main.php');
  }
}

function series_issue_manager_publish( $series_ID, $post_IDs, $pub_time, &$published, &$unpublished ) {
  // take the series out of the unpublished list
  $key = array_search( $series_ID, $unpublished );
  if ( FALSE !== $key ) {
    array_splice( $unpublished, $key, 1 );
    update_option( 'im_unpublished_series', $unpublished );
  }
  if ( !in_array( $series_ID, $published ) ) {
    // add to the published list
    $published[] = $series_ID;
    sort($published);
    update_option( 'im_published_series', $published );
    
    // see if we have a valid publication date/time
    $publish_at = strtotime( $pub_time['aa'].'-'.$pub_time['mm'].'-'.$pub_time['jj'].' '.$pub_time['hh'].':'.$pub_time['mn'] );
    
    if ( !$publish_at ) {
      $publish_at = strtotime(current_time('mysql'));
    }
    
    // $post_IDs should have all pending posts' IDs in the series
    $counter = 0;
    foreach ( explode(',',$post_IDs) as $post_ID ) {
      $post_ID = (int)$post_ID;
      $post = get_post( $post_ID );
      // set the date to about the appropriate time, keeping a small gap so posts stay in order
      wp_update_post( array(
        'ID' => $post->ID,
        'post_date' => date( 'Y-m-d H:i:s', $publish_at-($counter+1) ),
        'post_date_gmt' => '',
        'post_status' => 'publish'
      ) );
	  wp_set_post_series( $post_ID,'',$series_ID );
      $counter++;
    }
  }
}

function series_issue_manager_unpublish( $series_ID, &$published, &$unpublished ) {
  // take the series out of the published list
  $key = array_search( $series_ID, $published );
  if ( FALSE !== $key ) {
    array_splice( $published, $key, 1 );
    update_option( 'im_published_series', $published );
  }
  if ( !in_array( $series_ID, $unpublished ) ) {
    // add to the unpublished list
    $unpublished[] = $series_ID;
    sort( $unpublished );
    update_option( 'im_unpublished_series', $unpublished );
    
    // change all published posts in the series to pending
	$posts = get_objects_in_term($series_ID, 'series'); 
    foreach ( $posts as $post ) {
		if ( get_post_status($post) == 'draft' ) continue;
      wp_update_post( array(
        'ID' => $post,
        'post_status' => 'pending'
      ) );
	  wp_set_post_series( $post, '', $series_ID);
    }
  }
}

function series_issue_manager_publish_intercept( $post_ID, $post ) {
  $unpublished = get_option( 'im_unpublished_series' );
  $publishable = TRUE;
  // check if post is in an unpublished series
  
  foreach ( get_the_series($post_ID) as $series ) {
	 if ( in_array( $series->term_id, $unpublished ) ) {
      $publishable = FALSE;
      break;
    }
  }
  // if post is in an unpublished series, change its status to 'pending' instead of 'publish'
  if ( !$publishable ) {
    if ($post->post_status != 'publish') return;
	
	wp_update_post( array(
      'ID' => $post_ID,
      'post_status' => 'pending'
    ) );
  }
  return;
}

function series_issue_manager_activation(  ) {
  // if option records don't already exist, create them
  if ( !get_option( 'im_published_series' ) ) {
    add_option( 'im_published_series', array() );
  }
  if ( !get_option( 'im_unpublished_series' ) ) {
    add_option( 'im_unpublished_series', array() );
  }
}
function series_issue_manager_deactivation(  ) {
  // they don't have to exist to be deleted
  delete_option( 'im_published_series' );
  delete_option( 'im_unpublished_series' );
}
function series_issue_manager_scripts(  ) {
  wp_enqueue_script( "series_im_sort_articles", path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) )."/js/series_im_sort_articles.js"), array( 'jquery-ui-sortable' ) );
}

function series_issue_manager_add_series_form() {
	$published = get_option( 'im_published_series' );
	$unpublished = get_option( 'im_unpublished_series' );
	?>
        <div class="form-field">
			<label for="series_publish">
			<p><?php _e('Create as unpublished:', 'organize-series-publisher') ?>
				<input style="float:left; width: 20px;" name="series_publish" id="series_publish" type="checkbox" value="unpublish" /> 
			</p>
				<p><?php _e('When checked, all posts you assign to this series will remain unpublished until you publish the entire series.', 'organize-series-publisher'); ?></p>
			</label>
        </div>
   <?php
}

function series_issue_set_publish_status($series_id, $taxonomy_id) {
	global $_POST;
	extract($_POST, EXTR_SKIP);
	//If "Unpublish" is selected, put series Id into Unpublished array so that new posts in this  
	 //Series are not accidentally published
	if ( !isset($series_publish) ) $series_publish = null;
	if ($series_publish == 'unpublish') {
		$unpublished = get_option( 'im_unpublished_series' );

		  if ( !in_array( $series_id, $unpublished ) ) {
			// add to the unpublished list
			$unpublished[] = $series_id;
			sort( $unpublished );
			update_option( 'im_unpublished_series', $unpublished );
		}
	}
}

add_action('series_add_form_fields', 'series_issue_manager_add_series_form');
add_action('admin_menu', 'series_issue_manager_manage_page');
add_filter('save_post', 'series_issue_manager_publish_intercept',3,2);
add_action('created_series','series_issue_set_publish_status',2,2);
