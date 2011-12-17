function mc_reload_calendar(my_year)
{
	// Position the loading div over the calendar header, and fade it in
	jQuery("#mc_calendar_loading").css('top', jQuery("#mc_header").position().top);
	jQuery("#mc_calendar_loading").fadeIn(400);
	
	// The calendar is reloaded via AJAX
	jQuery.ajax(
	{
		type: "GET",
		url: mc_url + "month-calendar-ajax.php",
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
