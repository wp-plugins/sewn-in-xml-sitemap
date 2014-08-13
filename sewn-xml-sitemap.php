<?php
/*
Plugin Name: Sewn In XML Sitemap
Plugin URI: http://bitbucket.org/jupitercow/sewn-in-xml-sitemap
Description: Simple system for building XML Sitemaps out of posts when saved.
Version: 1.0.4
Author: Jake Snyder
Author URI: http://Jupitercow.com/
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

------------------------------------------------------------------------
Copyright 2014 Jupitercow, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

if (! class_exists('sewn_xml_sitemap') ) :

add_action( 'plugins_loaded', array('sewn_xml_sitemap', 'plugins_loaded') );

add_action( 'init', array('sewn_xml_sitemap', 'init') );

class sewn_xml_sitemap
{
	/**
	 * Class prefix
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const PREFIX = __CLASS__;

	/**
	 * Settings
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	public static $settings = array(
		'add_checkbox' => true,
		'post_types'   => array('post','page'),
	);

	/**
	 * Initialize the Class
	 *
	 * @author  Jake Snyder
	 * @since	1.0.0
	 * @return	void
	 */
	public static function init()
	{
		// actions
		add_action( 'save_post',                  array(__CLASS__, 'create_sitemap') );
		add_action( 'admin_init',                 array(__CLASS__, 'dependencies') );

		// filters
		add_filter( 'wp_list_pages_excludes',     array(__CLASS__, 'wp_list_pages_excludes') );

		// Load ACF Fields
		if ( self::$settings['add_checkbox'] ) self::register_field_groups();
	}

	/**
	 * Check for dependencies
	 *
	 * @author  Jake Snyder
	 * @since	1.0.2
	 * @return	void
	 */
	public static function dependencies()
	{
		if ( ! class_exists( 'acf' ) ) {
			add_action( 'admin_notices', array(__CLASS__, 'acf_dependency_message') );
		}
	}

	/**
	 * Add a nag for ACF
	 *
	 * @author  Jake Snyder
	 * @since	1.0.2
	 * @return	void
	 */
	public static function acf_dependency_message()
	{
		?>
		<div class="update-nag">
			Sewn In XML Sitemap requires the <a href="http://wordpress.org/plugins/advanced-custom-fields/">Advanced Custom Fields</a> plugin to be installed and activated.
		</div>
		<?php
	}

	/**
	 * Remove items from wp_list_pages() by default
	 *
	 * @author  Jake Snyder
	 * @since	1.0.2
	 * @return	void
	 */
	public static function wp_list_pages_excludes( $exclude_array )
	{
		if ( apply_filters( self::PREFIX . '/wp_list_pages', true ) ) :
			global $wpdb;
			$sitemap_excludes = $wpdb->get_col( "SELECT ID FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_type = 'page' AND ($wpdb->postmeta.meta_key='xml_sitemap_exclude' AND $wpdb->postmeta.meta_value!=0)" );

			if ( $sitemap_excludes ) {
				$exclude_array = array_merge( $exclude_array, $sitemap_excludes );
			}
		endif;

		return $exclude_array;
	}

	/**
	 * Create sitemap.xml file in the root directory
	 *
	 * @author  Tim Bowen, Jake Snyder
	 * @since	1.0.0
	 * @return	void
	 */
	public static function create_sitemap()
	{
		$postsForSitemap = get_posts( array(
			'numberposts' => -1,
			'orderby'     => 'modified',
			'post_type'   => apply_filters( self::PREFIX . '/post_types', apply_filters( 'sewn_seo/post_types', self::$settings['post_types'] ) ),
			'order'       => 'DESC',
			'meta_query'  => array(
				'relation'    => 'OR',
				array(
					'key'     => 'xml_sitemap_exclude',
					'value'   => '',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'xml_sitemap_exclude',
					'value'   => 1,
					'compare' => '!=',
				),
			)
		) );

		$sitemap  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" count="'. count($postsForSitemap) .'">'."\n";

		foreach ( $postsForSitemap as $post )
		{
			#$exclude = get_post_meta($post->ID, 'xml_sitemap_exclude', true);
			#if ( $exclude ) continue;
			setup_postdata($post);
			$postdate = explode(" ", $post->post_modified);
			$sitemap .= "\t".'<url>'."\n".
				"\t\t".'<loc>' . get_permalink($post->ID) . '</loc>'."\n" .
				"\t\t".'<lastmod>' . $postdate[0] . '</lastmod>'."\n" .
				"\t\t".'<changefreq>monthly</changefreq>'."\n" .
				"\t".'</url>'."\n";
		}

		$sitemap .= '</urlset>';

		$filename = 'sitemap.xml';
		if ( is_multisite() ) $filename = 'sitemap_' . sanitize_title(get_option('blogname')) . '.xml';

		$root = apply_filters( self::PREFIX . '/root', $_SERVER["DOCUMENT_ROOT"] );

		$fp = fopen( $root .'/'. $filename, 'w' );
		if ( $fp )
		{
			fwrite($fp, $sitemap);
			fclose($fp);
		}
	}

	/**
	 * On plugins_loaded test if we can use frontend_notifications
	 *
	 * @author  Jake Snyder
	 * @since	1.0.0
	 * @return	void
	 */
	public static function plugins_loaded()
	{
		if ( class_exists('sewn_seo') ) {
			self::$settings['add_checkbox'] = false;
		}
	}

	/**
	 * Better SEO: ACF SEO Fields
	 *
	 * @author  Jake Snyder
	 * @since	1.0.0
	 * @return	void
	 */
	public static function register_field_groups()
	{
		if (! function_exists("register_field_group") ) return;

		$args = array (
			'id' => 'acf_' . self::PREFIX,
			'title' => 'XML Sitemap',
			'fields' => array (
				array (
					'key' => 'field_52f9a1023c772',
					'label' => 'Exclude from XML Sitemap',
					'name' => 'xml_sitemap_exclude',
					'type' => 'true_false',
					'instructions' => 'This will keep the page from showing in the XML sitemap',
					'message' => '',
					'default_value' => 0,
				),
			),
			'location' => array (),
			'options' => array (
				'position' => 'normal',
				'layout' => 'default',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		);

		$default_location = array (
			'param' => 'post_type',
			'operator' => '==',
			'value' => '',
			'order_no' => 0,
			'group_no' => 0,
		);

		$post_types = apply_filters( self::PREFIX . '/post_types', apply_filters( 'sewn_seo/post_types', self::$settings['post_types'] ) );

		$i=0;
		foreach ( $post_types as $post_type )
		{
			$new_location = $default_location;
			$new_location['value'] = $post_type;
			$new_location['group_no'] = $i;
			$args['location'][] = array( $new_location );
			$i++;
		}

		register_field_group( $args );
	}
}

endif;