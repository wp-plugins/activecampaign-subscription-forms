<?php
/*
Plugin Name: ActiveCampaign Email Marketing
Plugin URI: http://www.activecampaign.com/email-marketing/extend-wordpress.php
Description: The ActiveCampaign email marketing plugin connects Wordpress with your email marketing software and allows you to choose a subscription form to embed (as a widget) anywhere on your site.  After enabling go to Appearance > Widgets to activate this plugin.
Author: ActiveCampaign
Version: 1
Author URI: http://www.activecampaign.com/
*/

# Changelog
## version 1: - initial release

function widget_ac_subscribe_public($args = false) {

	$options_site = ac_subscribe_options_site_get();
	$options_form = ac_subscribe_options_form_get();

	if ($args) extract($args);

	echo $before_widget;
	echo $before_title;

	if ( !function_exists(curl_init) ) {

		ac_subscribe_curl_fail();
	}
	else {

		// If it's set to fetch the form each time (using the API), or display the static HTML saved in the Wordpress database
		if (!$options_form["form_fetch"]) {

			if ($options_form["form_html"]) {
				echo $options_form["form_html"];
			}
		}
		else {

			if ($options_site["p_link"] && $options_site["username"] && $options_site["password"] && $options_form["form_id"]) {

				$api_url = $options_site["p_link"] . "admin/api.php?api_user=" . $options_site["username"] . "&api_pass=" . $options_site["password"] . "&api_action=form_view&api_output=serialize&id=" . $options_form["form_id"] . "&generate=1";

				$api_result = ac_subscribe_curl_get($api_url);

				echo $api_result["html"];
			}
		}
	}

	//echo print_r($options_site);
	//echo print_r($options_form);

	echo $after_title;
	echo $after_widget;
}

function widget_ac_subscribe_admin() {

	$options_site = ac_subscribe_options_site_get();
	$options_form = ac_subscribe_options_form_get();

	if ( !function_exists(curl_init) ) {

		ac_subscribe_curl_fail();
	}
	else {

		if ($_SERVER["REQUEST_METHOD"] == "POST") {

			// The second part of the form, where we ask which form they want to use.
			// Have to run this section of code first, since we exit right away once this is run.
			if ($_POST["ac_subscribe_form_submit"]) {

				$api_url = $options_site["p_link"] . "admin/api.php?api_user=" . $options_site["username"] . "&api_pass=" . $options_site["password"] . "&api_action=form_view&api_output=serialize&id=" . $options_form["form_id"] . "&generate=1";

				$api_result = ac_subscribe_curl_get($api_url);

				$options_form_update["form_id"] = $_POST["ac_subscribe_form_id"];
				$options_form_update["form_html"] = $api_result["html"];
				$options_form_update["form_fetch"] = ($_POST["ac_subscribe_form_fetch"]) ? 1 : 0;

				ac_subscribe_options_form_update($options_form_update);

				echo "

				Settings saved! Visit the public side to see the form in the sidebar. Refresh this page to go back and choose a different form.

				";

				exit();
			}

			// Update options_site values from form to database.
			// Also set friendly-named variables for use further down.
			// We use the form element values to conduct the API call - not the saved database values.
			// In other words, we don't save, then pull the same values. We just use the values they provided in the form.
			// For p_link, check for trailing slash - if there is not one, add it before saving.
			$options_site_update["p_link"] = $p_link = ( substr($_POST["p_link"], -1, 1) != "/" ) ? $_POST["p_link"] . "/" : $_POST["p_link"];
			$options_site_update["username"] = $username = $_POST["username"];
			$options_site_update["password"] = $password = $_POST["password"];

			ac_subscribe_options_site_update($options_site_update);

			if ($p_link && $username && $password) {

				$api_url = $p_link . "admin/api.php?api_user=" . $username . "&api_pass=" . $password . "&api_action=form_list&api_output=serialize&ids=all";

				$api_result = ac_subscribe_curl_get($api_url);

				// If the result code is 0, meaning the URL, username, or password could be incorrect,
				// or they don't have the form_list API call (using an older version)
				if (!$api_result["result_code"]) {

					echo "

					<p><span style=\"color: red; font-weight: bold;\">Connection failed.</span> Here is the message returned:</p>

					";

					echo "

					<p><span style=\"font-weight: bold;\">" . $api_result["result_message"] . "</span></p>

					<p style=\"font-size: 0.9em;\">Please make sure that your login information is correct, and that you are using a version of ActiveCampaign Email Marketing that supports
					API actions for subscription forms (began in version 5.0.16).</p>

					";

					// Show login form again
					ac_subscribe_admin_login();

					exit();
				}

				// Start second page of the form

				echo "

				<p>Please select the form you'd like to display:</p>

				";

				// Loop through each array item in the result
				// Remember, the result contains other items like "result_code"
				foreach ($api_result as $k => $v) {

					// Only look at array items that are not result_code, result_message, or result_output (included at the end of the result array)
					if ( $k === 0 || intval($k) ) {

						echo "

						<p>

							<input type=\"radio\" name=\"ac_subscribe_form_id\" id=\"form_id_" . $v["id"] . "\" value=\"" . $v["id"] . "\" />

							";

							echo "<label for=\"form_id_" . $v["id"] . "\">" . $v["name"] . "</label>";

							echo "

						</p>

						";
					}
				}
				?>

				<input type="checkbox" name="ac_subscribe_form_fetch" id="ac_subscribe_form_fetch" />
				<label for="ac_subscribe_form_fetch">Fetch form with each page load?</label>

				<input type="hidden" name="ac_subscribe_form_submit" value="true" />

				<?php
			}
		}
		else {

			ac_subscribe_admin_login();
		}
	}
}

function ac_subscribe_options_site_get() {

	$options_site = get_option("widget_ac_subscribe_site");

	return $options_site;
}

function ac_subscribe_options_site_update($options_site) {

	update_option("widget_ac_subscribe_site", $options_site);
}

function ac_subscribe_options_form_get() {

	$options_form = get_option("widget_ac_subscribe_form");

	return $options_form;
}

function ac_subscribe_options_form_update($options_form) {

	update_option("widget_ac_subscribe_form", $options_form);
}

function ac_subscribe_curl_get($api_url) {

	$api_request = curl_init($api_url);
	curl_setopt($api_request, CURLOPT_HEADER, 0);
	curl_setopt($api_request, CURLOPT_RETURNTRANSFER, 1);
	$api_response = (string)curl_exec($api_request);
	curl_close($api_request);
	$api_result = unserialize($api_response);

	return $api_result;
}

function ac_subscribe_curl_fail() {
	echo "<b>CURL is not supported on your server (introduced in PHP 4.0.2).</b> Please enable it to use this widget.";
}

function ac_subscribe_admin_login() {

	$options_site = ac_subscribe_options_site_get();

	?>

	<p>
		Your Software URL:
		<input type="text" name="p_link" id="p_link" value="<?php echo $options_site["p_link"]; ?>" style="width:99%;" />
	</p>

	<p>
		Your Software Username:
		<input type="text" name="username" id="username" value="<?php echo $options_site["username"]; ?>" style="width:99%;" />
	</p>

	<p>
		Your Software Password:
		<input type="password" name="password" id="password" value="<?php echo $options_site["password"]; ?>" style="width:99%;" />
	</p>

	<?php
}

function ac_subscribe_init() {
	register_sidebar_widget("ActiveCampaign Subscription Form", "widget_ac_subscribe_public");
	register_widget_control("ActiveCampaign Subscription Form", 'widget_ac_subscribe_admin');
}

add_action("plugins_loaded", "ac_subscribe_init");

?>