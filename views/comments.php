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
$comments_tabs = array(
	'date'      => __( 'Comments per Date', 'posts-and-users-stats' ),
	'author'    => __( 'Comments per Author', 'posts-and-users-stats' ),
	'status'    => __( 'Comments per Status', 'posts-and-users-stats' ),
);

// Get the selected tab.
if ( isset( $_GET['tab'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_GET['tab'] ) ), $comments_tabs ) ) {
	$selected_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
} else {
	$selected_tab = 'date';
}
?>
<div class="wrap posts-and-users-stats">
	<h1><?php esc_html_e( 'Comments Statistics', 'posts-and-users-stats' ); ?> &rsaquo; <?php echo esc_html( $comments_tabs[ $selected_tab ] ); ?></h1>
	
	<nav class="nav-tab-wrapper">
	<?php foreach ( $comments_tabs as $tab_slug => $tab_title ) { ?>
		<a href="<?php echo esc_url( admin_url( 'tools.php?page=posts_and_users_stats_comments&tab=' ) . sanitize_text_field( $tab_slug ) ); ?>"
			class="<?php posts_and_users_stats_echo_tab_class( $selected_tab == $tab_slug ); ?>"><?php echo esc_html( $tab_title ); ?></a>
	<?php } ?>
	</nav>

<?php
if ( 'date' === $selected_tab ) {
	global $wpdb;
	// phpcs:disable WordPress.VIP.DirectDatabaseQuery.NoCaching
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
	// phpcs:enable WordPress.VIP.DirectDatabaseQuery.NoCaching

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
				<?php esc_html_e( 'Comments over Time', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-comments-time"></div>
			<script>
				posts_and_users_stats_time_line_chart(
					'#chart-comments-time',
					[
						<?php
						$comments_count = 0;
						foreach ( $comments_per_date as $comments_of_date ) {
							$date           = strtotime( $comments_of_date->date );
							$comments_count += $comments_of_date->count;
							echo '{x: new Date(' . esc_js( gmdate( 'Y', $date ) ) . ','
								 . esc_js( gmdate( 'm', $date ) - 1 ) . ','
								 . esc_js( gmdate( 'd', $date ) ) . '), y: '
								 . esc_js( $comments_count ) . '},';
						}
						?>
					],
					'Y-MM-DD',
					'<?php esc_html_e( 'Time', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>
		<h3 id="monthly">
			<?php echo esc_html( $per_month_string ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-monthly',
				'table-monthly',
				$per_month_string
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
					$comment_year = $year_object->year;
					?>
				<tr>
					<th scope="row"><a href="#<?php echo esc_attr( $comment_year ); ?>"><?php echo esc_html( $comment_year ); ?></a></th>
					<?php
					foreach ( range( 1, 12, 1 ) as $month ) {
						$date = gmdate( 'Y-m', strtotime( $comment_year . '-' . $month . '-1' ) );
						if ( array_key_exists( $date, $comments_per_month ) ) {
							$count = $comments_per_month[ $date ]->count;
						} else {
							$count = 0;
						}
						?>
					<td class="number"><?php echo esc_html( $count ); ?></td>
					<?php } ?>
					<td class="number"><?php echo esc_html( $year_object->count ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>	
			<?php
			foreach ( $comments_per_year as $year_object ) {
				$comment_year = $year_object->year;
				?>
	<section>
		<h3 id="<?php echo esc_attr( $comment_year ); ?>">
				<?php
				esc_html_e( 'Year', 'posts-and-users-stats' );
				echo ' ' . esc_html( $comment_year );
				posts_and_users_stats_echo_export_button(
					'csv-daily-' . $comment_year,
					'table-daily-' . $comment_year,
					$per_date_string . '-' . $comment_year
				);
				?>
			</h3>
		<table id="table-daily-<?php echo esc_attr( $comment_year ); ?>" class="wp-list-table widefat">
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
					<?php
					foreach ( range( 1, 12, 1 ) as $month ) {
						if ( checkdate( $month, $day, $comment_year ) ) {
							$date = gmdate( 'Y-m-d', strtotime( $comment_year . '-' . $month . '-' . $day ) );
							if ( array_key_exists( $date, $comments_per_date ) ) {
								$count = $comments_per_date[ $date ]->count;
							} else {
								$count = 0;
							}
						} else {
							$count = '&mdash;';
						}
						?>
					<td class="number"><?php echo esc_html( $count ); ?></td>
					<?php } ?>
				</tr>
				<?php } ?>
				<tr>
					<th scope="row"><strong><?php esc_html_e( 'Sum', 'posts-and-users-stats' ); ?></strong></th>
						<?php
						foreach ( range( 1, 12, 1 ) as $month ) {
							$date = gmdate( 'Y-m', strtotime( $comment_year . '-' . $month . '-1' ) );
							if ( array_key_exists( $date, $comments_per_month ) ) {
								$count = $comments_per_month[ $date ]->count;
							} else {
								$count = 0;
							}
							?>
					<td class="number"><strong><?php echo esc_html( $count ); ?></strong></td>
						<?php } ?>
				</tr>
			</tbody>
		</table>
	</section>
				<?php
			} // end loop
		} // end if
} else if ( 'author' === $selected_tab ) {
	global $wpdb;
	// phpcs:ignore WordPress.VIP.DirectDatabaseQuery.NoCaching
	$comments_per_author = $wpdb->get_results(
		"SELECT comment_author as author, count(comment_ID) as count
            FROM {$wpdb->comments}
            WHERE comment_approved = 1
            GROUP BY author",
		OBJECT_K
	);
	?>
	<section>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php esc_html_e( 'Comments per Author', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-comments-authors"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-comments-authors',
					[
						<?php
						foreach ( $comments_per_author as $author ) {
							echo "'" . esc_js( $author->author ) . "',";
						}
						?>
					],
					[
						<?php
						foreach ( $comments_per_author as $author ) {
							echo esc_js( $author->count ) . ',';
						}
						?>
					],
					'<?php esc_html_e( 'Author', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>
		<h3>
			<?php esc_html_e( 'Comments per Author', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-authors',
				'table-authors',
				__( 'Comments per Author', 'posts-and-users-stats' )
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
} else if ( 'status' === $selected_tab ) {
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
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php esc_html_e( 'Comments per Status', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-comments-status"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-comments-status',
					[
						<?php
						foreach ( $comment_statuses as $comment_status_key => $name ) {
							echo "'" . esc_js( $name ) . "',";
						}
						?>
					],
					[
						<?php
						foreach ( $comment_statuses as $comment_status_key => $name ) {
							echo esc_js( $comments_per_status->$comment_status_key ) . ',';
						}
						?>
					],
					'<?php esc_html_e( 'Status', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Comments', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>
		<h3>
			<?php esc_html_e( 'Comments per Status', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-status',
				'table-status',
				__( 'Comments per Status', 'posts-and-users-stats' )
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
				<?php foreach ( $comment_statuses as $comment_status_key => $name ) { ?>
				<tr>
					<td><?php echo esc_html( $name ); ?></td>
					<td class="number"><?php echo esc_html( $comments_per_status->$comment_status_key ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
<?php } ?>
</div>
