<?php
/**
 * Bootstrap the plugin
 *
 * @package  Hide_Post
 * @since  1.0.0
 */

/**
 * Unlist_Posts setup
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'Unlist_Posts' ) ) {

	/**
	 * Class Unlist_Posts
	 *
	 * @since  1.0.0
	 */
	class Unlist_Posts {

		/**
		 * Instance of Unlist_Posts
		 *
		 * @since  1.0.0
		 * @var Unlist_Posts
		 */
		private static $_instance = null;

		/**
		 * Instance of Unlist_Posts
		 *
		 * @since  1.0.0
		 * @return Unlist_Posts Instance of Unlist_Posts
		 */
		public static function instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'init' ) );
			$this->includes();
		}

		/**
		 * Initialize the plugin only when the WP_Query is ready.
		 *
		 * @since  1.0.0
		 */
		public function init() {
			add_filter( 'posts_where', array( $this, 'where_clause' ), 20, 2 );
			add_filter( 'get_next_post_where', array( $this, 'post_navigation_clause' ), 20, 1 );
			add_filter( 'get_previous_post_where', array( $this, 'post_navigation_clause' ), 20, 1 );
			add_action( 'wp_head', array( $this, 'hide_post_from_searchengines' ) );
			add_filter( 'wp_robots', array( $this, 'no_robots_for_unlisted_posts' ) );
			add_filter( 'rank_math/frontend/robots', array( $this, 'change_robots_for_rankmath' ) );
			add_filter( 'wp_list_pages_excludes', array( $this, 'wp_list_pages_excludes' ) );
		}

		/**
		 * Include required files.
		 *
		 * @since 1.0.0
		 */
		public function includes() {

			if ( is_admin() ) {
				require_once UNLIST_POSTS_DIR . 'class-unlist-posts-admin.php';
			}
		}

		/**
		 * Change robots for unlisted post/pages when RankMath is activated.
		 *
		 * @param array $robots Array of robots to sanitize.
		 *
		 * @return array Modified robots.
		 */
		public function change_robots_for_rankmath( $robots ) {
			// Bail if posts unlists is disabled.
			if ( false === $this->allow_post_unlist() ) {
				return $robots;
			}

			$hidden_posts = get_option( 'unlist_posts', array() );

			if ( in_array( get_the_ID(), $hidden_posts, true ) && false !== get_the_ID() ) {
				$robots['index'] = 'noindex';
			}
			return $robots;
		}

		/**
		 * Filter where clause to hide selected posts.
		 *
		 * @since  1.0.0
		 *
		 * @param  String   $where Where clause.
		 * @param  WP_Query $query WP_Query &$this The WP_Query instance (passed by reference).
		 *
		 * @return String $where Where clause.
		 */
		public function where_clause( $where, $query ) {

			// Bail if posts unlists is disabled.
			if ( false === $this->allow_post_unlist() ) {
				return $where;
			}

			$hidden_posts = get_option( 'unlist_posts', array() );

			// bail if none of the posts are hidden or we are on admin page or singular page.
			if ( ( is_admin() && ( ! wp_doing_ajax() || $this->is_admin_referer() ) ) || $query->is_singular || empty( $hidden_posts ) ) {
				return $where;
			}

			global $wpdb;
			$where .= ' AND ' . $wpdb->prefix . 'posts.ID NOT IN ( ' . esc_sql( $this->hidden_post_string() ) . ' ) ';

			return $where;
		}

		/**
		 * Filter post navigation query to hide the selected posts.
		 *
		 * @since  1.0.0
		 *
		 * @param  String $where Where clause.
		 */
		public function post_navigation_clause( $where ) {

			// Bail if posts unlists is disabled.
			if ( false === $this->allow_post_unlist() ) {
				return $where;
			}

			$hidden_posts = get_option( 'unlist_posts', array() );

			// bail if none of the posts are hidden or we are on admin page or singular page.
			if ( ( is_admin() && ( ! wp_doing_ajax() || $this->is_admin_referer() ) ) || empty( $hidden_posts ) ) {
				return $where;
			}

			$where .= ' AND p.ID NOT IN ( ' . esc_sql( $this->hidden_post_string() ) . ' ) ';

			return $where;
		}

		/**
		 * Add meta tags to block search engines on a page if the page is unlisted.
		 *
		 * @since  1.0.1
		 */
		public function hide_post_from_searchengines() {

			// wp_no_robots is deprecated since WP 5.7.
			if ( function_exists( 'wp_robots_no_robots' ) ) {
				return;
			}

			// Bail if posts unlists is disabled.
			if ( false === $this->allow_post_unlist() ) {
				return false;
			}

			$hidden_posts = get_option( 'unlist_posts', array() );

			if ( in_array( get_the_ID(), $hidden_posts, true ) && false !== get_the_ID() ) {
				wp_no_robots();
			}
		}

		/**
		 * This directive tells web robots not to index the page content if the page is unlisted.
		 *
		 * @since  1.1.4
		 * @param  Array $robots Associative array of robots directives.
		 */
		public function no_robots_for_unlisted_posts( $robots ) {
			// Bail if posts unlists is disabled.
			if ( false === $this->allow_post_unlist() ) {
				return $robots;
			}

			$hidden_posts = get_option( 'unlist_posts', array() );

			if ( in_array( get_the_ID(), $hidden_posts, true ) && false !== get_the_ID() ) {
				// Disable robots tags from Yoast SEO.
				add_filter( 'wpseo_robots_array', '__return_empty_array' );
				return wp_robots_no_robots( $robots );
			}

			return $robots;
		}

		/**
		 * Exclude the unlisted posts from the wp_list_posts()
		 *
		 * @since  1.0.2
		 * @param  Array $exclude_array Array of posts to be excluded from post list.
		 * @return Array Array of posts to be excluded from post list.
		 */
		public function wp_list_pages_excludes( $exclude_array ) {

			// Bail if posts unlists is disabled.
			if ( false === $this->allow_post_unlist() ) {
				return $exclude_array;
			}

			$hidden_posts  = get_option( 'unlist_posts', array() );
			$exclude_array = array_merge( $exclude_array, $hidden_posts );

			return $exclude_array;
		}

		/**
		 * Convert the array of posts to comma separated string to make it compatible to wpdb query.
		 *
		 * @since  1.0.0
		 *
		 * @return String Comma separated string of post id's.
		 */
		public function hidden_post_string() {
			$hidden_posts = get_option( 'unlist_posts', array() );

			return implode( ', ', $hidden_posts );
		}

		/**
		 * Check if the current AJAX request originated from an admin page.
		 *
		 * @since  1.1.10
		 * @return boolean True if the AJAX request referer is an admin page.
		 */
		private function is_admin_referer() {
			// Require an editing capability so a spoofed admin Referer from a subscriber-level user can't unmask unlisted posts.
			if ( ! current_user_can( 'edit_posts' ) ) {
				return false;
			}

			$referer = wp_get_referer();
			if ( ! $referer ) {
				return false;
			}

			$referer_parts = wp_parse_url( $referer );
			$admin_parts   = wp_parse_url( admin_url() );
			if ( ! is_array( $referer_parts ) || ! is_array( $admin_parts ) ) {
				return false;
			}

			// Require a same-host match — scheme is deliberately ignored so HTTPS browsers behind a TLS-terminating proxy still work even when admin_url() returns HTTP.
			if ( empty( $referer_parts['host'] ) || empty( $admin_parts['host'] ) ) {
				return false;
			}
			if ( strtolower( $referer_parts['host'] ) !== strtolower( $admin_parts['host'] ) ) {
				return false;
			}

			if ( empty( $referer_parts['path'] ) || empty( $admin_parts['path'] ) ) {
				return false;
			}

			// trailingslashit on both sides so /wp-admin-foo/ can't false-match /wp-admin/.
			$admin_path   = trailingslashit( $admin_parts['path'] );
			$referer_path = trailingslashit( $referer_parts['path'] );

			return 0 === strpos( $referer_path, $admin_path );
		}

		/**
		 * Allow post unlist to be disabled using a filter.
		 *
		 * @since  1.1.0
		 * @return boolean True - This is the default value. This means that post unlist is enabled.
		 */
		private function allow_post_unlist() {
			return apply_filters( 'unlist_posts_enabled', true );
		}
	}

	Unlist_Posts::instance();
}
