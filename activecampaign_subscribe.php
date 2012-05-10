<?php
/*
Plugin Name: ActiveCampaign Email Marketing
Plugin URI: http://www.activecampaign.com/email-marketing/extend-wordpress.php
Description: The ActiveCampaign email marketing plugin connects Wordpress with your email marketing software and allows you to choose a subscription form to embed (as a widget) anywhere on your site.  After enabling go to Appearance > Widgets to activate this plugin.
Author: ActiveCampaign
Version: 2.1
Author URI: http://www.activecampaign.com/
*/

# Changelog
## version 1: - initial release
## version 1.1: Verified this works with latest versions of WordPress and ActiveCampaign; Updated installation instructions
## version 2.0: Re-configured to work with ActiveCampaign version 5.4. Also improved some areas.
## version 2.1: Changed internal API requests to use only API URL and Key instead of Username and Password. Also provided option to remove style blocks, and converting <input type="button" into <input type="submit"

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
		if (!(int)$options_form["form_fetch"]) {
			// if it's set to display the static HTML saved in the Wordpress database
			if ($options_form["form_html"]) {
				$form_html = $options_form["form_html"];

				if ( (int)$options_form["remove_css"] ) {
					// remove <style> block
					preg_match_all("|<style[^>]*>(.*)</style>|iUs", $form_html, $matches);
					if ( isset($matches[0]) and count($matches[0]) > 0 ) {
						$form_html = str_replace($matches[0], "", $form_html);
					}
				}

				// replace <input type="button" with <input type="submit" (otherwise the form won't submit)
//print_r($matches);exit();
				$form_html = preg_replace("/<input type=[\"']?button[\"']? value=[\"']?Subscribe[\"']?>/i", "<input type=\"submit\" value=\"Subscribe\">", $form_html);
				echo $form_html;
			}
		}
		else {
			// If it's set to fetch the form each time (using the API)
			if ($options_site["api_url"] && $options_site["api_key"] && $options_form["form_id"]) {

				$api_url = $options_site["api_url"] . "admin/api.php?api_key=" . $options_site["api_key"] . "&api_action=form_view&api_output=serialize&id=" . $options_form["form_id"];
				$api_result = ac_subscribe_curl_get($api_url);

				// for some reason the very first character of the string is "s" instead of "<". Example: sstyle>
				$api_result = preg_replace("/^s?/i", "<", $api_result);

				if ( (int)$options_form["remove_css"] ) {
					// remove <style> block
					preg_match_all("|<style[^>]*>(.*)</style>|iUs", $api_result, $matches);
					if ( isset($matches[0]) and count($matches[0]) > 0 ) {
						$api_result = str_replace($matches[0], "", $api_result);
					}
				}

				// replace <input type="button" with <input type="submit" (otherwise the form won't submit)
				$api_result = preg_replace("/<input type=[\"']?button[\"']? value=[\"']?Subscribe[\"']?>/i", "<input type=\"submit\" value=\"Subscribe\">", $api_result);
				echo $api_result;
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
			// Hidden form element which is set to true when that part of the form is submitted
			if ($_POST["ac_subscribe_form_submit"] == true) {

				// SECOND submit

				$api_url = $options_site["api_url"] . "admin/api.php?api_key=" . $options_site["api_key"] . "&api_action=form_view&api_output=serialize&id=" . $_POST["ac_subscribe_form_id"];

				$api_result = ac_subscribe_curl_get($api_url);
//print_r($api_result);exit();

				if (is_array($api_result) && isset($api_result["result_code"]) && !(int)$api_result["result_code"]) {
					echo "
					<p><span style=\"color: red; font-weight: bold;\">Connection failed.</span> Here is the message returned:</p>
					<p><span style=\"font-weight: bold;\">" . $api_result["result_message"] . "</span></p>
					<p>API URL:<br /><a href=\"" . $api_url . "\">" . $api_url . "</a></p>
					";
					exit();
				}

				$options_form_update["form_id"] = $_POST["ac_subscribe_form_id"];
				// for some reason the very first character of the string is "s" instead of "<". Example: sstyle>
				$api_result = preg_replace("/^s?/i", "<", $api_result);
				$options_form_update["form_html"] = $api_result;
				$options_form_update["form_fetch"] = ( isset($_POST["ac_subscribe_form_fetch"]) ) ? 1 : 0;
				$options_form_update["remove_css"] = ( isset($_POST["ac_subscribe_remove_css"]) ) ? 1 : 0;

				ac_subscribe_options_form_update($options_form_update);

				echo "Settings saved! Visit the public side to see the form in the sidebar. Refresh this page to go back and choose a different form.";

				exit();
			}

			if ($_POST["api_url"] != "" && $_POST["api_url"] != "/" && $_POST["api_key"] != "") {

				// FIRST submit

				// Update options_site values from form to database.
				// Also set friendly-named variables for use further down.
				// We use the form element values to conduct the API call - not the saved database values.
				// In other words, we don't save, then pull the same values. We just use the values they provided in the form.
				// For p_link, check for trailing slash - if there is not one, add it before saving.
				$options_site_update["api_url"] = $api_url = ( substr($_POST["api_url"], -1, 1) != "/" ) ? $_POST["api_url"] . "/" : $_POST["api_url"];
				$options_site_update["api_key"] = $api_key = $_POST["api_key"];

				ac_subscribe_options_site_update($options_site_update);

				if ($api_url && $api_key) {

					$api_url = $api_url . "admin/api.php?api_key=" . $api_key . "&api_action=form_list&api_output=serialize";
//print_r($api_url);

					$api_result = ac_subscribe_curl_get($api_url);
//print_r($api_result["result_code"]);exit();
					// If the result code is 0, meaning the URL, username, or password could be incorrect,
					// or they don't have the form_list API call (using an older version)
					if (!(int)$api_result["result_code"]) {

						echo "

						<p><span style=\"color: red; font-weight: bold;\">" . $api_result["result_message"] . "</span></p>

						<p style=\"font-size: 0.9em;\">Please make sure that your login information is correct, and that you have at least one integration form set up already.</p>

						";

						// Show login form again
						ac_subscribe_admin_login();

						exit();
					}

					// Start second page of the form

					echo "

					<p>Please select the form to display:</p>

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
				}
				else {

					ac_subscribe_admin_login();
				}

				?>

				<input type="checkbox" name="ac_subscribe_form_fetch" id="ac_subscribe_form_fetch" />
				<label for="ac_subscribe_form_fetch">Fetch form with each page load?</label>

				<br />

				<input type="checkbox" name="ac_subscribe_remove_css" id="ac_subscribe_remove_css" />
				<label for="ac_subscribe_remove_css">Remove embedded CSS? (Can often conflict with WordPress styles.)</label>

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
	curl_setopt($api_request, CURLOPT_FOLLOWLOCATION, true);
	$api_response = curl_exec($api_request);
	curl_close($api_request);
//print_r($api_response);exit();
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
		Your ActiveCampaign API URL:
		<input type="text" name="api_url" id="api_url" value="<?php echo $options_site["api_url"]; ?>" style="width: 99%;" />
	</p>

	<p>
		Your ActiveCampaign API Key:
		<input type="text" name="api_key" id="api_key" value="<?php echo $options_site["api_key"]; ?>" style="width: 99%;" />
	</p>

	<?php
}

function ac_subscribe_init() {
	register_sidebar_widget("ActiveCampaign Subscription Form", "widget_ac_subscribe_public");
	register_widget_control("ActiveCampaign Subscription Form", 'widget_ac_subscribe_admin');
}

add_action("plugins_loaded", "ac_subscribe_init");

?>