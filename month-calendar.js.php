<?php

	if (!function_exists('add_action'))
	{
    	require_once("../../../wp-config.php");
	}

	header('Content-type: text/javascript');
	$mc_url = plugins_url() . '/' . str_replace(basename( __FILE__), "", plugin_basename(__FILE__));
	$mc_file = $mc_url . 'month-calendar-ajax.php';

?>

function mc_reload_calendar(my_year)
{
	jQuery("#mc_calendar_loading").fadeIn(400);
	
	jQuery.ajax(
	{
		type: "GET",
		url: "<?php echo $mc_file; ?>",
		data: "my_year=" + my_year,
		success: function(msg)
		{
			jQuery("#mc_calendar").html(msg);
			jQuery("#mc_calendar_loading").fadeOut(400);
		},
		error: function()
		{
			jQuery("#mc_calendar_loading").fadeOut(400);
		}
	});
}
