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
		add_filter( 'display_post_states', array( $this, 'add_unlisted_post_status' ), 10, 2 );
		add_filter( 'parse_query', array( $this, 'filter_unlisted_posts' ) );
		add_action( 'init', array( $this, 'add_post_filter' ) );
		add_action( 'admin_init', array( $this, 'register_list_table_hooks' ) );
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_field' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit_field' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_inline_edit_script' ) );
	}

	/**
	 * Return the list of public post types the plugin operates on.
	 *
	 * @return array
	 */
	private function get_supported_post_types() {
		return get_post_types( array( 'public' => true ), 'names', 'and' );
	}

	/**
	 * Register the Unlisted column on every supported post-type list table.
	 *
	 * The custom column is required for `quick_edit_custom_box` and
	 * `bulk_edit_custom_box` to fire for our field.
	 */
	public function register_list_table_hooks() {
		foreach ( $this->get_supported_post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_status_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_status_column' ), 10, 2 );
		}
	}

	/**
	 * Add the Unlisted column header.
	 *
	 * @param array $columns Existing list table columns.
	 * @return array
	 */
	public function add_status_column( $columns ) {
		$columns['unlist_posts_status'] = __( 'Unlisted', 'unlist-posts' );
		return $columns;
	}

	/**
	 * Render the Unlisted column cell. The data-unlisted attribute is read by JS
	 * to pre-check the Quick Edit checkbox.
	 *
	 * @param string $column  Column key being rendered.
	 * @param int    $post_id Post being rendered.
	 */
	public function render_status_column( $column, $post_id ) {
		if ( 'unlist_posts_status' !== $column ) {
			return;
		}

		$unlisted = $this->is_unlisted( $post_id );
		printf(
			'<span class="unlist-posts-status" data-unlisted="%s">%s</span>',
			esc_attr( $unlisted ? '1' : '0' ),
			$unlisted ? esc_html__( 'Yes', 'unlist-posts' ) : '&mdash;'
		);
	}

	/**
	 * Output the Quick Edit checkbox.
	 *
	 * @param string $column    Column key.
	 * @param string $post_type Post type being edited.
	 */
	public function quick_edit_field( $column, $post_type ) {
		if ( 'unlist_posts_status' !== $column ) {
			return;
		}
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}
		wp_nonce_field( 'unlist_posts_quick_edit', 'unlist_posts_qe_nonce' );
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft">
					<input type="checkbox" name="unlist_posts_quick_edit" value="1" />
					<span class="checkbox-title"><?php esc_html_e( 'Unlist this post', 'unlist-posts' ); ?></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Output the Bulk Edit tri-state select.
	 *
	 * @param string $column    Column key.
	 * @param string $post_type Post type being edited.
	 */
	public function bulk_edit_field( $column, $post_type ) {
		if ( 'unlist_posts_status' !== $column ) {
			return;
		}
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Unlisted', 'unlist-posts' ); ?></span>
					<select name="unlist_posts_bulk_edit">
						<option value="-1"><?php esc_html_e( '&mdash; No Change &mdash;', 'unlist-posts' ); ?></option>
						<option value="unlist"><?php esc_html_e( 'Unlist', 'unlist-posts' ); ?></option>
						<option value="list"><?php esc_html_e( 'List', 'unlist-posts' ); ?></option>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Enqueue inline JS that pre-checks the Quick Edit checkbox based on the
	 * row's current state.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_inline_edit_script( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}

		$script = <<<'JS'
(function($) {
	if ( typeof inlineEditPost === 'undefined' ) {
		return;
	}
	var origEdit = inlineEditPost.edit;
	inlineEditPost.edit = function( id ) {
		origEdit.apply( this, arguments );
		var postId = 0;
		if ( typeof id === 'object' ) {
			postId = parseInt( this.getId( id ), 10 );
		} else {
			postId = parseInt( id, 10 );
		}
		if ( ! postId ) {
			return;
		}
		var unlisted = $( '#post-' + postId ).find( '.unlist-posts-status' ).data( 'unlisted' );
		$( '.inline-edit-row' )
			.find( 'input[name="unlist_posts_quick_edit"]' )
			.prop( 'checked', String( unlisted ) === '1' );
	};
})(jQuery);
JS;
		wp_add_inline_script( 'inline-edit-post', $script );
	}

	/**
	 * Whether a post is currently in the unlisted option list.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_unlisted( $post_id ) {
		$hidden_posts = (array) get_option( 'unlist_posts', array() );
		return in_array( (int) $post_id, array_map( 'intval', $hidden_posts ), true );
	}

	/**
	 * Add or remove a post ID in the unlisted option.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $unlist  True to unlist, false to list.
	 */
	private function set_unlisted( $post_id, $unlist ) {
		$post_id      = (int) $post_id;
		$hidden_posts = (array) get_option( 'unlist_posts', array() );

		if ( $unlist ) {
			if ( ! in_array( $post_id, $hidden_posts, true ) ) {
				$hidden_posts[] = $post_id;
			}
			$hidden_posts = array_unique( $hidden_posts );
		} else {
			$hidden_posts = array_values( array_diff( $hidden_posts, array( $post_id ) ) );
		}

		update_option( 'unlist_posts', $hidden_posts );
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
	 * Save meta field. Dispatches to the appropriate flow:
	 * Bulk Edit, Quick Edit, or the post-edit metabox.
	 *
	 * @param  int $post_id Current post being saved.
	 *
	 * @return void
	 */
	public function save_meta( $post_id ) {
		// Bail if we're doing an auto save or on a revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( false !== wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Bulk Edit: nonce is verified by core in edit.php (bulk-posts) before
		// bulk_edit_posts() loops and triggers save_post per ID.
		if ( isset( $_REQUEST['bulk_edit'] ) && isset( $_REQUEST['unlist_posts_bulk_edit'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_REQUEST['unlist_posts_bulk_edit'] ) );
			if ( 'unlist' === $value ) {
				$this->set_unlisted( $post_id, true );
			} elseif ( 'list' === $value ) {
				$this->set_unlisted( $post_id, false );
			}
			return;
		}

		// Quick Edit.
		if ( isset( $_POST['unlist_posts_qe_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['unlist_posts_qe_nonce'] ) ), 'unlist_posts_quick_edit' ) ) {
				return;
			}
			$this->set_unlisted( $post_id, isset( $_POST['unlist_posts_quick_edit'] ) );
			return;
		}

		// Post-edit metabox.
		if ( ! isset( $_POST['unlist_post_nounce'] ) || ! wp_verify_nonce( $_POST['unlist_post_nounce'], 'unlist_post_nounce' ) ) {
			return;
		}
		$this->set_unlisted( $post_id, isset( $_POST['unlist_posts'] ) );
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

}

Unlist_Posts_Admin::instance();
