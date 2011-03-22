<?php
/*
Plugin Name: CF OpenX Zone Override
Plugin URI: http://crowdfavorite.com
Description: Provides the ability to override the OpenX zone being displayed by the CF OpenX Handler plugin
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com 
*/

/**
 * Copyright (c) 2011 Crowd Favorite. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

load_plugin_textdomain('cfoxo');

define('CFOXO_VER', '1.0');

/**
 * 
 * Init Functions
 *
 */

function cfoxo_request_handler() {
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cfoxo_submit':
				if (!empty($_POST['cfoxo']) && is_array($_POST['cfoxo'])) {
					cfoxo_save_options($_POST['cfoxo']);
					wp_redirect(admin_url('options-general.php?page='.basename(__FILE__).'&updated=true'));
					die();
				}
				wp_redirect(admin_url('options-general.php?page='.basename(__FILE__).'&updated=failure'));
				die();
				break;
		}
	}
}
add_action('init', 'cfoxo_request_handler');

function cfoxo_resources_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cfoxo_css':
				cfoxo_css();
				die();
				break;
			case 'cfoxo_js':
				cfoxo_js();
				die();
				break;
		}
	}
}
add_action('init', 'cfoxo_resources_handler', 1);

function cfoxo_save_options($posted = array()) {
	if (!is_array($posted) || empty($posted)) { return; }
	
	$options = array();
	foreach ($posted as $overrides) {
		if (empty($overrides['zone']) || empty($overrides['start']) || empty($overrides['end'])) { continue; }
		$zone = $overrides['zone'];
		$override = $overrides['override'];
		
		$start_time = zeroise($overrides['start']['hour'], 2).':'.zeroise($overrides['start']['minute'], 2).':'.zeroise($overrides['start']['second'], 2).' '.zeroise($overrides['start']['month'], 2).'-'.zeroise($overrides['start']['day'], 2).'-'.zeroise($overrides['start']['year'], 4);
		$end_time = zeroise($overrides['end']['hour'], 2).':'.zeroise($overrides['end']['minute'], 2).':'.zeroise($overrides['end']['second'], 2).' '.zeroise($overrides['end']['month'], 2).'-'.zeroise($overrides['end']['day'], 2).'-'.zeroise($overrides['end']['year'], 4);
		
		$options[] = array(
			'zone' => $zone,
			'override' => $override,
			'start_time' => $start_time,
			'end_time' => $end_time
		);
	}

	return update_option('cfoxo_options', $options);
}

/**
 * 
 * Admin Functions
 * 
 */

function cfoxo_menu_items() {
	add_options_page(
		__('CF OpenX Override', 'cfoxo'), 
		__('CF OpenX Override', 'cfoxo'), 
		10, 
		basename(__FILE__), 
		'cfoxo_options_page'
	);
}
add_action('admin_menu', 'cfoxo_menu_items');

function cfoxo_options_page() {
	$options = get_option('cfoxo_options');
	$cfox_options = get_option('cfox_options');

	// Update or Error message
	if (!empty($_GET['updated'])) {
		switch ($_GET['updated']) {
			case 'failure':
				?>
				<div class="updated"> 
					<p><strong><?php _e('Update failure, please try again&hellip;', 'cfoxo'); ?></strong></p>
				</div>
				<?php
				break;
			case 'true':
				?>
				<div class="updated"> 
					<p><strong><?php _e('Settings Saved', 'cfoxo'); ?></strong></p>
				</div>
				<?php
				break;
		}
	}
	?>
	<div class="wrap">
		<?php echo screen_icon().'<h2>'.__('CF OpenX Override', 'cfoxo').'</h2>'; ?>
		<div id="cfoxo-options">
			<h3><?php _e('Zone Overrides', 'cfoxo'); ?></h3>
			<?php
			if (!is_array($cfox_options['zones']) || empty($cfox_options['zones'])) {
				?>
				<p>
					<?php _e('No CF OpenX Zone have been setup.  Please setup zones before proceeding.', 'cfoxo'); ?> <a href="<?php echo admin_url('options-general.php?page=cf-openx-handler.php'); ?>"><?php _e('Edit OpenX Zones','cfoxo'); ?></a>
				</p>
				<?php
			}
			else {
				?>
				<p>
					<?php _e('NOTE: To override a CF OpenX zone, both the zone to override and the zone to override with must be setup on the CF OpenX settings page.', 'cfoxo'); ?>
				</p>
				<form action="<?php echo admin_url(); ?>" method="post">
					<ul id="cfoxo-options-list"<?php echo (is_array($options) && !empty($options) ? ' class="has-override-content"' : ''); ?>>
						<?php
						if (is_array($options) && !empty($options)) {
							foreach ($options as $key => $option) {
								echo '<li id="cfoxo-settings-li-'.$key.'">'.cfoxo_item_settings($key, $option).'</li>';
							}
						}
						?>
					</ul>
					<div class="clear"></div>
					<div class="cfoxo-buttons">
						<input type="hidden" name="cf_action" value="cfoxo_submit" />
						<input type="submit" name="cfoxo-submit" class="button-primary" value="<?php _e('Save Options', 'cfoxo'); ?>" />
						<button class="button" id="cfoxo-add-item"><?php _e('Add New Override', 'cfoxo'); ?></button>
					</div>
				</form>
				<div class="cfoxo-hidden-option" style="display:none;">
					<div id="cfoxo-new-item"><?php cfoxo_item_settings('###NEW###'); ?></div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}

function cfoxo_item_settings($id = '0', $options = array()) {
	$cfox_options = get_option('cfox_options');
	// If we don't have any zones, we can't override any zones
	if (!is_array($cfox_options['zones']) || empty($cfox_options['zones'])) { return; }
	
	$zone = 0;
	$override = 0;
	$start_time = 0;
	$end_time = 0;
	
	if (is_array($options) && !empty($options['zone'])) {
		$zone = $options['zone'];
		$override = $options['override'];
		$start_time = $options['start_time'];
		$end_time = $options['end_time'];
		
		// Break the Start time into pieces we can display for settings
		$start_time = 		explode(' ', $start_time);
		$start_time_time = 	explode(':', $start_time[0]);
		$start_time_day = 	explode('-', $start_time[1]);
		
		$start_hour = 		$start_time_time[0];
		$start_minute = 	$start_time_time[1];
		$start_second = 	$start_time_time[2];
		$start_month = 		$start_time_day[0];
		$start_day = 		$start_time_day[1];
		$start_year = 		$start_time_day[2];

		// Break the End time into pieces we can display for settings
		$end_time = 		explode(' ', $end_time);
		$end_time_time = 	explode(':', $end_time[0]);
		$end_time_day = 	explode('-', $end_time[1]);

		$end_hour = 		$end_time_time[0];
		$end_minute = 		$end_time_time[1];
		$end_second = 		$end_time_time[2];
		$end_month = 		$end_time_day[0];
		$end_day = 			$end_time_day[1];
		$end_year = 		$end_time_day[2];
	}
	
	?>
	<div class="cfoxo-item-settings">
		<div class="cfoxo-item-settings-zone">
			<span class="cfoxo-item-settings-text"><?php _e('Select zone to override: ', 'cfoxo'); ?></span>
			<select name="cfoxo[<?php echo $id; ?>][zone]">
				<option value="0"<?php selected($zone, '0'); ?>><?php _e('--Select Zone--', 'cfoxo'); ?></option>
				<?php foreach ($cfox_options['zones'] as $key => $zoneinfo) { ?>
					<option value="<?php echo esc_attr($zoneinfo['id']); ?>"<?php selected($zoneinfo['id'], $zone); ?>><?php echo esc_attr($zoneinfo['id'] . ' - '.$zoneinfo['desc']); ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="cfoxo-item-settings-override">
			<span class="cfoxo-item-settings-text"><?php _e('Select zone to use: ', 'cfoxo'); ?></span>
			<select name="cfoxo[<?php echo $id; ?>][override]">
				<option value="0"<?php selected($override, '0'); ?>><?php _e('Empty Zone', 'cfoxo'); ?></option>
				<?php foreach ($cfox_options['zones'] as $key => $zoneinfo) { ?>
					<option value="<?php echo esc_attr($zoneinfo['id']); ?>"<?php selected($zoneinfo['id'], $override); ?>><?php echo esc_attr($zoneinfo['id'] . ' - '.$zoneinfo['desc']); ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="clear"></div>
		<div class="cfoxo-item-settings-time">
			<div class="cfoxo-item-settings-time-start">
				<span class="cfoxo-item-settings-text"><?php _e('Select the Start time', 'cfoxo'); ?><span class="cfoxo-note"><?php _e('(Select 0 for all to start immediately)', 'cfoxo'); ?></span>:</span>
				<br />
				<div class="cfoxo-item-settings-time-start-hour cfoxo-item-settings-time-start-item">
					<span class="cfoxo-item-settings-text"><?php _e('Hour: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][start][hour]">
						<?php 
						for ($i = 0; $i <= 12; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $start_hour, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-start-minute cfoxo-item-settings-time-start-item">
					<span class="cfoxo-item-settings-text"><?php _e('Minute: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][start][minute]">
						<?php 
						for ($i = 0; $i <= 60; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $start_minute, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-start-second cfoxo-item-settings-time-start-item">
					<span class="cfoxo-item-settings-text"><?php _e('Second: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][start][second]">
						<?php 
						for ($i = 0; $i <= 60; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $start_second, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-start-month cfoxo-item-settings-time-start-item">
					<span class="cfoxo-item-settings-text"><?php _e('Month: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][start][month]">
						<?php 
						for ($i = 0; $i <= 12; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $start_month, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-start-day cfoxo-item-settings-time-start-item">
					<span class="cfoxo-item-settings-text"><?php _e('Day: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][start][day]">
						<?php 
						for ($i = 0; $i <= 31; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $start_day, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-start-year cfoxo-item-settings-time-start-item">
					<span class="cfoxo-item-settings-text"><?php _e('Year: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][start][year]">
						<option value="0"<?php selected(zeroise($i, 4), 0); ?>>0</option>
						<?php 
						for ($i = 2011; $i <= 2020; $i++) {
							echo '<option value="'.zeroise($i, 4).'"'.selected(zeroise($i, 4), $start_year, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="clear"></div>
			</div>
			<div class="cfoxo-item-settings-time-end">
				<span class="cfoxo-item-settings-text"><?php _e('Select the End time', 'cfoxo'); ?><span class="cfoxo-note"><?php _e('(Select 0 for all to override indefinitely)', 'cfoxo'); ?></span>:</span>
				<br />
				<div class="cfoxo-item-settings-time-end-hour cfoxo-item-settings-time-end-item">
					<span class="cfoxo-item-settings-text"><?php _e('Hour: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][end][hour]">
						<?php 
						for ($i = 0; $i <= 12; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $end_hour, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-end-minute cfoxo-item-settings-time-end-item">
					<span class="cfoxo-item-settings-text"><?php _e('Minute: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][end][minute]">
						<?php 
						for ($i = 0; $i <= 60; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $end_minute, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-end-second cfoxo-item-settings-time-end-item">
					<span class="cfoxo-item-settings-text"><?php _e('Second: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][end][second]">
						<?php 
						for ($i = 0; $i <= 60; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $end_second, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-end-month cfoxo-item-settings-time-end-item">
					<span class="cfoxo-item-settings-text"><?php _e('Month: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][end][month]">
						<?php 
						for ($i = 0; $i <= 12; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $end_month, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-end-day cfoxo-item-settings-time-end-item">
					<span class="cfoxo-item-settings-text"><?php _e('Day: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][end][day]">
						<?php 
						for ($i = 0; $i <= 31; $i++) {
							echo '<option value="'.zeroise($i, 2).'"'.selected(zeroise($i, 2), $end_day, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="cfoxo-item-settings-time-end-year cfoxo-item-settings-time-end-item">
					<span class="cfoxo-item-settings-text"><?php _e('Year: ', 'cfoxo'); ?></span>
					<select name="cfoxo[<?php echo $id; ?>][end][year]">
						<option value="0"<?php selected(zeroise($i, 4), 0); ?>>0</option>
						<?php 
						for ($i = 2011; $i <= 2020; $i++) {
							echo '<option value="'.zeroise($i, 4).'"'.selected(zeroise($i, 4), $end_year, false).'>'.$i.'</option>';
						}
						?>
					</select>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<?php
}


/**
 * 
 * CSS/JS Functions
 * 
 */

// Check to see if we are on the proper page before we try to enqueue CSS and JS
if (!empty($_GET['page']) && strpos($_GET['page'], basename(__FILE__)) !== false) {
	// Add the CSS
	wp_enqueue_style('cfoxo', site_url('?cf_action=cfoxo_css'), array(), CFOXO_VER, 'screen');
	
	// Add the JS
	wp_enqueue_script('jquery');
	wp_enqueue_script('cfoxo', site_url('?cf_action=cfoxo_js'), array('jquery'), CFOXO_VER);
}

function cfoxo_css() {
	header('Content-type: text/css');
	?>
	.has-override-content {
		border: 1px solid #E6E6E6;
		background-color:#CCCCCC;
		-moz-border-radius: 5px; /* FF1+ */
		-webkit-border-radius: 5px; /* Saf3+, Chrome */
		-khtml-border-radius: 5px; /* Konqueror */
		border-radius: 5px; /* Standard. IE9 */
		padding: 6px 8px;
		width: 750px;
		margin-bottom:20px;
	}
	
	.cfoxo-item-settings-text {
		font-weight:bold;
	}
	
	.cfoxo-item-settings-time-end-item .cfoxo-item-settings-text,
	.cfoxo-item-settings-time-start-item .cfoxo-item-settings-text {
		font-weight:normal;
	}
	
	.cfoxo-item-settings-time-start div,
	.cfoxo-item-settings-time-end div {
		margin:10px 5px 10px 0;
	}
	
	.cfoxo-note {
		font-weight:normal;
		padding-left:10px;
		font-size:10px;
	}
	
	.cfoxo-item-settings-time-start-item,
	.cfoxo-item-settings-time-end-item {
		float:left;
	}
	
	.cfoxo-item-settings {
		border: 1px solid #E6E6E6;
		background-color: #F9F9F9;
		-moz-border-radius: 5px; /* FF1+ */
		-webkit-border-radius: 5px; /* Saf3+, Chrome */
		-khtml-border-radius: 5px; /* Konqueror */
		border-radius: 5px; /* Standard. IE9 */
		padding: 6px 8px;
	}
	
	.cfoxo-item-settings-override,
	.cfoxo-item-settings-zone,
	.cfoxo-item-settings-time-start,
	.cfoxo-item-settings-time-end {
		margin-bottom:5px;
	}

	.cfoxo-item-settings-time-start .cfoxo-item-settings-text,
	.cfoxo-item-settings-time-end .cfoxo-item-settings-text {
		margin-bottom:5px;
	}
	
	<?php
	die();
}

function cfoxo_js() {
	header('Content-type: text/javascript');
	?>
	;(function($) {
		$(function() {
			$("#cfoxo-add-item").click(function(e) {
				var id = new Date().valueOf();
				var section = id.toString();
				var html = $("#cfoxo-new-item").html().replace(/###NEW###/g, section);
				
				$("#cfoxo-options-list").addClass('has-override-content').append('<li id="cfoxo-settings-li-'+section+'">'+html+'</li>');
			    e.preventDefault();
			});
		});
	})(jQuery);
	<?php
	die();
}

/**
 * 
 * Display Functions
 * 
 */

/**
 * Override the Zone ID based on the parameters set in the Admin
 *
 * @param int $zoneID 
 * @return int
 */
function cfoxo_zone_override($zoneID = 0) {
	if ($zoneID <= 0) { return $zoneID; }
	
	return $zoneID;
}
add_filter('cfox-display-zone-id', 'cfoxo_zone_override');

?>