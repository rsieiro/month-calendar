<?php

/*
Plugin Name: Month Calendar
Plugin URI: http://github.com/rsieiro/month-calendar
Description: A calendar widget that shows post count per month.
Version: 1.1
Author: Rodrigo Sieiro
Author URI: http://rodrigo.sharpcube.com
*/

// Plugin version
define('MONTH_CALENDAR_VERSION', '1.1');

// Find the plugin path
// Thanks to Alex King for this code snippet
// http://alexking.org/blog/2011/12/15/wordpress-plugins-and-symlinks
$mc_file = __FILE__;

if (isset($plugin)) {
	$my_plugin_file = $plugin;
} else if (isset($mu_plugin)) {
	$my_plugin_file = $mu_plugin;
} else if (isset($network_plugin)) {
	$my_plugin_file = $network_plugin;
}

// This URL will always point to the path our plugin files are located
define('MC_FILE', $mc_file);
define('MC_PATH', WP_PLUGIN_DIR . '/' . basename(dirname($mc_file)) . '/');
define('MC_URL', plugins_url() . '/' . basename(dirname($mc_file)) . '/');

class Month_Widget_Calendar extends WP_Widget
{
	function Month_Widget_Calendar()
	{
		// Register the widget with WP
		$widget_ops = array('classname' => 'month_calendar', 'description' => __( 'A calendar widget that shows post count per month') );
		$this->WP_Widget('month_calendar', __('Month Calendar'), $widget_ops);
	}

	function widget( $args, $instance ) 
	{
		// Get the instance settings
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title'], $instance, $this->id_base);

		// Show the widget, including title and the loading div		
		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo '<div id="mc_wrapper">';
		echo '<div id="mc_calendar">';
		echo '<script type="text/javascript">';
		echo 'var mc_url = "' . MC_URL . '";';
		echo '</script>';
		mc_get_calendar(true);
		echo '</div>';
		echo '<div id="mc_calendar_loading" style="display: none;">Loading...</div>';
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance )
	{
		// Save the new widget settings for this instance
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['use_css'] = strip_tags($new_instance['use_css']);
		update_option('mc_use_css', $instance['use_css']);
		
		return $instance;
	}

	function form( $instance )
	{
		// Get the instance settings
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'use_css' => '1' ) );
		$title = strip_tags($instance['title']);
		
		// use_css is loaded from general WP options
		$use_css = get_option('mc_use_css', '1');
		
		// Prepare and show the settings for the admin interface
		$checked = '';
		if ($use_css == '1') $checked = 'checked="checked"';
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		<p>
		<input type="checkbox" id="<?php echo $this->get_field_id('use_css'); ?>" name="<?php echo $this->get_field_name('use_css'); ?>" value="1" <?php echo $checked; ?> />
		<label for="<?php echo $this->get_field_id('use_css'); ?>"><?php _e('Include default CSS file'); ?></label>
		</p>
		
<?php
	}
}

function mc_widget_init() 
{
	// We use WP options to save this because we need it in places where
	// there's no instance for the widget, so we can't use widget settings
	add_option('mc_use_css', '1');
	
	register_widget('Month_Widget_Calendar');
}

function mc_get_calendar($echo = true, $my_year = 0)
{
	global $wpdb, $wp_locale, $posts;
	
	if ( $my_year == 0 )
	{
		// If no year came via function parameter, check if we're inside an archive page
		// so we can get the year from there. If not, use the current year
		if (is_year())
		{
			$thisyear = get_query_var('year');
		}
		else if (is_month())
		{
			$thisyear = intval(get_query_var('year')) == 0 ? substr(get_query_var('m'), 0, 4) : get_query_var('year');
		}
		else
		{
			$thisyear = gmdate('Y', current_time('timestamp'));
		}
	}
	else
	{
		// Use the year that came via function parameter
		$thisyear = ''.intval($my_year);
	}

	// We use a simple per-year text cache.
	// Here we check if there's a cache for the requested year, and use it if found
	$cache = array();
	$key = md5( 'month_calendar_' . $thisyear );
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

	// If no cache was found, create an empty one
	if ( !is_array($cache) )
		$cache = array();

	// Check if we have at least one published post, otherwise the calendar will always be empty
	if ( !$posts ) {
		$gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1");
		if ( !$gotsome ) {
			$cache[ $key ] = '';
			wp_cache_set( 'get_mcalendar', $cache, 'calendar' );
			return;
		}
	}

	// If the calendar year is the current year, we disable the "next year" link
	if ( $thisyear == gmdate('Y', current_time('timestamp')))
	{
		$prevyear = intval($thisyear)-1;
		
		$prev_link = "<a id='mc_prev_year' onclick='javascript:mc_reload_calendar($prevyear);' title='$prevyear'>&lt;&lt;</a>";
		$next_link = "<a id='mc_next_year' class='mc_inactive'>&gt;&gt;</a>";
	}
	else
	{
		$prevyear = intval($thisyear)-1;
		$nextyear = intval($thisyear)+1;
		
		$prev_link = "<a id='mc_prev_year' onclick='javascript:mc_reload_calendar($prevyear);' title='$prevyear'>&lt;&lt;</a>";
		$next_link = "<a id='mc_next_year' onclick='javascript:mc_reload_calendar($nextyear);' title='$nextyear'>&gt;&gt;</a>";
	}

	// Let's start the output, including a header
	$calendar_output = "
	<div id='mc_header'>
		$thisyear
		$prev_link
		$next_link
	</div>
	<div class='mc_months'>
		<ul class='mc_list'>\n";

	// This query loads a post count per month for the specified year
	// Only published posts with dates in the past are considered
	$sql = "select month(post_date), count(1)
	        from   $wpdb->posts
	        where  year(post_date) = '$thisyear'
	        and    post_type = 'post' AND post_status = 'publish'
			and    post_date < '" . current_time('mysql') . "'
	        group  by month(post_date)
	        order  by month(post_date)";

	// Run the query and get the results in an array
	$month_posts = $wpdb->get_results($sql, ARRAY_N);
	$month_cal = array();
	
	// First we fill the array with zeroes, so we have an item for each month
	for ($i = 1; $i <= 12; $i++)
	{
		$month_cal[$i] = 0;
	}
	
	// Now we fill the array with post count for months that have it
	foreach ($month_posts as $row)
	{
	    $month_cal[$row[0]] = $row[1];
	}
	
	for ($i = 1; $i <= 12; $i++)
	{
		// We get the month abbreviation according to the current WP locale
		$mname = $wp_locale->get_month_abbrev($wp_locale->get_month($i));
		$mlink = get_month_link( $thisyear, $i );

		// The month link will be disabled if there were no posts
		// Otherwise we show either "1 post" or "n posts"
		if ($month_cal[$i] == 0)
			$calendar_output .= "\t\t\t<li class='mc_month mc_m$i mc_inactive'><a class='mc_link'>$mname<span class='mc_postcount'>(0 posts)</span></a></li>\n";
		else if ($month_cal[$i] == 1)
			$calendar_output .= "\t\t\t<li class='mc_month mc_m$i'><a class='mc_link' href='$mlink'>$mname<span class='mc_postcount'>($month_cal[$i] post)</span></a></li>\n";
		else
			$calendar_output .= "\t\t\t<li class='mc_month mc_m$i'><a class='mc_link' href='$mlink'>$mname<span class='mc_postcount'>($month_cal[$i] posts)</span></a></li>\n";
	}

	// Finish the output
	$calendar_output .= "\t\t</ul>
	</div>\n";

	// Here we save a cache for the current year, so we don't have to load it every time
	// We let WP decide when to expire the cache
	$cache[ $key ] = $calendar_output;
	wp_cache_set( 'get_mcalendar', $cache, 'calendar' );

	// Depending on the function parameter, we either show or return the output
	if ( $echo )
		echo apply_filters( 'get_mcalendar',  $calendar_output );
	else
		return apply_filters( 'get_mcalendar',  $calendar_output );

}

function mc_add_header_code()
{
	// Get our use_css setting from WP options
	$use_css = get_option('mc_use_css', '1');
	
	
	if (function_exists('wp_enqueue_script') && !is_admin()) 
	{
		// Registers jquery and jquery-ui, if not already registered
		if (!wp_script_is('jquery', 'registered')) wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js', false, '1.4.3');
		if (!wp_script_is('jquery-ui', 'registered')) wp_register_script('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js', array('jquery'), '1.8.6');
		
		// Enqueues the javascript file
		wp_enqueue_script('mc_js', MC_URL . 'month-calendar.js', array('jquery', 'jquery-ui'), MONTH_CALENDAR_VERSION);
		
		// Only enqueue the CSS file if the user wants to
		if ($use_css) {
			wp_enqueue_style('mc_style', MC_URL . 'month-calendar.css', array(), MONTH_CALENDAR_VERSION);
		}
	} 
}

function mc_reload_calendar()
{
	// The year will come via a GET parameter
	// But if no year is informed, defaults to 0 (will be treated later)
	if (isset($_GET['my_year']))
		$my_year = intval($_GET['my_year']);
	else
		$my_year = 0;

	// Reload the calendar	
	mc_get_calendar(true, $my_year);
}

add_action('widgets_init', 'mc_widget_init');
add_action('init', 'mc_add_header_code');