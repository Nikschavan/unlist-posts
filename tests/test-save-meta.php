<?php
/**
 * Class TestSaveMeta
 *
 * @package Unlist_Posts
 */

/**
 * Cover the three save_meta() dispatch flows: metabox, Quick Edit, Bulk Edit.
 */
class TestSaveMeta extends WP_UnitTestCase {

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private $editor_user_id;

	/**
	 * Set up a privileged user and reset shared state.
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
		wp_set_current_user( $this->editor_user_id );

		delete_option( 'unlist_posts' );
		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * Tear down request globals to avoid leaking state between tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_POST    = array();
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * Helper: return the currently unlisted post IDs.
	 *
	 * @return array
	 */
	private function unlisted_ids() {
		$stored = get_option( 'unlist_posts', array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		return array_values( array_map( 'intval', $stored ) );
	}

	/* ------------------------------------------------------------------
	 * Metabox flow.
	 * ------------------------------------------------------------------ */

	/**
	 * Metabox checked: post is added to the option.
	 */
	public function test_metabox_checked_unlists_post() {
		$post_id = self::factory()->post->create();

		$_POST['unlist_posts']       = '1';
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertContains( $post_id, $this->unlisted_ids() );
	}

	/**
	 * Metabox unchecked on a previously unlisted post: post is removed.
	 */
	public function test_metabox_unchecked_lists_post() {
		$post_id = self::factory()->post->create();
		update_option( 'unlist_posts', array( $post_id ) );

		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertNotContains( $post_id, $this->unlisted_ids() );
	}

	/**
	 * Metabox flow with an invalid nonce: option is left untouched.
	 */
	public function test_metabox_bad_nonce_is_ignored() {
		$post_id = self::factory()->post->create();

		$_POST['unlist_posts']       = '1';
		$_POST['unlist_post_nounce'] = 'not-a-real-nonce';

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertNotContains( $post_id, $this->unlisted_ids() );
	}

	/* ------------------------------------------------------------------
	 * Quick Edit flow.
	 * ------------------------------------------------------------------ */

	/**
	 * Quick Edit checkbox checked: post is added.
	 */
	public function test_quick_edit_checked_unlists_post() {
		$post_id = self::factory()->post->create();

		$_POST['unlist_posts_quick_edit'] = '1';
		$_POST['unlist_posts_qe_nonce']   = wp_create_nonce( 'unlist_posts_quick_edit' );

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertContains( $post_id, $this->unlisted_ids() );
	}

	/**
	 * Quick Edit checkbox unchecked: post is removed.
	 */
	public function test_quick_edit_unchecked_lists_post() {
		$post_id = self::factory()->post->create();
		update_option( 'unlist_posts', array( $post_id ) );

		// Checkbox absent in $_POST signals "unchecked".
		$_POST['unlist_posts_qe_nonce'] = wp_create_nonce( 'unlist_posts_quick_edit' );

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertNotContains( $post_id, $this->unlisted_ids() );
	}

	/**
	 * Quick Edit with an invalid nonce: option is left untouched.
	 */
	public function test_quick_edit_bad_nonce_is_ignored() {
		$post_id = self::factory()->post->create();

		$_POST['unlist_posts_quick_edit'] = '1';
		$_POST['unlist_posts_qe_nonce']   = 'not-a-real-nonce';

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertNotContains( $post_id, $this->unlisted_ids() );
	}

	/* ------------------------------------------------------------------
	 * Bulk Edit flow.
	 * ------------------------------------------------------------------ */

	/**
	 * Bulk Edit "unlist": post is added.
	 */
	public function test_bulk_edit_unlist_adds_post() {
		$post_id = self::factory()->post->create();

		$_REQUEST['bulk_edit']              = 'Update';
		$_REQUEST['unlist_posts_bulk_edit'] = 'unlist';
		$_REQUEST['_wpnonce']               = wp_create_nonce( 'bulk-posts' );

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertContains( $post_id, $this->unlisted_ids() );
	}

	/**
	 * Bulk Edit "list": post is removed.
	 */
	public function test_bulk_edit_list_removes_post() {
		$post_id = self::factory()->post->create();
		update_option( 'unlist_posts', array( $post_id ) );

		$_REQUEST['bulk_edit']              = 'Update';
		$_REQUEST['unlist_posts_bulk_edit'] = 'list';
		$_REQUEST['_wpnonce']               = wp_create_nonce( 'bulk-posts' );

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertNotContains( $post_id, $this->unlisted_ids() );
	}

	/**
	 * Bulk Edit value "-1" ("No Change"): state is preserved.
	 */
	public function test_bulk_edit_no_change_preserves_state() {
		$unlisted = self::factory()->post->create();
		$listed   = self::factory()->post->create();
		update_option( 'unlist_posts', array( $unlisted ) );

		$_REQUEST['bulk_edit']              = 'Update';
		$_REQUEST['unlist_posts_bulk_edit'] = '-1';
		$_REQUEST['_wpnonce']               = wp_create_nonce( 'bulk-posts' );

		Unlist_Posts_Admin::instance()->save_meta( $unlisted );
		Unlist_Posts_Admin::instance()->save_meta( $listed );

		$ids = $this->unlisted_ids();
		$this->assertContains( $unlisted, $ids );
		$this->assertNotContains( $listed, $ids );
	}

	/**
	 * Bulk Edit with an invalid nonce: option is left untouched.
	 */
	public function test_bulk_edit_bad_nonce_is_ignored() {
		$post_id = self::factory()->post->create();

		$_REQUEST['bulk_edit']              = 'Update';
		$_REQUEST['unlist_posts_bulk_edit'] = 'unlist';
		$_REQUEST['_wpnonce']               = 'not-a-real-nonce';

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$this->assertNotContains( $post_id, $this->unlisted_ids() );
	}

	/* ------------------------------------------------------------------
	 * Option normalization.
	 * ------------------------------------------------------------------ */

	/**
	 * If a legacy install stored '' as the option, the save flow must not
	 * persist an empty-string entry back into the array.
	 */
	public function test_save_normalizes_legacy_empty_string_option() {
		update_option( 'unlist_posts', '' );

		$post_id = self::factory()->post->create();

		$_POST['unlist_posts']       = '1';
		$_POST['unlist_post_nounce'] = wp_create_nonce( 'unlist_post_nounce' );

		Unlist_Posts_Admin::instance()->save_meta( $post_id );

		$stored = get_option( 'unlist_posts' );
		$this->assertSame( array( $post_id ), array_values( $stored ) );
	}
}
