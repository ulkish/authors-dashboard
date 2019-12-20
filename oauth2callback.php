<?php
/**
 * Calllback file for Google Analytics to respond to. This file will need to
 * be relocated to a static server from which all calls to GA will be made.
 * An API with endpoints for the plugin instances will also need to be created.
 *
 * @package Authors Dashboard
 */

require_once '../../../wp-config.php';
require_once '../../../wp-load.php';
require_once __DIR__ . '/vendor/autoload.php'; // Google API PHP Client Library.

// Create the client object and set the authorization configuration
// from the client_secrets.json you downloaded from the Developers Console.
$client = new Google_Client();
$client->setAuthConfig( __DIR__ . '/client_secrets.json' );
$client->setRedirectUri(
	plugin_dir_url( '' ) . 'authors-dashboard/oauth2callback.php'
);
$client->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
// Necessary to get a refresh token.
$client->setApprovalPrompt( 'force' );
// Same as above.
$client->setAccessType( 'offline' );
// If we're NOT receiving 'code' containing the access token, redirect to auth.
if ( ! isset( $_GET['code'] ) ) {
	$auth_url = $client->createAuthUrl();
	wp_redirect( $auth_url );
} else {
	// Authenticate and get an ACCESS token from Google, that ACCESS token
	// contains a REFRESH token that we will use to get new ACCESS tokens.
	$client->authenticate( $_GET['code'] );
	$token = $client->getAccessToken();
	update_option( 'autd_access_token', $token );
	$redirect_uri = admin_url() . 'options-general.php?page=authors_dashboard_page';
	wp_redirect( $redirect_uri );
}
