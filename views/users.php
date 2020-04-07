<?php
/**
 * The users stats page.
 *
 * @package posts-and-users-stats
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define tabs.
$users_tabs = array(
	'role'      => __( 'Users per Role', 'posts-and-users-stats' ),
	'date'      => __( 'Users over Time', 'posts-and-users-stats' ),
);

// Get the selected tab.
if ( isset( $_GET['tab'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_GET['tab'] ) ), $users_tabs ) ) {
	$selected_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
} else {
	$selected_tab = 'role';
}
?>
<div class="wrap posts-and-users-stats">
	<h1><?php esc_html_e( 'Users Statistics', 'posts-and-users-stats' ); ?> &rsaquo; <?php echo esc_html( $users_tabs[ $selected_tab ] ); ?></h1>

	<nav class="nav-tab-wrapper">
	<?php foreach ( $users_tabs as $tab_slug => $tab_title ) { ?>
		<a href="<?php echo esc_url( admin_url( 'tools.php?page=posts_and_users_stats_users&tab=' ) . sanitize_text_field( $tab_slug ) ); ?>"
			class="<?php posts_and_users_stats_echo_tab_class( $selected_tab == $tab_slug ); ?>"><?php echo esc_html( $tab_title ); ?></a>
	<?php } ?>
	</nav>
	
	<?php
	if ( 'role' == $selected_tab ) {
		$users = count_users();
		$users_per_role = $users['avail_roles']; // extract users per role.
		$users_per_role = array_diff( $users_per_role, array( 0 ) ); // remove roles with count = 0.

		$role_data = get_editable_roles();

		$roles = array();
		foreach ( $users_per_role as $user_role => $count ) {
			$roles[ translate_user_role( $role_data[ $user_role ]['name'] ) ] = $count;
		}
		?>
	<section>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php esc_html_e( 'Users per Role', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-users-roles"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-users-roles',
					[
						<?php
						foreach ( $roles as $user_role => $count ) {
							echo "'" . esc_js( $user_role ) . "',";
						}
						?>
					],
					[
						<?php
						foreach ( $roles as $user_role => $count ) {
							echo esc_js( $count ) . ',';
						}
						?>
					],
					'<?php esc_html_e( 'Role', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Users', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>
		<h3><?php esc_html_e( 'Users per Role', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-users-per-role',
				'table-users-per-role',
				__( 'Users per Role', 'posts-and-users-stats' )
			);
			?>
			</h3>
		<p>
		<?php
		echo sprintf(
			// translators: the number of users.
			esc_html( __( 'There are %s total users.', 'posts-and-users-stats' ) ),
			esc_html( $users['total_users'] )
		);
		?>
		</p>
		<table id="table-users-per-role" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Role', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Number of users', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $roles as $user_role => $count ) { ?>
					<tr>
						<td><?php echo esc_html( translate_user_role( $user_role ) ); ?></td>
						<td><?php echo esc_html( $count ); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	
		<?php
	} else if ( 'date' == $selected_tab ) {
		global $wpdb;
		// phpcs:ignore WordPress.VIP.DirectDatabaseQuery.NoCaching
		$user_registration_dates = $wpdb->get_results(
			'SELECT DATE(user_registered) AS date, count(*) as count
				FROM ' . $wpdb->prefix . 'users
				GROUP BY date ASC',
			OBJECT_K
		);
		?>
	<section>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php esc_html_e( 'Users over Time', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-users-time"></div>
			<script>
				posts_and_users_stats_time_line_chart(
					'#chart-users-time',
					[
						<?php
						$users = 0;
						foreach ( $user_registration_dates as $registration ) {
							$date = strtotime( $registration->date );
							$users += $registration->count;
							echo '{x: new Date(' . esc_js( gmdate( 'Y', $date ) )
								 . ',' . esc_js( gmdate( 'm', $date ) - 1 ) . ','
								 . esc_js( gmdate( 'd', $date ) ) . '), y: '
								 . esc_js( $users ) . '},';
						}
						?>
					],
					'Y-MM-DD',
					'<?php esc_html_e( 'Time', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Users', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>

		<h3><?php esc_html_e( 'Users over Time', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-users-date',
				'table-users-date',
				__( 'Users over Time', 'posts-and-users-stats' )
			);
			?>
			</h3>
		<table id="table-users-date" class="wp-list-table widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'posts-and-users-stats' ); ?></th>
					<th><?php esc_html_e( 'Number of new users', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $user_registration_dates as $registration ) { ?>
				<tr>
					<td><?php echo esc_html( $registration->date ); ?></td>
					<td><?php echo esc_html( $registration->count ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	<?php } ?>
</div>
