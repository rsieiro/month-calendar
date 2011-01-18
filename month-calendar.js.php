<?php

	// This will load the basic WP options and make our plugin functions accessible
	// It's needed because we're calling this PHP directly and not through WP
	if (!function_exists('add_action'))
	{
    	require_once("../../../wp-config.php");
	}

	// Let the browser know we're outputting javascript
	header('Content-type: text/javascript');
	
	// This URL will always point to the path our plugin files are located
	$mc_url = plugins_url() . '/' . str_replace(basename( __FILE__), "", plugin_basename(__FILE__));
	$mc_file = $mc_url . 'month-calendar-ajax.php';

?>

function mc_reload_calendar(my_year)
{
	// Position the loading div over the calendar header, and fade it in
	jQuery("#mc_calendar_loading").css('top', jQuery("#mc_header").position().top);
	jQuery("#mc_calendar_loading").fadeIn(400);
	
	// The calendar is reloaded via AJAX
	jQuery.ajax(
	{
		type: "GET",
		url: "<?php echo $mc_file; ?>",
		data: "my_year=" + my_year,
		success: function(msg)
		{
			// Replace the calendar with the data returned by the call
			// and fade out the loading div
			jQuery("#mc_calendar").html(msg);
			jQuery("#mc_calendar_loading").fadeOut(400);
		},
		error: function()
		{
			// In case of error, we simply return to the previous state
			// No message is shown
			jQuery("#mc_calendar_loading").fadeOut(400);
		}
	});
}
