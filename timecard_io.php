<?php 
/**
 * 
 * CashPress v3.0
 * Custom post type and admin input fields for timecard data
 * 
**/

add_action('init', 'timecards');

function timecards(){
  $labels = array(
    'name' => _x('Timecards', 'post type general name'),
    'singular_name' => _x('Timecard', 'post type singular name'),
    'add_new' => _x('New Timecard', 'timecard'),
    'add_new_item' => __('Create New Timecard'),
    'edit_item' => __('Edit Timecard'),
    'new_item' => __('New Timecard'),
    'view_item' => __('View Timecard'),
    'search_items' => __('Search Timecards'),
    'not_found' =>  __('No timecards found'),
    'not_found_in_trash' => __('No timecards found in Trash'), 
    'parent_item_colon' => ''
  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'exclude_from_search' => true,
    'show_ui' => true, 
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'post',
    'hierarchical' => false,
    'menu_position' => 25,
    'menu_icon' => plugins_url() . '/timecards/images/timecards.png',
    'supports' => array('')
  ); 
  register_post_type('timecards',$args);
}

// Add filter to insure the timecard is displayed when user updates a timecard

add_filter('post_updated_messages', 'timecard_updated_messages');
function timecard_updated_messages( $messages ) {

  $messages['timecards'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Timecard updated. <a href="%s">View Timecard</a>'), esc_url( get_permalink(@$post_id) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Timecard updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Timecard restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Timecard published. <a href="%s">View timecard</a>'), esc_url( get_permalink(@$post_ID) ) ),
    7 => __('Timecard saved.'),
    8 => sprintf( __('Timecard submitted. <a target="_blank" href="%s">Preview timecard</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink(@$post_ID) ) ) ),
    9 => sprintf( __('Timecard scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview timecard</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( @$post->post_date ) ), esc_url( get_permalink(@$post_ID) ) ),
    10 => sprintf( __('Timecard draft updated. <a target="_blank" href="%s">Timecard</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink(@$post_ID) ) ) ),
  );

  return @$messages;
}

$tcd_pluginsurl = plugins_url();

// Styling for the custom post type editor icon
add_action( 'admin_head', 'tcd_editor_icons' );
function tcd_editor_icons() {
    ?>
    <style type="text/css" media="screen">
    #icon-edit.icon32-posts-timecards {background: url(../wp-content/plugins/timecards/images/timecard_32.png) no-repeat;}
    </style>
<?php }
  
/*========================= First Custom Field Section ========================*/
	function timecard_metadata(){  
        global $post; 
        $custom = get_post_custom($post->ID);  
        $savedtimedata = $custom["savedtimedata"][0]; 
        $savedstatus = $custom["savedstatus"][0]; 


        
        echo '<input type="hidden" name="tcd-nonce" id="tcd-nonce" value="' .wp_create_nonce('tc-d'). '" />';
?>
<?php global $current_user;
      get_currentuserinfo();
?>
<?php echo $tcd_pluginsurl; ?>
<div class="timecard_content">
<button id="clockinout1" class="button-primary" onClick="get_time(); changeState(1); "><?php if ($savedstatus != ''){ echo $savedstatus; } else { echo 'Clock In'; } ?></button>

<div id="stored"><?php echo $savedtimedata; ?></div>
<input type="text" HIDDEN id="savedtimedata" name="savedtimedata" value="<?php echo $savedtimedata; ?>"/>
<input type="text" HIDDEN id="currentuser" name="currentuser" value="<?php echo $current_user->user_login; ?>"/>
<input type="text" HIDDEN id="savedstatus" name="savedstatus" value="<?php if ($savedstatus != ''){ echo $savedstatus; } else { echo 'Clock In'; } ?>"/>
<div id="linebreak"><br></div>
<div id="insertinput"><input type="text" id="time_simple" value=""/></div>

</div>
<script type="text/javascript">
function get_time() {
var my_time = new Date();
var the_minute = my_time.getMinutes();
var the_hour = my_time.getHours();

document.getElementById("savedtimedata").value = document.getElementById("savedtimedata").value + document.getElementById("linebreak").innerHTML + "<p id='" + document.getElementById("savedstatus").value + "'>" + document.getElementById("currentuser").value + ' ' + document.getElementById("savedstatus").value + document.getElementById("linebreak").innerHTML + my_time; + '<input type="text" id="time_simple">' + '</input>' + '</p>'

}
function changeState(idElement) {
    var clockinout = document.getElementById('clockinout' + idElement);
    if (idElement === 1) {
        if (clockinout.innerHTML === 'Clock In') clockinout.innerHTML = 'Clock Out';
        else {
            clockinout.innerHTML = 'Clock In';
        }
    }
document.getElementById("savedstatus").value = document.getElementById("clockinout1").innerHTML
}
</script>

<?php  
}  
    
function add_timecard_metadata(){  
        add_meta_box('timecard_metadata', __('Timecard Details', 'tcd_timecard_metadata'), 'timecard_metadata', 'timecards', 'normal', 'low');  
} 
    
add_action('admin_init', 'add_timecard_metadata'); 
   

	
/*===================== Create Post Titles Using Meta Data=================*/
   

function create_tcd_title_meta($meta_data_title){
     global $post;
     if ($post->post_type == 'timecards') {
	 $meta_data_title = @$_POST['currentuser'] . "'s Timecard Ending " . date('l F jS Y');
;
     }
     return $meta_data_title;
}
add_filter('title_save_pre','create_tcd_title_meta');


/*====================== Saves all Custom Field Data ======================*/    
function save_meta_timecard($post_id){  
		
		if (!wp_verify_nonce(@$_POST['tcd-nonce'], 'tc-d')) return $post_id;
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
		update_post_meta(@$post_id, "savedtimedata", @$_POST["savedtimedata"]);
	   	update_post_meta(@$post_id, "savedstatus", @$_POST["savedstatus"]);
	   	update_post_meta(@$post_id, "currentuser", @$_POST["currentuser"]);	      
}  
	
	
add_action('save_post', 'save_meta_timecard'); 

