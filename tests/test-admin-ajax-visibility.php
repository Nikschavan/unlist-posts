<?php
/**
 * Class TestAdminAjaxVisibility
 *
 * @package Unlist_Posts
 */

/**
 * Make sure unlisted posts are visible in admin AJAX requests.
 */
class TestAdminAjaxVisibility extends WP_UnitTestCase {

	/**
	 * User ID for an editor user.
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
		parent::setUp();
		$this->editor_user_id = self::factory()->user->create(
			array(
				'role' => 'editor',
			)
		);
	}

	/**
	 * Helper to unlist a post.
	 *
	 * @param int $post_id Post ID to unlist.
	 */
	private function unlist_post( $post_id ) {
		wp_set_current_user( $this->editor_user_id );
		$_POST['unlist_posts']       = true;
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );
		Unlist_Posts_Admin::instance()->save_meta( $post_id );
	}

	/**
	 * Test that unlisted posts are visible in admin AJAX requests
	 * originating from the admin panel (e.g., LearnDash group assignment).
	 */
	public function test_unlisted_post_visible_in_admin_ajax() {
		$unlisted_post = self::factory()->post->create();
		$this->unlist_post( $unlisted_post );

		// Simulate an AJAX request originating from the admin panel.
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_SERVER['HTTP_REFERER'] = admin_url( 'user-edit.php?user_id=1' );

		$query = new WP_Query(
			array(
				'post_type' => 'post',
			)
		);

		// Unlisted post should be visible in admin-originated AJAX.
		$this->assertNotEmpty( $query->posts );
		$this->assertEquals( $unlisted_post, $query->posts[0]->ID );

		remove_filter( 'wp_doing_ajax', '__return_true' );
		unset( $_SERVER['HTTP_REFERER'] );
	}

	/**
	 * Test that unlisted posts are still hidden in frontend AJAX requests.
	 */
	public function test_unlisted_post_hidden_in_frontend_ajax() {
		$unlisted_post = self::factory()->post->create();
		$this->unlist_post( $unlisted_post );

		// Simulate an AJAX request originating from the frontend.
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_SERVER['HTTP_REFERER'] = home_url( '/members-area/' );

		$query = new WP_Query(
			array(
				'post_type' => 'post',
			)
		);

		// Unlisted post should still be hidden in frontend AJAX.
		$this->assertEmpty( $query->posts );

		remove_filter( 'wp_doing_ajax', '__return_true' );
		unset( $_SERVER['HTTP_REFERER'] );
	}

	/**
	 * Test that unauthenticated users cannot bypass filtering by spoofing the admin referer.
	 */
	public function test_unlisted_post_hidden_for_unauthenticated_admin_ajax() {
		$unlisted_post = self::factory()->post->create();
		$this->unlist_post( $unlisted_post );

		// Switch to a user with no capabilities.
		wp_set_current_user( 0 );

		// Simulate an AJAX request with a spoofed admin referer.
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_SERVER['HTTP_REFERER'] = admin_url( 'user-edit.php?user_id=1' );

		$query = new WP_Query(
			array(
				'post_type' => 'post',
			)
		);

		// Unlisted post should still be hidden because user lacks edit_posts capability.
		$this->assertEmpty( $query->posts );

		remove_filter( 'wp_doing_ajax', '__return_true' );
		unset( $_SERVER['HTTP_REFERER'] );
	}
}
