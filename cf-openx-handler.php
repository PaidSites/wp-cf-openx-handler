<?php
/*
Plugin Name: CF OpenX Handler
Plugin URI: http://crowdfavorite.com
Description: Plugin for getting OpenX ads in many areas using specific criteria
Version: 1.2
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1');
// ini_set('error_reporting', E_ALL);

load_plugin_textdomain('cfox');

// Constants
	define('CFOX_VERSION', '1.2');
	define('CFOX_DIR',trailingslashit(realpath(dirname(__FILE__))));


/*************************************************/
/****************WP-ADMIN FUNCTIONS***************/
/*************************************************/

function cfox_menu_items() {
	if(current_user_can('manage_options')) {
		add_options_page(
			__('CF OpenX Handler', 'cfox')
			, __('CF OpenX Handler', 'cfox')
			, 10
			, basename(__FILE__)
			, 'cfox_options_page'
		);
	}
}
add_action('admin_menu','cfox_menu_items');

function cfox_request_handler() {
	if(current_user_can('manage_options')) {
		if(isset($_POST['cf_action'])) {
			switch($_POST['cf_action']) {
				case 'cfox_save_settings':
					cfox_options_handler($_POST['cfox_options']);
					wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-openx-handler.php&cfox_message=updated');
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
				$zones[] = array('id' => $zoneid, 'desc' => $zoneinfo['zoneDesc']);
			}
		}
	}
	$cfox_options = array('server' => str_replace(array('http://','https://'),'',$cfox_submit['server']), 'zones' => $zones);
	if (!get_option('cfox_options')) {
		add_option('cfox_options', $cfox_options, false, 'no');
	}
	else {
		update_option('cfox_options',$cfox_options);
	}
}

function cfox_options_page() {
	$cfox_options = maybe_unserialize(get_option('cfox_options'));
	if ( isset($_GET['cfox_message']) ) {
		switch($_GET['cfox_message']) {
			case 'updated':
				print('
					<div id="cfox_updated" class="updated fade">
						<p>'.__('Settings Updated.', 'cfox').'</p>
					</div>
				');
				break;
		}
	}
	screen_icon();
	print('
		<div class="wrap">
			<h2>'.__('Crowd Favorite OpenX Handler Options', 'cfox').'</h2>
			<h3>'.__('Type the path to the adserver delivery directory into the text field.', 'cfox').'</h3>
			<p>
				'.__('Example: ').'<code>openx.example.com/www/delivery</code>
			</p>
			<form action="'.get_bloginfo('url').'/wp-admin/options-general.php" method="post" id="cfox-form">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Option Name', 'cfox').'</th>
							<th scope="col">'.__('Option Value', 'cfox').'</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="vertical-align: middle; font-weight: bold;">
								'.__('Server URL:', 'cfox').'
							</td>
							<td>
								<input type="text" name="cfox_options[server]" id="cfox_server" size="50" value="'.attribute_escape($cfox_options['server']).'" />
								<br />
								'.__('Omit http:// and https://', 'cfox').'
							</td>
						</tr>
					</tbody>
				</table>
				<br /><br />
				<h3>'.__('The zones added below will be available for use on this blog.  No other zones will be acknowledged.', 'cfox').'</h3>
				<div id="cfox_zone_head">
					<table class="widefat">
						<thead>
							<tr>
								<th scope="col" width="320">'.__('Zone ID', 'cfox').'</th>
								<th scope="col">'.__('Zone Description', 'cfox').'</th>
							</tr>
						</thead>
					</table>');
					if(is_array($cfox_options['zones'])) {
						foreach($cfox_options['zones'] as $key => $zoneinfo) {
							print('<div id="cfox_zone_'.attribute_escape($key).'">
							<table class="widefat">
								<tbody>
									<tr>
										<td width="320">
											<input type="text" name="cfox_options[zones]['.attribute_escape($key).'][zoneID]" id="cfox_zone_'.attribute_escape($key).'_zoneID" size="10" value="'.attribute_escape($zoneinfo['id']).'" />
										</td>
										<td>
											<input type="text" name="cfox_options[zones]['.attribute_escape($key).'][zoneDesc]" id="cfox_zone_'.attribute_escape($key).'_zoneDesc" size="50" value="'.attribute_escape($zoneinfo['desc']).'" />
										</td>
										<td width="60px" style="text-align: center;">
											<input type="button" class="cfox_button" id="cfox_delete_'.attribute_escape($key).'" value="'.__('Delete', 'cfox').'" onClick="deleteZone('.attribute_escape($key).')" />
										</td>
									</tr>
								</tbody>
							</table>
						</div>');
						}
					}
					print('</div>
				<div id="cfox_zone_foot">
					<table class="widefat">
						<tbody>
							<tr>
								<td>
									<p class="submit" style="border-top: none; padding:0; margin:0;">
										<input type="button" name="zone_add" id="zone_add" value="'.__('Add New Zone', 'cfox').'" onClick="addZone()" />
									</p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<p class="submit" style="border-top: none;">
					<input type="hidden" name="cf_action" value="cfox_save_settings" />
					<input type="submit" name="submit" id="cfox_submit" value="'.__('Save OpenX Server Settings', 'cfox').'" />
				</p>
			</form>				
		</div>
	');
	$html = '';
	echo apply_filters('cfox_admin_page', $html);
}

function cfox_admin_js() {
	header('Content-type: text/javascript');
	?>
	function addZone() {
		var id = new Date().valueOf();
		var section = id.toString();
		var html = '<div id="cfox_zone_###SECTION###">\
			<table class="widefat">\
				<tbody>\
					<tr>\
						<td width="320">\
							<textarea name="cfox_options[zones][###SECTION###][zoneIDurl]" id="cfox_zone_###SECTION###_zoneID" rows="5" style="width:300px;"></textarea>\
							<br />\
							Please enter the Invocation code, or the URL from the zone edit area.\
						</td>\
						<td>\
							<input type="text" name="cfox_options[zones][###SECTION###][zoneDesc]" id="cfox_zone_###SECTION###_zoneDesc" size="50" value="" />\
						</td>\
						<td width="60px" style="text-align: center;">\
							<input type="button" class="button" id="cfox_delete_###SECTION###" value="<?php _e('Delete'); ?>" onClick="deleteZone(###SECTION###)" />\
						</td>\
					</tr>\
				</tbody>\
			</table>\
		</div>';
		html = html.replace(/###SECTION###/g, section);
		jQuery('#cfox_zone_head').append(html);
	}
	function deleteZone(id) {
		if(confirm('Are you sure you want to delete this?')) {
			jQuery('#cfox_zone_'+id).remove();
			return false;
		}
	}
	<?php
	die();
}

function cfox_admin_css() {
	header('Content-type: text/css');
	?>
	.cfox_button {
		font-family: "Lucida Grande", "Lucida Sans Unicode", Tahoma, Verdana, sans-serif;
		padding: 3px 5px;
		font-size: 12px;
		line-height: 1.5em;
		border-width: 1px;
		border-style: solid;
		-moz-border-radius: 3px;
		-khtml-border-radius: 3px;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		cursor: pointer;
		text-decoration: none;	
		border-color: #80b5d0;
		background-color: #E5E5E5;
		color: #224466;
	}
	.cfox_button:hover {
		color: #D54E21;
		border-color: #535353;
	}
	<?php
	die();
}

function cfox_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'index.php?cf_action=cfox_admin_css" />';
	echo '<script type="text/javascript" src="'.trailingslashit(get_bloginfo('url')).'index.php?cf_action=cfox_admin_js"></script>';
}
if(isset($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
	add_action('admin_head', 'cfox_admin_head');
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
		$widget_ops = array( 'classname' => 'cfox', 'description' => 'Widget for adding OpenX handlers in the Traditional OpenX way using JavaScript' );
		$this->WP_Widget( 'cfox', 'CF OpenX Ad', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		
		$title = esc_attr( $instance['title'] );
		$zone = $instance['zone'];
		
		echo $before_widget;
		if (!empty($title)) {
			echo $before_title.$title.$after_title;
		}
		echo '<div class="cfox_widget">';
		cfox_js_code($zone);
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['zone'] = strip_tags($new_instance['zone']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'zone' => '' ) );
		$title = esc_attr( $instance['title'] );
		$cfox_options = maybe_unserialize(get_option('cfox_options'));
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('zone'); ?>"><?php _e('Zone:')?></label>
			<select id="<?php echo $this->get_field_id('zone'); ?>" name="<?php echo $this->get_field_name('zone'); ?>" class="widefat">
				<option value="0"<?php selected($instance['zone'], '0'); ?>><?php _e('Select Zone ID:'); ?></option>
				<?php
				if (is_array($cfox_options['zones']) && !empty($cfox_options['zones'])) {
					foreach ($cfox_options['zones'] as $key => $zoneinfo) {
						?>
						<option value="<?php echo attribute_escape($zoneinfo['id']); ?>"<?php selected($instance['zone'], attribute_escape($zoneinfo['id'])); ?>><?php echo attribute_escape($zoneinfo['id'] . ' - '.$zoneinfo['desc']); ?></option>
						<?php
					}
				}
				?>
			</select>
		</p>
		<?php
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('cfox_widget');" ) );

class cfox_preload_widget extends WP_Widget {
	function cfox_preload_widget() {
		$widget_ops = array( 'classname' => 'cfox_preload', 'description' => 'Widget for adding OpenX content using the Preload page method' );
		$this->WP_Widget( 'cfox_preload', 'CF OpenX Preload Ad', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		
		$title = esc_attr( $instance['title'] );
		$zone = $instance['zone'];
		
		echo $before_widget;
		if (!empty($title)) {
			echo $before_title.$title.$after_title;
		}
		echo '<div class="cfox_preload_widget">';
		echo cfox_get_zone_content($zone);
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['zone'] = strip_tags($new_instance['zone']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'zone' => '' ) );
		$title = esc_attr( $instance['title'] );
		$cfox_options = maybe_unserialize(get_option('cfox_options'));
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('zone'); ?>"><?php _e('Zone:')?></label>
			<select id="<?php echo $this->get_field_id('zone'); ?>" name="<?php echo $this->get_field_name('zone'); ?>" class="widefat">
				<option value="0"<?php selected($instance['zone'], '0'); ?>><?php _e('Select Zone ID:'); ?></option>
				<?php
				if (is_array($cfox_options['zones']) && !empty($cfox_options['zones'])) {
					foreach ($cfox_options['zones'] as $key => $zoneinfo) {
						?>
						<option value="<?php echo attribute_escape($zoneinfo['id']); ?>"<?php selected($instance['zone'], attribute_escape($zoneinfo['id'])); ?>><?php echo attribute_escape($zoneinfo['id'] . ' - '.$zoneinfo['desc']); ?></option>
						<?php
					}
				}
				?>
			</select>
		</p>
		<?php
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('cfox_preload_widget');" ) );

/*************************************************/
/************CODE RETRIEVAL FUNCTIONS*************/
/*************************************************/

function cfox_get_js_code($cfox_zoneID = 0) {
	$contexts = '';
	if (function_exists('cfcn_get_context')) {
		$cf_context = cfcn_get_context();

		if (is_array($cf_context) && !empty($cf_context)) { 
			foreach ($cf_context as $key => $value) {
				if (is_array($value) && !empty($value)) {
					$contexts .= '&'.urlencode($key).'=';
					$i = 1;
					foreach ($value as $key2 => $item) {
						$contexts .= urlencode($item);
						if ($i < count($value)) {
							$contexts .= ',';
						}
						$i++;
					}
				}
				else {
					$contexts .= '&'.urlencode($key).'='.urlencode($value);
				}
			}
		}
	}
	
	$cfox_options = get_option('cfox_options');
	
	if($cfox_zoneID == 0 || $cfox_options == '') {
		return false;
	}

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
	if (!empty($contexts)) {
		$return .= 'document.write("'.$contexts.'")';
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
	echo cfox_get_js_code($cfox_zoneID);
}

function cfox_template($cfox_zoneID = 0, $before = '', $after = '', $preload = false) {
	if (empty($before)) { 
		$before = '<div class="cfox_ad">'; 
	}
	if (empty($after)) { 
		$after = '</div>'; 
	}

	echo cfox_get_template($cfox_zoneID, $before, $after, $preload);
}

function cfox_get_template($cfox_zoneID = 0,$before = '',$after = '', $preload = false) {
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

function cfox_get_zone_content($cfox_zoneID) {
	if (empty($cfox_zoneID)) { return ''; }
	
	$contexts = '';
	if (function_exists('cfcn_get_context')) {
		$cf_context = cfcn_get_context();

		if (is_array($cf_context) && !empty($cf_context)) { 
			foreach ($cf_context as $key => $value) {
				if (is_array($value) && !empty($value)) {
					$contexts .= '&'.urlencode($key).'=';
					$i = 1;
					foreach ($value as $key2 => $item) {
						$contexts .= urlencode($item);
						if ($i < count($value)) {
							$contexts .= ',';
						}
						$i++;
					}
				}
				else {
					$contexts .= '&'.urlencode($key).'='.urlencode($value);
				}
			}
		}
	}

	$cfox_options = get_option('cfox_options');
	
	if (!isset($cfox_options['server']) || empty($cfox_options['server'])) {
		return false;
	}
	$random = md5(rand(0, 999999999));
	$url = 'http://'.$cfox_options['server'].'/ajs.php?zoneid='.$cfox_zoneID.'&cb='.$random.$contexts;
	$remote = wp_remote_get($url);
	
	if (!is_array($remote) || is_a($remote, 'WP_Error')) {
		return false;
	}
	
	$content = $remote['body'];
	
	if (strpos($content, '+=') === false) {
		return false;
	}
	
	return '<script type="text/javascript">'.$content.'</script>';
}

/**
 * cfox_shortcode - Function that adds a shortcode so that the Invocation code can be built anywhere the "do_shortcode" function is called.
 * -- The invocation of the shortcode should look like [cfopenx zone="#"]
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



/*************CF LINKS AUTO INSERT FUNCTIONALITY*************/


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
					<input type="hidden" name="cflk_key" value="'.attribute_escape($cflk_key).'" />
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


// Deprecated Widget Functionality for Pre WP 2.7
// function cfox_widget( $args, $widget_args = 1 ) {
// 	extract( $args, EXTR_SKIP );
// 	if ( is_numeric($widget_args) )
// 		$widget_args = array( 'number' => $widget_args );
// 	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
// 	extract( $widget_args, EXTR_SKIP );
// 
// 	$options = get_option('cfox_widget');
// 	if ( !isset($options[$number]) )
// 		return;
// 	$title = $options[$number]['title'];
// 	$zoneID = $options[$number]['zoneID'];
// 	if($title == '') {
// 		echo $before_widget;
// 	}
// 	else {
// 		echo $before_widget . $before_title . $title . $after_title;
// 	}
	// echo '<div class="cfox_widget">';
	// cfox_js_code($zoneID);
	// echo '</div>';
	// echo $after_widget;
// }
// 
// function cfox_widget_control( $widget_args = 1 ) {
// 	global $wp_registered_widgets, $wpdb;
// 	static $updated = false;
// 
// 	if ( is_numeric($widget_args) )
// 		$widget_args = array( 'number' => $widget_args );
// 	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
// 	extract( $widget_args, EXTR_SKIP );
// 
// 	$options = get_option('cfox_widget');
// 	if ( !is_array($options) )
// 		$options = array();
// 
// 	if ( !$updated && !empty($_POST['sidebar']) ) {
// 		$sidebar = (string) $_POST['sidebar'];
// 		$sidebars_widgets = wp_get_sidebars_widgets();
// 		if ( isset($sidebars_widgets[$sidebar]) )
// 			$this_sidebar =& $sidebars_widgets[$sidebar];
// 		else
// 			$this_sidebar = array();
// 
// 		foreach ( $this_sidebar as $_widget_id ) {
// 			if ( 'cfox_widget' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
// 				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
// 				if ( !in_array( "cfox-$widget_number", $_POST['widget-id'] ) )
// 					unset($options[$widget_number]);
// 			}
// 		}
// 		foreach ( (array) $_POST['cfox'] as $widget_number => $cfox_instance ) {
// 			if ( !isset($cfox_instance['title']) && isset($options[$widget_number]) )
// 				continue;
// 			$title = trim(strip_tags(stripslashes($cfox_instance['title'])));
// 			$zoneID = $cfox_instance['zoneID'];
// 			$options[$widget_number] = compact('title','zoneID');
// 		}
// 		update_option('cfox_widget', $options);
// 		$updated = true;
// 	}
// 	if ( -1 == $number ) { 
// 		$title = '';
// 		$zoneID = '';
// 		$number = '%i%';
// 	} else {
// 		$title = attribute_escape($options[$number]['title']);
// 		$zoneID = attribute_escape($options[$number]['zoneID']);
// 	}
	// $cfox_options = maybe_unserialize(get_option('cfox_options'));
/*
// ?>
// 		<p>
// 			<label for="cfox-title-<?php echo $number; ?>"><?php _e('Title: '); ?></label>
// 			<br />
// 			<input id="cfox-title-<?php echo $number; ?>" name="cfox[<?php echo $number; ?>][title]" class="widefat" type="text" value="<?php print(htmlspecialchars($title)); ?>" />
// 		</p>
		<p>
			<label for="cfox-zoneID-<?php echo $number; ?>"><?php _e('Zone: '); ?></label>
			<br />
			<select id="cfox-zoneID-<?php echo $number; ?>" name="cfox[<?php echo $number; ?>][zoneID]" class="widefat" style="max-width: 230px;">
				<option value="0"><?php _e('Select Zone ID:'); ?></option>
				<?php
				foreach($cfox_options['zones'] as $key => $zoneinfo) {
					if($zoneinfo['id'] == $zoneID) {
						$selected = 'selected=selected';
					}
					else {
						$selected = '';
					}
					?>
					<option value="<?php print(attribute_escape($zoneinfo['id'])); ?>" <?php print($selected); ?>><?php print(attribute_escape($zoneinfo['id'] . ' - '.$zoneinfo['desc'])); ?></option>
					<?php
				}
				?>
			</select>
		</p>
		<input type="hidden" id="cfox-submit-<?php echo $number; ?>" name="cfox[<?php echo $number; ?>][submit]" value="1" />
// <?php
// }
*/
// 
// function cfox_widget_register() {
// 	if ( !$options = get_option('cfox_widget') )
// 		$options = array();
// 
// 	$widget_ops = array('classname' => 'cfox_widget', 'description' => __('Widget for serving data from the OpenX ad system. This widget loads data in the traditional OpenX way using JavaScript on page load.'));
// 	$name = __('CF OpenX Ad');
// 
// 	$id = false;
// 	foreach ( array_keys($options) as $o ) {
// 		if ( !isset($options[$o]['title']) )
// 			continue;
// 		$id = "cfox-$o";
// 		wp_register_sidebar_widget( $id, $name, 'cfox_widget', $widget_ops, array( 'number' => $o ) );
// 		wp_register_widget_control( $id, $name, 'cfox_widget_control', array( 'id_base' => 'cfox' ), array( 'number' => $o ) );
// 	}
// 	if ( !$id ) {
// 		wp_register_sidebar_widget( 'cfox-1', $name, 'cfox_widget', $widget_ops, array( 'number' => -1 ) );
// 		wp_register_widget_control( 'cfox-1', $name, 'cfox_widget_control', array( 'id_base' => 'cfox' ), array( 'number' => -1 ) );
// 	}
// }
// add_action( 'widgets_init', 'cfox_widget_register' );
// 
// /*************PRELOAD CONTENT WIDGET**************/
// 
// function cfox_preload_widget( $args, $widget_args = 1 ) {
// 	extract( $args, EXTR_SKIP );
// 	if ( is_numeric($widget_args) )
// 		$widget_args = array( 'number' => $widget_args );
// 	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
// 	extract( $widget_args, EXTR_SKIP );
// 
// 	$options = get_option('cfox_preload_widget');
// 	if ( !isset($options[$number]) )
// 		return;
// 	$title = $options[$number]['title'];
// 	$zoneID = $options[$number]['zoneID'];
// 	if($title == '') {
// 		echo $before_widget;
// 	}
// 	else {
// 		echo $before_widget . $before_title . $title . $after_title;
// 	}
// 	echo '<div class="cfox_preload_widget">';
// 	cfox_get_zone_content($zoneID);
// 	echo '</div>';
// 	echo $after_widget;
// }
// 
// function cfox_preload_widget_control( $widget_args = 1 ) {
// 	global $wp_registered_widgets, $wpdb;
// 	static $updated = false;
// 
// 	if ( is_numeric($widget_args) )
// 		$widget_args = array( 'number' => $widget_args );
// 	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
// 	extract( $widget_args, EXTR_SKIP );
// 
// 	$options = get_option('cfox_preload_widget');
// 	if ( !is_array($options) )
// 		$options = array();
// 
// 	if ( !$updated && !empty($_POST['sidebar']) ) {
// 		$sidebar = (string) $_POST['sidebar'];
// 		$sidebars_widgets = wp_get_sidebars_widgets();
// 		if ( isset($sidebars_widgets[$sidebar]) )
// 			$this_sidebar =& $sidebars_widgets[$sidebar];
// 		else
// 			$this_sidebar = array();
// 
// 		foreach ( $this_sidebar as $_widget_id ) {
// 			if ( 'cfox_preload' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
// 				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
// 				if ( !in_array( "cfox-preload-$widget_number", $_POST['widget-id'] ) )
// 					unset($options[$widget_number]);
// 			}
// 		}
// 		foreach ( (array) $_POST['cfox_preload'] as $widget_number => $cfox_instance ) {
// 			if ( !isset($cfox_instance['title']) && isset($options[$widget_number]) )
// 				continue;
// 			$title = trim(strip_tags(stripslashes($cfox_instance['title'])));
// 			$zoneID = $cfox_instance['zoneID'];
// 			$options[$widget_number] = compact('title','zoneID');
// 		}
// 		update_option('cfox_preload_widget', $options);
// 		$updated = true;
// 	}
// 	if ( -1 == $number ) { 
// 		$title = '';
// 		$zoneID = '';
// 		$number = '%i%';
// 	} else {
// 		$title = attribute_escape($options[$number]['title']);
// 		$zoneID = attribute_escape($options[$number]['zoneID']);
// 	}
// 	$cfox_options = maybe_unserialize(get_option('cfox_options'));
/*
// ?>
// 		<p>
// 			<label for="cfox-preload-title-<?php echo $number; ?>"><?php _e('Title: '); ?></label>
// 			<br />
// 			<input id="cfox-preload-title-<?php echo $number; ?>" name="cfox_preload[<?php echo $number; ?>][title]" class="widefat" type="text" value="<?php print(htmlspecialchars($title)); ?>" />
// 		</p>
// 		<p>
// 			<label for="cfox-preload-zoneID-<?php echo $number; ?>"><?php _e('Zone: '); ?></label>
// 			<br />
// 			<select id="cfox-preload-zoneID-<?php echo $number; ?>" name="cfox_preload[<?php echo $number; ?>][zoneID]" class="widefat" style="max-width: 230px;">
// 				<option value="0"><?php _e('Select Zone ID:'); ?></option>
// 				<?php
// 				foreach($cfox_options['zones'] as $key => $zoneinfo) {
// 					if($zoneinfo['id'] == $zoneID) {
// 						$selected = 'selected=selected';
// 					}
// 					else {
// 						$selected = '';
// 					}
// 					?>
// 					<option value="<?php print(attribute_escape($zoneinfo['id'])); ?>" <?php print($selected); ?>><?php print(attribute_escape($zoneinfo['id'] . ' - '.$zoneinfo['desc'])); ?></option>
// 					<?php
// 				}
// 				?>
// 			</select>
// 		</p>
// 		<input type="hidden" id="cfox-preload-submit-<?php echo $number; ?>" name="cfox_preload[<?php echo $number; ?>][submit]" value="1" />
// <?php
// }
*/
// 
// function cfox_preload_widget_register() {
// 	if ( !$options = get_option('cfox_preload_widget') )
// 		$options = array();
// 
// 	$widget_ops = array('classname' => 'cfox_preload_widget', 'description' => __('Widget for serving data from the OpenX ad system.  This widget loads OpenX Ads pre page load.'));
// 	$name = __('CF OpenX Preload Ad');
// 
// 	$id = false;
// 	foreach ( array_keys($options) as $o ) {
// 		if ( !isset($options[$o]['title']) )
// 			continue;
// 		$id = "cfox-preload-$o";
// 		wp_register_sidebar_widget( $id, $name, 'cfox_preload_widget', $widget_ops, array( 'number' => $o ) );
// 		wp_register_widget_control( $id, $name, 'cfox_preload_widget_control', array( 'id_base' => 'cfox-preload' ), array( 'number' => $o ) );
// 	}
// 	if ( !$id ) {
// 		wp_register_sidebar_widget( 'cfox-preload-1', $name, 'cfox_preload_widget', $widget_ops, array( 'number' => -1 ) );
// 		wp_register_widget_control( 'cfox-preload-1', $name, 'cfox_preload_widget_control', array( 'id_base' => 'cfox-preload' ), array( 'number' => -1 ) );
// 	}
// }
// add_action( 'widgets_init', 'cfox_preload_widget_register' );

?>