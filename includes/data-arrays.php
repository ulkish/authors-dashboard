<?php
function get_data_array($report, $type){
	// Gathering total page values for every age bracket.
	$data_array = array();
	foreach ( $report as $row ) {
		
		switch ($type) {
			case 'page_views':
				$date           = date_create( $row['dimensions'][1] );
				$labels = '"' . date_format( $date, 'M j' ) . '"';
				break;
			case 'countries':
				$labels    = $row['dimensions'][1];
				break;
			case 'languages':
				$labels = locale_get_display_language( $row['dimensions'][1] );
				break;
			case 'age':
				$labels = '"' . $row['dimensions'][1] . '"';
				break;
			case 'gender':
				$labels  = '"' . ucfirst( $row['dimensions'][1] ) . '"';
				break;
		}		

		$page_views  = $row['metrics'][0]['values'][0];
		if ( ! isset( $ages_array[ $labels ] ) ) {
			$data_array[ $labels ] = $page_views;
		} else {
			$data_array[ $labels ] += $page_views;
		}
	}
	
	if ($type=='countries' || $type=='languages' ) {
		arsort( $data_array ); // Ordering by descending value.
	}
	if ($type=='page_views') {
		$data_count = count( $data_array );
		while ( $data_count >= 30 ) {
			array_shift( $data_array );
			$data_count--;
		}
	}
	
	return $data_array;
}

function get_republish_array($republished_report){

	$republish_pre_array = array();
	foreach ( $republished_report as $key => $row ) {
		if ( strpos( $row['dimensions'][2], 'google' ) === false && strpos( $row['dimensions'][2], 'sapiens.org' ) === false ) { // Filter direct and google urls.
			$republish_pre_array[] = array(
				'republished_site'  => $row['dimensions'][2],
				'republished_url'   => $row['dimensions'][3],
				'republished_views' => $row['metrics'][0]['values'][0],
			);
		}
	}
	$sites = array();
	foreach ( $republish_pre_array as $site_data ) {
		if ( ! array_key_exists( $site_data['republished_site'], $sites ) ) {
			$sites[ $site_data['republished_site'] ] = [ $site_data ];
		} else {
			array_push( $sites[ $site_data['republished_site'] ], $site_data );
		}
	}
	// Results sortered by the value of 'republished_views' and now containing
	// a total amount of views in 'total_views'.
	$republish_array = array();
	foreach ( $sites as $result ) {
		$views = array_column( $result, 'republished_views' );
		$total = array_sum( array_column( $result, 'republished_views' ) );
		array_multisort( $views, SORT_DESC, $result );
		$result[0]['total_views'] = $total;
		array_push( $republish_array, $result );
	}
	foreach ( $republish_array as $key => $result ) {
		$result = array_slice( $result, 0, 1 );
		unset( $result[0]['republished_views'] );
		$republish_total_views                      += $result[0]['total_views'];
		$republish_array[ $key ]                     = $result;
		$republish_array[ $key ]['republished_site'] = $result[0]['republished_site'];
		$republish_array[ $key ]['republished_url']  = $result[0]['republished_url'];
		$republish_array[ $key ]['total_views']      = $result[0]['total_views'];
		unset( $republish_array[ $key ][0] );
	}
	$views = array_column( $republish_array, 'total_views' );
	array_multisort( $views, SORT_DESC, $republish_array );

	$result = array('republish_total_views' => $republish_total_views, 
					'republish_array' => $republish_array);
	return $result;
}

function get_posts_slugs_array(){
	global $wp_query;
	$args = array(
		'posts_per_page' => -1,
		'tax_query' => array(
			array(
			  'taxonomy' => 'authors',
			  'field' => 'id',
			  'terms' => $wp_query->get_queried_object_id(), 
			  'include_children' => false
			)
		)
	);
	
	$posts = new WP_Query( $args ); 
	foreach ($posts->posts as $post) {
		$url = get_the_permalink($post->ID);
		$slug = str_replace( get_site_url(), '', $url);
		$posts_slugs_array[]=$slug;
	}

	return $posts_slugs_array;

}



	