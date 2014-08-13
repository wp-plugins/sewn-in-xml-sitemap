=== Plugin Name ===
Contributors: jcow
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=jacobsnyder%40gmail%2ecom&lc=US&item_name=Jacob%20Snyder&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: gravity forms, update posts, frontend, front end
Requires at least: 3.6.1
Tested up to: 3.9.1
Stable tag: 1.2.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple way to automatically generate XML Sitemaps when a page or post is saved. Very simple, no cruft or extra features you won't use.


== Description ==

There are two main customizations available.

*	Choose which post types are added (posts and pages by default)
*	When ACF is installed, adds a metabox to all included post types to remove single posts

# Sewn In XML Sitemap

A nice and simple way to create XML Sitemaps when a page or post is saved. Very simple, no cruft or extra features you won't use.

## Control what post types are added

By default only pages and posts are added, but you can remove either of those and/or add more using this filter:

`
/**
 * Add a post type to the XML sitemap
 *
 * Takes the default array('post','page') and adds 'news' and 'event' post types to it. Returns: array('post','page','news','event')
 *
 * @param	array	$post_types	List of post types to be added to the XML Sitemap
 * @return	array	$post_types	Modified list of post types
 */
add_filter( 'sewn_seo/post_types', 'custom_sitemap_post_types' );
function custom_sitemap_post_types( $post_types )
{
	$post_types[] = 'news';
	$post_types[] = 'event';
	return $post_types;
}
`

`
/**
 * Completely replace the post types in the XML sitemap
 *
 * This will replace the default completely. Returns: array('news','event')
 *
 * The result is to remove 'post' and 'page' post types and to add 'news' and 'event' post types
 *
 * @param	array	$post_types	List of post types to be added to the XML Sitemap
 * @return	array	$post_types	Modified list of post types
 */
add_filter( 'sewn_seo/post_types', 'custom_sitemap_post_types' );
function custom_sitemap_post_types( $post_types )
{
	$post_types = array('news','event');
	return $post_types;
}
`

## Remove a specific post from the sitemap

A checkbox is added to each post type that is included in the sitemap. Checking it will remove that specific item from the sitemap.

This checkbox also removes posts from wp_list_pages, you can turn that off using this filter:

`
add_filter( 'sewn_xml_sitemap/wp_list_pages', '__return_false' );
`

## Customize WordPress SEO plugin

This works with the our simple SEO plugin. When installed, the XML sitemap checkbox integrates with the SEO fields and this plugin will use the SEO post types. The goal is to keep things very simple and integrated.

## Separate Post Types from SEO plugin

This plugin will also use the SEO post_types filter to keep them the same, you can use that filter (from the examples above) as a base to maintain flexibility and to set post types for both plugins at once. If you decide you would like them to be different, you can change the XML Sitemap post types using this filter:

`
add_filter( 'sewn_xml_sitemap/post_types', 'custom_sitemap_post_types' );
`

Just keep in mind that you are now filtering the `sewn_seo/post_types` if it is set, not the default. So this can be used to add or remove from the sewn_seo filter to customize as needed. If you are not using the seo filter, then this will be filter the default: `array('post','page')`. We generally recommend just using the `sewn_seo/post_types` filter.


= Compatibility =

This works with the Sewn In Simple SEO plugin. When installed, the XML sitemap checkbox integrates with the SEO fields and this plugin will use the SEO post types. The goal is to keep things very simple and integrated.


== Installation ==

*   Copy the folder into your plugins folder
*   Activate the plugin via the Plugins admin page


== Frequently Asked Questions ==

= No questions yet. =


== Screenshots ==

1. The checkbox to remove posts in the backend.


== Changelog ==

## 1.0.3 - 2014-08-03

- Added to the repo


== Upgrade Notice ==

= 1.0.3 =
This is the first version in the Wordpress repository.
