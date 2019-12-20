<?php
/**
 * Main functionality of the Facebook API section of the plugin lays here.
 *
 * @package Authors Dashboard
 */

// TODO LIST:
// - Check rate limit for Facebook data requests.

// Loading app credentials.
require_once __DIR__ . '/facebook-app-credentials.php';

/**
 * Performs a request to the GraphAPI to check a given URL engagement stats.
 *
 * @param string $url URL on which to perform the search with.
 * @param array  $facebook_credentials Array of necessary credentials.
 * @return mixed $graph_node GraphNode Object.
 */
function get_facebook_data( $url, $facebook_credentials ) {

	if ( strpos( $url, 'https://www.sapiens.org' ) === false ) {
		$url = str_replace(
			get_site_url(),
			'https://www.sapiens.org',
			$url
		);
	}

	$fb = new \Facebook\Facebook(
		[
			'app_id'                => $facebook_credentials['app_id'],
			'app_secret'            => $facebook_credentials['app_secret'],
			'default_graph_version' => 'v2.10',
		]
	);

	try {
		// Get the GraphNode Object for the specified URL containing its engagement stats.
		$response = $fb->get(
			'?id=' . $url . '&fields=engagement',
			$facebook_credentials['access_token']
		);
	} catch ( \Facebook\Exceptions\FacebookResponseException $e ) {
		// When Graph returns an error.
		echo 'Graph returned an error: ' . esc_textarea( $e->getMessage() );
		exit;
	} catch ( \Facebook\Exceptions\FacebookSDKException $e ) {
		// When validation fails or other local issues.
		echo 'Facebook SDK returned an error: ' . esc_textarea( $e->getMessage() );
		exit;
	}
	$graph_node = $response->getGraphNode();
	return $graph_node['engagement']['share_count'];
}

/**
 * Performs a search for every post/page and returns an array containing all
 * engagement reports.
 *
 * @param array $facebook_credentials Necessary credentials.
 * @return array $all_facebook_data All share_counts found.
 */
function get_all_facebook_data( $facebook_credentials ) {
	$all_facebook_data = array();
	$args              = array(
		'posts_per_page' => -1,
		'post_type'      => 'any',
	);
	$all_posts_query   = new WP_Query( $args );
	// Query all the posts.
	while ( $all_posts_query->have_posts() ) {
		$all_posts_query->the_post();
		$post_id       = $all_posts_query->post->ID;
		$post_url      = get_permalink( $post_id );
		$facebook_data = get_facebook_data( $post_url, $facebook_credentials );
		array_push(
			$all_facebook_data,
			array(
				'post_id'     => $post_id,
				'share_count' => $facebook_data,
			)
		);
	}
	return $all_facebook_data;
}

/**
 * Stores all the Facebook data gathered in every postmeta.
 *
 * @param array $all_facebook_data All share_count data.
 * @return void
 */
function store_facebook_data( $all_facebook_data ) {
	foreach ( $all_facebook_data as $facebook_data ) {
		update_post_meta(
			$facebook_data['post_id'],
			'autd_facebook_data',
			$facebook_data['share_count']
		);
	}
}

/**
 * Combines the main functions of the plugin into one for easier
 * hooking into WP.
 *
 * @return void
 */
function get_and_store_facebook_data() {
	$app_id       = '535448793933963';
	$app_secret   = '3d1bdfd0e2ea3f58e80662295f6613c7';
	$access_token = $app_id . '|' . $app_secret;

	$facebook_credentials = array(
		'app_id'       => $app_id,
		'app_secret'   => $app_secret,
		'access_token' => $access_token,
	);

	$all_facebook_data = get_all_facebook_data( $facebook_credentials );
	store_facebook_data( $all_facebook_data );
}
// add_action( 'init', 'get_and_store_facebook_data' ); // Uncomment this if you need to do some testing.
