<?php
/**
 * Class TestWPListPagesUnlisted
 *
 * @package Unlist_Posts
 */

/**
 * Make sure WP_Query does not return the unlisted posts.
 */
class TestWPListPagesUnlisted extends WP_UnitTestCase {

	/**
	 * User ID for the editor user.
	 *
	 * @var int
	 */
	private $editor_user_id;

	/**
	 * Setup the tests class.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->editor_user_id = self::factory()->user->create(
			array(
				'role' => 'editor',
			)
		);
	}

	/**
	 * Test to make sure unlisted posts are hidden from wp_list_pages_excludes filter.
	 */
	public function test_unlisted_post_not_in_wp_list_pages() {
		_delete_all_posts();
		wp_set_current_user( $this->editor_user_id );

		// Listed Post.
		$listed_post = self::factory()->post->create(
			array(
				'post_title' => 'Listed Post Title',
				'post_type' => 'page',
			)
		);

		// Unlisted Post.
		$unlisted_post = self::factory()->post->create(
			array(
				'post_title' => 'Unlisted Post Title',
				'post_type' => 'page',
			)
		);
		$_POST['unlist_posts']       = true;
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );
		Unlist_Posts_Admin::instance()->save_meta( $unlisted_post );

		$list_pages_markup = wp_list_pages(
			array(
				'echo' => false,
			)
		);

		// Assert that the Unlisted Post is visible in wp_list_page().
		$this->assertStringNotContainsString( 'Unlisted Post Title', $list_pages_markup );
	}

	/**
	 * Test to make sure unlisted posts are hidden from wp_list_pages_excludes filter.
	 */
	public function test_listed_post_in_wp_list_pages() {
		_delete_all_posts();
		wp_set_current_user( $this->editor_user_id );

		// Listed Post.
		$listed_post = self::factory()->post->create(
			array(
				'post_title' => 'Listed Post Title',
				'post_type' => 'page',
			)
		);

		// Unlisted Post.
		$unlisted_post = self::factory()->post->create(
			array(
				'post_title' => 'Unlisted Post Title',
				'post_type' => 'page',
			)
		);
		$_POST['unlist_posts']       = true;
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );
		Unlist_Posts_Admin::instance()->save_meta( $unlisted_post );

		$list_pages_markup = wp_list_pages(
			array(
				'echo' => false,
			)
		);

		// Assert that the Listed Post is visible in wp_list_page().
		$this->assertStringContainsString( 'Listed Post Title', $list_pages_markup );
	}
}
