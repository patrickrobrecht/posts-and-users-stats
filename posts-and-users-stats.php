<?php
/**
 * Plugin Name: Posts and Users Stats
 * Plugin URI: https://patrick-robrecht.de/wordpress/
 * Description: Statistics about the number of posts and users, provided as diagrams, tables and csv export.
 * Version: 1.1.4
 * Author: Patrick Robrecht
 * Author URI: https://patrick-robrecht.de/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: posts-and-users-stats
 *
 * @package posts-and-users-stats
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define( 'POSTS_AND_USERS_STATS_VERSION', '1.1.4' );

/**
 * Load text domain for translation.
 */
function posts_and_users_stats_load_plugin_textdomain() {
	load_plugin_textdomain( 'posts-and-users-stats' );
}
// Add text domain during initialization.
add_action( 'init', 'posts_and_users_stats_load_plugin_textdomain' );

/**
 * Load CSS and JavaScript libraries.
 */
function posts_and_users_stats_load_assets() {
	if ( posts_and_users_stats_current_user_can() ) {
		posts_and_users_stats_enqueue_style(
			'posts-and-users-stats-css',
			'/assets/style.min.css'
		);
		posts_and_users_stats_enqueue_style(
			'chartist-css',
			'/lib/chartist.min.css'
		);

		posts_and_users_stats_enqueue_script(
			'chartist',
			'/lib/chartist.min.js'
		);
		posts_and_users_stats_enqueue_script(
			'chartist-plugin-axistitle',
			'/lib/chartist-plugin-axistitle.min.js'
		);
		posts_and_users_stats_enqueue_script(
			'moment',
			'/lib/moment.min.js'
		);
		posts_and_users_stats_enqueue_script(
			'posts-and-users-stats-functions',
			'/assets/functions.min.js'
		);
	}
}

/**
 * Loads the CSS file.
 *
 * @param string $style_name the name of the style.
 * @param string $style_path the plugin-relative path of the CSS file.
 */
function posts_and_users_stats_enqueue_style( $style_name, $style_path ) {
	wp_enqueue_style(
		$style_name,
		plugins_url(
			$style_path,
			__FILE__
		),
		[],
		POSTS_AND_USERS_STATS_VERSION
	);
}

/**
 * Loads the JavaScript file.
 *
 * @param string $script_name the name of the script.
 * @param string $script_path the plugin-relative path of the JavaScript.
 * @param array  $dependencies the dependencies.
 */
function posts_and_users_stats_enqueue_script( $script_name, $script_path, $dependencies = [] ) {
	wp_enqueue_script(
		$script_name,
		plugins_url(
			$script_path,
			__FILE__
		),
		$dependencies,
		POSTS_AND_USERS_STATS_VERSION
	);
}

/**
 * Create an item and submenu items in the WordPress admin menu.
 */
function posts_and_users_stats_add_menu() {
	$page_hook_suffixes = array();
	$page_hook_suffixes[] = add_management_page(
		__( 'Posts Statistics', 'posts-and-users-stats' ),
		__( 'Posts Statistics', 'posts-and-users-stats' ),
		'export',
		'posts_and_users_stats_posts',
		'posts_and_users_stats_show_posts'
	);
	$page_hook_suffixes[] = add_management_page(
		__( 'Comments Statistics', 'posts-and-users-stats' ),
		__( 'Comments Statistics', 'posts-and-users-stats' ),
		'export',
		'posts_and_users_stats_comments',
		'posts_and_users_stats_show_comments'
	);
	$page_hook_suffixes[] = add_management_page(
		__( 'Users Statistics', 'posts-and-users-stats' ),
		__( 'Users Statistics', 'posts-and-users-stats' ),
		'export',
		'posts_and_users_stats_users',
		'posts_and_users_stats_show_users'
	);

	// Load CSS and JavaScript on plugin pages.
	foreach ( $page_hook_suffixes as $page_hook_suffix ) {
		add_action( "admin_print_styles-{$page_hook_suffix}", 'posts_and_users_stats_load_assets' );
	}
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
 * Show the comments stats page.
 */
function posts_and_users_stats_show_comments() {
	if ( posts_and_users_stats_current_user_can() ) {
		include_once 'views/comments.php';
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
 * Echo the class attribute of a navigation tab.
 *
 * @param bool $is_active_tab true if and only if the tab is active.
 */
function posts_and_users_stats_echo_tab_class( $is_active_tab ) {
	echo 'nav-tab';
	if ( $is_active_tab ) {
		echo ' nav-tab-active';
	}
}

/**
 * Output the link to a csv export.
 *
 * @param string $button_id the ID of the link.
 * @param string $table_id the ID of the table to export.
 * @param string $name the file name.
 */
function posts_and_users_stats_echo_export_button( $button_id, $table_id, $name ) {
	$filename = str_replace( ' ', '-', get_bloginfo( 'name' ) . '-' . $name ) . '-' . gmdate( 'Y-m-d-H-i-s' ); ?>
	<a class="page-title-action" href="#" id="<?php echo esc_attr( $button_id ); ?>" role="button"><?php esc_html_e( 'Export as CSV', 'posts-and-users-stats' ); ?></a>
	<script type='text/javascript'>
	jQuery(document).ready(function () {
		jQuery("#<?php echo esc_attr( $button_id ); ?>").click(function (event) {
			posts_and_users_stats_export_table_to_csv.apply(this, [jQuery('#<?php echo esc_attr( $table_id ); ?>'), '<?php echo esc_attr( $filename ); ?>.csv']);
		});
	});
	</script>
<?php } ?>
