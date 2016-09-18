<?php
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) exit;

	$tabs = array(
			'role' 		=> __( 'Users per Role', 'posts-and-users-stats' ),
			'date'		=> __( 'Users Accounts', 'posts-and-users-stats' ),
	);
	if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ) {
		$selected_tab = $_GET['tab'];
	} else {
		$selected_tab = 'role';
	}
	
	$start_time = microtime( true );
?>
<div class="wrap posts-and-users-stats">
	<h1><?php _e( 'Users Statistics', 'posts-and-users-stats' ); ?> &rsaquo; <?php echo $tabs[ $selected_tab ]; ?></h1>

	<h2 class="nav-tab-wrapper">
	<?php foreach( $tabs as $tab_slug => $tab_title ) { ?>
		<a href="<?php echo admin_url( 'tools.php?page=posts_and_users_stats_users' ) . '&tab=' . sanitize_text_field( $tab_slug ); ?>" 
			class="<?php posts_and_users_stats_echo_tab_class( $selected_tab == $tab_slug ); ?>"><?php echo $tab_title; ?></a>
	<?php } ?>
	</h2>
	
	<?php if ( $selected_tab == 'role' ) {
		$users = count_users();
		$roles = $users['avail_roles'];
		$roles = array_diff( $roles, array( 0 ) ); // removes roles with count = 0
	?>
	<div>
		<h2><?php _e( 'Users per Role', 'posts-and-users-stats' ) ?></h2>
		<p><?php echo sprintf( __('There are %s total users.', 'posts-and-users-stats' ), $users['total_users'] ); ?></p>
		<div id="chart-roles"></div>
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Role', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php _e( 'Number of users', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $roles as $role => $count ) { ?>
					<tr>
						<td><?php echo $role; ?></td>
						<td><?php echo $count; ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<script>
		jQuery(function() {
			jQuery('#chart-roles').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Users per Role', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					categories: [ 
						<?php foreach( $roles as $role => $count ) {
							echo "'" . $role . "',";
						}?> ],
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Users', 'posts-and-users-stats' ); ?>'
					}
				},
				legend: {
					enabled: false,
				},
				series: [ {
					data: [ <?php foreach( $users['avail_roles'] as $role => $count ) {
						echo $count . ','; 
						}?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __('Users per Role', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
	</div>
	
	<?php } else if ( $selected_tab == 'date' ) {
		global $wpdb;
		$user_registration_dates = $wpdb->get_results( "SELECT DATE(user_registered) AS date, count(*) as count
				FROM " . $wpdb->prefix . "users
				GROUP BY date ASC");
	?>
	<div>
		<div id="chart-registration"></div>
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th><?php _e( 'Date', 'posts-and-users-stats' ); ?></th>
					<th><?php _e( 'Number of new users', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php foreach( $user_registration_dates as $registration ) { ?>
				<tr>
					<td><?php echo $registration->date; ?></td>
					<td><?php echo $registration->count; ?></td>
				</tr>
		<?php } ?>
			</tbody>
		</table>
		<script>
		jQuery(function() {
			jQuery('#chart-registration').highcharts({
				chart: {
					type: 'spline'
				},
				title: {
					text: '<?php _e( 'Users Accounts', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					type: 'datetime',
					dateTimeLabelFormats: {
						month: '%m \'%y',
					},
		            title: {
		                text: '<?php _e( 'Users Accounts', 'posts-and-users-stats' ); ?>'
		            }
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Users Accounts', 'posts-and-users-stats' ); ?>',
						min: 0
					}
				},
				legend: {
					enabled: false,
				},
				series: [ {
					name: '<?php _e( 'Users', 'posts-and-users-stats' ); ?>',
					data: [ <?php $users = 0;
						foreach( $user_registration_dates as $registration ) {
							$date = strtotime( $registration->date );
							$year = date( 'Y', $date );
							$month = date( 'm', $date );
							$day = date( 'd', $date );
							$users += $registration->count;
							echo '[Date.UTC(' . $year . ',' . ( $month - 1 ) . ',' . $day . '), ' . $users . '], '; 
						}?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __('User Accounts', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
	</div>
	<?php } ?>
	<?php $end_time = microtime( true ); ?>
	<p><?php echo sprintf( __( 'Statistics generated in %s seconds.', 'posts-and-users-stats' ), number_format_i18n( $end_time - $start_time, 2 ) ); ?></p>
</div>