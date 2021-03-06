<?php

class Posts_GA_Settings_Page {

	function __construct() {
		add_action(
			'admin_menu',
			array( $this, 'admin_menu' )
		);
	}

	function admin_menu() {
		add_options_page(
			'Posts Google Analytics',
			'Posts Google Analytics',
			'manage_options',
			'authors_dashboard_page',
			array(
				$this,
				'create_settings_page',
			)
		);
	}

	function create_settings_page() {
		// Google Analytics settings.
		$view_id = get_option( 'autd_view_id' );
		$notice  = get_transient( 'autd_user_message_event' );
		if ( $notice ) {
			echo $notice;
		}
		// Facebook API settings.
		$facebook_app_id     = get_option( 'autd_facebook_app_id' );
		$facebook_app_secret = get_option( 'autd_facebook_app_secret' );

		if ( ! empty( $facebook_app_id ) && ! empty( $facebook_app_secret ) ) {
			set_option(
				'autd_facebook_access_token',
				$facebook_app_id . '|' . $facebook_app_secret
			);
		}

		?>
		<div class="wrap">
			<h2>Post Views With Google Analytics</h2>
			<p>Please create Google Analytics account <a href="https://analytics.google.com/">here</a>.
				Then create a View and paste your View ID below. As a last step authorize our plugin to retrieve<br>
				and display your Google Analytics data.</p>
			<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
				<input type="hidden" name="action" value="get_google_ids">
				<?php wp_nonce_field( 'get_google_ids', 'google_ids_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="view_id">View ID: </label>
						</th>
						<td>
							<input type="text" name="view_id" id="view_id"
							value="<?php echo $view_id ?>">
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button button-primary"
					value="Submit">
				</p>
			</form>
		</div>
		<?php

	}
}
new Posts_GA_Settings_Page();
