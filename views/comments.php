<?php
/**
 * The comments stats page.
 *
 * @package posts-and-users-stats
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define tabs.
$tabs = array(
	'date'      => __( 'Comments per Date', 'posts-and-users-stats' ),
	'author'    => __( 'Comments per Author', 'posts-and-users-stats' ),
	'status'    => __( 'Comments per Status', 'posts-and-users-stats' ),
);

// Get the selected tab.
if ( isset( $_GET['tab'] ) && array_key_exists( wp_unslash( $_GET['tab'] ), $tabs ) ) {
	$selected_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
} else {
	$selected_tab = 'date';
}
?>
<div class="wrap posts-and-users-stats">
	<h1><?php esc_html_e( 'Comments Statistics', 'posts-and-users-stats' ); ?> &rsaquo; <?php echo esc_html( $tabs[ $selected_tab ] ); ?></h1>
	
	<h2 class="nav-tab-wrapper">
	<?php foreach ( $tabs as $tab_slug => $tab_title ) { ?>
		<a href="<?php echo esc_url( admin_url( 'tools.php?page=posts_and_users_stats_comments&tab=' ) . sanitize_text_field( $tab_slug ) ); ?>"
			class="<?php posts_and_users_stats_echo_tab_class( $selected_tab == $tab_slug ); ?>"><?php echo esc_html( $tab_title ); ?></a>
	<?php } ?>
	</h2>
	
	<?php
	if ( 'date' == $selected_tab ) {
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

		$per_date_string = __( 'Comments per Date', 'posts-and-users-stats' );
		$per_month_string = __( 'Comments per Month', 'posts-and-users-stats' );
	?>
	<nav>
		<?php if ( ! is_array( $comments_per_date ) || count( $comments_per_date ) <= 0 ) { ?>
		<p><?php esc_html_e( 'No approved comments found!', 'posts-and-users-stats' ); ?>
		<?php } else { ?>
		<ul>
			<li><a href="#monthly"><?php echo esc_html( $per_month_string ); ?></a>
		<?php foreach ( $comments_per_year as $year_object ) { ?>
			<li><a href="#<?php echo esc_attr( $year_object->year ); ?>">
					<?php esc_html_e( 'Year', 'posts-and-users-stats' ); ?>
					<?php echo esc_html( $year_object->year ); ?></a></li>
		<?php } ?>
		</ul>
	</nav>
	<section>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				TITLE
			</div>
			<div id="chart-users-roles"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-users-roles',
					[
						XDATA
					],
					[
						YDATA
					],
					'XTITLE',
					'YTITLE'
				)
			</script>
		</div>

		<div id="chart-monthly" class="chart"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-monthly').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo esc_js( $per_month_string ); ?>'
				},
				subtitle: {
					text: '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>'
				},
				xAxis: {
					type: 'datetime',
					dateTimeLabelFormats: {
						month: '%m/%Y'
					},
					title: {
						text: '<?php esc_html_e( 'Month', 'posts-and-users-stats' ); ?>'
					}
				},
				yAxis: {
					title: {
						text: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>'
					},
					min: 0
				},
				legend: {
					enabled: false
				},
				series: [ {
					name: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ 
					<?php
					foreach ( $comments_per_month as $comments_of_month ) {
						$date = strtotime( $comments_of_month->month . '-01' );
						$year = date( 'Y', $date );
						$month = date( 'm', $date );
						echo '[Date.UTC(' . esc_js( $year ) . ',' . esc_js( $month - 1 ) . ',1),' . esc_js( $comments_of_month->count ) . '], ';
					}
					?>
					]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo esc_js( posts_and_users_stats_get_export_file_name( $per_month_string ) ); ?>'
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
					text: '<?php echo esc_js( $per_date_string ); ?>'
				},
				subtitle: {
					text: '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>'
				},
				xAxis: {
					type: 'datetime',
					title: {
						text: '<?php esc_html_e( 'Date', 'posts-and-users-stats' ); ?>'
					}
				},
				yAxis: {
					title: {
						text: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>'
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
					enabled: false
				},
				series: [ {
					name: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ 
					<?php
					foreach ( $comments_per_date as $comments_of_date ) {
						$date = strtotime( $comments_of_date->date );
						$year = date( 'Y', $date );
						$month = date( 'm', $date );
						$day = date( 'd', $date );
						echo '[Date.UTC(' . esc_js( $year ) . ',' . esc_js( $month - 1 ) . ',' . esc_js( $day ) . '),' . esc_js( $comments_of_date->count ) . '], ';
					}
					?>
					]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo esc_js( posts_and_users_stats_get_export_file_name( $per_date_string ) ); ?>'
				}
			});
		});
		</script>
		<h3 id="monthly"><?php ; ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-monthly',
				'table-monthly',
				posts_and_users_stats_get_export_file_name( $per_month_string )
			);
			?>
			</h3>
		<table id="table-monthly" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="row"><?php esc_html_e( 'Month', 'posts-and-users-stats' ); ?></th>
					<?php foreach ( range( 1, 12, 1 ) as $month ) { ?>
					<th scope="col"><?php echo esc_html( date_i18n( 'M', strtotime( '2016-' . $month . '-1' ) ) ); ?></th>
					<?php } ?>
					<th scope="col"><?php esc_html_e( 'Sum', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $comments_per_year as $year_object ) {
					$year = $year_object->year;
					?>
				<tr>
					<th scope="row"><a href="#<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $year ); ?></a></th>
					<?php foreach ( range( 1, 12, 1 ) as $month ) { ?>
					<td class="number">
					<?php
					$date = date( 'Y-m', strtotime( $year . '-' . $month . '-1' ) );
					if ( array_key_exists( $date, $comments_per_month ) ) {
						echo esc_html( $comments_per_month[ $date ]->count );
					} else {
						echo 0;
					}
					?>
					</td>
					<?php } ?>
					<td class="number"><?php echo esc_html( $year_object->count ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>	
		<?php
		foreach ( $comments_per_year as $year_object ) {
			$year = $year_object->year;
		?>
	<section>
		<h3 id="<?php echo esc_attr( $year ); ?>">
			<?php
			esc_html_e( 'Year', 'posts-and-users-stats' );
			echo ' ' . esc_html( $year );
			posts_and_users_stats_echo_export_button(
				'csv-daily-' . $year,
				'table-daily-' . $year,
				posts_and_users_stats_get_export_file_name( $per_date_string . '-' . $year )
			);
			?>
			</h3>
		<table id="table-daily-<?php echo esc_attr( $year ); ?>" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="row"><?php esc_html_e( 'Month', 'posts-and-users-stats' ); ?></th>
					<?php foreach ( range( 1, 12, 1 ) as $month ) { ?>
					<th scope="col"><?php echo esc_html( date_i18n( 'M', strtotime( '2016-' . $month . '-1' ) ) ); ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( range( 1, 31, 1 ) as $day ) { ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Day', 'posts-and-users-stats' ); ?> <?php echo esc_html( $day ); ?></th>
					<?php foreach ( range( 1, 12, 1 ) as $month ) { ?>
					<td class="number">
					<?php
					if ( checkdate( $month, $day, $year ) ) {
						$date = date( 'Y-m-d', strtotime( $year . '-' . $month . '-' . $day ) );
						if ( array_key_exists( $date, $comments_per_date ) ) {
							echo esc_html( $comments_per_date[ $date ]->count );
						} else {
							echo 0;
						}
					} else {
						echo '&mdash;';
					}
							?>
							</td>
					<?php } ?>
				</tr>
				<?php } ?>
				<tr>
					<th scope="row"><strong><?php esc_html_e( 'Sum', 'posts-and-users-stats' ); ?></strong></th>
					<?php foreach ( range( 1, 12, 1 ) as $month ) { ?>
					<td class="number"><strong>
					<?php
					$date = date( 'Y-m', strtotime( $year . '-' . $month . '-1' ) );
					if ( array_key_exists( $date, $comments_per_month ) ) {
						echo esc_html( $comments_per_month[ $date ]->count );
					} else {
						echo 0;
					}
					?>
					</strong></td>
					<?php } ?>
				</tr>
			</tbody>
		</table>
	</section>
		<?php
		}
} // end if
?>
	
	<?php
	} else if ( 'author' == $selected_tab ) {
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
					text: '<?php esc_html_e( 'Comments per Author', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>'
				},
				xAxis: {
					categories: [
						<?php
						foreach ( $comments_per_author as $author ) {
							echo "'" . esc_js( $author->author ) . "',";
						}
						?>
					]
				},
				yAxis: {
					title: {
						text: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>'
					},
					min: 0
				},
				legend: {
					enabled: false
				},
				series: [ {
					name: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ 
					<?php
					foreach ( $comments_per_author as $author ) {
						echo esc_js( $author->count ) . ',';
					}
					?>
					]
				} ],
				credits: {
					enabled: false
				},
				exporting: {
					filename: '<?php echo esc_js( posts_and_users_stats_get_export_file_name( __( 'Comments per Author', 'posts-and-users-stats' ) ) ); ?>'
				}
			});
		});
		</script>
		<h3><?php esc_html_e( 'Comments per Author', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-authors',
				'table-authors',
				posts_and_users_stats_get_export_file_name( __( 'Comments per Author', 'posts-and-users-stats' ) )
			);
			?>
			</h3>
		<table id="table-authors" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Author', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $comments_per_author as $author ) { ?>
				<tr>
					<td><?php echo esc_html( $author->author ); ?></td>
					<td class="number"><?php echo esc_html( $author->count ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	
	<?php
	} else if ( 'status' == $selected_tab ) {
		$comments_per_status = wp_count_comments();

		$wp_comment_statuses = get_comment_statuses();
		$comment_statuses = array(
			'approved' => $wp_comment_statuses['approve'],
			'spam' => $wp_comment_statuses['spam'],
			'trash' => $wp_comment_statuses['trash'],
			'post-trashed' => __( 'Post trashed', 'posts-and-users-stats' ),
			'moderated' => __( 'Pending', 'posts-and-users-stats' ),
		);
		?>
	<section>
		<div id="chart-status" class="chart"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-status').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php esc_html_e( 'Comments per Status', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>'
				},
				xAxis: {
					categories: [ 
						<?php
						foreach ( $comment_statuses as $status => $name ) {
							echo "'" . esc_js( $name ) . "',";
						}
						?>
					]
				},
				yAxis: {
					title: {
						text: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>'
					},
					min: 0
				},
				legend: {
					enabled: false
				},
				series: [ {
					name: '<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>',
					data: [ 
					<?php
					foreach ( $comment_statuses as $status => $name ) {
						echo esc_js( $comments_per_status->$status ) . ',';
					}
					?>
					]
				} ],
				credits: {
					enabled: false
				},
				exporting: {
					filename: '<?php echo esc_js( posts_and_users_stats_get_export_file_name( __( 'Comments per Status', 'posts-and-users-stats' ) ) ); ?>'
				}
			});
		});
		</script>
		<h3><?php esc_html_e( 'Comments per Status', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-status',
				'table-status',
				posts_and_users_stats_get_export_file_name( __( 'Comments per Status', 'posts-and-users-stats' ) )
			);
			?>
			</h3>
		<table id="table-status" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Status', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $comment_statuses as $status => $name ) { ?>
				<tr>
					<td><?php echo esc_html( $name ); ?></td>
					<td class="number"><?php echo esc_html( $comments_per_status->$status ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	<?php } ?>
</div>
