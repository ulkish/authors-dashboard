<?php
/**
 * Runs on uninstall of Authors Dashboard.
 *
 * @package   Authors Dashboard
 * @author    hugomoran
 * @license   GPL-2.0+
 * @link      [Your URL]
 */

// Check that we should be doing this.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly.
}

// Delete options.
$options = array(
	'access_token',
	'view_id',
	'tracking_id',
);

foreach ( $options as $option ) {
	if ( get_option( $option ) ) {
		delete_option( $option );
	}
}

// Delete pageViews table from postmeta.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '%pageViews%';" );
