<?php
/**
 * Registers the custom post type for listings.
 *
 * @package EngineWP\RelayCore
 */

namespace EngineWP\RelayCore;

/**
 * Class RegisterPostType
 *
 * Handles the registration of the custom post type for listings.
 *
 * @package EngineWP\RelayCore
 */
class RegisterPostType {


	/**
	 * Registers the custom post type for listings.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'               => 'Listings',
			'singular_name'      => 'Listing',
			'menu_name'          => 'Listings',
			'name_admin_bar'     => 'Listing',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Listing',
			'new_item'           => 'New Listing',
			'edit_item'          => 'Edit Listing',
			'view_item'          => 'View Listing',
			'all_items'          => 'All Listings',
			'search_items'       => 'Search Listings',
			'parent_item_colon'  => 'Parent Listings:',
			'not_found'          => 'No listings found.',
			'not_found_in_trash' => 'No listings found in Trash.',
		);

        // phpcs:ignore WordPress.WP.PostTypeRegistration -- This is a custom post type registration.
		$args = array(
			'labels'      => $labels,
			'public'      => true,
			'has_archive' => true,
			'rewrite'     => array( 'slug' => 'listings' ),
			'supports'    => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'   => 'dashicons-admin-home',
		);

		register_post_type( 'listing', $args );
	}
}
