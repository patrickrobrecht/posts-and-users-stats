<?php
/**
 * The posts stats page.
 *
 * @package posts-and-users-stats
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define tabs.
$posts_tabs = array(
	'date'      => __( 'Posts per Publication Date', 'posts-and-users-stats' ),
	'taxonomy'  => __( 'Posts per Category and Tag', 'posts-and-users-stats' ),
	'author'    => __( 'Posts per Author and Post Type', 'posts-and-users-stats' ),
	'status'    => __( 'Posts per Status', 'posts-and-users-stats' ),
);

// Get the selected tab.
if ( isset( $_GET['tab'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_GET['tab'] ) ), $posts_tabs ) ) {
	$selected_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
} else {
	$selected_tab = 'date';
}

// Get the list of all post types, including custom post types except the listed ones.
$post_types = array_diff(
	get_post_types(),
	array(
		'custom_css',
		'customize_changeset',
		'nav_menu_item',
		'oembed_cache',
		'revision',
		'user_request',
		'wp_block',
	)
);
?>
<div class="wrap posts-and-users-stats">
	<h1><?php esc_html_e( 'Posts Statistics', 'posts-and-users-stats' ); ?> &rsaquo; <?php echo esc_html( $posts_tabs[ $selected_tab ] ); ?></h1>

	<nav class="nav-tab-wrapper">
	<?php foreach ( $posts_tabs as $tab_slug => $tab_title ) { ?>
		<a href="<?php echo esc_url( admin_url( 'tools.php?page=posts_and_users_stats_posts' ) . '&tab=' . sanitize_text_field( $tab_slug ) ); ?>"
			class="<?php posts_and_users_stats_echo_tab_class( $selected_tab == $tab_slug ); ?>"><?php echo esc_html( $tab_title ); ?></a>
	<?php } ?>
	</nav>

<?php
if ( 'date' === $selected_tab ) {
	// Get the selected post type.
	if ( isset( $_POST['type'] ) && check_admin_referer( 'posts_and_users_stats' ) && in_array( wp_unslash( $_POST['type'] ), $post_types ) ) {
		$selected_post_type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
	} else {
		$selected_post_type = '';
	}

	// Get the numbers of posts per date, month, year.
	if ( '' == $selected_post_type ) {
		$post_type_query = '';
		$selected_post_type_name = __( 'Content', 'posts-and-users-stats' );
	} else {
		$post_type_query = " AND post_type = '" . $selected_post_type . "'";
		$selected_post_type_object = get_post_type_object( $selected_post_type );
		$selected_post_type_labels = $selected_post_type_object->labels;
		$selected_post_type_name = $selected_post_type_object->label;
	}

	global $wpdb;
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
    // phpcs:disable WordPress.VIP.DirectDatabaseQuery.NoCaching
	$posts_per_date = $wpdb->get_results(
		"SELECT DATE(post_date) as date, count(ID) as count
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'" . $post_type_query .
			'GROUP BY date',
		OBJECT_K
	);
	$posts_per_month = $wpdb->get_results(
		"SELECT DATE_FORMAT(post_date, '%Y-%m') as month, count(ID) as count
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'" . $post_type_query .
			'GROUP BY month',
		OBJECT_K
	);
	$posts_per_year = $wpdb->get_results(
		"SELECT YEAR(post_date) as year, count(ID) as count
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'" . $post_type_query .
			'GROUP BY year
            ORDER BY year DESC',
		OBJECT_K
	);
	// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    // phpcs:enable WordPress.VIP.DirectDatabaseQuery.NoCaching

	$per_date_string = sprintf(
		// translators: post type.
		__( '%s per Date', 'posts-and-users-stats' ),
		$selected_post_type_name
	);
	$per_month_string = sprintf(
		// translators: post type.
		__( '%s per Month', 'posts-and-users-stats' ),
		$selected_post_type_name
	);
	?>
	<form method="POST" action="">
		<?php wp_nonce_field( 'posts_and_users_stats' ); ?>
		<fieldset>
			<legend><?php esc_html_e( 'With a selection only the posts defined are counted, otherwise any content.', 'posts-and-users-stats' ); ?></legend>
			<select id="type" name="type">
				<option value="content" <?php selected( $selected_post_type, '', true ); ?>><?php esc_html_e( 'all post types', 'posts-and-users-stats' ); ?></option>
				<?php foreach ( $post_types as $post_type ) { ?>
				<option value="<?php echo esc_attr( $post_type ); ?>" <?php selected( $selected_post_type, $post_type, true ); ?>><?php echo esc_html( get_post_type_object( $post_type )->label ); ?></option>
				<?php } ?>
			</select>
			<button type="submit" class="button-secondary" ><?php esc_html_e( 'Select', 'posts-and-users-stats' ); ?></button>
		</fieldset>
	</form>
	<nav>
		<?php if ( ! is_array( $posts_per_date ) || count( $posts_per_date ) <= 0 ) { ?>
		<p><?php echo esc_html( $selected_post_type_labels->not_found ); ?>
		<?php } else { ?>
		<ul>
			<li><a href="#monthly"><?php echo esc_html( $per_month_string ); ?></a>
			<?php foreach ( $posts_per_year as $year_object ) { ?>
				<li><a href="#<?php echo esc_attr( $year_object->year ); ?>"><?php esc_html_e( 'Year', 'posts-and-users-stats' ); ?> <?php echo esc_html( $year_object->year ); ?></a></li>
			<?php } ?>
		</ul>
	</nav>	
	<section>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php echo esc_html( $per_month_string ); ?>
			</div>
			<div id="chart-posts-monthly"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-posts-monthly',
					[
						<?php
						foreach ( $posts_per_month as $posts_of_month ) {
							$date = strtotime( $posts_of_month->month . '-01' );
							echo "'" . esc_js( gmdate( 'm-Y', $date ) ) . "',";
						}
						?>
					],
					[
						<?php
						foreach ( $posts_per_month as $posts_of_month ) {
							echo esc_js( $posts_of_month->count ) . ',';
						}
						?>
					],
					'<?php esc_html_e( 'Month', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>

		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php echo esc_html( $per_date_string ); ?>
			</div>
			<div id="chart-posts-time"></div>
			<script>
				posts_and_users_stats_time_line_chart(
					'#chart-posts-time',
					[
						<?php
						$posts = 0;
						foreach ( $posts_per_date as $posts_of_date ) {
							$date = strtotime( $posts_of_date->date );
							$year = gmdate( 'Y', $date );
							$month = gmdate( 'm', $date );
							$day = gmdate( 'd', $date );
							$posts += $posts_of_date->count;
							echo '{x: new Date(' . esc_js( $year ) . ',' . esc_js( $month - 1 ) . ',' . esc_js( $day ) . '), y: ' . esc_js( $posts ) . '},';
						}
						?>
					],
					'Y-MM-DD',
					'<?php esc_html_e( 'Published Posts over Time', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?>'
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
				foreach ( $posts_per_year as $year_object ) {
					$year = $year_object->year;
					?>
				<tr>
					<th scope="row"><a href="#<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $year ); ?></a></th>
					<?php
					foreach ( range( 1, 12, 1 ) as $month ) {
						$date = gmdate( 'Y-m', strtotime( $year . '-' . $month . '-1' ) );
						if ( array_key_exists( $date, $posts_per_month ) ) {
							$count = $posts_per_month[ $date ]->count;
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
			foreach ( $posts_per_year as $year_object ) {
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
					$per_date_string . '-' . $year
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
					<?php
					foreach ( range( 1, 12, 1 ) as $month ) {
						if ( checkdate( $month, $day, $year ) ) {
							$date = gmdate( 'Y-m-d', strtotime( $year . '-' . $month . '-' . $day ) );
							if ( array_key_exists( $date, $posts_per_date ) ) {
								$count = $posts_per_date[ $date ]->count;
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
							$date = gmdate( 'Y-m', strtotime( $year . '-' . $month . '-1' ) );
							if ( array_key_exists( $date, $posts_per_month ) ) {
								$sum = $posts_per_month[ $date ]->count;
							} else {
								$sum = 0;
							}
							?>
					<td class="number"><strong><?php echo esc_html( $sum ); ?></strong></td>
						<?php } ?>
				</tr>
			</tbody>
		</table>
	</section>
				<?php
			} // endforeach (years)
		} // end if (post per date > 0)
} else if ( 'taxonomy' === $selected_tab ) {
	// Get the list of all taxonomies except nav_menu and link_category.
	$taxonomies = get_taxonomies();
	$taxonomies = array_diff( $taxonomies, array( 'nav_menu', 'link_category' ) );
	?>
	<nav>
		<ul>
		<?php foreach ( $taxonomies as $taxonomy ) { ?>
			<li><a href="#<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_html( get_taxonomy( $taxonomy )->labels->name ); ?></a></li>
		<?php } ?>
		</ul>
	</nav>
	<?php
	foreach ( $taxonomies as $taxonomy ) {
		$taxonomy_labels = get_taxonomy( $taxonomy )->labels;
		$headline = sprintf(
			// translators: taxonomy label.
			__( 'Published Posts per %s', 'posts-and-users-stats' ),
			$taxonomy_labels->singular_name
		);
		$terms = get_terms( $taxonomy );
		?>
		<?php if ( ! is_array( $terms ) || count( $terms ) <= 0 ) { ?>
		<section>
			<h3 id="<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_html( $headline ); ?></h3>
			<p><?php echo esc_html( $taxonomy_labels->not_found ); ?></p>
		</section>
		<?php } else { ?>
		<section>
			<div class="chart-container">
				<div class="chart-title">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
					<?php echo esc_html( $headline ); ?>
				</div>
				<div id="chart-<?php echo esc_attr( $taxonomy ); ?>"></div>
				<script>
					posts_and_users_stats_bar_chart(
						'#chart-<?php echo esc_attr( $taxonomy ); ?>',
						[
							<?php
							foreach ( $terms as $term ) {
								echo "'" . esc_js( $term->name ) . "',";
							}
							?>
						],
						[
							<?php
							foreach ( $terms as $term ) {
								echo esc_js( $term->count ) . ',';
							}
							?>
						],
						'<?php echo esc_html( $taxonomy_labels->singular_name ); ?>',
						'<?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?>'
					)
				</script>
			</div>

			<h3 id="<?php echo esc_attr( $taxonomy ); ?>">
				<?php echo esc_html( $headline ); ?>
				<?php
				posts_and_users_stats_echo_export_button(
					'csv-' . $taxonomy,
					'table-' . $taxonomy,
					$headline
				);
				?>
			</h3>

			<table id="table-<?php echo esc_attr( $taxonomy ); ?>" class="wp-list-table widefat">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html( $taxonomy_labels->singular_name ); ?></th>
						<th scope="col"><?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $terms as $term ) { ?>
					<tr>
						<td><?php echo esc_html( $term->name ); ?></td>
						<td class="number"><?php echo esc_html( $term->count ); ?></td>
					<?php } ?>
					</tr>
				</tbody>
			</table>
		</section>
			<?php
		} // end if count( $terms) > 0
	} // end loop over taxonomies
} else if ( 'author' === $selected_tab ) {
	// Get the total number of published posts per post type.
	$posts_per_type = array();
	$total = 0;
	foreach ( $post_types as $post_type ) {
		$type_object = get_post_type_object( $post_type );
		$count = wp_count_posts( $post_type )->publish;
		$posts_per_type[ $type_object->label ] = $count;
		$total += $count;
	}
	$posts_per_type['total'] = $total;

	// Get the number of published posts per author (per post type and total count for the author).
	$posts_per_author = array();
	foreach ( get_users() as $user ) {
		$user_data = array(
			'ID'    => $user->ID,
			'name'  => $user->display_name,
		);
		$total = 0;
		foreach ( $post_types as $post_type ) {
			$count = intval(
				// phpcs:ignore WordPress.VIP.RestrictedFunctions.count_user_posts_count_user_posts
				count_user_posts( $user->ID, $post_type, true )
			);
			$user_data[ $post_type ] = $count;
			$total += $count;
		}
		$user_data['total'] = $total;
		array_push( $posts_per_author, $user_data );
	}
	?>
	<section>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php esc_html_e( 'Posts per Author', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-posts-authors"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-posts-authors',
					[
						<?php
						foreach ( $posts_per_author as $author ) {
							echo "'" . esc_js( $author['name'] ) . "',";
						}
						?>
					],
					[
						<?php
						foreach ( $posts_per_author as $author ) {
							echo esc_js( $author['total'] ) . ',';
						}
						?>
					],
					'<?php esc_html_e( 'Author', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php esc_html_e( 'Posts per Type', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-posts-types"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-posts-types',
					[
						<?php
						foreach ( $posts_per_type as $type => $count ) {
							if ( 'total' !== $type && $count > 0 ) {
								echo "'" . esc_js( $type ) . "',";
							}
						}
						?>
					],
					[
						<?php
						foreach ( $posts_per_type as $type => $count ) {
							if ( 'total' !== $type && $count > 0 ) {
								echo esc_js( $count ) . ',';
							}
						}
						?>
					],
					'<?php esc_html_e( 'Post type', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>
		<h3>
			<?php esc_html_e( 'Posts per Author and Post Type', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-authors-and-types',
				'table-authors-and-types',
				__( 'Posts per Author and Post Type', 'posts-and-users-stats' )
			);
			?>
		</h3>
		<table id="table-authors-and-types" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Author', 'posts-and-users-stats' ); ?></th>
					<?php
					foreach ( $post_types as $post_type ) {
						$type_object = get_post_type_object( $post_type );
						?>
					<th><?php echo esc_html( $type_object->label ); ?></th>
					<?php } ?>
					<th><?php esc_html_e( 'all post types', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $posts_per_author as $author ) { ?>
				<tr>
					<td><?php echo esc_html( $author['name'] ); ?></td>
					<?php
					foreach ( $post_types as $post_type ) {
						if ( 'post' === $post_type ) {
							$count = $author['post'];
						} else {
							$count = $author[ $post_type ];
						}
						?>
					<td class="number"><?php echo esc_html( $count ); ?></td>
					<?php } ?>
					<td class="number"><strong><?php echo esc_html( $author['total'] ); ?></strong></td>
				</tr>
				<?php } ?>
				<tr>
					<td><strong><?php esc_html_e( 'all authors', 'posts-and-users-stats' ); ?></strong></td>
					<?php foreach ( $posts_per_type as $type => $count ) { ?>
					<td class="number"><strong><?php echo esc_html( $count ); ?></strong></td>
				<?php } ?>
				</tr>
			</tbody>
		</table>
	</section>
	
	<?php
} else if ( 'status' === $selected_tab ) {
	// Get a full list of possible post status.
	$statuses = get_post_statuses();
	$statuses['future'] = __( 'Published in the future', 'posts-and-users-stats' );

	// Get the number of posts per status.
	$posts_per_status = wp_count_posts();
	?>
	<section>
		<div class="chart-container">
			<div class="chart-title">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>:
				<?php esc_html_e( 'Posts per Status', 'posts-and-users-stats' ); ?>
			</div>
			<div id="chart-posts-status"></div>
			<script>
				posts_and_users_stats_bar_chart(
					'#chart-posts-status',
					[
						<?php
						foreach ( $statuses as $status_slug => $status_name ) {
							echo "'" . esc_js( $status_name ) . "',";
						}
						?>
					],
					[
						<?php
						foreach ( $statuses as $status_slug => $status_name ) {
							echo esc_js( $posts_per_status->$status_slug ) . ',';
						}
						?>
					],
					'<?php esc_html_e( 'Post type', 'posts-and-users-stats' ); ?>',
					'<?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?>'
				)
			</script>
		</div>
		<h3>
			<?php esc_html_e( 'Posts per Status', 'posts-and-users-stats' ); ?>
			<?php
			posts_and_users_stats_echo_export_button(
				'csv-status',
				'table-status',
				__( 'Posts per Status', 'posts-and-users-stats' )
			);
			?>
		</h3>
		<table id="table-status" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Status', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Posts', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $statuses as $status_slug => $status_name ) { ?>
				<tr>
					<td><?php echo esc_html( $status_name ); ?></td>
					<td class="number"><?php echo esc_html( $posts_per_status->$status_slug ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	<?php } ?>
</div>
