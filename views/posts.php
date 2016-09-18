<?php
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	$tabs = array(
			'date' 		=> __( 'Posts per Publication Date', 'posts-and-users-stats' ),
			'taxonomy'	=> __( 'Posts per Category and Tag', 'posts-and-users-stats' ),
			'author'	=> __( 'Posts per Author and Type', 'posts-and-users-stats' ),
			'status'	=> __( 'Posts per Status', 'posts-and-users-stats' ),
	);
	if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ) {
		$selected_tab = $_GET['tab'];
	} else {
		$selected_tab = 'date';
	}
	
	$start_time = microtime( true );
?>
<div class="wrap posts-and-users-stats">
	<h1><?php _e( 'Posts Statistics', 'posts-and-users-stats' ); ?> &rsaquo; <?php echo $tabs[ $selected_tab ]; ?></h1>
	
	<h2 class="nav-tab-wrapper">
	<?php foreach( $tabs as $tab_slug => $tab_title ) { ?>
		<a href="<?php echo admin_url( 'tools.php?page=posts_and_users_stats_posts' ) . '&tab=' . sanitize_text_field( $tab_slug ); ?>" 
			class="<?php posts_and_users_stats_echo_tab_class( $selected_tab == $tab_slug ); ?>"><?php echo $tab_title; ?></a>
	<?php } ?>
	</h2>
	
	<?php if ( $selected_tab == 'date' ) {
		global $wpdb;
		$posts_per_date = $wpdb->get_results( "SELECT DATE(post_date) as date, count(ID) as count
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				GROUP BY date", OBJECT_K);
		$posts_per_month = $wpdb->get_results( "SELECT DATE_FORMAT(post_date, '%Y-%m') as month, count(ID) as count
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				GROUP BY month", OBJECT_K);
		$posts_per_year = $wpdb->get_results( "SELECT YEAR(post_date) as year, count(ID) as count
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				GROUP BY year
				ORDER BY year DESC", OBJECT_K);
	?>
	<div>
		<ul>
			<li><a href="#monthly"><?php _e( 'Posts per Month', 'posts-and-users-stats' ); ?></a>
		<?php foreach( $posts_per_year as $year_object ) { ?>
			<li><a href="#<?php echo $year_object->year; ?>"><?php echo $year_object->year; ?></a></li>
		<?php } ?>
		</ul>
		
		<div id="chart-monthly"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-monthly').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Posts per Month', 'posts-and-users-stats' ); ?>'
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
		                text: '<?php _e( 'Date', 'posts-and-users-stats' ); ?>'
		            }
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>',
						min: 0
					}
				},
				legend: {
					enabled: false,
				},
				series: [ {
					name: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>',
					data: [ <?php foreach( $posts_per_month as $posts_of_month ) {
						$date = strtotime( $posts_of_month->month . '-01' );
						$year = date( 'Y', $date );
						$month = date( 'm', $date );
						echo '[Date.UTC(' . $year . ',' . ( $month - 1 ) . ',1),' . $posts_of_month->count . '], '; 
						}?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __('Posts per Month', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
		
		<div id="chart-daily"></div>
		<script>
		jQuery(function() {
			jQuery('#chart-daily').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Posts per Date', 'posts-and-users-stats' ); ?>'
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
						text: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>',
						min: 0
					}
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
					name: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>',
					data: [ <?php foreach( $posts_per_date as $posts_of_date ) {
						$date = strtotime( $posts_of_date->date );
						$year = date( 'Y', $date );
						$month = date( 'm', $date );
						$day = date( 'd', $date );
						echo '[Date.UTC(' . $year . ',' . ( $month - 1 ) . ',' . $day .'),' . $posts_of_date->count . '], '; 
						}?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __('Posts per Month', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
				
		<h3 id="monthly"><?php _e( 'Posts per Month', 'posts-and-users-stats' ); ?>
			<?php posts_and_users_stats_echo_export_button (
				'csv-monthly',
				'table-monthly',
				posts_and_users_stats_get_export_file_name( __('Posts per Month', 'posts-and-users-stats' ) )
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
				<?php foreach( $posts_per_year as $year_object ) {
					$year = $year_object->year; ?>
				<tr>
					<th scope="row"><a href="#<?php echo $year ?>"><?php echo $year ?></a></th>
					<?php foreach( range( 1, 12, 1) as $month ) { ?>
					<td class="number"><?php 
							$date = date('Y-m', strtotime( $year . '-' . $month . '-1' ) );
							if ( array_key_exists( $date, $posts_per_month) ) {
								posts_and_users_stats_echo_link( get_month_link( $year, $month ), $posts_per_month[$date]->count );
							} else {
								echo 0;
							} ?></td>
					<?php } ?>
					<td class="number"><?php echo posts_and_users_stats_echo_link( get_year_link( $year ), $year_object->count ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		
		<?php foreach( $posts_per_year as $year_object ) {
			$year = $year_object->year; ?>
		<h3 id="<?php echo $year?>"><?php echo __( 'Year', 'posts-and-users-stats' ) . ' ' . $year; ?>
			<?php posts_and_users_stats_echo_export_button (
				'csv-daily-' . $year,
				'table-daily-' . $year,
				posts_and_users_stats_get_export_file_name( __('Posts per Day', 'posts-and-users-stats' ) . '-' . $year )
			); ?></h3>
		<table id="table-daily-<?php echo $year?>" class="wp-list-table widefat">
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
								$date = date('Y-m-d', strtotime( $year . '-' . $month . '-' . $day ) );
								if ( array_key_exists( $date, $posts_per_date ) ) {
									posts_and_users_stats_echo_link( get_day_link( $year, $month, $day ), $posts_per_date[$date]->count );
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
							$date = date('Y-m', strtotime( $year . '-' . $month . '-1' ) );
							if ( array_key_exists( $date, $posts_per_month) ) {
								posts_and_users_stats_echo_link( get_month_link( $year, $month ), $posts_per_month[$date]->count );
							} else {
								echo 0;
							} ?></strong></td>
					<?php } ?>
				</tr>
			</tbody>
		</table>
		<?php } ?>
		<script>
			
		</script>
	</div>
	
	<?php } else if ($selected_tab == 'taxonomy') {
		// Get the list of all taxonomies except nav_menu and link_category
		$taxonomies = get_taxonomies();
		$taxonomies = array_diff( $taxonomies, array( 'nav_menu', 'link_category' ) );
	?>
	<div>
		<ul>
		<?php foreach( $taxonomies as $taxonomy ) { ?>
			<li><a href="#<?php echo $taxonomy; ?>"><?php echo $taxonomy_label = get_taxonomy( $taxonomy )->labels->name; ?></a></li>
		<?php } ?>
		</ul>
		
		<?php foreach( $taxonomies as $taxonomy ) {
			$taxonomy_label = get_taxonomy( $taxonomy )->labels->name;
			$headline = sprintf( __( 'Published Posts per %s', 'posts-and-users-stats' ), $taxonomy_label );
			$terms = get_terms( $taxonomy );
		?>
		<h3 id="<?php echo $taxonomy; ?>"><?php echo $headline; ?>
			<?php posts_and_users_stats_echo_export_button (
				'csv-' . $taxonomy,
				'table-' . $taxonomy,
				posts_and_users_stats_get_export_file_name( $headline )
			); ?></h3>
		<?php if ( is_array( $terms ) && sizeof( $terms ) > 0) { ?>
		<div id="chart-<?php echo $taxonomy; ?>"></div>
		<table id="table-<?php echo $taxonomy; ?>" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php echo $taxonomy_label; ?></th>
					<th scope="col"><?php _e( 'Posts', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $terms as $term ) { ?>
				<tr>
					<td><?php echo $term->name; ?></td>
					<td class="number"><?php echo $term->count; ?></td>
				<?php } ?>
				</tr>
			</tbody>
		</table>
		<script>
		jQuery(function() {
			jQuery('#chart-<?php echo $taxonomy; ?>').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo $headline; ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					categories: [ 
						<?php foreach( $terms as $term ) {
							echo "'" . $term->name. "',";
						}?> ],
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>'
					}
				},
				legend: {
					enabled: false,
				},
				series: [ {
					data: [ <?php foreach( $terms as $term ) {
						echo $term->count . ','; 
						}?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( $headline ); ?>'
				}
			});
		});
		</script>
		<?php 	} else { // no terms ?>
		<p><?php echo sprintf( __( 'No entries for %s found.', 'posts-and-users-stats' ), $taxonomy_label ); ?></p>
		<?php 	}
			} 
		?>
	</div>
	
	<?php } else if ( $selected_tab == 'author' ) {
		// Get the list of all post types, including custom post types, but without revisions and menu items.
		$post_types = array_diff( get_post_types(), array( 'revision', 'nav_menu_item' ) );
		
		// Get the total number of published posts per post type.
		$posts_per_type = array();
		$total = 0;
		foreach( $post_types as $post_type ) {
			$type_object = get_post_type_object( $post_type );
			$count = wp_count_posts( $post_type )->publish;
			$posts_per_type[$type_object->label] = $count;
			$total += $count;
		}
		$posts_per_type['total'] = $total;
		
		// Get the number of published posts per author (per post type and total count for the author).
		$posts_per_author = array();
		foreach( get_users() as $user ) {
			$user_data = array(
					'ID' 	=> $user->ID,
					'name'	=> $user->display_name
			);
			$total = 0;
			foreach( $post_types as $post_type ) {
				$count = count_user_posts( $user->ID , $post_type, true );
				$user_data[$post_type] = $count;
				$total += $count;
			}
			$user_data['total'] = $total;
			array_push( $posts_per_author, $user_data );
		}
	?>
	<div>
		<div id="chart-authors"></div>
		<div id="chart-types"></div>
		<h3><?php posts_and_users_stats_echo_export_button (
				'csv-authors-and-types',
				'table-authors-and-types',
				posts_and_users_stats_get_export_file_name( __('Posts per Author and Type', 'posts-and-users-stats' ) )
			); ?></h3>
		<table id="table-authors-and-types" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Author', 'posts-and-users-stats' ); ?></th>
					<?php foreach( $post_types as $post_type ) {
						$type_object = get_post_type_object( $post_type ); ?>
					<th><?php echo $type_object->label; ?></th>
					<?php } ?>
					<th><?php _e( 'All Types', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $posts_per_author as $author ) { ?>
				<tr>
					<td><?php echo $author['name']; ?></td>
					<?php foreach( $post_types as $post_type ) { ?>
					<td class="number"><?php if ( $post_type == 'post' ) {
							posts_and_users_stats_echo_link( get_author_posts_url( $author['ID'] ), $author['post'] );
						} else {
							echo $author[$post_type]; 
						} ?></td>
					<?php } ?>
					<td class="number"><strong><?php echo $author['total']; ?></strong></td>
				</tr>
				<?php } ?>
				<tr>
					<td><strong><?php _e( 'All Authors', 'posts-and-users-stats' ); ?></strong></td>
					<?php foreach ( $posts_per_type as $type => $count ) { ?>
					<td class="number"><strong><?php echo $count; ?></strong></td>
				<?php } ?>
				</tr>
			</tbody>
		</table>
		<script>
		jQuery(function() {
			jQuery('#chart-authors').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Posts per Author', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					categories: [ 
						<?php foreach( $posts_per_author as $author ) {
							echo "'" . $author['name'] . "',";
						}?> ],
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>'
					}
				},
				legend: {
					enabled: false,
				},
				series: [ {
					name: 'all',
					data: [ <?php foreach( $posts_per_author as $author ) {
						echo $author['total'] . ','; 
						}?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __('Posts per Author', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		jQuery(function() {
			jQuery('#chart-types').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Posts per Type', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					categories: [ 
						<?php foreach( $posts_per_type as $type => $count ) {
							if ( $type != 'total' && $count > 0)  {
								echo "'" . $type . "',";
							}
						}?> ],
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>'
					}
				},
				legend: {
					enabled: false,
				},
				series: [ {
					name: 'all',
					data: [ <?php foreach( $posts_per_type as $type => $count ) {
							if ( $type != 'total' && $count > 0)  {
								echo $count . ','; 
							} 
						} ?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __('Posts per Type', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
	</div>
	
	<?php } else if ( $selected_tab == 'status' ) {
		// Get a full list of possible post status.
		$statuses = get_post_statuses();
		$statuses['future'] = __( 'Published in the future');
		
		// Get the number of posts per status.
		$posts_per_status = wp_count_posts();
	?>
	<div>
		<div id="chart-status"></div>
		<h3><?php posts_and_users_stats_echo_export_button (
				'csv-status',
				'table-status',
				posts_and_users_stats_get_export_file_name( __('Posts per Status', 'posts-and-users-stats' ) )
			); ?></h3>
		<table id="table-status" class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Status', 'posts-and-users-stats' ); ?></th>
					<th scope="col"><?php _e( 'Posts', 'posts-and-users-stats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $statuses as $status_slug => $status_name ) { ?>
				<tr>
					<td><?php echo $status_name; ?></td>
					<td class="number"><?php echo $posts_per_status->$status_slug; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<script>
		jQuery(function() {
			jQuery('#chart-status').highcharts({
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php _e( 'Posts per Status', 'posts-and-users-stats' ); ?>'
				},
				subtitle: {
					text: '<?php echo get_bloginfo( 'name' ); ?>'
				},
				xAxis: {
					categories: [ 
						<?php foreach( $statuses as $status_slug => $status_name ) {
							echo "'" . $status_name . "',";
						}?> ],
				},
				yAxis: {
					title: {
						text: '<?php _e( 'Posts', 'posts-and-users-stats' ); ?>'
					}
				},
				legend: {
					enabled: false,
				},
				series: [ {
					data: [ <?php foreach( $statuses as $status_slug => $status_name ) {
						echo $posts_per_status->$status_slug . ','; 
						}?> ]
				} ],
				credits: {
					enabled: false	
				},
				exporting: {
					filename: '<?php echo posts_and_users_stats_get_export_file_name( __('Posts per Status', 'posts-and-users-stats' ) ); ?>'
				}
			});
		});
		</script>
	</div>
	<?php } ?>
	<?php $end_time = microtime( true ); ?>
	<p><?php echo sprintf( __( 'Statistics generated in %s seconds.', 'posts-and-users-stats' ), number_format_i18n( $end_time - $start_time, 2 ) ); ?></p>
</div>