<?php
/**
 * Plugin Name:     Unlist Posts & Pages
 * Plugin URI:      https://github.com/Nikschavan/hide-post
 * Description:     Hide posts, pages or  custom items from your site and make them accessible only with the direct link.
 * Author:          Nikhil Chavan
 * Author URI:      https://www.nikhilchavan.com/
 * Text Domain:     unlist-posts
 * Version:         1.2.0
 * Requires at least: 5.7
 * Requires PHP:      7.4
 *
 * @package         Hide_Post
 */

defined( 'ABSPATH' ) or exit;

define( 'UNLIST_POSTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'UNLIST_POSTS_URI', plugins_url( '/', __FILE__ ) );
define( 'UNLIST_POSTS_VER', '1.2.0' );

require_once UNLIST_POSTS_DIR . 'class-unlist-posts.php';
