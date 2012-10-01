<?php
/*
Plugin Name: ActiveCampaign
Plugin URI: http://www.activecampaign.com/extend-wordpress.php
Description: This plugin connects WordPress with your ActiveCampaign software and allows you to embed a subscription form on your site. After enabling go to Appearance > Widgets to activate this plugin.
Author: ActiveCampaign
Version: 3.0
Author URI: http://www.activecampaign.com
*/

# Changelog
## version 1: - initial release
## version 1.1: Verified this works with latest versions of WordPress and ActiveCampaign; Updated installation instructions
## version 2.0: Re-configured to work with ActiveCampaign version 5.4. Also improved some areas.
## version 2.1: Changed internal API requests to use only API URL and Key instead of Username and Password. Also provided option to remove style blocks, and converting <input type="button" into <input type="submit"
## version 3.0: Re-wrote widget backend to use most recent WordPress Widget structure. Improvements include streamlined code and API usage, ability to reset or refresh your forms, and better form width detection.

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

 			<p style="color: green; font-weight: bold;"><?php echo $instance["message"]; ?></p>

 			<?php

 		}

 		$instance["step"] = (isset($instance["step"]) && (int)$instance["step"]) ? $instance["step"] : 1;

 		?>

 		<input type="hidden" name="<?php echo $this->get_field_name("step"); ?>" id="activecampaign_step" value="<?php echo $instance["step"]; ?>" />

 		<?php

 		switch ($instance["step"]) {

 			case 1:

 				?>

				<p>
					API URL:
					<input type="text" name="<?php echo $this->get_field_name("api_url"); ?>" id="activecampaign_api_url" value="<?php echo esc_attr($instance["api_url"]); ?>" style="width: 99%;" />
				</p>

				<p>
					API Key:
					<input type="text" name="<?php echo $this->get_field_name("api_key"); ?>" id="activecampaign_api_key" value="<?php echo esc_attr($instance["api_key"]); ?>" style="width: 99%;" />
				</p>

 				<?php

 			break;

 			case 2:

 				?>

				<p>Choose a form to display:</p>

 				<?php

 				if ($instance["forms"]) {

	 				foreach($instance["forms"] as $form) {

	 					$checked = (isset($instance["form_id"]) && (int)$instance["form_id"] && (int)$instance["form_id"] == (int)$form["id"]) ? "checked=\"checked\"" : "";

	 					?>

	 					<input type="radio" name="<?php echo $this->get_field_name("form_id"); ?>" id="activecampaign_form_<?php echo $form["id"]; ?>" value="<?php echo $form["id"]; ?>" <?php echo $checked; ?> />
	 					<label for="activecampaign_form_<?php echo $form["id"]; ?>"><?php echo $form["name"]; ?></label>
	 					<br />

	 					<?php

	 				}

	 				?>

	 				<div style="border: 1px dotted #ccc; border-width: 1px 0 0 0; color: #999; margin-top: 11px; padding: 5px 0 0 0;">

						<input type="radio" name="<?php echo $this->get_field_name("re"); ?>" id="activecampaign_form_refresh" value="refresh" />
						<label for="activecampaign_form_refresh">Refresh</label>

						<br />

						<input type="radio" name="<?php echo $this->get_field_name("re"); ?>" id="activecampaign_form_reset" value="reset" />
						<label for="activecampaign_form_reset">Reset</label>

					</div>

	 				<?php

 				}

 			break;

 		}

	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
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

		// processes widget options to be saved

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
				$instance["account"] = $account->account;
				$instance = activecampaign_getforms($ac, $instance);
			}

		}
		elseif ($instance["step"] == 2) {

			$instance["account"] = $old_instance["account"];
			$instance["api_url"] = $old_instance["api_url"];
			$instance["api_key"] = $old_instance["api_key"];

			$ac = new ActiveCampaign($instance["api_url"], $instance["api_key"]);

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

function dbg($var, $continue = 0, $element = "pre") {
  echo "<" . $element . ">";
  //echo "Parameter: " . $$var . "\n";
  echo "Vartype: " . gettype($var) . "\n";
  if ( is_array($var) )
  {
  	echo "Elements: " . count($var) . "\n\n";

    //print_r( array_values($var) );

    //print_r( array_keys($var) );
  }
  elseif ( is_string($var) )
  {
		echo "Length: " . strlen($var) . "\n\n";
  }
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
		$instance["error"] = $forms->error;
	}
	return $instance;
}

function activecampaign_form_html($ac, $instance) {
	foreach ($instance["forms"] as $form) {
		if ((int)$form["id"] == (int)$instance["form_id"]) {
			// fetch the HTML source
			$html = $ac->api("form/html?id=" . $form["id"]);
			if ($html) {
				// replace the API URL with the account URL (IE: https://account.api-us1.com is changed to http://account.activehosted.com).
				// (the form has to submit to the account URL.)
				$html = preg_replace("/action=['\"][^'\"]+['\"]/", "action='http://" . $instance["account"] . "/proc.php'", $html);
				// replace the Submit button to be an actual submit type.
				$html = preg_replace("/input type='button'/", "input type='submit'", $html);
			}
			if ((int)$form["widthpx"]) {
				// if there is a custom width set
				// find the ._form CSS rule
				preg_match_all("/._form {[^}]*}/", $html, $_form_css);
				if (isset($_form_css[0]) && $_form_css[0]) {
					foreach ($_form_css[0] as $_form) {
						// find width:400px
						preg_match("/width:[0-9]+px/", $_form, $width);
						if (isset($width[0]) && $width[0]) {
							// IE: replace width:400px with width:200px
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

add_action("widgets_init", "activecampaign_register_widgets");

/*
$args = array(
	"name" => sprintf(__("Sidebar %d"), $i ),
	"id" => "sidebar-$i",
	"description" => "",
	"class" => "",
	"before_widget" => "<li id='%1$s' class='widget %2$s'>",
	"after_widget" => "</li>",
	"before_title" => "<h2 class='widgettitle'>",
	"after_title" => "</h2>",
);

register_sidebar($args);
*/

?>