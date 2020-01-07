<?php

class Post_Views_Column {

	function __construct() {
		add_filter(
			'manage_posts_columns',
			array( $this, 'create_views_column_head' )
		);
		add_filter(
			'manage_pages_columns',
			array( $this, 'create_views_column_head' )
		);
		add_action(
			'manage_posts_custom_column',
			array( $this, 'create_views_column_content' ),
			10,
			2
		);
		add_action(
			'manage_pages_custom_column',
			array( $this, 'create_views_column_content' ),
			10,
			2
		);
		add_filter(
			'manage_edit-post_sortable_columns',
			array( $this, 'set_sortable_columns' )
		);
		add_filter(
			'manage_edit-page_sortable_columns',
			array( $this, 'set_sortable_columns' )
		);
		add_action(
			'pre_get_posts',
			array( $this, 'manage_wp_posts_be_qe_pre_get_posts' ),
			1
		);

	}

	// Adds a custom column to post list table.
	function create_views_column_head( $defaults ) {
		$defaults['post_views'] = 'Views';
		return $defaults;
	}

	// Displays column content.
	function create_views_column_content( string $column_name, int $post_ID ) {
		if ( 'post_views' === $column_name ) {
			$ga_page_views = get_post_meta( $post_ID, 'autd_ga_page_views_post', true );
			if ( ! empty( $ga_page_views ) ) {
				echo esc_textarea( $ga_page_views['total_views'] );
			} else {
				echo '-';
			}
		}
	}

	function set_sortable_columns( array $columns ) : array {
		$columns['post_views'] = 'autd_ga_page_views_post';
		return $columns;
	}

	function manage_wp_posts_be_qe_pre_get_posts( $query ) {
		if ( $query->is_main_query() && ( $orderby = $query->get( 'orderby' ) ) ) {
			switch ( $orderby ) {
				// If we're ordering by 'autd_ga_page_views_post'.
				case 'autd_ga_page_views_post':
					// Set our query's meta_key, which is used for custom fields.
					$query->set( 'meta_key', 'autd_ga_page_views_post' );
					// Sort by numeric order.
					$query->set( 'orderby', 'meta_value_num' );
					break;
			}
		}
	}
}
new Post_Views_Column();
