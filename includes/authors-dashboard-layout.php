<?php 
function get_article_stats($page_views, $facebook_data, $tweet_count, $post_id) {
	$plugin_dir_url = plugin_dir_url( __FILE__ ).'../';
	$result = '<h2 class="kicker">Article Stats</h2>
					<ul class="article_stats">';
					if ( ! empty( $page_views ) ) {
	$result.=		'<li>
							<span class="stats_icon">
							<img src="' . $plugin_dir_url . 'assets/images/views.svg'.'"></span>
							<p class="number">' .  number_format($page_views['total_views']) . '</p>
							<p class="label">Total views</p>
						</li>';
					}
					if ( ! empty( $facebook_data ) ) {
	$result.=			'<li>
							<span class="stats_icon">
							<img src="' . $plugin_dir_url . 'assets/images/facebook.svg'.'"></span>
							<p class="number">' .  number_format($facebook_data) . '</p>
							<p class="label">Shared on Facebook</p>
						</li>';
					}
					if ( ! empty( $tweet_count ) ) {
	$result.=			'<li>
							<span class="stats_icon">
							<img src="' . $plugin_dir_url . 'assets/images/twitter.svg'.'"></span>
							<p class="number">' .  number_format($tweet_count[0]) . '</p>
							<p class="label">Shared on Twitter</p>
						</li>';
					}
					if ( comments_open() && ! is_page() ) {
						$comments_count = wp_count_comments( $post_id );
	$result.=			'<li>
							<span class="stats_icon">
							<img src="' . $plugin_dir_url . 'assets/images/comments.svg'.'"></span>
							<p class="number">' .  number_format($comments_count->approved) . '</p>
							<p class="label">Comments<br /><br /></p>
						</li>';
					}

					$republish = get_post_meta( $post_id, 'rc_republish_content', true );
					if ( 'no-republish' !== $republish ) {
						$republish_total_views = (!empty($page_views['republish_total_views']) ) ? $page_views['republish_total_views'] : '0';
	$result.=			'<li>
							<span class="stats_icon">
							<img src="' . $plugin_dir_url . 'assets/images/reply.svg'.'"></span>
							<p class="number">' .  number_format($republish_total_views). '</p>
							<p class="label">Republish views</p>
						</li>';
					}

	$result.=			'</ul>';

	return $result;
}


function get_monthly_views_chart($page_views) {
	if(empty($page_views['page_views_array'])){
		return false;
	}
	$page_views_string = implode( ',', array_keys($page_views['page_views_array']) );
	$dates_string      = implode( ',', $page_views['page_views_array'] );

	$result = '<section class="stats_charts">
					<h2 class="kicker">Monthly views</h2>
					<div class="graphic">
						<canvas id="monthly-views" width="800" height="450"></canvas>
					</div>
				</section>';
	$result.= '<script>
					new Chart(document.getElementById("monthly-views"), {
						type: "line",
						data: {
							labels: [' . $page_views_string . '],
							datasets: [{ 
								data: [' . $dates_string . '],
								label: "Users",
								borderColor: "#BF2528",
								fill: true
							},

							]
						},
						options: {
							title: {
							display: false,
							text: "Monthly views"
							},
							legend: {
									display: false
							},
							layout: {
								padding: {
									left: 5,
									right: 5,
									top: 25,
									bottom: 5
								}
							},
						},
						
					});
				</script>';
	return $result; 
}

function get_gender_chart($page_views) {
	if(empty($page_views['genders_array'])){
		return false;
	}
	$genders_string = implode( ',', array_keys($page_views['genders_array']) );
	$gender_total = array_sum($page_views['genders_array']);

	foreach ($page_views['genders_array'] as $key => $value) {
		$page_views['genders_array'][$key] = round($value * 100 / $gender_total);
	}

	$gender_page_views_string = implode( ',', $page_views['genders_array'] );

	$result = '<section class="stats_charts">
					<h2 class="kicker">Gender</h2>
					<div class="graphic">
						<canvas id="gender" width="800" height="450"></canvas>
					</div>
				</section>';					
	$result.= '<script>
					new Chart(document.getElementById("gender"), {
						type: "doughnut",
						data: {
						labels: ['.$genders_string.'],
						datasets: [
							{
							label: "",
							backgroundColor: ["#BF2528", "#184B96"],
							data: ['.$gender_page_views_string.']
							}
						]
						},
						options: {
							legend: { display: true },
							title: {
								display: true,
								text: ""
							},
							layout: {
								padding: {
									left: 0,
									right: 0,
									top: 0,
									bottom: 25
								}
							},
							tooltips: {
						     	callbacks: {
						        	label: function(tooltipItem, data) {
						        		var dataset = data.datasets[tooltipItem.datasetIndex];        
						          		return dataset.data[tooltipItem.index] + "%";
						        	}
						      	}
						    },
						}
					});
				</script>';
	return $result;
}

function get_age_chart($page_views) {
	if(empty($page_views['ages_array'])){
		return false;
	}

	$ages_string = implode( ',', array_keys($page_views['ages_array']));
	$age_total = array_sum($page_views['ages_array']);
	$maxs = array_keys($page_views['ages_array'], max($page_views['ages_array']));
	
	foreach ($page_views['ages_array'] as $key => $value) {
		$page_views['ages_array'][$key] = round($value * 100 / $age_total);
		$bar_colors[] = ($key == $maxs[0]) ? '"#BF2528"' : '"#184B96"';
	}
	$bar_colors_string = implode( ',',$bar_colors);
	$age_page_views_string = implode( ',',$page_views['ages_array']);

	$result = '<section class="stats_charts">
					<h2 class="kicker">Age</h2>
					<div class="graphic">
						<canvas id="age" width="800" height="450"></canvas>
					</div>
				</section>';
	$result.= '<script>
					new Chart(document.getElementById("age"), {
						type: "bar",
						data: {
						labels: ['.$ages_string.'],
						datasets: [
							{
							label: "",
							backgroundColor: ['.$bar_colors_string.'],
							data: ['.$age_page_views_string.']
							}
						]
						},
						options: {
							legend: { display: false },
							title: {
								display: true,
								text: ""
							},
							layout: {
								padding: {
									left: 15,
									right: 15,
									top: 0,
									bottom: 10
								}
							},
							scales: {
								yAxes: [{
								    ticks: {
								        beginAtZero: true
								    }
								}]
							},
							tooltips: {
						     	callbacks: {
						        	label: function(tooltipItem, data) {
						        		var dataset = data.datasets[tooltipItem.datasetIndex];        
						          		return dataset.data[tooltipItem.index] + "%";
						        	}
						      	}
						    },
						}
					});
			</script>';
	return $result;
}

function get_countries_chart($page_views) {
	if(empty($page_views['countries_array'])){
		return false;
	}

	$total_views = array_sum($page_views['countries_array']);
	$countries = array_slice($page_views['countries_array'], 0, 10);
	$result = '<section class="stats_charts">
					<h2>Visitors per Country (Top 10)</h2>
					<div class="bar-list"><ul>';
					foreach ($countries as $country => $value) {

						$bar_percentage = $value * 100 / $total_views;
	$result.=			'<li>
							<span class="data">
								<span class="label">'. $country.'</span>
								<span class="bar" style="width:'.$bar_percentage.'%;"></span>
								<span class="percentage">'.ceil($bar_percentage).'%</span>
							</span>
						</li>';
					}
	$result.=		'</ul></div></section>';
	return $result;
}

function get_languages_chart($page_views) {
	if(empty($page_views['languages_array'])){
		return false;
	}
	
	$total_views = array_sum($page_views['languages_array']);
	$languages = array_slice($page_views['languages_array'], 0, 10);
	$result = '<section class="stats_charts">
					<h2>Visitors per Language (Top 10)</h2>	
					<div class="bar-list"><ul>';
					foreach ($languages as $language => $value) {
						$bar_percentage = $value * 100 / $total_views;
	$result.=			'<li>
							<span class="data">
								<span class="label">'. $language.'</span>
								<span class="bar" style="width:'.$bar_percentage.'%;"></span>
								<span class="percentage">'.ceil($bar_percentage).'%</span>
							</span>
						</li>';
					}
	$result.=	'</ul></div></section>';
	return $result;
}


function get_republish_table($page_views) {
	if(empty($page_views['republish_array'])){
		return false;
	}

	$page_views['republish_array'];

	$result = '<section class="stats_charts tablestyle">
					<h2>Republish details</h2>	
					<div class="bar-list"><ul>';
					foreach ($page_views['republish_array'] as $value) {
	$result.=			'<li>
							<span class="label"><a href="http://'. $value['republished_url'].'" target="_blank">'. $value['republished_site'].'</a></span>
							<span class="data">
							<span class="number">'. $value['total_views'].' views</span>
							</span>
						</li>';
					}
	$result.=	'</ul></div></section>';
	return $result;
}

function get_tweets( $tweet_count, $post_id ){
	if ( ! empty( $tweet_count ) ) {
		$result=	'<section id="tweets-block">
						<button id="view-tweets">
							<i class="plus-icon"></i> View Tweets 
							<span class="">' . $tweet_count[0] . '</span>
						</button>
						<div id="tweets-list" style="display:none">';
		$tweets_data = get_post_meta( $post_id, 'tweets_data' );
		if ( ! empty( $tweets_data ) ) {
			foreach ( $tweets_data[0] as $tweet_data ) {
				$result.= get_tweet( $tweet_data );
			}
		}
		$result.= '</div></section>';
	}
	return $result;
}

function get_tweet( $tweet_data ) {
	global $wp_embed;
	$url = 'https://twitter.com/' . $tweet_data['user'] . '/status/' . $tweet_data['id'];
	return $wp_embed->run_shortcode( '[embed]' . $url . '[/embed]' );
}


function current_user_authored(&$post){
	if (current_user_can('administrator')) { return true; }
	$user_author_id = author_get_from_cookie();
	$author_ids = get_all_author_ids($post);
	return in_array($user_author_id, $author_ids);
}

function current_user_is_author($author_id){
	$user_author_id = author_get_from_cookie();
	return $user_author_id == $author_id;
}

function author_get_from_cookie(){
	if (!is_user_logged_in()) { return false; }
	if (!isset($_COOKIE['authorkey'])) { return false; }
	return author_get($_COOKIE['authorkey']);
}

function author_get($authorkey){
	if ($authorkey) { 
		$author = get_terms(array(
			'meta_key'       => 'stats_key',
			'meta_value'     => $authorkey,
			'taxonomy'  => 'authors',
		));
	
		if (count($author) == 1){
			$author = $author[0]->term_id;
			return $author;
		}
	}
	return false; 
}

add_action( 'template_redirect', 'author_login');
function author_login(){
	global $wp;
	
	$authorkey = $_GET['authorkey'];
	if (!isset($authorkey) || empty($authorkey)){ return; }
	
	if (author_get($authorkey) && !isset($_COOKIE['redirected'])){

		// Login a fake user to work around WPE eating cookies for anon users. http://goo.gl/8UUJIs
		if (!is_user_logged_in()){
			$user = wp_signon(array(
				'user_login' => 'stats-guest', 
				'user_password' => 'YLrjP>Sk{d$8<+G*h)eQX^^zZ69Vy:Tb8#DNycb<#', 
			), false);
		}
		// Send the user back to this page, with a cookie storing this author's ID.
		setcookie('authorkey', $authorkey, time()+60*60*24, '/');
		// This 8-second cookie is checked on the IF above, to prevent a redirect loop
		setcookie('redirected', '1', time()+8, '/');
		
		$url = trailingslashit(home_url($wp->request));
		wp_redirect($url, 302);
		die();
	}
}



add_action( 'edit_term', 'set_stats_key', 10, 3 );
function set_stats_key($term_id, $tt_id, $taxonomy) {
	$term = get_term($term_id, $taxonomy);
	$term_slug = $term->slug;
	if($taxonomy=='authors'){
		$stats_key = get_field('stats_key', $term->taxonomy . '_' . $term->term_id);
	}
	if(empty($stats_key)){
		$key = wp_generate_password(20, false, false);
		update_field('stats_key', $key, $term->taxonomy . '_' . $term->term_id);
	}
}

add_filter('acf/prepare_field/name=stats_key', 'stats_key_instructions', 10, 1);
function stats_key_instructions($field) {
	$tag_ID = (int) $_GET['tag_ID'];
	if (!$tag_ID){ 
		$field['instructions'] = preg_replace("%{{if_link}}.*?{{/if_link}}%", "", $field['instructions']);
		return $field; 
	}
	$link = get_term_link($tag_ID, 'authors') . "?authorkey=" . $field["value"];
	$instructions = str_replace("{{author_stats_link}}", $link, $field['instructions']);
	$instructions = preg_replace("%{{/?if_link}}%", "", $instructions);
	$field['instructions'] = $instructions;
    return $field;
    
}

function author_disable_admin(){
	if (!is_user_logged_in ()) { return; }
	
	$user = wp_get_current_user();
	if ($user->user_login != 'stats-guest' ){ return; }
	
    add_filter( 'show_admin_bar', '__return_false' );
	
	if (is_admin()){
		wp_redirect(home_url());
	}
}
add_action('init', 'author_disable_admin', 1);


function get_total_views(&$post){
	if (current_user_authored($post)){
		$plugin_dir_url = plugin_dir_url( __FILE__ ).'../';
		$page_views = get_post_meta( $post->ID, 'ga_page_views_post' );
		$result = '<ul class="author_list_stats">';
		$result.= 	'<li>
						<a class="ununderlined" href="'. get_permalink($post) .'stats/">
							<span class="stats_icon">
								<img src="' . $plugin_dir_url . 'assets/images/views.svg'.'">
							</span>
						</a>
						<a href="' . get_permalink($post) . 'stats/">
							<span class="number">' . $page_views[0]['total_views'] . '</span>
							<span class="label">Total views</span>
						</a>
					</li>';
		$result.= '</ul>';
	}
	return $result;
}



function get_historical_views_chart() { // FIXME remove, not supported.
	if (current_user_authored($post)){
		global $wp_query;
		get_and_store_page_views(false, true);
		$historical_views = get_user_meta($wp_query->get_queried_object_id(), 'ga_author_historical_views');

		if(empty($historical_views)){
			return false;
		}

		$page_views_string =  "'" . implode ( "', '", array_keys($historical_views[0]) ) . "'";
		$dates_string      = implode( ',', $historical_views[0] );

		$result = '<section class="stats_charts">
						<h2 class="kicker">All articles by month</h2>
						<div class="graphic">
							<canvas id="monthly-views" width="800" height="450"></canvas>
						</div>
					</section>';
		$result.= '<script>
						new Chart(document.getElementById("monthly-views"), {
							type: "line",
							data: {
								labels: [' . $page_views_string . '],
								datasets: [{ 
									data: [' . $dates_string . '],
									label: "Users",
									borderColor: "#BF2528",
									fill: true
								},

								]
							},
							options: {
								title: {
								display: false,
								text: "Monthly views"
								},
								legend: {
										display: false
								},
								layout: {
									padding: {
										left: 5,
										right: 5,
										top: 25,
										bottom: 5
									}
								},
							},
							
						});
					</script>';
		return $result;
	}
}
