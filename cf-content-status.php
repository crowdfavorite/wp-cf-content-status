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
#cfcs_meta_box label {
	display: block;
}
#cfcs_meta_box label.checkbox {
	display: inline;
}
.cfcs-status {
	border-radius: 3px;
	-moz-border-radius: 3px;
	-webkit-border-radius: 3px;
	padding: 3px 5px;
}
.cfcs-status-to-do {
	background: #f4d4d4;
}
.cfcs-status-in-progress {
	background: #ffc;
}
.cfcs-status-to-review {
	background: #e0edf5;
}
.cfcs-status-complete {
	background: #c5eac0;
}
<?php
	die();
}

function cfcs_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.admin_url('?cf_action=cfcs_admin_css" />');
}
add_action('admin_head', 'cfcs_admin_head');

function cfcs_save_post($post_id) {
	$update = false;
	if (!empty($_POST['cfcs_content_status_data'])) {
		$post = get_post($post_id);
		if ($post->post_type == 'page') {
			$update = true;
		}
		else if ($post->post_type == 'post') {
			if (!empty($_POST['cfcs_track_status'])) {
				$update = true;
				$track = 1;
			}
			else {
				$track = 0;
			}
			update_post_meta($post_id, '_cfcs_track_status', $track);
		}
		if ($update) {
			update_post_meta($post_id, '_cfcs_status', $_POST['cfcs_status']);
			update_post_meta($post_id, '_cfcs_notes', $_POST['cfcs_notes']);
		}
	}
}
add_action('save_post', 'cfcs_save_post');

function cfcs_statuses() {
	return array(
		'to-do' => 'To Do',
		'in-progress' => 'In Progress',
		'to-review' => 'To Review',
		'complete' => 'Complete',
	);
}

function cfcs_meta_box() {
	global $post;
	$class = '';
	switch ($post->post_type) {
		case 'post':
			$track = get_post_meta($post->ID, '_cfcs_track_status', true);
			if (!$track) {
				$class = 'hidden';
			}
?>
	<p>
		<input type="checkbox" name="cfcs_track_status" id="cfcs_track_status" value="1" <?php checked('1', $track); ?> />
		<label for="cfcs_track_status" class="checkbox">Track Status</label>
	</p>
<?php
		break;
	}
	$options = cfcs_statuses();
?>
	<input type="hidden" name="cfcs_content_status_data" value="1" />
	<div class="settings <?php echo $class; ?>">
	<p>
		<label for="cfcs_status">Status</label>
		<select name="cfcs_status" id="cfcs_status">
<?php
	foreach ($options as $k => $v) {
?>
			<option value="<?php echo $k; ?>" <?php selected($k, get_post_meta($post->ID, '_cfcs_status', true)); ?>><?php echo $v; ?></option>
<?php
}
?>
		</select>
	</p>
	<p>
		<label for="cfcs_notes">Notes</label>
		<textarea name="cfcs_notes" id="cfcs_notes"><?php echo esc_html(get_post_meta($post->ID, '_cfcs_notes', true)); ?></textarea>
	</p>
	</div>
	<script type="text/javascript">
	jQuery(function($) {
		$('#cfcs_track_status').unbind('click').click(function(event) {
			var settingsDiv = $(this).parents('#cfcs_meta_box').find('div.settings');
			if ($(this).is(':checked')) {
				settingsDiv.removeClass('hidden');
			}
			else {
				settingsDiv.addClass('hidden');
			}
			if ($.browser.msie) {
				event.cancelBubble = true;
			}
			else {
				event.stopPropagation();
			}
		});
	});
	</script>
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
			'index.php',
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
		$status_link = '<a href="index.php?page='.$plugin_file.'">'.__('Content Status', 'cf-content-status').'</a>';
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
	$pages = query_posts('post_type=page&posts_per_page=9999&orderby=title&order=ASC');
	if (count($pages)) {
		add_filter('manage_edit-pages_columns', 'cfcs_edit_pages_cols');
?>
<table class="widefat page fixed" cellspacing="0" style="margin-bottom: 20px;">
  <thead>
  <tr>
<?php print_column_headers('edit-pages'); ?>
  </tr>
  </thead>

  <tfoot>
  <tr>
<?php print_column_headers('edit-pages', false); ?>
  </tr>
  </tfoot>

  <tbody>
  <?php page_rows($pages, 1, 9999); ?>
  </tbody>
</table>
<?php
		remove_filter('manage_edit-pages_columns', 'cfcs_edit_pages_cols');
	}
	$posts = query_posts('meta_key=_cfcs_track_status&meta_value=1&posts_per_page=9999&orderby=title&order=ASC');
	if (count($posts)) {
		add_filter('manage_edit_columns', 'cfcs_edit_posts_cols');
?>
<table class="widefat post fixed" cellspacing="0">
	<thead>
	<tr>
<?php print_column_headers('edit'); ?>
	</tr>
	</thead>

	<tfoot>
	<tr>
<?php print_column_headers('edit', false); ?>
	</tr>
	</tfoot>

	<tbody>
<?php post_rows(); ?>
	</tbody>
</table>
<?php
		remove_filter('manage_edit_columns', 'cfcs_edit_posts_cols');
	}
	print('
</div>
	');
}

function cfcs_edit_pages_cols($cols) {
	unset($cols['cb']);
	$cols['title'] = 'Pages';
	$cols['cfcs-status'] = 'Status';
	$cols['cfcs-notes'] = 'Notes';
	return $cols;
}

function cfcs_edit_posts_cols($cols) {
	unset($cols['cb']);
	$cols['title'] = 'Posts';
	$cols['cfcs-status'] = 'Status';
	$cols['cfcs-notes'] = 'Notes';
	return $cols;
}

function cfcs_edit_list_col($name, $post_id) {
	switch ($name) {
		case 'cfcs-status':
			$statuses = cfcs_statuses();
			$show = '';
			$val = get_post_meta($post_id, '_cfcs_status', true);
			if (isset($statuses[$val])) {
				$show = $statuses[$val];
			}
			echo '<span class="cfcs-status cfcs-status-'.esc_html($val).'">'.esc_html($show).'</span>';
		break;
		case 'cfcs-notes':
			echo esc_html(get_post_meta($post_id, '_cfcs_notes', true));
		break;
	}
}
add_action('manage_pages_custom_column', 'cfcs_edit_list_col', 10, 2);
add_action('manage_posts_custom_column', 'cfcs_edit_list_col', 10, 2);

//a:23:{s:11:"plugin_name";s:17:"CF Content Status";s:10:"plugin_uri";s:35:"http://crowdfavorite.com/wordpress/";s:18:"plugin_description";s:91:"Allows tracking and monitoring of content completion status in preparation for site launch.";s:14:"plugin_version";s:3:"1.0";s:6:"prefix";s:4:"cfcs";s:12:"localization";s:17:"cf-content-status";s:14:"settings_title";s:14:"Content Status";s:13:"settings_link";s:14:"Content Status";s:4:"init";b:0;s:7:"install";b:0;s:9:"post_edit";s:1:"1";s:12:"comment_edit";b:0;s:6:"jquery";b:0;s:6:"wp_css";b:0;s:5:"wp_js";b:0;s:9:"admin_css";s:1:"1";s:8:"admin_js";b:0;s:8:"meta_box";s:1:"1";s:15:"request_handler";b:0;s:6:"snoopy";b:0;s:11:"setting_cat";b:0;s:14:"setting_author";b:0;s:11:"custom_urls";b:0;}

?>