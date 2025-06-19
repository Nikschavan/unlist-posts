=== Unlist Posts & Pages ===
Contributors: Nikschavan
Tags: post, unlist posts, hide posts,
Requires at least: 4.6
Tested up to: 6.5
Stable tag: 1.1.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hide posts, pages or  custom items from your site and make them accessible only with the direct link.

== Description ==

Unlisted means your post/page will not come up in search results or on anywhere else on your site. Only those who have the link of the post can view it just like any normal post on the site.

The Post could still be seen by anyone, but only if they guess the link!

The plugin updates MySQL query directly in WP_Query class of WordPress to leave out the posts which are unlisted by the user. As this modifies the core WP_Query, this also works very well with all the plugins which pull out posts/pages from database by using WP_Query, For eg. Posts slider plugins, <a href="https://www.ultimatebeaver.com/modules/advanced-posts/?utm_source=wp-repo&utm_campaign=unlist-posts&utm_medium=other-plugins">Advanced Posts</a> in <a href="https://www.ultimatebeaver.com/?utm_source=wp-repo&utm_campaign=unlist-posts&utm_medium=other-plugins">Ultimate Addon for Beaver Builder</a> etc.

[Try it out on a free dummy site](https://bsf.io/unlist-posts-demo)

= Supported & Actively Developed =
Need help with something? Have an issue to report? [Get in touch](https://github.com/Nikschavan/unlist-posts "Unlist Posts & Pages on GitHub"). with us on GitHub.

= Limitations Of the Plugin =

- WP_Query has an a flag to 'Suppress' the filters. If any plugin is using this flag when querying the posts then the unlisted post will not be hidden in that plugin's output.
- Similarly if any plugin is using a custom MySQL query, then the unlisted posts will not be hidden from it's output.

== Installation ==

1. Go to the *Plugins* menu and click *Add New*.
2. Search for *Unlist Posts*.
3. Click *Install Now* next to the *Unlist Posts* plugin.
4. Activate the plugin.

Just select option "Unlist Post" in any post of any type and that post will be hidden from the whole site, it can be only accessed if you have the direct link to the post.

== Changelog ==

= 1.1.9 =
- Improvement: Comments for unlisted posts were hidden, These should be displayed now.
- Improvement: Make sure robots tag is noindex for unlisted posts when Rank Math plugin is active.

= 1.1.8 =
- Improvement: Updated the description of the plugin (props [@NekoJonez](https://github.com/NekoJonez))

= 1.1.7 =
- Fix: Support translations on translate.wordpress.org.

= 1.1.6 =
- Fix: Unlisted post visible in preview/next post links.

= 1.1.5 =
- Fix: Compatibility with Yoast SEO's robots tags options. Robots tags from Unlist Posts will override tag changes from Yoast SEO.

= 1.1.4 =
- Fix: Due to a bug unlisted posts were visible in the search results, which we have fixed now.

= 1.1.3 =
- Deprecated: wp_no_robots() has been deprecated.
- Security: Use escaping for displaying unlist post description.

= 1.1.2 =
- Fix: Post metabox did not show the correct status if the post is unlisted or not in the classic editor.

= 1.1.1 =
- Improvement: Added the WordPress 5.6 compatibilty.

= 1.1.0 =
- New: Add post status filter to make it easier to find out the unlisted posts. (Props [@matthewmcvickar](https://github.com/matthewmcvickar) [#40](https://github.com/Nikschavan/unlist-posts/pull/40))
- Fix: Don't save post status for the revision posts.

= 1.0.4 =
- Fix: unlist posts does not work in Ajax callbacks. (props <a href="https://github.com/makovetskiy">@makovetskiy</a>)

= 1.0.3 =
- Fix: duplicate post ids being saved in the options array.

= 1.0.2 =
- New - Exclude the posts from wp_list_pages()

= 1.0.1 =
- New - Hide a page from search engines if it is unlisted.
- New - Hide the post from comments query if it is unlisted.
- Updated code architecture to be PHPCS compatible.

= 1.0.0 =
- Initial Release.
