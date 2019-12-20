<?php
/**
 * This file handles all the funcionality needed to hide unauthored posts
 * and remove the corresponding post count from the dashboard.
 *
 * @package Authors Dashboard
 */

add_filter( 'pre_get_posts', 'posts_for_current_author' );
add_action( 'admin_footer', 'jquery_remove_posts_count' );

/**
 * Makes sure current user can only see his own posts.
 *
 * @param object $query WP_Query instance.
 * @return object $query Modified WP_Query instance.
 */
function posts_for_current_author( $query ) {
	$is_author = current_user_can( 'author' );
	if ( $query->is_admin && $is_author ) {
		global $user_ID;
		$query->set( 'author', $user_ID );
	}
	return $query;
}
/**
 * Removes post count from the dashboard.
 *
 * @return void
 */
function jquery_remove_posts_count() {
	global $pagenow;
	$is_author = current_user_can( 'author' );
	if ( $is_author && 'edit.php' === $pagenow ) { ?>
		<script type="text/javascript">
		jQuery(function($){
				$("ul.subsubsub li.all").remove();
				$("ul.subsubsub li.draft").find("span.count").remove();
				$("ul.subsubsub li.publish").find("span.count").remove();
				$("ul.subsubsub li.private").find("span.count").remove();
				$("ul.subsubsub li.trash").find("span.count").remove();
		});
		</script>
		<?php
	}
}
