<?php
/*
Plugin Name: ActiveCampaign
Plugin URI: http://www.activecampaign.com/extend-wordpress.php
Description: This plugin connects WordPress with your ActiveCampaign software and allows you to embed a subscription form on your site.
Author: ActiveCampaign
Version: 4.5
Author URI: http://www.activecampaign.com
*/

# Changelog
## version 1: - initial release
## version 1.1: Verified this works with latest versions of WordPress and ActiveCampaign; Updated installation instructions
## version 2.0: Re-configured to work with ActiveCampaign version 5.4. Also improved some areas.
## version 2.1: Changed internal API requests to use only API URL and Key instead of Username and Password. Also provided option to remove style blocks, and converting `input type="button"` into `input type="submit"`
## version 3.0: Re-wrote widget backend to use most recent WordPress Widget structure. Improvements include streamlined code and API usage, ability to reset or refresh your forms, and better form width detection.
## version 3.5: You can now use a shortcode to display your subscription form.
## version 4.0: Added many additional settings to control how your form is displayed and submitted.
## version 4.5: Added ActiveCampaign to the Settings menu so you can use the shortcode independent of the widget.

define("ACTIVECAMPAIGN_URL", "");
define("ACTIVECAMPAIGN_API_KEY", "");
require_once "activecampaign-api-php/ActiveCampaign.class.php";

class ActiveCampaign_Widget extends WP_Widget {

  public function __construct() {
    // widget actual processes
    parent::__construct(
      "activecampaign_widget",
      "ActiveCampaign",
      array("description" => __("Add your ActiveCampaign subscription form to your site", "text_domain"),)
    );
  }

  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
  public function form($instance) {

    // outputs the options form on admin

    // alert/error message
    if (isset($instance["error"]) && $instance["error"]) {

      ?>

      <p style="color: red; font-weight: bold;"><?php echo $instance["error"]; ?></p>

      <?php

    }

    // positive message
    if (isset($instance["message"]) && $instance["message"]) {

      ?>

      <p style="color: green; font-size: 1.2em; font-weight: bold;"><?php echo $instance["message"]; ?></p>

      <?php

    }

    $instance["step"] = (isset($instance["step"]) && (int)$instance["step"]) ? $instance["step"] : 1;

    ?>

    <input type="hidden" name="<?php echo $this->get_field_name("step"); ?>" id="activecampaign_step" value="<?php echo $instance["step"]; ?>" />

    <?php

    switch ($instance["step"]) {

      case 1:

      	$api_url = (isset($instance["api_url"])) ? $instance["api_url"] : "";
      	$api_key = (isset($instance["api_key"])) ? $instance["api_key"] : "";

        ?>

        <p>
          API URL:
          <input type="text" name="<?php echo $this->get_field_name("api_url"); ?>" id="activecampaign_api_url" value="<?php echo esc_attr($api_url); ?>" style="width: 99%;" />
        </p>

        <p>
          API Key:
          <input type="text" name="<?php echo $this->get_field_name("api_key"); ?>" id="activecampaign_api_key" value="<?php echo esc_attr($api_key); ?>" style="width: 99%;" />
        </p>

        <?php

      break;

      case 2:

        ?>

        <p><b>Choose a form to display:</b></p>

        <?php

        if ($instance["forms"]) {

        	// just a flag to know if ANY form is checked (chosen)
        	$form_checked = 0;

          foreach ($instance["forms"] as $form) {

          	$checked = "";
          	if (isset($instance["form_id"]) && (int)$instance["form_id"] && (int)$instance["form_id"] == (int)$form["id"]) {
          		$checked = "checked=\"checked\"";
          		$form_checked = 1;
          	}

            ?>

            <input type="radio" name="<?php echo $this->get_field_name("form_id"); ?>" id="activecampaign_form_<?php echo $form["id"]; ?>" value="<?php echo $form["id"]; ?>" <?php echo $checked; ?> />
            <label for="activecampaign_form_<?php echo $form["id"]; ?>"><?php echo $form["name"]; ?></label>
            <br />

            <?php

          }

          // settings: checked or not

          $settings_swim_checked = (isset($instance["syim"]) && $instance["syim"] == "swim") ? "checked=\"checked\"" : "";
          $settings_sync_checked = (isset($instance["syim"]) && $instance["syim"] == "sync") ? "checked=\"checked\"" : "";
          if (!$settings_swim_checked && !$settings_sync_checked) $settings_swim_checked = "checked=\"checked\""; // default
          $settings_ajax_checked = (isset($instance["ajax"]) && (int)$instance["ajax"]) ? "checked=\"checked\"" : "";

          $settings_css_checked = "";
          if ( (isset($instance["css"]) && (int)$instance["css"]) || !$form_checked) {
          	// either it's been checked before, OR
          	// no form is chosen yet, so it's likely coming from step 1, so default the CSS checkbox to checked.
          	$settings_css_checked = "checked=\"checked\"";
          }

          $settings_action_value = (isset($instance["action"]) && $instance["action"]) ? $instance["action"] : "";

          ?>

          <div style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 25px; padding: 5px 0 0 0;">

          	<p><b>Settings:</b></p>

            <input type="radio" name="<?php echo $this->get_field_name("syim"); ?>" id="activecampaign_form_swim" value="swim" <?php echo $settings_swim_checked; ?> onchange="swim_toggle(this.checked);" />
            <label for="activecampaign_form_swim" style="">Add Subscriber</label>

						<br />

            <input type="radio" name="<?php echo $this->get_field_name("syim"); ?>" id="activecampaign_form_sync" value="sync" <?php echo $settings_sync_checked; ?> onchange="sync_toggle(this.checked);" />
            <label for="activecampaign_form_sync" style="">Sync Subscriber</label>

            <br />
            <br />

            <input type="checkbox" name="<?php echo $this->get_field_name("ajax"); ?>" id="activecampaign_form_ajax" value="1" <?php echo $settings_ajax_checked; ?> onchange="ajax_toggle(this.checked);" />
            <label for="activecampaign_form_ajax" style="">Process form using Ajax?</label>

            <br />

            <input type="checkbox" name="<?php echo $this->get_field_name("css"); ?>" id="activecampaign_form_css" value="1" <?php echo $settings_css_checked; ?> />
            <label for="activecampaign_form_css" style="">Keep original form CSS?</label>

            <br />
            <br />

            <label for="activecampaign_form_action" style="">Custom form <code>action</code> URL</label>
            <br />
            <input type="text" name="<?php echo $this->get_field_name("action"); ?>" id="activecampaign_form_action" value="<?php echo $settings_action_value; ?>" onkeyup="action_toggle(this.value);" style="width: 100%;" />

            <br />
            <br />

            <input type="radio" name="<?php echo $this->get_field_name("re"); ?>" id="activecampaign_form_refresh" value="refresh" />
            <label for="activecampaign_form_refresh" style="color: green;">Refresh Forms</label>

            <br />

            <input type="radio" name="<?php echo $this->get_field_name("re"); ?>" id="activecampaign_form_reset" value="reset" />
            <label for="activecampaign_form_reset" style="color: red;">Start Over</label>

          </div>

          <?php

        }

      break;

    }

    ?>

    <script type='text/javascript'>

			var swim_radio = document.getElementById("activecampaign_form_swim");
			var sync_radio = document.getElementById("activecampaign_form_sync");
			var ajax_checkbox = document.getElementById("activecampaign_form_ajax");
			var action_textbox = document.getElementById("activecampaign_form_action");

			function ac_str_is_url(url) {
				url += '';
			    return url.match( /((http|https|ftp):\/\/|www)[a-z0-9\-\._]+\/?[a-z0-9_\.\-\?\+\/~=&#%;:\|,\[\]]*[a-z0-9\/=?&;%\[\]]{1}/i );
			}

    	function swim_toggle(swim_checked) {
    		if (swim_checked) {

    		}
    	}

    	function sync_toggle(sync_checked) {
    		if (sync_checked && action_textbox.value == "") {
      		// if Sync is chosen, and there is no custom action URL, check the Ajax option.
    			ajax_checkbox.checked = true;
    		}
    	}

    	function ajax_toggle(ajax_checked) {
    		if ( !ajax_checked && sync_radio.checked && (!action_textbox.value || !ac_str_is_url(action_textbox.value)) )  {
					// if Sync is checked, and action value is empty or invalid, and they UNcheck Ajax, alert them.
					alert("If you use Sync, you need to use either the Ajax option, or your own custom action URL.");
					ajax_checkbox.checked = true;
    		}
    	}

    	function action_toggle(action_value) {
    		if (action_textbox.value && ac_str_is_url(action_textbox.value)) {

    		}
    	}

    </script>

    <?php

  }

  /**
   * Front-end display of widget.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     /Widget arguments.
   * @param array $instance Saved values from database.
   */

  public function widget($args, $instance) {

    // outputs the content of the widget

    if (isset($instance["form_html"])) {
      echo $instance["form_html"];
    }

    ?>

    <?php
  }

  public function update($new_instance, $old_instance) {

    // processes widget options (from the admin side) to be saved

    $instance = array();

    $end = false;
    $instance["error"] = "";
    $instance["message"] = "";
    $instance["step"] = (int)$new_instance["step"];

    if ($instance["step"] == 1) {

      $instance["api_url"] = strip_tags($new_instance["api_url"]);
      $instance["api_key"] = strip_tags($new_instance["api_key"]);
      $instance["forms"] = "";

      $ac = new ActiveCampaign($instance["api_url"], $instance["api_key"]);
      $test_connection = $ac->credentials_test();

      if (!$test_connection) {
        $instance["error"] = "Invalid API URL or Key.";
      }
      else {
        $account = $ac->api("account/view");
				$domain = (isset($account->cname) && $account->cname) ? $account->cname : $account->account;
        $instance["account"] = $domain;
        $instance = activecampaign_getforms($ac, $instance);
      }

    }
    elseif ($instance["step"] == 2) {

      $instance["account"] = $old_instance["account"];
      $instance["api_url"] = $old_instance["api_url"];
      $instance["api_key"] = $old_instance["api_key"];
      $instance["syim"] = $new_instance["syim"];
      $instance["ajax"] = 0;
      $instance["css"] = 1;
      $instance["action"] = "";

      $ac = new ActiveCampaign($instance["api_url"], $instance["api_key"]);

      if (isset($new_instance["ajax"]) && (int)$new_instance["ajax"]) {
				// Ajax option checked
				$instance["ajax"] = 1;
      }

      if (!isset($new_instance["css"])) {
				// CSS option UNchecked - they want to strip out the form CSS
				$instance["css"] = 0;
      }

      if (isset($new_instance["action"]) && $new_instance["action"]) {
				// custom "action" value supplied
				$instance["action"] = $new_instance["action"];
      }

      if ($new_instance["re"]) {
        // refreshing or resetting
        if ($new_instance["re"] == "refresh") {
          $instance = activecampaign_getforms($ac, $instance);
          $instance["form_id"] = $new_instance["form_id"];
          $instance = activecampaign_form_html($ac, $instance);
          $instance["message"] = "Refreshed";
          $instance["step"] = 2;
        }
        elseif ($new_instance["re"] == "reset") {
          $instance["account"] = "";
          //$instance["api_url"] = "";
          //$instance["api_key"] = "";
          $instance["step"] = 1;
        }
        $end = true;
      }
      else {
        // just save the form they chose
        $instance["forms"] = $old_instance["forms"];
        $instance["form_id"] = $new_instance["form_id"];
        $instance = activecampaign_form_html($ac, $instance);
        $instance["message"] = "Settings Saved";
        $end = true;
      }
    }

    if (!$end && !$instance["error"]) $instance["step"]++;

    return $instance;
  }

}

function activecampaign_shortcodes() {
	// check for Settings options saved first.
	$settings = get_option("settings_activecampaign");
	if ($settings) {
		if (isset($settings["form_html"]) && $settings["form_html"]) {
			return $settings["form_html"];
		}
	}
	else {
		// try widget options.
		$widget = get_option("widget_activecampaign_widget");
		// it comes out as an array with other things in it, so loop through it
		foreach ($widget as $k => $v) {
			// look for the one that appears to be the ActiveCampaign widget settings
			if (isset($v["api_url"]) && isset($v["api_key"]) && isset($v["form_html"])) {
				$widget_display = $v["form_html"];
				return $widget_display;
			}
		}
	}
  return "";
}

/*
 * The ActiveCampaign settings page.
 *
 */
function activecampaign_plugin_options() {

	if (!current_user_can("manage_options"))  {
		wp_die(__("You do not have sufficient permissions to access this page."));
	}

	$step = 1;
	$instance = array();
	$connected = false;

	if ($_SERVER["REQUEST_METHOD"] == "POST") {

		if ($_POST["api_url"] && $_POST["api_key"]) {

			$ac = new ActiveCampaign($_POST["api_url"], $_POST["api_key"]);

			if (!(int)$ac->credentials_test()) {
				echo "<p style='color: red; font-weight: bold;'>" . __("Access denied: Invalid credentials (URL and/or API key).", "menu-activecampaign") . "</p>";
			}
			else {

				$instance = $_POST;
//dbg($instance,1);

				// first form submit (after entering API credentials).

				// get account details.
				$account = $ac->api("account/view");
				$domain = (isset($account->cname) && $account->cname) ? $account->cname : $account->account;
				$instance["account"] = $domain;

				// get forms.
				$instance = activecampaign_getforms($ac, $instance);
//dbg($instance,1);

				$instance = activecampaign_form_html($ac, $instance);
				
				$connected = true;

			}

		}
		else {
			// one or both of the credentials fields is empty. it will just disconnect below because $instance is empty.
		}

		update_option("settings_activecampaign", $instance);

	}
	else {

		$instance = get_option("settings_activecampaign");
//dbg($instance);

		if (isset($instance["api_url"]) && $instance["api_url"] && isset($instance["api_key"]) && $instance["api_key"]) {

			// instance saved already.
			$connected = true;

		}
		else {

			// settings not saved yet.

			// see if they set up our widget (maybe we can pull the API URL and Key from that).
			$widget = get_option("widget_activecampaign_widget");

			if ($widget) {
				// if the ActiveCampaign widget is activated in a sidebar (dragged to a sidebar).

				$widget_info = current($widget); // take the first item.

				if (isset($widget_info["api_url"]) && $widget_info["api_url"] && isset($widget_info["api_key"]) && $widget_info["api_key"]) {
					// if they already supplied an API URL and key in the widget.
					$instance["api_url"] = $widget_info["api_url"];
					$instance["api_key"] = $widget_info["api_key"];
				}
			}

		}

	}

	?>

	<div class="wrap">

		<div id="icon-options-general" class="icon32"><br></div>

		<h2><?php echo __("ActiveCampaign Settings", "menu-activecampaign"); ?></h2>
	
		<p>
			<?php

				echo __("Configure your ActiveCampaign subscription form to be used as a shortcode anywhere on your site. Use <code>[activecampaign]</code> shortcode in posts and on pages after setting up everything below. Questions or problems? Contact help@activecampaign.com.", "menu-activecampaign");

			?>
		</p>
	
		<form name="activecampaign_settings_form" method="post" action="">

			<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 30px;" />

			<h3><?php echo __("API Credentials", "menu-activecampaign"); ?></h3>

			<p>
				<b><?php echo __("API URL", "menu-activecampaign"); ?>:</b>
				<br />
				<input type="text" name="api_url" id="activecampaign_api_url" value="<?php echo esc_attr($instance["api_url"]); ?>" style="width: 400px;" />
			</p>

			<p>
				<b><?php echo __("API Key", "menu-activecampaign"); ?>:</b>
				<br />
				<input type="text" name="api_key" id="activecampaign_api_key" value="<?php echo esc_attr($instance["api_key"]); ?>" style="width: 500px;" />
			</p>

			<?php

				if (!$connected) {

					?>

					<p><?php echo __("Get your API credentials from the Settings > API section:", "menu-activecampaign"); ?></p>
		
					<p><img src="<?php echo plugins_url("activecampaign-subscription-forms"); ?>/settings1.jpg" /></p>

					<?php

				}
				else {

					?>



					<?php

				}

			?>

			<?php

				if (isset($instance["forms"]) && $instance["forms"]) {

					?>

					<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 30px;" />

					<h3><?php echo __("Subscription Forms", "menu-activecampaign"); ?></h3>

					<p><i><?php echo __("Choose a subscription form. To add new forms go to your ActiveCampaign > Integration section.", "menu-activecampaign"); ?></i></p>

					<?php

					// just a flag to know if ANY form is checked (chosen)
					$form_checked = 0;

					foreach ($instance["forms"] as $form) {

						$checked = "";
						if (isset($instance["form_id"]) && (int)$instance["form_id"] && (int)$instance["form_id"] == (int)$form["id"]) {
							$checked = "checked=\"checked\"";
							$form_checked = 1;
						}

						?>

						<input type="radio" name="form_id" id="activecampaign_form_<?php echo $form["id"]; ?>" value="<?php echo $form["id"]; ?>" <?php echo $checked; ?> />
						<label for="activecampaign_form_<?php echo $form["id"]; ?>"><?php echo $form["name"]; ?></label>
						<br />

						<?php

					}

					$settings_swim_checked = (isset($instance["syim"]) && $instance["syim"] == "swim") ? "checked=\"checked\"" : "";
					$settings_sync_checked = (isset($instance["syim"]) && $instance["syim"] == "sync") ? "checked=\"checked\"" : "";
					if (!$settings_swim_checked && !$settings_sync_checked) $settings_swim_checked = "checked=\"checked\""; // default
					$settings_ajax_checked = (isset($instance["ajax"]) && (int)$instance["ajax"]) ? "checked=\"checked\"" : "";

					$settings_css_checked = "";
					if ( (isset($instance["css"]) && (int)$instance["css"]) || !$form_checked) {
						// either it's been checked before, OR
						// no form is chosen yet, so it's likely coming from step 1, so default the CSS checkbox to checked.
						$settings_css_checked = "checked=\"checked\"";
					}

					$settings_action_value = (isset($instance["action"]) && $instance["action"]) ? $instance["action"] : "";

					?>

					<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 30px;" />

					<h3><?php echo __("Subscription Form Options", "menu-activecampaign"); ?></h3>

					<p><i><?php echo __("Leave as default for normal behavior, or customize based on your needs.", "menu-activecampaign"); ?></i></p>

					<input type="radio" name="syim" id="activecampaign_form_swim" value="swim" <?php echo $settings_swim_checked; ?> onchange="swim_toggle(this.checked);" />
					<label for="activecampaign_form_swim" style="">Add Subscriber</label>

					<br />

					<input type="radio" name="syim" id="activecampaign_form_sync" value="sync" <?php echo $settings_sync_checked; ?> onchange="sync_toggle(this.checked);" />
					<label for="activecampaign_form_sync" style="">Sync Subscriber</label>

					<br />
					<br />

					<input type="checkbox" name="ajax" id="activecampaign_form_ajax" value="1" <?php echo $settings_ajax_checked; ?> onchange="ajax_toggle(this.checked);" />
					<label for="activecampaign_form_ajax" style="">Process form using Ajax?</label>

					<br />

					<input type="checkbox" name="css" id="activecampaign_form_css" value="1" <?php echo $settings_css_checked; ?> />
					<label for="activecampaign_form_css" style="">Keep original form CSS?</label>

					<br />
					<br />

					<label for="activecampaign_form_action" style="">Custom form <code>action</code> URL</label>
					<br />
					<input type="text" name="action" id="activecampaign_form_action" value="<?php echo $settings_action_value; ?>" onkeyup="action_toggle(this.value);" style="width: 400px;" />

					<script type='text/javascript'>

						var swim_radio = document.getElementById("activecampaign_form_swim");
						var sync_radio = document.getElementById("activecampaign_form_sync");
						var ajax_checkbox = document.getElementById("activecampaign_form_ajax");
						var action_textbox = document.getElementById("activecampaign_form_action");

						function ac_str_is_url(url) {
							url += '';
								return url.match( /((http|https|ftp):\/\/|www)[a-z0-9\-\._]+\/?[a-z0-9_\.\-\?\+\/~=&#%;:\|,\[\]]*[a-z0-9\/=?&;%\[\]]{1}/i );
						}

						function swim_toggle(swim_checked) {
							if (swim_checked) {

							}
						}

						function sync_toggle(sync_checked) {
							if (sync_checked && action_textbox.value == "") {
								// if Sync is chosen, and there is no custom action URL, check the Ajax option.
								ajax_checkbox.checked = true;
							}
						}

						function ajax_toggle(ajax_checked) {
							if ( !ajax_checked && sync_radio.checked && (!action_textbox.value || !ac_str_is_url(action_textbox.value)) )  {
								// if Sync is checked, and action value is empty or invalid, and they UNcheck Ajax, alert them.
								alert("If you use Sync, you need to use either the Ajax option, or your own custom action URL.");
								ajax_checkbox.checked = true;
							}
						}

						function action_toggle(action_value) {
							if (action_textbox.value && ac_str_is_url(action_textbox.value)) {

							}
						}

					</script>

					<?php

				}

				$button_value = ($connected) ? "Update" : "Connect";

			?>

			<p><button type="submit" style="font-size: 16px; margin-top: 25px; padding: 10px;"><?php echo __($button_value, "menu-activecampaign"); ?></button></p>

		</form>

		<?php

			if (isset($instance["form_html"])) {

				?>

				<hr style="border: 1px dotted #ccc; border-width: 1px 0 0 0; margin-top: 30px;" />

				<h3><?php echo __("Subscription Form Preview", "menu-activecampaign"); ?></h3>	

				<?php
			
				echo $instance["form_html"];
		
			}

		?>

	</div>

	<?php

}

function dbg($var, $continue = 0, $element = "pre") {
  echo "<" . $element . ">";
  echo "Vartype: " . gettype($var) . "\n";
  if ( is_array($var) ) echo "Elements: " . count($var) . "\n\n";
  elseif ( is_string($var) ) echo "Length: " . strlen($var) . "\n\n";
  print_r($var);
  echo "</" . $element . ">";
  if (!$continue) exit();
}

function activecampaign_getforms($ac, $instance) {
  $forms = $ac->api("form/getforms");
  if ((int)$forms->success) {
    $items = array();
    $forms = get_object_vars($forms);
    foreach ($forms as $key => $value) {
      if (is_int($key)) {
        $items[] = get_object_vars($value);
      }
    }
    $instance["forms"] = $items;
  }
  else {
  	if ($forms->error == "Failed: Nothing is returned") {
  		$instance["error"] = "Nothing was returned. Do you have at least one form created in your ActiveCampaign account?";
  	}
  	else {
    	$instance["error"] = $forms->error;
    }
  }
  return $instance;
}

function activecampaign_form_html($ac, $instance) {

  foreach ($instance["forms"] as $form) {

    if ((int)$form["id"] == (int)$instance["form_id"]) {

			$form_embed_params = array(
				"id" => $form["id"],
				"ajax" => $instance["ajax"],
				"css" => $instance["css"],
			);

			$sync = ($instance["syim"] == "sync") ? 1 : 0;

			if ($instance["action"]) {
				$form_embed_params["action"] = $instance["action"];
			}

    	if ((int)$form_embed_params["ajax"] && !isset($form_embed_params["action"])) {
    		// if they are using Ajax, but have not provided a custom action URL, we need to push it to a script where we can submit the form/process API request.
    		// remove the "http(s)" portion, because it was conflicting with the Ajax request (I was getting 404's).
    		$api_url_process = preg_replace("/https:\/\//", "", $instance["api_url"]);
				$form_embed_params["action"] = get_site_url() . "/wp-content/plugins/activecampaign-subscription-forms/form_process.php?api_url=" . $api_url_process . "&api_key=" . $instance["api_key"] . "&sync=" . $sync;
			}

			// prepare the params for the API call
    	$api_params = array();
			foreach ($form_embed_params as $var => $val) {
				$api_params[] = $var . "=" . urlencode($val);
			}

      // fetch the HTML source
      $html = $ac->api("form/embed?" . implode("&", $api_params));

      if ((int)$form_embed_params["ajax"]) {
      	// used for the result message that is displayed after submitting the form via Ajax
      	$html = "<div id=\"form_result_message\"></div>" . $html;
      }

      if ($html) {
        if ($instance["account"]) {
          // replace the API URL with the account URL (IE: https://account.api-us1.com is changed to http://account.activehosted.com).
          // (the form has to submit to the account URL.)
          if (!$instance["action"]) $html = preg_replace("/action=['\"][^'\"]+['\"]/", "action='http://" . $instance["account"] . "/proc.php'", $html);
        }
        // replace the Submit button to be an actual submit type.
        //$html = preg_replace("/input type='button'/", "input type='submit'", $html);
      }
      if ((int)$form["widthpx"]) {
        // if there is a custom width set
        // find the ._form CSS rule
        preg_match_all("/._form {[^}]*}/", $html, $_form_css);
        if (isset($_form_css[0]) && $_form_css[0]) {
          foreach ($_form_css[0] as $_form) {
            // find "width:400px"
            preg_match("/width:[0-9]+px/", $_form, $width);
            if (isset($width[0]) && $width[0]) {
              // IE: replace "width:400px" with "width:200px"
              $html = preg_replace("/" . $width[0] . "/", "width:" . (int)$form["widthpx"] . "px", $html);
            }
          }
        }
      }
      $instance["form_html"] = $html;
    }

  }

  return $instance;
}

function activecampaign_register_widgets() {
  register_widget("ActiveCampaign_Widget");
}

function activecampaign_display($args) {
  extract($args);
}

function activecampaign_register_shortcodes() {
  add_shortcode("activecampaign", "activecampaign_shortcodes");
}

function activecampaign_plugin_menu() {
	add_options_page(__("ActiveCampaign Settings", "menu-activecampaign"), __("ActiveCampaign", "menu-activecampaign"), "manage_options", "activecampaign", "activecampaign_plugin_options");
}

add_action("widgets_init", "activecampaign_register_widgets");
add_action("init", "activecampaign_register_shortcodes");
add_action("admin_menu", "activecampaign_plugin_menu");

?>