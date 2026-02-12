<?php
/**
 * Admin functions for the plugin.
 *
 * @package  hide-post
 */

defined( 'ABSPATH' ) or exit;

/**
 * Unlist_Posts_Admin setup
 *
 * @since 1.0
 */
class Unlist_Posts_Admin {

	/**
	 * Instance of Unlist_Posts_Admin
	 *
	 * @var Unlist_Posts_Admin
	 */
	private static $_instance = null;

	/**
	 * Instance of Unlist_Posts_Admin
	 *
	 * @return Unlist_Posts_Admin Instance of Unlist_Posts_Admin
	 */
	public static function instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_action( 'save_post', array( $this, 'save_meta_enable_robots' ) );
		add_filter( 'display_post_states', array( $this, 'add_unlisted_post_status' ), 10, 2 );
		add_filter( 'parse_query', array( $this, 'filter_unlisted_posts' ) );
		add_action( 'init', array( $this, 'add_post_filter' ) );
	}

	/**
	 * Register meta box(es).
	 */
	function register_metabox() {
		$args = array(
			'public' => true,
		);

		$post_types = get_post_types( $args, 'names', 'and' );

		add_meta_box(
			'ehf-meta-box',
			__( 'Unlist Post', 'unlist-posts' ),
			array(
				$this,
				'metabox_render',
			),
			$post_types,
			'side',
			'high'
		);
	}

	/**
	 * Render Meta field.
	 *
	 * @param  POST $post Currennt post object which is being displayed.
	 */
	function metabox_render( $post ) {
		$this->render_unlist_posts( $post );
		$this->render_enable_robots( $post );
	}

	/**
	 * Save meta field for unlist posts.
	 *
	 * @param  POST $post_id Currennt post object which is being displayed.
	 *
	 * @return Void
	 */
	public function save_meta( $post_id ) {
		// Bail if we're doing an auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if our nonce isn't there, or we can't verify it, bail.
		if ( ! isset( $_POST['unlist_post_nounce'] ) || ! wp_verify_nonce( $_POST['unlist_post_nounce'], 'unlist_post_nounce' ) ) {
			return;
		}

		// if our current user can't edit this post, bail.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Don't record unlist option for revisions.
		if ( false !== wp_is_post_revision( $post_id ) ) {
			return;
		}

		$hidden_posts = get_option( 'unlist_posts', array() );

		if ( '' === $hidden_posts ) {
			$hidden_posts = array();
		}

		if ( isset( $_POST['unlist_posts'] ) ) {
			$hidden_posts[] = $post_id;

			// Get only the unique post id's in the option array.
			$hidden_posts = array_unique( $hidden_posts );
		} elseif ( in_array( $post_id, $hidden_posts, true ) ) {

			// Get only the unique post id's in the option array.
			$hidden_posts = array_unique( $hidden_posts );

			$key = array_search( $post_id, $hidden_posts, true );
			unset( $hidden_posts[ $key ] );
		}

		update_option( 'unlist_posts', $hidden_posts );
	}

	/**
	 * Save meta field for enable robots.
	 *
	 * @param  POST $post_id Currennt post object which is being displayed.
	 *
	 * @return Void
	 */
	public function save_meta_enable_robots( $post_id ) {
		// Bail if we're doing an auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if our nonce isn't there, or we can't verify it, bail.
		if ( ! isset( $_POST['unlist_post_enable_robots_nounce'] ) || ! wp_verify_nonce( $_POST['unlist_post_enable_robots_nounce'], 'unlist_post_enable_robots_nounce' ) ) {
			return;
		}

		// if our current user can't edit this post, bail.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Don't record unlist option for revisions.
		if ( false !== wp_is_post_revision( $post_id ) ) {
			return;
		}

		$enable_robots = get_option( 'unlist_posts_enable_robots', array() );

		if ( '' === $enable_robots ) {
			$enable_robots = array();
		}

		if ( isset( $_POST['unlist_posts_enable_robots'] ) ) {
			$enable_robots[] = $post_id;

			// Get only the unique post id's in the option array.
			$enable_robots = array_unique( $enable_robots );
		} elseif ( in_array( $post_id, $enable_robots, true ) ) {

			// Get only the unique post id's in the option array.
			$enable_robots = array_unique( $enable_robots );

			$key = array_search( $post_id, $enable_robots, true );
			unset( $enable_robots[ $key ] );
		}

		update_option( 'unlist_posts_enable_robots', $enable_robots );
	}

	/**
	 * Add 'Unlisted' post status to post list items.
	 *
	 * @param Array $states   An array of post display states.
	 * @param Post  $post     The current post object.
	 *
	 * @return Array  $states An updated array of post display states.
	 */
	function add_unlisted_post_status( $states, $post ) {
		// Bail if the unlisted post filter is active, to avoid redundancy.
		if ( is_admin() && isset( $_GET['post_status'] ) && 'unlisted' === $_GET['post_status'] ) {
			return;
		}

		// Get the list of unlisted post IDs from the options table.
		$unlisted_posts = maybe_unserialize( get_option( 'unlist_posts', array() ) );

		// Check if this post is unlisted and mark it as so if appropriate.
		if ( in_array( $post->ID, $unlisted_posts, true ) ) {
			$states[] = __( 'Unlisted', 'unlist-posts' );
		}

		return $states;
	}

	/**
	 * Add 'Unlisted' filter to the post list.
	 *
	 * @param Array $views   An array of post list filters.
	 *
	 * @return Array $views  An updated array of post list filters.
	 */
	function add_unlisted_post_filter( $views ) {
		// Get the list of unlisted post IDs from the options table.
		$unlisted_posts = maybe_unserialize( get_option( 'unlist_posts', array() ) );
		$count          = false;

		// Mark 'Unlisted' filter as the current filter if it is.
		$link_attributes = '';
		if ( is_admin() && isset( $_GET['post_status'] ) && 'unlisted' === $_GET['post_status'] ) {
			$link_attributes = 'class="current" aria-current="page"';
		}

		if ( ! empty( $unlisted_posts ) ) {
			$post_type = get_current_screen()->post_type ? get_current_screen()->post_type : get_post_types();
			$query     = new WP_Query(
				array(
					'post_type' => $post_type,
					'post__in'  => $unlisted_posts,
				)
			);

			$count = isset( $query->found_posts ) ? $query->found_posts : false;
		}

		$link = add_query_arg(
			array(
				'post_status' => 'unlisted',
			)
		);

		if ( false !== $count && 0 !== $count ) {
			$views['unlisted'] = '<a href=" ' . esc_url( $link ) . ' " ' . $link_attributes . '>' . __( 'Unlisted', 'unlist-posts' ) . ' <span class="count">(' . esc_html( $count ) . ')</span></a>';
		}

		return $views;
	}

	/**
	 * Add posts filter for all the public posts.
	 *
	 * @return void
	 */
	function add_post_filter() {
		$args = array(
			'public' => true,
		);

		$post_types = get_post_types( $args, 'names', 'and' );

		foreach ( $post_types as $post_type ) {
			add_filter( 'views_edit-' . $post_type, array( $this, 'add_unlisted_post_filter' ) );
		}
	}

	/**
	 * Parse the post list query for unlisted posts.
	 *
	 * @param Object $query  The instance of WP_Query.
	 *
	 * @return Object $query  The updated instance of  WP_Query.
	 */
	function filter_unlisted_posts( $query ) {
		global $pagenow;

		if ( is_admin() && 'edit.php' === $pagenow && isset( $_GET['post_status'] ) && 'unlisted' === $_GET['post_status'] ) {
			// Get the list of unlisted post IDs from the options table.
			$unlisted_posts = maybe_unserialize( get_option( 'unlist_posts', array() ) );

			// Only show posts that are in the list of unlisted post IDs.
			$query->query_vars['post__in'] = $unlisted_posts;
		}

		return $query;
	}
	
	/**
	 * Render Unlist Posts meta field
	 *
	 * @param Post  $post     The current post object.
	 * @return void
	 */
	function render_unlist_posts( $post ) {
		$hidden_posts = get_option( 'unlist_posts', array() );

		if ( '' === $hidden_posts ) {
			$hidden_posts = array();
		}

		$checked = '';

		if ( in_array( (int) $post->ID, $hidden_posts, true ) ) {
			$checked = 'checked';
		}

		// We'll use this nonce field later on when saving.
		wp_nonce_field( 'unlist_post_nounce', 'unlist_post_nounce' );
		?>
		<p>
			<label class="checkbox-inline">
				<input name="unlist_posts" type="checkbox" <?php echo esc_attr( $checked ); ?> value=""><?php esc_html_e( 'Unlist this post?', 'unlist-posts' ); ?>
			</label>
		</p>
		<p class="description"><?php esc_html_e( 'This will hide the post from your site, The post can only be accessed from direct URL.', 'unlist-posts' ); ?> </p>
		<?php
	}
	
	/**
	 * Render enable robots meta field
	 *
	 * @param Post  $post     The current post object.
	 * @return void
	 */
	function render_enable_robots( $post ) {
		$enable_robots = get_option( 'unlist_posts_enable_robots', array() );

		if ( '' === $enable_robots ) {
			$enable_robots = array();
		}

		$checked = '';

		if ( in_array( (int) $post->ID, $enable_robots, true ) ) {
			$checked = 'checked';
		}

		// We'll use this nonce field later on when saving.
		wp_nonce_field( 'unlist_post_enable_robots_nounce', 'unlist_post_enable_robots_nounce' );
		?>
		<p>
			<label class="checkbox-inline">
				<input name="unlist_posts_enable_robots" type="checkbox" <?php echo esc_attr( $checked ); ?> value=""><?php esc_html_e( 'Allow Robots to Crawl?', 'unlist-posts' ); ?>
			</label>
		</p>
		<p class="description"><?php esc_html_e( 'By default, Unlist Posts does not allow indexing of unlisted posts, check this box to enable indexing.', 'unlist-posts' ); ?> </p>
		<?php
	}

}

Unlist_Posts_Admin::instance();
