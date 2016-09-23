<?php
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	// Define tabs.
	$tabs = array(
			'date' 		=> __( 'Comments per Date', 'posts-and-users-stats' ),
			'author'	=> __( 'Comments per Author', 'posts-and-users-stats' ),
			'status'	=> __( 'Comments per Status', 'posts-and-users-stats' ),
	);
	
	// Get the selected tab.
	if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ) {
		$selected_tab = $_GET['tab'];
	} else {
		$selected_tab = 'date';
	}
	
	$start_time = microtime( true );
?>
<div class="wrap posts-and-users-stats">
	<h1><?php _e( 'Comments Statistics', 'posts-and-users-stats' ); ?> &rsaquo; <?php echo $tabs[ $selected_tab ]; ?></h1>
	
	<h2 class="nav-tab-wrapper">
	<?php foreach( $tabs as $tab_slug => $tab_title ) { ?>
		<a href="<?php echo admin_url( 'tools.php?page=posts_and_users_stats_comments' ) . '&tab=' . sanitize_text_field( $tab_slug ); ?>" 
			class="<?php posts_and_users_stats_echo_tab_class( $selected_tab == $tab_slug ); ?>"><?php echo $tab_title; ?></a>
	<?php } ?>
	</h2>
	
	<?php if ( $selected_tab == 'date' ) {
		global $wpdb;		
		$comments_per_date = $wpdb->get_results(
				"SELECT DATE(comment_date) as date, count(comment_ID) as count
				FROM {$wpdb->comments}
				WHERE comment_approved = 1
				GROUP BY date",
				OBJECT_K
		);
		$comments_per_month = $wpdb->get_results(
				"SELECT DATE_FORMAT(comment_date, '%Y-%m') as month, count(comment_ID) as count
				FROM {$wpdb->comments}
				WHERE comment_approved = 1
				GROUP BY month",
				OBJECT_K
		);
		$comments_per_year = $wpdb->get_results(
				"SELECT YEAR(comment_date) as year, count(comment_ID) as count
				FROM {$wpdb->comments}
				WHERE comment_approved = 1
				GROUP BY year",
				OBJECT_K
		);
		
		$per_date_string = sprintf( __( '%s per Date', 'posts-and-users-stats' ), __( 'Comments', 'posts-and-users-stats' ) );
		$per_month_string = sprintf( __( '%s per Month', 'posts-and-users-stats' ), __( 'Comments', 'posts-and-users-stats' ) );
	?>
	<nav>
		<?php if ( !is_array( $comments_per_date ) || sizeof( $comments_per_date ) <= 0) { ?>
		<p><?php _e( 'No approved comments found!', 'posts-and-users-stats' ); ?>
		<?php } else { ?>
		<ul>
			<li><a href="#monthly"><?php echo $per_month_string; ?></a>
		<?php foreach( $comments_per_year as $year_object ) { ?>
			<li><a href="#<?php echo $year_object->year; ?>"><?php echo __( 'Year', 'posts-and-users-stats' ) . ' ' . $year_object->year; ?></a></li>
		<?php } ?>
		</ul>
	</nav>
	<section>	
		<div id="chart-monthly" class="chart"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-monthly').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo $per_month_string; ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					type: 'datetime',
					dateTimeLabelFormats: {
						month: '%m/%Y',
					},
		            title: {
		                text: '<?php _e( 'Month', 'posts-and-users-stats' ); ?>'
		            }
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>',
					},
					min: 0
				},
				legend: {
					enabled: false,
				},
				series: [ {
					name: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ <?php foreach( $comments_per_month as $comments_of_month ) {
								$date = strtotime( $comments_of_month->month . '-01' );
								$year = date( 'Y', $date );
								$month = date( 'm', $date );
								echo '[Date.UTC(' . $year . ',' . ( $month - 1 ) . ',1),' . $comments_of_month->count . '], ';
							} ?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( $per_month_string ); ?>'
				}
			});
		});
		</script>
		
		<div id="chart-daily" class="chart"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-daily').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo $per_date_string; ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					type: 'datetime',
		            title: {
		                text: '<?php _e( 'Date', 'posts-and-users-stats' ); ?>'
		            }
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>',
					},
					min: 0
				},
		        plotOptions: {
		            spline: {
		                marker: {
		                    enabled: true
		                }
		            }
		        },
				legend: {
					enabled: false,
				},
				series: [ {
					name: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ <?php foreach( $comments_per_date as $comments_of_date ) {
								$date = strtotime( $comments_of_date->date );
								$year = date( 'Y', $date );
								$month = date( 'm', $date );
								$day = date( 'd', $date );
								echo '[Date.UTC(' . $year . ',' . ( $month - 1 ) . ',' . $day .'),' . $comments_of_date->count . '], ';
							} ?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( $per_date_string ); ?>'
				}
			});
		});
		</script>
		<h3 id="monthly"><?php ; ?>
			<?php posts_and_users_stats_echo_export_button(
				'csv-monthly',
				'table-monthly',
				posts_and_users_stats_get_export_file_name( $per_month_string )
			); ?></h3>
		<table id="table-monthly" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="row"><?php _e( 'Month', 'posts-and-users-stats' ); ?></th>
					<?php foreach( range( 1, 12, 1) as $month ) { ?>
					<th scope="col"><?php echo date_i18n( 'M', strtotime( '2016-' . $month . '-1' ) ); ?></th>
					<?php } ?>
					<th scope="col"><?php _e( 'Sum', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $comments_per_year as $year_object ) {
					$year = $year_object->year; ?>
				<tr>
					<th scope="row"><a href="#<?php echo $year ?>"><?php echo $year ?></a></th>
					<?php foreach( range( 1, 12, 1) as $month ) { ?>
					<td class="number"><?php 
							$date = date( 'Y-m', strtotime( $year . '-' . $month . '-1' ) );
							if ( array_key_exists( $date, $comments_per_month ) ) {
								echo $comments_per_month[ $date ]->count;
							} else {
								echo 0;
							} ?></td>
					<?php } ?>
					<td class="number"><?php echo $year_object->count; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>	
		<?php foreach( $comments_per_year as $year_object ) {
			$year = $year_object->year; ?>
	<section>
		<h3 id="<?php echo $year; ?>"><?php echo __( 'Year', 'posts-and-users-stats' ) . ' ' . $year; ?>
			<?php posts_and_users_stats_echo_export_button(
				'csv-daily-' . $year,
				'table-daily-' . $year,
				posts_and_users_stats_get_export_file_name( $per_date_string . '-' . $year )
			); ?></h3>
		<table id="table-daily-<?php echo $year; ?>" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="row"><?php _e( 'Month', 'posts-and-users-stats' ); ?></th>
					<?php foreach( range( 1, 12, 1) as $month ) { ?>
					<th scope="col"><?php echo date_i18n( 'M', strtotime( '2016-' . $month . '-1' ) ); ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach( range( 1, 31, 1) as $day ) { ?>
				<tr>
					<th scope="row"><?php echo __( 'Day', 'posts-and-users-stats' ) . ' ' . $day; ?></th>
					<?php foreach( range( 1, 12, 1) as $month ) { ?>
					<td class="number"><?php 
							if ( checkdate( $month, $day, $year ) ) {
								$date = date( 'Y-m-d', strtotime( $year . '-' . $month . '-' . $day ) );
								if ( array_key_exists( $date, $comments_per_date ) ) {
									echo $comments_per_date[ $date ]->count;
								} else {
									echo 0;
								}
							} else {
								echo '-';
							} ?></td>
					<?php } ?>
				</tr>
				<?php } ?>
				<tr>
					<th scope="row"><strong><?php _e( 'Sum', 'posts-and-users-stats' ); ?></strong></th>
					<?php foreach( range( 1, 12, 1) as $month ) { ?>
					<td class="number"><strong><?php 
							$date = date( 'Y-m', strtotime( $year . '-' . $month . '-1' ) );
							if ( array_key_exists( $date, $comments_per_month ) ) {
								echo $comments_per_month[ $date ]->count;
							} else {
								echo 0;
							} ?></strong></td>
					<?php } ?>
				</tr>
			</tbody>
		</table>
	</section>
		<?php 	}
			} // end if ?>
	
	<?php } else if ( $selected_tab == 'author' ) {
		global $wpdb;
		$comments_per_author = $wpdb->get_results(
				"SELECT comment_author as author, count(comment_ID) as count
				FROM {$wpdb->comments}
				WHERE comment_approved = 1
				GROUP BY author",
				OBJECT_K
		);
	?>
	<section>
		<div id="chart-authors" class="chart"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-authors').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Comments per Author', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					categories: [
						<?php foreach( $comments_per_author as $author ) {
							echo "'" . $author->author . "',";
						} ?> ],
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>'
					},
					min: 0
				},
				legend: {
					enabled: false,
				},
				series: [ {
					name: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ <?php foreach( $comments_per_author as $author ) {
								echo $author->count . ',';
							} ?> ]
				} ],
				credits: {
					enabled: false
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __( 'Comments per Author', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
		<h3><?php echo __( 'Comments per Author', 'posts-and-users-stats' ); ?>
			<?php posts_and_users_stats_echo_export_button (
				'csv-authors',
				'table-authors',
				posts_and_users_stats_get_export_file_name( __('Comments per Author', 'posts-and-users-stats' ) )
			); ?></h3>
		<table id="table-authors" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Author', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php _e( 'Comments', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $comments_per_author as $author ) { ?>
				<tr>
					<td><?php echo $author->author; ?></td>
					<td class="number"><?php echo $author->count; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	
	<?php } else if ( $selected_tab == 'status' ) {
		$comments_per_status = wp_count_comments();
		
		$wp_comment_statuses = get_comment_statuses();
		$comment_statuses = array(
				'approved' => $wp_comment_statuses['approve'],
				'spam' => $wp_comment_statuses['spam'],
				'trash' => $wp_comment_statuses['trash'],
				'post-trashed' => __( 'Post trashed', 'posts-and-users-stats' ),
				'moderated' => __( 'Pending', 'posts-and-users-stats' )
		); ?>
	<section>
		<div id="chart-status" class="chart"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-status').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Comments per Status', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					categories: [ 
						<?php foreach( $comment_statuses as $status => $name ) {
							echo "'" . $name . "',";
						} ?> ],
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>'
					},
					min: 0
				},
				legend: {
					enabled: false,
				},
				series: [ {
					name: '<?php _e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ <?php foreach( $comment_statuses as $status => $name ) {
								echo $comments_per_status->$status . ',';
							} ?> ]
				} ],
				credits: {
					enabled: false
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __( 'Comments per Status', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
		<h3><?php _e( 'Comments per Status', 'posts-and-users-stats' ); ?>
			<?php posts_and_users_stats_echo_export_button (
				'csv-status',
				'table-status',
				posts_and_users_stats_get_export_file_name( __( 'Comments per Status', 'posts-and-users-stats' ) )
			); ?></h3>
		<table id="table-status" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Status', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php _e( 'Comments', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $comment_statuses as $status => $name ) { ?>
				<tr>
					<td><?php echo $name; ?></td>
					<td class="number"><?php echo $comments_per_status->$status; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	<?php } ?>
	<?php $end_time = microtime( true ); ?>
	<p><?php echo sprintf( __( 'Statistics generated in %s seconds.', 'posts-and-users-stats' ), number_format_i18n( $end_time - $start_time, 2 ) ); ?></p>
</div>