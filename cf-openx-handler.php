<?php
/*
Plugin Name: CF OpenX Handler
Plugin URI: http://crowdfavorite.com
Description: Plugin for getting OpenX ads in many areas using specific criteria
Version: 1.4.5
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1');
// ini_set('error_reporting', E_ALL);

load_plugin_textdomain('cfox');

// Constants
define('CFOX_VERSION', '1.4.5');
define('CFOX_DIR',trailingslashit(realpath(dirname(__FILE__))));


/*************************************************/
/****************WP-ADMIN FUNCTIONS***************/
/*************************************************/

function cfox_menu_items() {
	if(current_user_can('manage_options')) {
		add_options_page(
			__('CF OpenX', 'cfox')
			, __('CF OpenX', 'cfox')
			, 10
			, basename(__FILE__)
			, 'cfox_options_page'
		);
	}
}
add_action('admin_menu','cfox_menu_items');

function cfox_request_handler() {
	if(current_user_can('manage_options')) {
		if(!empty($_POST['cf_action'])) {
			switch($_POST['cf_action']) {
				case 'cfox_save_settings':
					cfox_options_handler($_POST['cfox_options']);
					wp_redirect(admin_url('options-general.php?page='.basename(__FILE__).'&cfox_message=updated'));
					die();
			}
		}
	}
	if(isset($_GET['cf_action'])) {
		switch($_GET['cf_action']) {
			case 'cfox_admin_js':
				cfox_admin_js();
				break;
			case 'cfox_admin_css':
				cfox_admin_css();
				break;
		}
	}
}
add_action('init','cfox_request_handler');

function cfox_options_handler($cfox_submit) {
	$zones = array();
	
	if (is_array($cfox_submit['zones']) && !empty($cfox_submit['zones'])) {
		foreach($cfox_submit['zones'] as $key => $zoneinfo) {
			$zoneid = '';

			if (!isset($zoneinfo['zoneID'])) {
				$result = preg_match('/zoneid=([0-9]+)/',$zoneinfo['zoneIDurl'],$matches);
				$zoneid = $matches[1];
				if (empty($zoneid)) {
					$result2 = preg_match('/([0-9]+)/',$zoneinfo['zoneIDurl'],$matches2);
					$zoneid = $matches2[1];
				}
			}
			else {
				$zoneid = $zoneinfo['zoneID'];
			}
			if(!empty($zoneid)) {
				$zones[] = array(
					'id' => strip_tags($zoneid), 
					'desc' => strip_tags($zoneinfo['zoneDesc'])
				);
			}
		}
	}
	$cfox_options = array(
		'server' => str_replace(array('http://','https://'),'',strip_tags($cfox_submit['server'])), 
		'zones' => $zones
	);
	if (!get_option('cfox_options')) {
		add_option('cfox_options', $cfox_options, false, 'no');
	}
	else {
		update_option('cfox_options',$cfox_options);
	}
}

function cfox_options_page() {
	$cfox_options = maybe_unserialize(get_option('cfox_options'));
	if (!empty($_GET['cfox_message'])) {
		switch ($_GET['cfox_message']) {
			case 'updated':
				?>
				<div id="cfox_updated" class="updated fade">
					<p><?php _e('Settings Updated.', 'cfox'); ?></p>
				</div>
				<?php
				break;
		}
	}
	screen_icon();
	?>
	<div class="wrap">
		<h2><?php _e('CF OpenX Options', 'cfox'); ?></h2>
		<h3><?php _e('Type the path to the adserver delivery directory into the text field.', 'cfox'); ?></h3>
		<p>
			<?php _e('Example: ', 'cfox'); ?><code><?php _e('openx.example.com/www/delivery', 'cfox'); ?></code>
		</p>
		<form action="<?php echo admin_url(); ?>" method="post" id="cfox-form">
			<table class="widefat">
				<thead>
					<tr>
						<th scope="col"><?php _e('Option Name', 'cfox'); ?></th>
						<th scope="col"><?php _e('Option Value', 'cfox'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="vertical-align: middle; font-weight: bold;">
							<?php _e('Server URL:', 'cfox'); ?>
						</td>
						<td>
							<input type="text" name="cfox_options[server]" id="cfox_server" size="50" value="<?php echo esc_attr($cfox_options['server']); ?>" />
							<br />
							<?php _e('Omit http:// and https://', 'cfox'); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<br /><br />
			<h3><?php _e('The zones added below will be available for use on this blog.  No other zones will be acknowledged.', 'cfox'); ?></h3>
			<div id="cfox_zone_head">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col" width="320"><?php _e('Zone ID', 'cfox'); ?></th>
							<th scope="col"><?php _e('Zone Description', 'cfox'); ?></th>
						</tr>
					</thead>
				</table>
				<?php
				if (is_array($cfox_options['zones']) && !empty($cfox_options['zones'])) {
					foreach ($cfox_options['zones'] as $key => $zoneinfo) {
						?>
						<div id="cfox_zone_<?php echo esc_attr($key); ?>">
							<table class="widefat">
								<tbody>
									<tr>
										<td width="320">
											<input type="text" name="cfox_options[zones][<?php echo esc_attr($key); ?>][zoneID]" id="cfox_zone_<?php echo esc_attr($key); ?>_zoneID" size="10" value="<?php echo esc_attr($zoneinfo['id']); ?>" />
										</td>
										<td>
											<input type="text" name="cfox_options[zones][<?php echo esc_attr($key); ?>][zoneDesc]" id="cfox_zone_<?php echo esc_attr($key); ?>_zoneDesc" size="50" value="<?php echo esc_attr($zoneinfo['desc']); ?>" />
										</td>
										<td width="60px" style="text-align: center;">
											<input type="button" class="cfox-button-delete button" id="cfox_delete_<?php echo esc_attr($key); ?>" value="<?php _e('Delete', 'cfox'); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<?php
					}
				}
				?>
			</div>
			<div id="cfox_zone_foot">
				<table class="widefat">
					<tbody>
						<tr>
							<td>
								<p class="submit" style="border-top: none; padding:0; margin:0;">
									<input type="button" name="zone_add" id="zone_add" class="cfox-button-add button" value="<?php _e('Add New Zone', 'cfox'); ?>" />
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<p class="submit" style="border-top: none;">
				<input type="hidden" name="cf_action" value="cfox_save_settings" />
				<input type="submit" name="submit" id="cfox_submit" class="button-primary" value="<?php _e('Save OpenX Server Settings', 'cfox'); ?>" />
			</p>
		</form>
		<div id="cfox_zone_new_default" style="display:none;">
			<div id="cfox_zone_###SECTION###">
				<table class="widefat">
					<tbody>
						<tr>
							<td width="320">
								<textarea name="cfox_options[zones][###SECTION###][zoneIDurl]" id="cfox_zone_###SECTION###_zoneID" rows="5" style="width:300px;"></textarea>
								<br />
								<?php _e('Please enter the Invocation code, or the URL from the zone edit area.', 'cfox'); ?>
							</td>
							<td>
								<input type="text" name="cfox_options[zones][###SECTION###][zoneDesc]" id="cfox_zone_###SECTION###_zoneDesc" size="50" value="" />
							</td>
							<td width="60px" style="text-align: center;">
								<input type="button" class="cfox-button-delete button" id="cfox_delete_###SECTION###" value="<?php _e('Delete', 'cfox'); ?>" />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>				
	</div>	
	<?php
	$html = '';
	echo apply_filters('cfox_admin_page', $html);
}

function cfox_admin_js() {
	header('Content-type: text/javascript');
	?>
	;(function($) {
		$(function() {
			$(".cfox-button-delete").live('click', function() {
				if (confirm('<?php _e('Are you sure you want to delete this zone?', 'cfox'); ?>')) {
					var id = $(this).attr('id').replace('cfox_delete_', '');
					$("#cfox_zone_"+id).remove();
				}
				return false;
			});
			
			$(".cfox-button-add").live('click', function() {
				var id = new Date().valueOf();
				var section = id.toString();
				$("#cfox_zone_head").append($("#cfox_zone_new_default").html().replace(/###SECTION###/g, section));
				return false;
			});
		});
	})(jQuery);
	<?php
	die();
}

function cfox_admin_css() {
	header('Content-type: text/css');
	?>
	<?php
	die();
}

// Add the CSS and JS files to the proper page in the admin
if (!empty($_GET['page']) && strpos($_GET['page'], 'cf-openx') !== false) {
	wp_enqueue_script('jquery');
	wp_enqueue_script('cfox-admin-js', admin_url('?cf_action=cfox_admin_js'), array('jquery'), CFOX_VERSION);
	wp_enqueue_style('cfox-admin-css', admin_url('?cf_action=cfox_admin_css'), array(), CFOX_VERSION, 'screen');
}

function cfox_get_zones() {
	$options = maybe_unserialize(get_option('cfox_options'));
	$zones = array();
	
	if (is_array($options['zones']) && !empty($options['zones'])) {
		foreach ($options['zones'] as $key => $zone) {
			$zones[$key] = $zone;
		}
	}
	return $zones;
}

/*************************************************/
/*****************WIDGET FUNCTIONS****************/
/*************************************************/

class cfox_widget extends WP_Widget {
	function cfox_widget() {
		$widget_ops = array('classname' => 'cfox', 'description' => 'Widget for adding OpenX handlers in the Traditional OpenX way using JavaScript');
		parent::__construct('cfox', 'CF OpenX Ad', $widget_ops);
	}

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		
		$title = esc_attr($instance['title']);
		$zone = $instance['zone'];
		
		echo $before_widget;
		if (!empty($title)) {
			echo $before_title.$title.$after_title;
		}
		echo '<div class="cfox_widget">'.cfox_get_js_code($zone).'</div>';
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['zone'] = strip_tags($new_instance['zone']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args((array) $instance, array('title' => '', 'zone' => ''));
		$title = esc_attr($instance['title']);
		$cfox_options = maybe_unserialize(get_option('cfox_options'));
		
		if (!is_array($cfox_options['zones']) || empty($cfox_options['zones'])) {
			?>
			<p>
				<?php _e('No Zones have been setup.  Please use the edit link below to setup new zones.', 'cfox'); ?>
			</p>
			<?php
		}
		else {
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'cfox'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('zone'); ?>"><?php _e('Zone:', 'cfox'); ?></label>
				<select id="<?php echo $this->get_field_id('zone'); ?>" name="<?php echo $this->get_field_name('zone'); ?>" class="widefat">
					<option value="0"<?php selected($instance['zone'], '0'); ?>><?php _e('Select Zone ID:', 'cfox'); ?></option>
					<?php foreach ($cfox_options['zones'] as $key => $zoneinfo) { ?>
						<option value="<?php echo esc_attr($zoneinfo['id']); ?>"<?php selected($instance['zone'], esc_attr($zoneinfo['id'])); ?>><?php echo esc_attr($zoneinfo['id'] . ' - '.$zoneinfo['desc']); ?></option>
					<?php } ?>
				</select>
			</p>
			<?php
			if (defined('WP_CACHE') && WP_CACHE) {
				?>
				<p><small><?php _e('NOTE: The Ads being displayed will never be cached by WP Super Cache. These will always be dynamic.', 'cfox'); ?></small></p>
				<?php
			}
		}
		?>
		<p>
			<a href="<?php echo admin_url('options-general.php?page='.basename(__FILE__)); ?>"><?php _e('Edit OpenX Zones','cfox'); ?></a>
		</p>
		<?php
	}
}
add_action('widgets_init', create_function('', "register_widget('cfox_widget');"));

class cfox_preload_widget extends WP_Widget {
	function cfox_preload_widget() {
		$widget_ops = array('classname' => 'cfox_preload', 'description' => 'Widget for adding OpenX content using the Preload page method');
		parent::__construct('cfox_preload', 'CF OpenX Preload Ad', $widget_ops);
	}

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		global $cache_enabled, $super_cache_enabled, $wp_super_cache_late_init;
		
		$title = esc_attr($instance['title']);
		$zone = $instance['zone'];
		$cache = intval($instance['cache']);
		
		echo $before_widget;
		if (!empty($title)) {
			echo $before_title.$title.$after_title;
		}
		
		if ($cache && defined('WP_CACHE') && WP_CACHE && $cache_enabled && !$super_cache_enabled && $wp_super_cache_late_init) { 
			?>
			<div class="cfox_preload_widget">
				<!--mfunc cfox_zone_content(<?php echo $zone; ?>) -->
					<?php cfox_zone_content($zone); ?>
				<!--/mfunc-->
			</div>
			<?php
		}
		else {
			?>
			<div class="cfox_preload_widget">
				<?php cfox_zone_content($zone); ?>
			</div>
			<?php
		}
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		global $cache_enabled,$super_cache_enabled, $wp_super_cache_late_init;
		
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['zone'] = strip_tags($new_instance['zone']);

		if (!defined('WP_CACHE') || !WP_CACHE || !$cache_enabled || $super_cache_enabled) {
			$instance['cache'] = 0;
		}
		else {
			$instance['cache'] = intval($new_instance['cache']);
		}

		return $instance;
	}

	function form($instance) {
		global $cache_enabled,$super_cache_enabled, $wp_super_cache_late_init;
		
		$instance = wp_parse_args((array) $instance, array('title' => '', 'zone' => '', 'cache' => 1));
		$title = esc_attr($instance['title']);
		$cfox_options = maybe_unserialize(get_option('cfox_options'));
		
		if (!is_array($cfox_options['zones']) || empty($cfox_options['zones'])) {
			?>
			<p>
				<?php _e('No Zones have been setup.  Please use the edit link below to setup new zones.', 'cfox'); ?>
			</p>
			<?php
		}
		else {
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'cfox'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('zone'); ?>"><?php _e('Zone:', 'cfox'); ?></label>
				<select id="<?php echo $this->get_field_id('zone'); ?>" name="<?php echo $this->get_field_name('zone'); ?>" class="widefat">
					<option value="0"<?php selected($instance['zone'], '0'); ?>><?php _e('Select Zone ID:', 'cfox'); ?></option>
					<?php foreach ($cfox_options['zones'] as $key => $zoneinfo) { ?>
						<option value="<?php echo esc_attr($zoneinfo['id']); ?>"<?php selected($instance['zone'], esc_attr($zoneinfo['id'])); ?>><?php echo esc_attr($zoneinfo['id'] . ' - '.$zoneinfo['desc']); ?></option>
					<?php } ?>
				</select>
			</p>
			<?php if (defined('WP_CACHE') && WP_CACHE && $cache_enabled && !$super_cache_enabled && $wp_super_cache_late_init) { ?> 
				<p>
					<label for="<?php echo $this->get_field_id('cache'); ?>"><?php _e('Dynamically Load Ad:', 'cfox'); ?></label>
					<select id="<?php echo $this->get_field_id('cache'); ?>" name="<?php echo $this->get_field_name('cache'); ?>" class="widefat">
						<option value="0"<?php selected($instance['cache'], '0'); ?>><?php _e('No'); ?></option>
						<option value="1"<?php selected($instance['cache'], '1'); ?>><?php _e('Yes'); ?></option>
					</select>
					<br />
					<small><?php _e('This will bypass WP Super Cache.', 'cfox'); ?></small>
				</p>
			<?php } else if (defined('WP_CACHE') && WP_CACHE) {
				if ($super_cache_enabled) {
					?>
					<p><small><?php _e('These ads will be cached using WP Super Cache. To remove caching for this widget, set WP Super Cache to "Legacy Page Cache".', 'cfox'); ?></small></p>
					<?php
				}
				else if (!$wp_super_cache_late_init) {
					?>
					<p><small><?php _e('These ads will be cached using WP Super Cache. To remove caching for this widget, enable "Late Init" for WP Super Cache.', 'cfox'); ?></small></p>
					<?php
				}
			}
		}
		?>
		<p>
			<a href="<?php echo admin_url('options-general.php?page='.basename(__FILE__)); ?>"><?php _e('Edit OpenX Zones', 'cfox'); ?></a>
		</p>
		<?php
	}
}
add_action('widgets_init', create_function('', "register_widget('cfox_preload_widget');"));

/*************************************************/
/************CODE RETRIEVAL FUNCTIONS*************/
/*************************************************/

function cfox_get_js_code($cfox_zoneID = 0) {
	// Provide the ability to override the zone
	$cfox_zoneID = apply_filters('cfox-display-zone-id', $cfox_zoneID);

	// No need to proceed unless we have a valid zone id
	if($cfox_zoneID == 0 || $cfox_zoneID == '') { return false; }
	
	// Give other plugins the ability to insert extra parameters into the OpenX Invocation
	$params = apply_filters('cfox_params', '');
	
	$cfox_options = get_option('cfox_options');

	$random = md5(rand(0, 999999999));
	$n = substr(md5(rand(0, 999999999)), 0, 6);
	
	$return = "
		<script type='text/javascript'>
			<!--//<![CDATA[
			   var m3_u = (location.protocol=='https:'?'https://".$cfox_options['server']."/ajs.php':'http://".$cfox_options['server']."/ajs.php');
			   var m3_r = Math.floor(Math.random()*99999999999);
			   if (!document.MAX_used) document.MAX_used = ',';
			   document.write (\"<scr\"+\"ipt type='text/javascript' src='\"+m3_u);
			   document.write (\"?zoneid=". $cfox_zoneID ."\");
			   document.write ('&amp;cb=' + m3_r);
	";
	if (!empty($params)) {
		$return .= 'document.write("'.$params.'");';
	}
	$return .= "
			   if (document.MAX_used != ',') document.write (\"&amp;exclude=\" + document.MAX_used);
			   document.write (\"&amp;loc=\" + escape(window.location));
			   if (document.referrer) document.write (\"&amp;referer=\" + escape(document.referrer));
			   if (document.context) document.write (\"&context=\" + escape(document.context));
			   if (document.mmm_fo) document.write (\"&amp;mmm_fo=1\");
			   document.write (\"'><\/scr\"+\"ipt>\");
			//]]>-->
		</script>
		<noscript>
			<a href='http://".$cfox_options['server']."/ck.php?n=".$n."&amp;cb=".$random."' target='_blank'>
				<img src='http://".$cfox_options['server']."/avw.php?zoneid=".$cfox_zoneID."&amp;cb=".$random."&amp;n=".$n."' border='0' alt='' />
			</a>
		</noscript>
	"; 
	
	return $return;
}

function cfox_js_code($cfox_zoneID = 0) {
	// No need to proceed unless we have a valid zone id
	if($cfox_zoneID == 0 || $cfox_zoneID == '') { return false; }
	
	echo cfox_get_js_code($cfox_zoneID);
}

function cfox_get_template($cfox_zoneID = 0, $before = '', $after = '', $preload = false) {
	// No need to proceed unless we have a valid zone id
	if($cfox_zoneID == 0 || $cfox_zoneID == '') { return false; }
	
	if (empty($before)) { 
		$before = '<div class="cfox_ad">'; 
	}
	if (empty($after)) { 
		$after = '</div>'; 
	}

	$content = '';
	if ($preload) {
		$content = cfox_get_zone_content($cfox_zoneID);
	}
	else {
		$content = cfox_get_js_code($cfox_zoneID);
	}

	return $before.$content.$after;
}

function cfox_template($cfox_zoneID = 0, $before = '', $after = '', $preload = false) {
	// No need to proceed unless we have a valid zone id
	if($cfox_zoneID == 0 || $cfox_zoneID == '') { return false; }
	
	echo cfox_get_template($cfox_zoneID, $before, $after, $preload);
}

function cfox_get_zone_content($cfox_zoneID = '') {
	// Provide the ability to override the zone
	$cfox_zoneID = apply_filters('cfox-display-zone-id', $cfox_zoneID);

	// No need to proceed unless we have a valid zone id
	if($cfox_zoneID == 0 || $cfox_zoneID == '') { return false; }

	// Give other plugins the ability to insert extra parameters into the OpenX Invocation
	$params = apply_filters('cfox_params', '');

	$cfox_options = get_option('cfox_options');
	
	if (!isset($cfox_options['server']) || empty($cfox_options['server'])) {
		return false;
	}
	$random = md5(rand(0, 999999999));
	// Add the htmloutput variable so we can get Raw HTML from OpenX if that is available
	$params .= '&htmloutput=true';
	$url = 'http://'.$cfox_options['server'].'/ajs.php?zoneid='.$cfox_zoneID.'&cb='.$random.$params;
	$remote = wp_remote_get($url);
	
	if (!is_array($remote) || is_a($remote, 'WP_Error')) {
		return false;
	}
	
	$body = apply_filters(
		'cfox_remote_body',
		empty($remote['body']) ? null : $remote['body'],
		$remote
	);
	
	if (strpos($body, '+=') !== false || strpos($body, 'document.write(') !== false) {
		// Check to see if we received JS code from OpenX.  If so, wrap it in script tags and return
		return '<script type="text/javascript">'.$body.'</script>';
	}
	else if (!empty($body) && $body != '') {
		// If we just received content, return that.
		return $body;
	}
	
	// If nothing was received, return false
	return false;
}

function cfox_zone_content($cfox_zoneID = '') {
	// No need to proceed unless we have a valid zone id
	if($cfox_zoneID == 0 || $cfox_zoneID == '') { return false; }
	
	echo cfox_get_zone_content($cfox_zoneID);
}

/**
 * cfox_shortcode - Function that adds a shortcode so that the Invocation code can be built anywhere the "do_shortcode" function is called.
 * -- The invocation of the shortcode should look like [cfopenx zone="#"], [cfox zone="#"], [cfopenx zone="#" preload="true"] or [cfox zone="#" preload="true"]
 *
 * @param array $attrs - Array containing parameters passing through the invocation code
 * @param string $content - Content between open and close shortcode tags.  Should be empty
 * @return void - Returns the OpenX invocation code if a zone id is present
 */
function cfox_shortcode($attrs, $content = null) {
	if (is_array($attrs) && !empty($attrs)) {
		// Check to make sure that the zone id is present
		if (!empty($attrs['zone'])) {
			if (empty($attrs['preload'])) {
				return cfox_get_js_code($attrs['zone']);
			}
			else {
				return cfox_get_zone_content($attrs['zone']);
			}
		}
	}
	return $content;
}
add_shortcode('cfopenx', 'cfox_shortcode');
add_shortcode('cfox', 'cfox_shortcode');



## CF Links Addition

// Add the functionality only if the CF Links plugin exists and is active
if (function_exists('cflk_edit')) {
	
	function cfox_link_edit($html, $cflk_key) {
		// Get the zones, then build the Select options with the info
		$zones = cfox_get_zones();
		if (is_array($zones) && !empty($zones)) {
			foreach ($zones as $key => $zone) {
				$zone_options .= '<option value="'.$key.'">'.$zone['id'].' - '.$zone['desc'].'</option>';
			}

			// Get the inserted zones, and build the TRs for them
			$options = get_option('cfox_link');
			if (is_array($options[$cflk_key]) && !empty($options[$cflk_key])) {
				foreach ($options[$cflk_key] as $key => $zone) {
					$zone_info .= '
						<tr id="cfox-zone-'.$key.'">
							<td>
								Zone '.$zones[$zone]['id'].': '.$zones[$zone]['desc'].'
							</td>
							<td style="text-align:center;">
								<input type="button" class="button cfox_delete" id="cfox_delete_'.$key.'" value="'.__('Delete', 'cfox').'" onClick="cfox_link_delete('.$key.');" />
								<input type="hidden" name="cfox['.$key.'][zone]" value="'.$zone.'" />
							</td>
						</tr>
					';
				}
			}
		}
		
		if ( isset($_GET['cfox_message']) && $_GET['cfox_message'] = 'updated' ) {
			$html .= '
				<div id="message" class="updated fade">
					<p>'.__('OpenX Links Settings Updated.', 'cfox').'</p>
				</div>
			';
		}
		
		$html .= '
		<div class="wrap">
			<h3>CF OpenX Link Insertion</h3>
			<p>
				The CF OpenX Handler plugin will insert ads from the list of Zones below at random into the list of links above.  <em>This list only applies to this links list.</em>
				<br />
				<strong>NOTE: The Update Settings button above does not update the Zone Settings.</strong>
			</p>
			<form action="" method="post" id="cfox-form">
				<table id="cfox" class="widefat" style="width:500px;">
					<thead>
						<tr>
							<th scope="col" width="440px">'.__('Zone', 'cfox').'</th>
							<th scope="col" width="60px" style="text-align:center;">'.__('Remove', 'cfox').'</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td style="text-align:left;" colspan="2">
								<input type="button" class="button" name="cfox_add" id="cfox_add" value="'.__('Add New Zone', 'cfox').'" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						'.$zone_info.'
					</tbody>
				</table>
				<p class="submit" style="border-top: none;">
					<input type="hidden" name="cf_action" value="cfox_links_settings_update" />
					<input type="hidden" name="cflk_key" value="'.esc_attr($cflk_key).'" />
					<input type="submit" name="submit" id="cfox-submit" value="'.__('Update Zone Settings', 'cfox').'" class="button-primary" />
				</p>
			</form>
			<div id="cfox_newitem" style="display:none;">
				<table class="widefat">
					<tr id="cfox-zone-###SECTION###">
						<td>
							<select id="cfox_zone_###SECTION###" class="cfox_zones" name="cfox[###SECTION###][zone]">
								<option value="0">--Select Zone--</option>
								'.$zone_options.'
							</select>
						</td>
						<td style="text-align:center;">
							<input type="button" class="button cfox_delete" id="cfox_delete_###SECTION###" value="'.__('Delete', 'cfox').'" onClick="cfox_link_delete(###SECTION###);" />
						</td>
					</tr>
				</table>
			</div>
		</div>
		';

		return $html;
	}
	add_filter('cflk_edit', 'cfox_link_edit', 10, 2);
	
	function cfox_link_js($js) {
		$js .= "
		jQuery(document).ready(function() {
			jQuery('#cfox_add').click(function() {
				var id = new Date().valueOf();
				var section = id.toString();

				var html = jQuery('#cfox_newitem table').html().replace('<tbody>', '').replace('</tbody>', '').replace(/###SECTION###/g, section);
				jQuery('#cfox tbody').append(html);
			});
		});
		
		function cfox_link_delete(key) {
			if (confirm('Are you sure you want to delete this?')) {
				jQuery('#cfox-zone-'+key).remove();
				return false;
			}
		}
		";
		return $js;
	}
	add_filter('cflk_admin_js', 'cfox_link_js');
	
	function cfox_link_css($css) {
		$css .= "
		.cfox_zones {
			max-width:400px;
			width: expression(this.clientWidth > 400 ? '400px':this.clientWidth+'px');
		}
		";
		return $css;
	}
	add_filter('cflk_admin_css', 'cfox_link_css');
	
	function cfox_link_request_handler() {
		if(current_user_can('manage_options')) {
			$blogurl = '';
			if (is_ssl()) {
				$blogurl = str_replace('http://','https://',get_bloginfo('wpurl'));
			}
			else {
				$blogurl = get_bloginfo('wpurl');
			}		
			
			if(isset($_POST['cf_action'])) {
				switch($_POST['cf_action']) {
					case 'cfox_links_settings_update':
						cfox_links_options_handler($_POST['cfox'], $_POST['cflk_key']);
						wp_redirect($blogurl.'/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link='.$_POST['cflk_key'].'&cfox_message=updated');
						die();
				}
			}
		}
	}
	add_action('init', 'cfox_link_request_handler');
	
	function cfox_links_options_handler($posted, $cflk_key) {
		$options = get_option('cfox_link');
		
		unset($options[$cflk_key]);
		$options[$cflk_key] = array();
		
		if (is_array($posted) && !empty($posted)) {
			foreach ($posted as $key => $zone) {
				if ($zone['zone'] != 0) {
					$options[$cflk_key][] = stripslashes($zone['zone']);
				}
			}
		}
		
		delete_option('cfox_link');
		add_option('cfox_link', $options, '', 'no');
	}
	
	function cfox_links_filter($html, $links, $args) {
		$options = get_option('cfox_link');
		$key = $links['key'];
		if (!is_array($options[$key]) || empty($options[$key])) { return $html; }

		$zones = cfox_get_zones();
		$ads = array();
		$count = 1;
		foreach ($options[$key] as $key => $zone) {
			$ads[] = array(
				'position' => rand(1,count($links['data'])+1),
				'params' => array(
					'zone_id' => $zones[$zone]['id']
				),
				'callback' => 'cfox_get_zone_content',
				'add_class' => ' cf-ad-'.$count
			);
			$count++;
		}
		
		$other_options = array(
			'parent_before' => $args['before'],
			'parent_after' => $args['after']
		);
		
		$html = cfox_insert_content($html, $ads, $other_options);
		return $html;
	}
	add_filter('cflk_get_links','cfox_links_filter',99999,3);

	if (!is_admin() && !class_exists('phpQuery')) {
		include_once(CFOX_DIR.'includes/phpQuery-onefile.php');
	}

	function cfox_insert_content($html,$inserts,$options = array()) {
		if (!is_array($inserts) || empty($inserts)) { return $html; }

		$defaults = array(
			'parent_node' => 'ul',
			'find_node' => 'li',
			'before' => '<li>',
			'after' => '</li>',
			'add_class' => '',
			'parent_before' => '',
			'parent_after' => ''
		);

		$options = wp_parse_args($options, $defaults);
		extract(stripslashes_deep($options), EXTR_SKIP);

		if (function_exists('cf_sort_by_key')) { /* function from cf-compat */
			$inserts = cf_sort_by_key($inserts,'position');
		}
		
		// We need this so we can search for children items and insert accordingly
		if (empty($parent_before)) {
			$html = '<'.$parent_node.'>'.$html.'</'.$parent_node.'>';
		}
		
		$h = phpQuery::newDocumentHTML($html);
		
		if (!empty($parent_before)) {
			$h[$parent_node]->addClass('cf-has-inserted-items');
		}
		
		foreach($inserts as $insert) {
			if (empty($insert['callback'])) { continue; }
			if (empty($insert['params'])) { $insert['params'] = array(); }
			if (empty($insert['add_class'])) { $insert['add_class'] = ''; }
			$content = call_user_func_array($insert['callback'], $insert['params']);
			$empty_class = '';
			if (!$content) {
				$empty_class .= ' cf-empty-node';
				$content = '';
			}
			$html = $before.$content.$after;
			if(!empty($h[$parent_node.' '.$find_node.':nth-child('.$insert['position'].')'])) {
				pq($html)->addClass('cf-inserted-item'.$empty_class.$add_class.$insert['add_class'])->insertBefore($h[$parent_node.' '.$find_node.':nth-child('.$insert['position'].')']);
			}
			else {
				pq($html)->addClass('cf-inserted-item'.$empty_class.$add_class.$insert['add_class'])->appendTo($h[$parent_node]);
			}
		}
		
		// Return just the children items if we did the auto insert of the parent before
		if (empty($parent_before)) {
			return $h[$parent_node.' '.$find_node];
		}
		return $h;
	}		
}


## CF Readme Addition
function cfox_add_readme() {
	if (function_exists('cfreadme_enqueue')) {
		cfreadme_enqueue('cf-openx-handler', 'cfox_readme');
		cfreadme_enqueue('openx-help', 'cfox_openx_readme', array('cf-openx-handler'));
	}
}
add_action('admin_init', 'cfox_add_readme');
	
function cfox_readme() {
	$file = CFOX_DIR.'README.txt';
	if (is_file($file) && is_readable($file)) {
		$markdown = file_get_contents($file);
		$markdown = preg_replace('|!\[(.*?)\]\((.*?)\)|', '![$1]('.CFOX_DIR.'/$2)', $markdown);
		return $markdown;
	}
	return null;
}

function cfox_openx_readme() {
	$file = CFOX_DIR.'OpenX-Help.txt';
	if (is_file($file) && is_readable($file)) {
		$markdown = file_get_contents($file);
		$markdown = preg_replace('|!\[(.*?)\]\((.*?)\)|', '![$1]('.CFOX_DIR.'/$2)', $markdown);
		return $markdown;
	}
	return null;
}


?>