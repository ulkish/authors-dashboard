<?php
/**
 * Main functionality of the Google Analytics section of the plugin lays here.
 * Will need to be refactored when the calls to GA start to be made from a
 * private static server.
 *
 * @package Authors Dashboard
 */

/**
 * Creates a GA client and gets a report, then formats and stores
 * that report in a transient.
 *
 * @param boolean $cron Cron?.
 * @return void
 */

// Adds a custom column to display our Google Analytics data.
require_once __DIR__ . '/includes/data-arrays.php';

function get_and_store_page_views( $cron = false, $posts_slugs = false, $post_id = false ) {
	// Create a client and authorize it.
	$client = get_client();
	if ( $client ) {
		// Create an authorized analytics service object.
		$analytics = new Google_Service_AnalyticsReporting( $client );
		try {
			if ( true === $cron ) {
				// Call the Analytics Reporting API V4.
				$response = get_cron_report( $analytics );
				// Get results and store them in a transient.
				store_cron_page_views( $response );
			} elseif ( $posts_slugs != false ) {
				$author_posts_report = get_author_posts_report( $analytics );
				store_authors_page_views( $author_posts_report );
			} else {
				$monthly_page_views_report  = get_monthly_page_views_report( $analytics );
				$total_page_views_report  = get_total_page_views_report( $analytics );
				$audience_report    = get_audience_report( $analytics );
				$republished_report = get_republished_report( $analytics );

				$responses = array(
					'monthly_page_views_report' => $monthly_page_views_report,
					'total_page_views_report'   => $total_page_views_report,
					'audience_report'           => $audience_report,
					'republished_report'        => $republished_report,
				);
				store_page_views( $responses, $post_id );
			}
			// Create a success message.
			$user_message = '<div class="updated notice">
			<p>Success! Check your data in the posts/pages dashboard.</p>
			</div>';
			set_transient( 'user_message_event', $user_message, 5 );
		} catch ( Google_Service_Exception $e ) {
			echo esc_textarea( $e->getErrors()[0]['message'] );
			// delete_option( 'access_token' );
			// Create an error message.
			$user_message = '<div class="error notice">
			<p>' . esc_textarea( $e->getErrors()[0]['message'] )
			. ' Please select the correct user.</p>
			</div>';
			set_transient( 'user_message_event', $user_message, 5 );
			exit;
		}
	}
}

/**
 * Creates an authorized Google client.
 *
 * @return object $client Google Client object.
 */
function get_client() {
	$client = new Google_Client();
	$client->setAuthConfig( __DIR__ . '/client_secrets.json' );
	$client->setIncludeGrantedScopes( true );// Incremental auth.
	$client->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
	$client->setAccessType( 'offline' ); // Necessary to get a refresh token.
	$client->setApprovalPrompt( 'force' ); // Necessary to get a refresh token.
	// If the user has already authorized this app then get an access token
	// else redirect to ask the user to authorize access to Google Analytics.
	$access_token = get_option( 'autd_access_token' );
	if ( ! empty( $access_token ) ) {
		// If the access token has expired, fetch a new one.
		if ( $client->isAccessTokenExpired() ) {
			$client->fetchAccessTokenWithRefreshToken( $access_token['refresh_token'] );
			$client->setAccessToken( $client->getAccessToken() );
			return $client;
		} else {
			// Else set the access token as usual.
			$client->setAccessToken( $access_token );
		}
		return $client;
	} else {
		$redirect_uri = plugin_dir_url( __FILE__ ) . 'oauth2callback.php';
		wp_redirect( $redirect_uri );
	}
}
/**
 * Use GA Reporting v4 to get a report from a list of specific parameters
 * set below. Needs an authorized client to work.
 *
 * @param object $analytics Authorized service object.
 * @return array Complete report.
 */
function get_audience_report( $analytics ) {
	// This post url.
	$post_url = str_replace( '/stats', '', $_SERVER['REQUEST_URI'] );

	// Using following View IDs for testing: 196032391  199522412.
	$view_id = get_option( 'autd_view_id' );

	// Creating the DateRange object from 2016 to today.
	$since_creation_date_range = new Google_Service_AnalyticsReporting_DateRange();
	$since_creation_date_range->setStartDate( '2016-01-01' );
	$since_creation_date_range->setEndDate( 'today' );

	// Creating the Metrics object.
	$unique_page_views = new Google_Service_AnalyticsReporting_Metric();
	$unique_page_views->setExpression( 'ga:uniquePageViews' );
	$unique_page_views->setAlias( 'uniquePageViews' );

	// Creating the Dimensions objects.
	$page_path = new Google_Service_AnalyticsReporting_Dimension();
	$page_path->setName( 'ga:pagePath' );

	$user_age_bracket = new Google_Service_AnalyticsReporting_Dimension();
	$user_age_bracket->setName( 'ga:userAgeBracket' );

	$user_gender = new Google_Service_AnalyticsReporting_Dimension();
	$user_gender->setName( 'ga:userGender' );

	$user_language = new Google_Service_AnalyticsReporting_Dimension();
	$user_language->setName( 'ga:language' );

	$user_country = new Google_Service_AnalyticsReporting_Dimension();
	$user_country->setName( 'ga:country' );

	$date_dimension = new Google_Service_AnalyticsReporting_Dimension();
	$date_dimension->setName( 'ga:date' );

	// Creating Dimension Filter.
	$dimention_filter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
	$dimention_filter->setDimensionName( 'ga:pagePath' );
	$dimention_filter->setOperator( 'EXACT' );
	$dimention_filter->setExpressions( $post_url );

	// Creating the DimensionFilterClauses.
	$dimention_filter_clause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
	$dimention_filter_clause->setFilters( array( $dimention_filter ) );

	// Creating the ReportRequest objects.
	$requests = array();

	$country_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$country_request->setViewId( $view_id );
	$country_request->setDateRanges( array( $since_creation_date_range ) );
	$country_request->setDimensions( array( $page_path, $user_country ) );
	$country_request->setMetrics( array( $unique_page_views ) );
	$country_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );
	array_push( $requests, $country_request );

	$language_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$language_request->setViewId( $view_id );
	$language_request->setDateRanges( array( $since_creation_date_range ) );
	$language_request->setDimensions( array( $page_path, $user_language ) );
	$language_request->setMetrics( array( $unique_page_views ) );
	$language_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );
	array_push( $requests, $language_request );

	$gender_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$gender_request->setViewId( $view_id );
	$gender_request->setDateRanges( array( $since_creation_date_range ) );
	$gender_request->setDimensions( array( $page_path, $user_gender ) );
	$gender_request->setMetrics( array( $unique_page_views ) );
	$gender_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );
	array_push( $requests, $gender_request );

	$age_bracket_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$age_bracket_request->setViewId( $view_id );
	$age_bracket_request->setDateRanges( array( $since_creation_date_range ) );
	$age_bracket_request->setDimensions( array( $page_path, $user_age_bracket ) );
	$age_bracket_request->setMetrics( array( $unique_page_views ) );
	$age_bracket_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );
	array_push( $requests, $age_bracket_request );

	$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	$body->setReportRequests( $requests );
	return $analytics->reports->batchGet( $body );
}

/**
 * Use GA Reporting v4 to get a report from a list of specific parameters
 * set below. Needs an authorized client to work.
 *
 * @param object $analytics Authorized service object.
 * @return array Complete report.
 */
function get_monthly_page_views_report( $analytics ) {
	// This post url.
	$post_url = str_replace( '/stats', '', $_SERVER['REQUEST_URI'] );

	// Using following View IDs for testing: 196032391  199522412.
	$view_id = get_option( 'autd_view_id' );

	// Creating the DateRange object from a month ago to today.
	$month_date_range = new Google_Service_AnalyticsReporting_DateRange();
	$month_date_range->setStartDate( '30daysAgo' );
	$month_date_range->setEndDate( 'today' );

	// Creating the Metrics object.
	$unique_page_views = new Google_Service_AnalyticsReporting_Metric();
	$unique_page_views->setExpression( 'ga:uniquePageViews' );
	$unique_page_views->setAlias( 'uniquePageViews' );

	// Creating the Dimensions objects.
	$page_path = new Google_Service_AnalyticsReporting_Dimension();
	$page_path->setName( 'ga:pagePath' );

	$date_dimension = new Google_Service_AnalyticsReporting_Dimension();
	$date_dimension->setName( 'ga:date' );

	// Creating Dimension Filter.
	$dimention_filter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
	$dimention_filter->setDimensionName( 'ga:pagePath' );
	$dimention_filter->setOperator( 'EXACT' );
	$dimention_filter->setExpressions( $post_url );

	// Creating the DimensionFilterClauses.
	$dimention_filter_clause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
	$dimention_filter_clause->setFilters( array( $dimention_filter ) );

	$page_views_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$page_views_request->setViewId( $view_id );
	$page_views_request->setDateRanges( array( $month_date_range ) );
	$page_views_request->setDimensions( array( $page_path, $date_dimension ) );
	$page_views_request->setMetrics( array( $unique_page_views ) );
	$page_views_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );

	$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	$body->setReportRequests( array( $page_views_request ) );
	return $analytics->reports->batchGet( $body );
}


function get_total_page_views_report( $analytics ) {
	// This post url.
	$post_url = str_replace( '/stats', '', $_SERVER['REQUEST_URI'] );

	// Using following View IDs for testing: 196032391  199522412.
	$view_id = get_option( 'autd_view_id' );

	// Creating the DateRange object from 2016 to today.
	$since_creation_date_range = new Google_Service_AnalyticsReporting_DateRange();
	$since_creation_date_range->setStartDate( '2016-01-01' );
	$since_creation_date_range->setEndDate( 'today' );

	// Creating the Metrics object.
	$unique_page_views = new Google_Service_AnalyticsReporting_Metric();
	$unique_page_views->setExpression( 'ga:uniquePageViews' );
	$unique_page_views->setAlias( 'uniquePageViews' );

	// Creating the Dimensions objects.
	$page_path = new Google_Service_AnalyticsReporting_Dimension();
	$page_path->setName( 'ga:pagePath' );

	// Creating Dimension Filter.
	$dimention_filter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
	$dimention_filter->setDimensionName( 'ga:pagePath' );
	$dimention_filter->setOperator( 'EXACT' );
	$dimention_filter->setExpressions( $post_url );

	// Creating the DimensionFilterClauses.
	$dimention_filter_clause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
	$dimention_filter_clause->setFilters( array( $dimention_filter ) );

	$page_views_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$page_views_request->setViewId( $view_id );
	$page_views_request->setDateRanges( array( $since_creation_date_range ) );
	$page_views_request->setDimensions( array( $page_path) );
	$page_views_request->setMetrics( array( $unique_page_views ) );
	$page_views_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );

	$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	$body->setReportRequests( array( $page_views_request ) );
	return $analytics->reports->batchGet( $body );
}

/**
 * Gets a report containing the total page views received by a list of
 * posts belonging to an author. Needs an authorized client to work.
 *
 * @param object $analytics Authorized service object.
 * @param array  $list_of_posts Authors posts.
 * @return array Complete report.
 */
function get_author_posts_report( $analytics ) {
	// Using following View IDs for testing: 196032391  199522412.
	$view_id = get_option( 'autd_view_id' );
	
	// Creating the DateRange object from 2016 to today.
	$since_creation_date_range = new Google_Service_AnalyticsReporting_DateRange();
	$since_creation_date_range->setStartDate( '2016-01-01' );
	$since_creation_date_range->setEndDate( 'today' );

	// Creating the Metrics object.
	$unique_page_views = new Google_Service_AnalyticsReporting_Metric();
	$unique_page_views->setExpression( 'ga:uniquePageViews' );
	$unique_page_views->setAlias( 'uniquePageViews' );

	// Creating the Dimensions objects.
	$page_path = new Google_Service_AnalyticsReporting_Dimension();
	$page_path->setName( 'ga:pagePath' );

	$year_dimension = new Google_Service_AnalyticsReporting_Dimension();
	$year_dimension->setName( 'ga:year' );

	$month_dimension = new Google_Service_AnalyticsReporting_Dimension();
	$month_dimension->setName( 'ga:month' );

	// Creating Dimension Filter.
	$posts_slugs = get_posts_slugs_array();
	$dimention_filter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
	$dimention_filter->setDimensionName( 'ga:pagePath' );
	$dimention_filter->setOperator( 'IN_LIST' );
	$dimention_filter->setExpressions( $posts_slugs );

	// Creating the DimensionFilterClauses.
	$dimention_filter_clause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
	$dimention_filter_clause->setFilters( array( $dimention_filter ) );

	$page_views_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$page_views_request->setViewId( $view_id );
	$page_views_request->setDateRanges( array( $since_creation_date_range ) );
	$page_views_request->setDimensions( array( $page_path, $year_dimension, $month_dimension ) );
	$page_views_request->setMetrics( array( $unique_page_views ) );
	$page_views_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );

	$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	$body->setReportRequests( array( $page_views_request ) );
	return $analytics->reports->batchGet( $body );
}
/**
 * Use GA Reporting v4 to get a report from a list of specific parameters
 * set below. Needs an authorized client to work.
 *
 * @param object $analytics Authorized service object.
 * @return array Complete report.
 */
function get_republished_report( $analytics ) {
	// This post url.
	$post_url = str_replace( '/stats', '', $_SERVER['REQUEST_URI'] );

	// Using following View IDs for testing: 196032391  199522412.
	$view_id = get_option( 'autd_view_id' );

	// Creating the DateRange object from 2016 to today.
	$since_creation_date_range = new Google_Service_AnalyticsReporting_DateRange();
	$since_creation_date_range->setStartDate( '2016-01-01' );
	$since_creation_date_range->setEndDate( 'today' );

	// Creating the Metrics object.
	$event_value = new Google_Service_AnalyticsReporting_Metric();
	$event_value->setExpression( 'ga:totalEvents' );
	$event_value->setAlias( 'totalEvents' );

	// Creating the Dimensions objects.
	$page_path = new Google_Service_AnalyticsReporting_Dimension();
	$page_path->setName( 'ga:pagePath' );

	$event_category = new Google_Service_AnalyticsReporting_Dimension();
	$event_category->setName( 'ga:eventCategory' );

	$event_label = new Google_Service_AnalyticsReporting_Dimension();
	$event_label->setName( 'ga:eventLabel' );

	$full_referrer = new Google_Service_AnalyticsReporting_Dimension();
	$full_referrer->setName( 'ga:fullReferrer' );

	// Creating Dimension Filter.
	$dimention_filter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
	$dimention_filter->setDimensionName( 'ga:pagePath' );
	$dimention_filter->setOperator( 'EXACT' );
	$dimention_filter->setExpressions( $post_url );

	$event_filter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
	$event_filter->setDimensionName( 'ga:eventCategory' );
	$event_filter->setOperator( 'EXACT' );
	$event_filter->setExpressions( 'Republish' );

	// Creating the DimensionFilterClauses.
	$dimention_filter_clause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
	$dimention_filter_clause->setOperator( 'AND' );
	$dimention_filter_clause->setFilters( array( $dimention_filter, $event_filter ) );

	$republished_request = new Google_Service_AnalyticsReporting_ReportRequest();
	$republished_request->setViewId( $view_id );
	$republished_request->setDateRanges( array( $since_creation_date_range ) );
	$republished_request->setDimensions( array( $page_path, $event_category, $event_label, $full_referrer ) );
	$republished_request->setMetrics( array( $event_value ) );
	$republished_request->setDimensionFilterClauses( array( $dimention_filter_clause ) );

	$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	$body->setReportRequests( array( $republished_request ) );
	return $analytics->reports->batchGet( $body );
}


/**
 * Formats and stores GA data pertaining to a specific post in
 * a transient.
 *
 * @param array $responses Responses from the GA queries.
 * @return void
 */
function store_page_views( $responses, $post_id ) {
	// Storing data in shorter variable names.
	$monthly_page_views_report  = $responses['monthly_page_views_report']['reports'][0]['data']['rows'];
	$country_report     = $responses['audience_report']['reports'][0]['data']['rows'];
	$language_report    = $responses['audience_report']['reports'][1]['data']['rows'];
	$gender_report      = $responses['audience_report']['reports'][2]['data']['rows'];
	$age_report         = $responses['audience_report']['reports'][3]['data']['rows'];
	$url                = $page_views_report[0]['dimensions'][0];
	$republished_report = $responses['republished_report']['reports'][0]['data']['rows'];
	// Getting total monthly views and total views since creation.
	$monthly_total = $responses['monthly_page_views_report']['reports'][0]['data']['totals'][0]['values'][0];
	$total_views   = $responses['total_page_views_report']['reports'][0]['data']['totals'][0]['values'][0];
	
	// Storing all data in a transient.
	$republish_data = get_republish_array($republished_report);
	$page_views = array(
		'page_views_array'      => get_data_array($monthly_page_views_report, 'page_views'),
		'genders_array'         => get_data_array($gender_report, 'gender'),
		'ages_array'            => get_data_array($age_report, 'age'),
		'total_views'           => $total_views,
		'monthly_total'         => $monthly_total,
		'countries_array'       => get_data_array($country_report, 'countries'),
		'languages_array'       => get_data_array($language_report, 'languages'),
		'republish_array'       => $republish_data['republish_array'],
		'republish_total_views' => $republish_data['republish_total_views'],
	);
	update_post_meta( $post_id, 'autd_ga_page_views_post', $page_views );
	update_post_meta( $post_id, 'autd_total_views_last_update', time() );
}


function store_authors_page_views( $response ) {
	global $wp_query;
	$historical_readers_report = $response['reports'][0]['data']['rows'];
	foreach ( $historical_readers_report as $key => $row ) {
		$historical_readers_report_pre_array[] = array(
				'year'  => $row['dimensions'][1],
				'month'   => $row['dimensions'][2],
				'views' => $row['metrics'][0]['values'][0],
			);
	}
	
	$historical_readers_report_array = sort_by_year_and_month( $historical_readers_report_pre_array );
	update_user_meta( $wp_query->get_queried_object_id(), 'ga_author_historical_views', $historical_readers_report_array);
}

function sort_by_year_and_month( $results_array ) {
    $sorted_array = array();
    foreach ( $results_array as $result ) {
        if ( ! array_key_exists( $result['year'] . '-' . $result['month'], $sorted_array ) ) {
            $sorted_array[ $result['year'] . '-' . $result['month'] ] = $result['views'];
        } else {
            $sorted_array[ $result['year'] . '-' . $result['month'] ] += $result['views'];
        }
    }
    ksort( $sorted_array );
    return $sorted_array;
}


function get_cron_report( $analytics ) {

	// Using following View IDs for testing: 196032391  199522412.
	$view_id = get_option( 'autd_view_id' );

	// Creating the DateRange object from 2016 to today.
	$since_creation_date_range = new Google_Service_AnalyticsReporting_DateRange();
	$since_creation_date_range->setStartDate( '2016-01-01' );
	$since_creation_date_range->setEndDate( 'today' );

	// Creating the Metrics object.
	$unique_page_views = new Google_Service_AnalyticsReporting_Metric();
	$unique_page_views->setExpression( 'ga:uniquePageViews' );
	$unique_page_views->setAlias( 'uniquePageViews' );

	// Creating the Dimensions objects.
	$page_path = new Google_Service_AnalyticsReporting_Dimension();
	$page_path->setName( 'ga:pagePath' );

	// Creating Dimension Filter.
	$dimention_filter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
	$dimention_filter->setDimensionName( 'ga:pagePath' );
	$dimention_filter->setOperator( 'IN_LIST' );
	$dimention_filter->setExpressions( get_updateable_posts_urls() );

	// Creating the DimensionFilterClauses.
	$dimention_filter_clause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
	$dimention_filter_clause->setFilters( array( $dimention_filter ) );

	// Creating the ReportRequest object.
	$request = new Google_Service_AnalyticsReporting_ReportRequest();
	$request->setViewId( $view_id );
	$request->setDateRanges( array( $since_creation_date_range ) );
	$request->setDimensions( array( $page_path ) );
	$request->setMetrics( array( $unique_page_views ) );
	$request->setDimensionFilterClauses( array( $dimention_filter_clause ) );

	$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	$body->setReportRequests( array( $request ) );
	return $analytics->reports->batchGet( $body );
}

function get_updateable_posts_urls( $data = false ) {
	$hour_ago_timestamp = strtotime( '-1 hour', time() );
	$args = array(
		'numberposts'      => -1,
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'meta_key'         => '',
		'meta_value'       => '',
		'post_type'        => array( 'post', 'page', 'debate_post', 'blog_post' ),
		'suppress_filters' => true,
		'meta_query'       => array(
			'relation' => 'OR',
			array(
				'key'     => 'autd_total_views_last_update',
				'compare' => 'NOT EXISTS',
				'value'   => '', // This is ignored, but is necessary.
			),
			array(
				'key'     => 'autd_total_views_last_update',
				'value'   => $hour_ago_timestamp,
				'compare' => '<',
			),
		),
	);

	$all_posts  = get_posts( $args );
	$posts_urls = array();
	if ( 'ids' === $data ) {
		foreach ( $all_posts as $key => $post ) {
			$post_url                = str_replace(
				get_site_url(),
				'',
				get_permalink( $post->ID )
			);
			$posts_urls[ $post->ID ] = $post_url;
		}
	} else {
		foreach ( $all_posts as $post ) {
			$post_url = str_replace( get_site_url(), '', get_permalink( $post->ID ) );
			array_push( $posts_urls, $post_url );
		}
	}
	return $posts_urls;
}

function store_cron_page_views( $response ) {
	$raw_results = $response['reports'][0]['data']['rows'];
	$pages       = get_updateable_posts_urls( 'ids' );
	$page_views  = array();
	foreach ( $raw_results as $key => $row ) {
		$post_id       = array_search( $row['dimensions'][0], $pages, true );
		$url           = $row['dimensions'][0];
		$total_views   = $row['metrics'][0]['values'][0];
		$now_timestamp = time();
		$ga_page_views_post = get_post_meta( $post_id, 'autd_ga_page_views_post', true );
		$page_views         = array(
			'page_views_array'      => $ga_page_views_post['page_views_array'],
			'genders_array'         => $ga_page_views_post['genders_array'],
			'ages_array'            => $ga_page_views_post['ages_array'],
			'total_views'           => $total_views,
			'monthly_total'         => $ga_page_views_post['monthly_total'],
			'countries_array'       => $ga_page_views_post['countries_array'],
			'languages_array'       => $ga_page_views_post['languages_array'],
			'republish_array'       => $ga_page_views_post['republish_array'],
			'republish_total_views' => $ga_page_views_post['republish_total_views'],
		);

		update_post_meta( $post_id, 'autd_ga_page_views_post', $page_views );
		update_post_meta( $post_id, 'autd_total_views_last_update', $now_timestamp );
	}
}
