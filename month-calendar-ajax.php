<?php

	// This will load the basic WP options and make our plugin functions accessible
	// It's needed because we're calling this PHP directly and not through WP
	if (!function_exists('add_action'))
	{
	    require_once("../../../wp-config.php");
	}
	
	// We simply reload the calendar. The year was informed via a GET parameter
	// and the HTML output will be the new calendar for the requested year
	mc_reload_calendar();

?>