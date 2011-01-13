<?php

/*
Plugin Name: Month Calendar
Plugin URI: http://sharpcube.com
Description: A calendar widget that shows post count per month.
Version: 1.0
Author: Rodrigo Sieiro
Author URI: http://sharpcube.com
*/

define('MONTH_CALENDAR_VERSION', '1.0');

class Month_Widget_Calendar extends WP_Widget
{
	function Month_Widget_Calendar() 
	{
		$widget_ops = array('classname' => 'month_calendar', 'description' => __( 'A month calendar with posts count') );
		$this->WP_Widget('month_calendar', __('Month Calendar'), $widget_ops);
	}

	function widget( $args, $instance ) 
	{
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title'], $instance, $this->id_base);
		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo '<div id="mc_calendar">';
		mc_get_calendar(true);
		echo '</div>';
		echo '<div id="mc_calendar_loading" style="display: none;">Loading...</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	function form( $instance )
	{
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = strip_tags($instance['title']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		
<?php
	}
}

function mc_widget_init() 
{
	register_widget('Month_Widget_Calendar');
}

function mc_get_calendar($echo = true, $my_year = 0)
{
	global $wpdb, $wp_locale, $posts;

	if ( $my_year == 0 )
		$thisyear = gmdate('Y', current_time('timestamp'));
	else
		$thisyear = ''.intval($my_year);

	$cache = array();
	$key = md5( $thisyear );
	if ( $cache = wp_cache_get( 'get_mcalendar', 'calendar' ) ) {
		if ( is_array($cache) && isset( $cache[ $key ] ) ) {
			if ( $echo ) {
				echo apply_filters( 'get_mcalendar',  $cache[$key] );
				return;
			} else {
				return apply_filters( 'get_mcalendar',  $cache[$key] );
			}
		}
	}

	if ( !is_array($cache) )
		$cache = array();

	if ( !$posts ) {
		$gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1");
		if ( !$gotsome ) {
			$cache[ $key ] = '';
			wp_cache_set( 'get_mcalendar', $cache, 'calendar' );
			return;
		}
	}

	if ( $thisyear == gmdate('Y', current_time('timestamp')))
	{
		$prevyear = intval($thisyear)-1;
		
		$prev_link = "<a class='mc_prev_year' onclick='javascript:mc_reload_calendar($prevyear);' title='$prevyear'>Previous</a>";
		$next_link = "<a class='mc_next_year mc_inactive'>Next</a>";
	}
	else
	{
		$prevyear = intval($thisyear)-1;
		$nextyear = intval($thisyear)+1;
		
		$prev_link = "<a class='mc_prev_year' onclick='javascript:mc_reload_calendar($prevyear);' title='$prevyear'>Previous</a>";
		$next_link = "<a class='mc_next_year' onclick='javascript:mc_reload_calendar($nextyear);' title='$nextyear'>Next</a>";
	}

	$calendar_output = "
	<div class='mc_header'>
		<h2>$thisyear</h2>
		$prev_link
		$next_link
	</div>
	<div class='mc_months'>
		<ul>\n";

	$sql = "select month(post_date), count(1)
	        from   $wpdb->posts
	        where  year(post_date) = '$thisyear'
	        and    post_type = 'post' AND post_status = 'publish'
			and    post_date < '" . current_time('mysql') . "'
	        group  by month(post_date)
	        order  by month(post_date)";

	$month_posts = $wpdb->get_results($sql, ARRAY_N);
	$month_cal = array();
	
	for ($i = 1; $i <= 12; $i++)
	{
		$month_cal[$i] = 0;
	}
	
	foreach ($month_posts as $row)
	{
	    $month_cal[$row[0]] = $row[1];
	}
	
	for ($i = 1; $i <= 12; $i++)
	{
		$mname = $wp_locale->get_month_abbrev($wp_locale->get_month($i));
		$mlink = get_month_link( $thisyear, $i );
		
		if ($month_cal[$i] == 0)
			$calendar_output .= "\t\t\t<li class='mc_m$i mc_inactive'><a>$mname<span class='mc_postcount'>(0 posts)</span></a></li>\n";
		else if ($month_cal[$i] == 1)
			$calendar_output .= "\t\t\t<li class='mc_m$i'><a href='$mlink'>$mname<span class='mc_postcount'>($month_cal[$i] post)</span></a></li>\n";
		else
			$calendar_output .= "\t\t\t<li class='mc_m$i'><a href='$mlink'>$mname<span class='mc_postcount'>($month_cal[$i] posts)</span></a></li>\n";
	}

	$calendar_output .= "\t\t</ul>
	</div>\n";

	$cache[ $key ] = $calendar_output;
	wp_cache_set( 'get_mcalendar', $cache, 'calendar' );

	if ( $echo )
		echo apply_filters( 'get_mcalendar',  $calendar_output );
	else
		return apply_filters( 'get_mcalendar',  $calendar_output );

}

function mc_add_header_code()
{
	$mc_url = plugins_url() . '/' . str_replace(basename( __FILE__), "", plugin_basename(__FILE__));
	
	if (function_exists('wp_enqueue_script') && !is_admin()) 
	{
		// Registers jquery and jquery-ui, if not already registered
		if (!wp_script_is('jquery', 'registered')) wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js', false, '1.4.3');
		if (!wp_script_is('jquery-ui', 'registered')) wp_register_script('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js', array('jquery'), '1.8.6');
		
		// Enqueues the javascript file
		wp_enqueue_script('mc_calendar_js', $mc_url . 'month-calendar.js.php', array('jquery', 'jquery-ui'), MONTH_CALENDAR_VERSION);
	} 
}

function mc_reload_calendar()
{
	// If no year is informed, defaults to 0 (will be treated later)
	if (isset($_GET['my_year']))
		$my_year = intval($_GET['my_year']);
	else
		$my_year = 0;

	// Reloads the calendar	
	mc_get_calendar(true, $my_year);
}

add_action('widgets_init', 'mc_widget_init');
add_action('init', 'mc_add_header_code');