<?php
/*
Plugin Name: CF Content Status 
Plugin URI: http://crowdfavorite.com/wordpress/ 
Description: Allows tracking and monitoring of content completion status in preparation for site launch. 
Version: 1.0 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

/* TODO

- attach page to dash menu instead of settings
- plugin action links
- create meta form
- option to track status on post
- save settings with post save
- dashboard page show status

*/

load_plugin_textdomain('cf-content-status');

function cfcs_request_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cfcs_admin_css':
				cfcs_admin_css();
				die();
				break;
		}
	}
}
add_action('init', 'cfcs_request_handler');

function cfcs_admin_css() {
	header('Content-type: text/css');
?>
fieldset.options div.option {
	background: #EAF3FA;
	margin-bottom: 8px;
	padding: 10px;
}
fieldset.options div.option label {
	display: block;
	float: left;
	font-weight: bold;
	margin-right: 10px;
	width: 150px;
}
fieldset.options div.option span.help {
	color: #666;
	font-size: 11px;
	margin-left: 8px;
}
<?php
	die();
}

function cfcs_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.admin_url('?cf_action=cfcs_admin_css" />');
}
add_action('admin_head', 'cfcs_admin_head');

function cfcs_save_post($post_id, $post) {
	if (!empty($_POST['cfcs_content_status_data'])) {
// SAVE DATA HERE
		update_post_meta($post_id, 'cfcs_status', $_POST['cfcs_status']);
		update_post_meta($post_id, 'cfcs_notes', $_POST['cfcs_notes']);
	}
}
add_action('save_post', 'cfcs_save_post');

function cfcs_meta_box() {
	global $post;
?>
	<input type="hidden" name="cfcs_content_status_data" value="1" />
	<label for="cfcs_status">Status</label>
	<select name="cfcs_status" id="cfcs_status">
		<option value="to-do" <?php selected('to-do', get_post_meta($post->ID, 'cfcs_status')); ?>>To Do</option>
		<option value="in-progress" <?php selected('in-progress', get_post_meta($post->ID, 'cfcs_status')); ?>>In Progress</option>
		<option value="to-review" <?php selected('to-review', get_post_meta($post->ID, 'cfcs_status')); ?>>Needs Review</option>
		<option value="complete" <?php selected('complete', get_post_meta($post->ID, 'cfcs_status')); ?>>Complete</option>
	</select>
	<label for="cfcs_notes">Notes</label>
	<textarea name="cfcs_notes" id="cfcs_notes"><?php echo esc_html(get_post_meta($post->ID, 'cfcs_notes')); ?></textarea>
<?php
}
function cfcs_add_meta_box() {
	add_meta_box('cfcs_meta_box', __('CF Content Status', 'cf-content-status'), 'cfcs_meta_box', 'post', 'side');
	add_meta_box('cfcs_meta_box', __('CF Content Status', 'cf-content-status'), 'cfcs_meta_box', 'page', 'side');
}
add_action('admin_init', 'cfcs_add_meta_box');

function cfcs_admin_menu() {
	if (current_user_can('edit_posts')) {
		add_submenu_page(
			'index.php'
			__('Content Status', 'cf-content-status'),
			__('Content Status', 'cf-content-status'),
			10,
			basename(__FILE__),
			'cfcs_status_report'
		);
	}
}
add_action('admin_menu', 'cfcs_admin_menu');

function cfcs_plugin_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$status_link = '<a href="dashboard.php?page='.$plugin_file.'">'.__('Content Status', 'cf-content-status').'</a>';
		array_unshift($links, $status_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'cfcs_plugin_action_links', 10, 2);

function cfcs_status_report() {
	print('
<div class="wrap">
	<h2>'.__('Content Status', 'cf-content-status').'</h2>
	');
	print('
</div>
	');
}

//a:23:{s:11:"plugin_name";s:17:"CF Content Status";s:10:"plugin_uri";s:35:"http://crowdfavorite.com/wordpress/";s:18:"plugin_description";s:91:"Allows tracking and monitoring of content completion status in preparation for site launch.";s:14:"plugin_version";s:3:"1.0";s:6:"prefix";s:4:"cfcs";s:12:"localization";s:17:"cf-content-status";s:14:"settings_title";s:14:"Content Status";s:13:"settings_link";s:14:"Content Status";s:4:"init";b:0;s:7:"install";b:0;s:9:"post_edit";s:1:"1";s:12:"comment_edit";b:0;s:6:"jquery";b:0;s:6:"wp_css";b:0;s:5:"wp_js";b:0;s:9:"admin_css";s:1:"1";s:8:"admin_js";b:0;s:8:"meta_box";s:1:"1";s:15:"request_handler";b:0;s:6:"snoopy";b:0;s:11:"setting_cat";b:0;s:14:"setting_author";b:0;s:11:"custom_urls";b:0;}

?>