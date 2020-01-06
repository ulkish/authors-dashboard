<?php
/**
 * Plugin Name:       Authors Dashboard with Google Analytics
 * Plugin URI:        https://tipit.net/
 * Description:       Add a statistics dashboard to your posts.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Hugo Moran
 * Author URI:        https://tipit.net
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Authors Dashboard is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Authors Dashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Authors Dashboard. If not, see https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Authors Dashboard
 */

// TODO LIST:
// - Automatically run flush_rewrite_rules() when this plugin is activated.
// - Fix Google exceptions always deleting the access_token.
// - Use wp_localize_script instead of adding the gtag to wp_footer.
// - Change wp_redirect() to wp_safe_redirect throughout the plugin.
// - Check rate limit for Google Analytics data requests.


// Loads the Google API PHP Client Library and the Facebok Graph API.
require_once __DIR__ . '/vendor/autoload.php';
// Loading the Facebook section of the plugin.
require_once __DIR__ . '/authors-dashboard-facebook.php';
// Loading the Twitter section of the plugin.
require_once __DIR__ . '/authors-dashboard-twitter.php';
// Creates the plugin's settings page.
require_once __DIR__ . '/includes/settings-page-class.php';
// Adds a custom column to display our Google Analytics data.
require_once __DIR__ . '/includes/post-views-column-class.php';
// Hides posts that don't belong to the user.
require_once __DIR__ . '/includes/hide-unauthored-posts.php';
// Stats layout.
require_once __DIR__ . '/includes/authors-dashboard-layout.php';
// Creates a Google Client to get and store a report from Google Analytics.
require_once __DIR__ . '/google-analytics.php';

/**
 * Gets the View ID from the user and uses it to
 * authorize and execute main plugin function.
 *
 * @return void
 */
function authors_dashboard_get_google_ids() {
	// Exit if nonce does not match.
	if ( ! isset( $_POST['google_ids_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['google_ids_nonce'] ) ), 'get_google_ids' )
	) {
		echo 'Sorry, your nonce did not verify.';
		exit;
	} else {
		// Check for field values and execute main function.
		if ( ! empty( $_POST['view_id'] ) ) {
			// Sanitizing and storing the View ID.
			$view_id = sanitize_text_field( wp_unslash( $_POST['view_id'] ) );
			update_option( 'autd_view_id', $view_id );

			if ( empty( get_option( 'autd_access_token' ) ) ) {
				$redirect_uri = plugin_dir_url( __FILE__ ) . 'oauth2callback.php';
				wp_redirect( $redirect_uri );
			}
		}
	}
}
add_action( 'admin_post_get_google_ids', 'authors_dashboard_get_google_ids' );

/**
 * This function will run as soon as this plugin is activated, adding
 * our CRON to WP and flushing rewrite rules.
 *
 * @return void
 */
function authors_dashboard_plugin_activation() {
	if ( ! wp_next_scheduled( 'authors_dashboard_data_query' ) ) {
		wp_schedule_event( time(), 'hourly', 'authors_dashboard_data_query' );
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'authors_dashboard_plugin_activation' );

/**
 * Gathers data from all the plugin's APIs.
 *
 * @return void
 */
function get_and_store_all_data() {
	get_and_store_twitter_data();
	$cron = true;
	get_and_store_page_views( $cron, false );
}
add_action( 'authors_dashboard_data_query', 'get_and_store_all_data' );


/**
 * Makes sure our data gathering CRON is gone after plugin deactivation.
 *
 * @return void
 */
function authors_dashboard_plugin_deactivation() {
	wp_clear_scheduled_hook( 'authors_dashboard_data_query' );
}
register_deactivation_hook( __FILE__, 'authors_dashboard_plugin_deactivation' );

// Adding rewrites. The code below only takes effect after flushing the
// rewrite rules.
add_action( 'init', 'stats_endpoint_init' );
add_action( 'template_include', 'stats_endpoint_template_include' );

/**
 * Add our new stats endpoint
 */
function stats_endpoint_init() {
	add_rewrite_endpoint( 'stats', EP_PERMALINK | EP_PAGES );
}
add_action( 'init', 'stats_endpoint_init' );
/**
 * Respond to our new endpoint
 *
 * @param mixed $template Template.
 *
 * @return mixed $template Modified template.
 */
function stats_endpoint_template_include( $template ) {
	global $wp_query;
	// Since the "stats" query variable does not require a value, we need to
	// check for its existence.
	if ( is_singular() && isset( $wp_query->query_vars['stats'] )) {
		if (!current_user_authored(get_post())){
			wp_safe_redirect(str_replace("stats/", "", esc_url_raw(add_query_arg([]))));
			exit;
		}
		// Displaying Google Analytics data.
		add_filter( 'the_content', 'display_page_views_in_content' );
		function display_page_views_in_content( $content ) {
			$app_id          = '535448793933963';
			$app_secret      = '3d1bdfd0e2ea3f58e80662295f6613c7';
			$access_token    = $app_id . '|' . $app_secret;
			$app_credentials = array(
				'app_id'       => $app_id,
				'app_secret'   => $app_secret,
				'access_token' => $access_token,
			);
			$post_id         = get_post()->ID;
			// Displaying Google Analytics data.
			// Check if we're inside the main loop in a single post page.
			if ( is_single() && in_the_loop() && is_main_query() ) {
				$page_views       = get_post_meta( $post_id, 'autd_ga_page_views_post', true );
				$last_updated     = get_post_meta( $post_id, 'autd_total_views_last_update', true );
				$five_minutes_ago = strtotime( '-15 minutes', time() );

				if ( empty( $page_views['page_views_array'] ) || ( $last_updated <= $five_minutes_ago ) ) {
					get_and_store_page_views( false, false, $post_id );
					$page_views = get_post_meta( $post_id, 'autd_ga_page_views_post', true );
				}
				$facebook_data = get_transient( 'autd_facebook_data_post_' . $post_id );
				if ( empty( $facebook_data ) ) {
					$facebook_data = get_facebook_data( get_permalink( $post_id ), $app_credentials );
					set_transient( 'autd_facebook_data_post_' . $post_id, $facebook_data, 300 );
					$facebook_data = get_transient( 'autd_facebook_data_post_' . $post_id );
				}

				$tweet_count = get_post_meta( get_post()->ID, 'autd_tweet_count' );
				if ( isset( $page_views ) || isset( $tweet_count ) || isset( $facebook_data ) ) {
					$plugin_dir_url = plugin_dir_url( __FILE__ );

					$content =
					'<style>
						/*.share-links-wrapper {display:none;}*/
						#footer .share-links-wrapper {display:block;}
						#feature #comments-block { margin-left:280px;}
					</style>

					<div class="stats alignleft">';
					$content .= get_article_stats( $page_views, $facebook_data, $tweet_count, $post_id );
					$content .= get_monthly_views_chart( $page_views );
					$content .= '</div>';
					$content .= get_gender_chart( $page_views );
					$content .= get_age_chart( $page_views );
					$content .= get_countries_chart( $page_views );
					$content .= get_languages_chart( $page_views );
					$content .= get_republish_table( $page_views );
					$content .= get_tweets( $tweet_count, $post_id );

					return $content;
				}
			}
			return $content;
		}
	}
	return $template;
}

function author_dashboard_scripts() {
	wp_register_style( 'author-dashboard-styles', plugin_dir_url( __FILE__ ) . 'assets/css/author-dashboard-styles.css' );
	wp_enqueue_style( 'author-dashboard-styles' );
	wp_enqueue_script( 'charts-js', 'https://cdn.jsdelivr.net/npm/chart.js@2.8.0/dist/Chart.min.js' );
}
add_action( 'wp_enqueue_scripts', 'author_dashboard_scripts' );
