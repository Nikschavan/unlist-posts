<?php
/**
 * Class AdjacentPosts
 *
 * @package Unlist_Posts
 */

/**
 * Make sure unlisted posts are hidden from get_adjacent_post().
 */
class AdjacentPosts extends WP_UnitTestCase {

	/**
	 * User ID for a editor user..
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
	 * A single example test.
	 */
	public function test_next_post() {
		_delete_all_posts();
		wp_set_current_user( $this->editor_user_id );

		// Create a post with backdated date.
		$listed_post   = self::factory()->post->create(
			array(
				'post_date' => '2021-06-09 15:05:32',
			)
		);
		$unlisted_post = self::factory()->post->create();

		// Set current post as listed post.
		$GLOBALS['post'] = get_post( $listed_post );

		// Before setting the post as unlisted, the adjacent post should be available.
		$next_post = get_adjacent_post( false, '', false );
		$this->assertInstanceOf( 'WP_Post', $next_post );
		$this->assertEquals( $unlisted_post, $next_post->ID );

		// Set the post as unlisted.
		$_POST['unlist_posts']       = true;
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );
		Unlist_Posts_Admin::instance()->save_meta( $unlisted_post );

		// After the post is set as unlisted, it should not be available as next post.
		$next_post = get_adjacent_post( false, '', false );
		$this->assertEquals( $next_post, null );
	}

	/**
	 * Test adjacent post link for previous post.
	 *
	 * @return void
	 */
	public function test_previous_post() {
		_delete_all_posts();
		wp_set_current_user( $this->editor_user_id );

		// Create a post with backdated date.
		$unlisted_post = self::factory()->post->create(
			array(
				'post_date' => '2021-06-09 15:05:32',
			)
		);
		$listed_post   = self::factory()->post->create();

		// Set current post as listed post.
		$GLOBALS['post'] = get_post( $listed_post );

		// Before setting the post as unlisted, the adjacent post should be available.
		$previous_post = get_adjacent_post();
		$this->assertInstanceOf( 'WP_Post', $previous_post );
		$this->assertEquals( $unlisted_post, $previous_post->ID );

		$_POST['unlist_posts']       = true;
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );
		Unlist_Posts_Admin::instance()->save_meta( $unlisted_post );

		// After the post is set as unlisted, it should not be available as next post.
		$previous_post = get_adjacent_post();
		$this->assertEquals( $previous_post, null );
	}

	/**
	 * Test if on a unlisted post, next post should be the next listed post.
	 *
	 * @return void
	 */
	public function test_adjacent_post_two_unlisted_posts() {
		_delete_all_posts();
		wp_set_current_user( $this->editor_user_id );

		self::factory()->post->create(
			array(
				'post_date' => '2021-06-09 15:05:32',
			)
		);
		$unlisted_post_1 = self::factory()->post->create(
			array(
				'post_date' => '2021-06-09 15:06:32',
			)
		);
		$unlisted_post_2 = self::factory()->post->create(
			array(
				'post_date' => '2021-06-09 15:07:32',
			)
		);
		$listed_post_2   = self::factory()->post->create(
			array(
				'post_date' => '2021-06-09 15:08:32',
			)
		);

		// Set current post as the first unlisted post.
		$GLOBALS['post'] = get_post( $unlisted_post_1 );

		// Before setting the post as unlisted, the adjacent post should be available.
		$next_post = get_adjacent_post( false, '', false );
		$this->assertInstanceOf( 'WP_Post', $next_post );
		$this->assertEquals( $unlisted_post_2, $next_post->ID );

		$_POST['unlist_posts']       = true;
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );
		Unlist_Posts_Admin::instance()->save_meta( $unlisted_post_1 );
		Unlist_Posts_Admin::instance()->save_meta( $unlisted_post_2 );

		// Before setting the post as unlisted, the adjacent post should be available.
		$next_post = get_adjacent_post( false, '', false );
		$this->assertEquals( $next_post->ID, $listed_post_2 );
		$this->assertNotContains( $next_post->ID, get_option( 'unlist_posts' ) );
	}
}
