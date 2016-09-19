<?php
/*
	Plugin Name: Posts and Users Stats
	Plugin URI: https://patrick-robrecht.de/wordpress/
	Description: Statistics about the number of posts and users
	Version: 0.1
	Author: Patrick Robrecht
	Author URI: https://patrick-robrecht.de/
	License: GPLv2 or later
	License URI: https://www.gnu.org/licenses/gpl-3.0.html
	Text Domain: posts-and-users-stats
	
	Posts and Users Stats is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by the Free Software 
	Foundation, either version 2 of the License, or any later version.
 
	Posts and Users Stats is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
	or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details 
	(see https://www.gnu.org/licenses/gpl-3.0.html).
*/

	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register and load the style sheet.
 */
function posts_and_users_stats_register_and_load_css() {
	wp_register_style(
			'posts-and-users-stats',
			plugins_url(
					'/css/style.css',
					__FILE__
			),
			array()
	);

	wp_enqueue_style( 'posts-and-users-stats' );
}
// Load css file.
add_action( 'admin_print_styles', 'posts_and_users_stats_register_and_load_css' );

/**
 * Register the Highcharts libraries and load these and JQuery.
 */
function posts_and_users_stats_register_and_load_scripts() {
	wp_register_script(
			'highcharts',
			plugins_url(
					'/js/highcharts.js',
					__FILE__
			),
			array( 'jquery' ) 
	);
	wp_register_script(
			'highcharts-exporting',
			plugins_url(
					'/js/exporting.js',
					__FILE__
			)
	);
	wp_register_script(
			'table-to-csv',
			plugins_url(
					'/js/table-to-csv.js',
					__FILE__
			)
	);	
	
// 	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'highcharts' );
	wp_enqueue_script( 'highcharts-exporting' );
	wp_enqueue_script( 'table-to-csv' );
}
// Load JavaScript libraries.
add_action( 'admin_print_scripts', 'posts_and_users_stats_register_and_load_scripts' );

/**
 * Create an item and submenu items in the WordPress admin menu.
 */
function posts_and_users_stats_add_menu() {
	add_management_page(
			__( 'Posts Statistics', 'posts-and-users-stats' ),
			__( 'Posts Statistics', 'posts-and-users-stats' ),
			'export',
			'posts_and_users_stats_posts',
			'posts_and_users_stats_show_posts'
	);
	add_management_page(
			__( 'Users Statistics', 'posts-and-users-stats' ),
			__( 'Users Statistics', 'posts-and-users-stats' ),
			'export',
			'posts_and_users_stats_users',
			'posts_and_users_stats_show_users'
	);
}
// Register the menu building function.
add_action( 'admin_menu', 'posts_and_users_stats_add_menu' );

/**
 * Checks whether the current user is in the admin area and has the capability to see the pages.
 * 
 * @return true if and only if the current user is allowed to see plugin pages
 */
function posts_and_users_stats_current_user_can() {
	return is_admin() && current_user_can( 'export' );
}

/**
 * Show the posts stats page.
 */
function posts_and_users_stats_show_posts() {
	if ( posts_and_users_stats_current_user_can() ) {
		include_once 'views/posts.php';
	}
}

/**
 * Show the users stats page.
 */
function posts_and_users_stats_show_users() {
	if ( posts_and_users_stats_current_user_can() ) {
		include_once 'views/users.php';
	}
}

/**
 * Returns a file name for the export (without file extension).
 * 
 * @param string $name the name of the export
 * @return string the file name
 */
function posts_and_users_stats_get_export_file_name( $name ) {
	$name = strtolower( str_replace(' ', '-', get_bloginfo( 'name' ) . '-' . $name ) );
	return $name . '-' . date( 'Y-m-d-H-i-s' );
}

/**
 * Echo the class attribute of a navigation tab.
 * 
 * @param unknown $is_active_tab true if and only if the tab is active
 */
function posts_and_users_stats_echo_tab_class( $is_active_tab ) {
	echo 'nav-tab';
	if ( $is_active_tab ) {
		echo ' nav-tab-active';
	}
}

/**
 * Outputs a link to the given URL.
 * 
 * @param string $url the URL
 * @param string|int $text the text of the link
 */
function posts_and_users_stats_echo_link( $url, $text ) {
	echo '<a href="' . $url . '">' . $text . '</a>';
}

/**
 * Output the link to a csv export.
 * 
 * @param unknown $button_id the ID of the link
 * @param unknown $table_id the ID of the table to export
 * @param unknown $filename the file name
 */
function posts_and_users_stats_echo_export_button( $button_id, $table_id, $filename ) { ?>
    <a class="page-title-action" href="#" id="<?php echo $button_id; ?>" role="button"><?php _e( 'Export as CSV', 'posts-and-users-stats' ); ?></a>
	<script type='text/javascript'>
	jQuery(document).ready(function () {
		jQuery("#<?php echo $button_id; ?>").click(function (event) {
			exportTableToCSV.apply(this, [jQuery('#<?php echo $table_id; ?>'), '<?php echo $filename; ?>.csv']);
		});
	});
    </script>
<?php } ?>
