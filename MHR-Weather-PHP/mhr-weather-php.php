<?php
/*
Plugin name: MHR-Weather
Version: 1.0
Description: A plugin to display current and forecasted weather for a user-defined location
Author: Michael Reinders
Author URI: https://michael-reinders.com
*/
//ini_set("allow_url_fopen", 1);
if(!class_exists("MHR_Weather")) {
	class MHR_Weather {
		function MHR_Weather() {

		}
	}
}
if (class_exists("MHR_Weather")) {
	$MHR_Weather = new MHR_Weather();
}
if(isset($MHR_Weather)) {
	//Actions
	add_action( 'admin_menu', 'Weather_Admin_Link' );
	// Add a new top level menu link to the ACP
	function Weather_Admin_Link()	{
	      add_menu_page(
	        'MHR Weather Plugin Settins', // Title of the page
	        'MHR-Weather', // Text to show on the menu link
	        'manage_options', // Capability requirement to see the link
	        __FILE__, // The 'slug' - file to display when clicking the link
	        'mhr_weather_settings_page' //calls the function to populate page html
	    );
	      //call the register settings function
	      add_action('admin_init', 'register_mhr_weather_settings');
	}
	function register_mhr_weather_settings() {
		//register settings
		register_setting('mhr_weather_settings_group', 'weather-location-zip');
		register_setting('mhr_weather_settings_group', 'weather-display-type');
		register_setting('mhr_weather_settings_group', 'weather-display-location');
		register_setting('mhr_weather_settings_group', 'weather-display-align');
	}
	//show admin page
	function mhr_weather_settings_page() { 
?>
<div class="wrap">
<h1>MHR Weather Plugin</h1>
<form method="post" action="options.php">
    <?php settings_fields( 'mhr_weather_settings_group' ); ?>
    <?php do_settings_sections( 'mhr_weather_settings_group' ); ?>
    <label name="weather-location-zip">Enter your Zip Code</label>
    <input type="text" name="weather-location-zip" value="<?php echo esc_attr(get_option('weather-location-zip')); ?>" /><br />
    <label name="weather-display-type">Which do you want to display?</label><br />
    <input type="radio" name="weather-display-type" value="current" <?php checked('current',  get_option('weather-display-type')); ?> />Current Weather Conditions (Sidebar)<br />
    <input type="radio" name="weather-display-type" value="forecast" <?php checked('forecast', get_option('weather-display-type')); ?> />5 Day Forecast (before Footer)<br />
    <input type="radio" name="weather-display-type" value="shortcode" <?php checked('shortcode', get_option('weather-display-type')); ?> />Use a Shortcode<br />
    <?php if (get_option('weather-display-type') == 'shortcode') {
     	echo '<p>Please Copy the following code(s) and paste them wherever you wish to display the weather.<br /><span style="font-size:9px;"> *May be incompatible with Gutenberg Editor. If you see a "Upade Failed" error when saving the page: please ignore. <br>Simply refresh the page, clicking OK or Reload if an alert shows, and the Weather should be visible on the page.</span></p>';
     	echo '<ul><li><i>Current Weather Conditions:</i> [MHR_Current_Weather_Conditions]</li>';
     	echo '<li><i>5 Day Forecast:</i> [MHR_Five_Day_Forecast]</li></ul>';
     } ?>
    <?php submit_button(); ?>
</form>
</div>
<?php } ?>
<?php
if (get_option('weather-display-type') == 'current') {
	add_action('wp_meta', 'mhr_process_current_weather');
} elseif (get_option('weather-display-type') == 'forecast') {
	add_action("get_footer", "mhr_process_weather_forecast");
} else {
	add_shortcode('MHR_Five_Day_Forecast', 'mhr_shortcode_forecast');
	add_shortcode('MHR_Current_Weather_Conditions', 'mhr_shortcode_current');
}
// Actual functions for building weather app
	function mhr_process_weather_forecast() {
		//Initialize the Zip code
		$zip = esc_attr(get_option('weather-location-zip'));
		// use Curl for forecast_URL
		$handle_f = curl_init();
		$forecast_url = 'https://api.openweathermap.org/data/2.5/forecast?zip=' . "$zip" . ',us&APPID=2cdcc7fc74739e655ab9bf5e352304ba&units=imperial';
		// Set the url
		curl_setopt($handle_f, CURLOPT_URL, $forecast_url);
		// Set the result output to be a string.
		curl_setopt($handle_f, CURLOPT_RETURNTRANSFER, true);
		$forecast_output = curl_exec($handle_f);
		curl_close($handle_f);
		// display the forecast weather info
	    $data = json_decode($forecast_output);
	    echo '<div style="color: grey; margin: 1%; display: inline-block; width: 100%;">';
	    echo '<hr />';
	    echo '<h1 style="text-align: center; margin: 1%; padding: 0;">', $data->city->name, ' (', $data->city->country, ') Forecast</h1>';
    	// the hour forecasted is 12hr from the time the query is made, so we need to get the 
	    // we need to get the forecaste time of index0, then calculate the x value to give us noon forecasts
	    $first_dt = $data->list[0]->dt_txt;
	    $jsonDateTime = new DateTime($dt_txt);
	    $first_hour = $jsonDateTime->format('H');
	    $x = 0;
	    if ($first_hour <= 12) {
	    	$x = floor((12 - $first_hour)/3);
	    } else {
	    	$x = floor((((24 - $first_hour) + 12)/3));
	    };
	    // loop each day for 5 days
	    for ($x; $x <= 39; $x+=8) {
	    	// store variables for weather data
	    	$dt_txt = $data->list[$x]->dt_txt;
		    $dateTime = new DateTime($dt_txt);
		    $day = $dateTime->format('D');
		    $time = $dateTime->format('g:i a');
		    $hour = $dateTime->format('H');
		    $description = $data->list[$x]->weather[0]->description;
		    $max_temp = $data->list[$x]->main->temp_max;
		    $min_temp = $data->list[$x]->main->temp_min;
		    // Create HTML output
		    echo '<div style="color: grey; width: 16%; margin: 0 2%; display: inline-block; text-align: center;">';
		    echo '<p style="font-size: 3vw; margin: 0;">', $day, '</p>';
		    // Set the correct image
		    if (strpos($description, 'cloud') !== false) {
		    	echo '<img style="width: 75%;" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Cloudy.png" alt="cloudy" />';
		    } elseif (strpos($description, 'rain') !== false) {
		    	echo '<img style="width: 75%;" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Rain.png" alt="rain" />';
		    } elseif (strpos($description, 'snow') !== false) {
		    	echo '<img style="width: 75%;" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Snow.png" alt="snow" />';
		    } else {
		    	if ($hour < 5 || $hour > 18) {
		    		echo '<img style="width: 75%;" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Clear_Night.png" alt="clear" />';
		    	} else {
		    		echo '<img style="width: 75%;" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Clear_Day.png" alt="clear" />';
		    	}
		    };
	    	echo '<p style="font-size: 2.5vw; margin: 0;">', $max_temp, '&deg; F</p>';
			echo '<p style="font-size: 2vw; margin: 0;"> ', $description, '</p>';
	    	echo '</div>';
	    }
	    	echo '</div>'; 
	    	return null;
	}
	// function for the Current Weather in Sidebar
	function mhr_process_current_weather() {
		//Initialize the Zip code
		$zip = esc_attr(get_option('weather-location-zip'));
		// use Curl for current_URL
		$handle_c = curl_init();
		$current_url = 'https://api.openweathermap.org/data/2.5/weather?zip=' . "$zip" . ',us&APPID=2cdcc7fc74739e655ab9bf5e352304ba&units=imperial';
		// Set the url
		curl_setopt($handle_c, CURLOPT_URL, $current_url);
		// Set the result output to be a string.
		curl_setopt($handle_c, CURLOPT_RETURNTRANSFER, true);
		$current_output = curl_exec($handle_c);
		curl_close($handle_c);
		//$json = file_get_contents($current_output);
	    $data = json_decode($current_output);
	    $city = $data->name;
	    $dt = $data->dt;
	    $sunrise = $data->sys->sunrise;
	    $sunset = $data->sys->sunset;
	    $country = $data->sys->country;
	    $description = $data->weather[0]->description;
	    $current_temp = $data->main->temp;
	    $min_temp = $data->main->temp_min;
	    $max_temp = $data->main->temp_max;
	    // Create HTML output
	    echo '<h1 style="margins: 0; font-size: 16px; text-align: center; font-weight: normal; color: grey;">My Current Weather Conditions</h1>';
	    echo '<div style="margin: 1%; display: table; width: 100%; text-align: center;">';
	    echo '<div style="vertical-align: middle; width: 30%; display: table-cell; margin: auto .75% auto 1.75%;"><h1 style="margin: 0; font-size: 2vw;">', $city, ' (', $country, ')</h1>';
	    echo '<p style="font-size: 1vw; margin: 0;">', $description, '</p></div>';
	    echo '<div style="vertical-align: middle; width: 30%; display: table-cell;"">';
	    // choose the correct image based on description
	    if (strpos($description, 'cloud') !== false) {
	    	echo '<img style="width: 100%" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Cloudy.png" alt="cloudy" />';
	    } elseif (strpos($description, 'rain') !== false) {
	    	echo '<img style="width: 100%" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Rain.png" alt="rain" />';
	    } elseif (strpos($description, 'snow') !== false) {
	    	echo '<img style="width: 100%" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Snow.png" alt="snow" />';
	    } else {
	    	if ($dt < $sunrise || $dt > $sunset) {
	    		echo '<img style="width: 100%" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Clear_Night.png" alt="clear" />';
	    	} else {
	    		echo '<img style="width: 100%" src="' . plugin_dir_url(dirname( __FILE__ )) . 'MHR-Weather-PHP/images/mhr_Clear_Day.png" alt="clear" />';
	    	}
	    };
	    echo '</div>';
	    echo '<div style="vertical-align: middle; width: 30%; margin: 0 1.5% 0 .75%; display: table-cell;"><h2 style="font-size: 2.5vw; padding: 0; margin: 0;">', round($current_temp, 0), '&deg; F </h2>';
	    echo '<p style="font-size: 1vw; margin: 0;"> ', $min_temp, '&deg; F | ';
	    echo $max_temp, '&deg; F</p></div>';
	    echo '</div>';
	}
	function mhr_shortcode_current() {
		$stringReturn = mhr_process_current_weather();
		return $stringReturn;
	}
	function mhr_shortcode_forecast() {
		$stringReturn = mhr_process_weather_forecast();
		return $stringReturn;
	}
}