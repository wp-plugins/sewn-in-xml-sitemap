<?php

/**
 * @link              https://github.com/jupitercow/sewn-in-xml-sitemap
 * @since             1.0.0
 * @package           Sewn_Xml_Sitemap
 *
 * @wordpress-plugin
 * Plugin Name:       Sewn In XML Sitemap
 * Plugin URI:        https://wordpress.org/plugins/sewn-in-xml-sitemap/
 * Description:       Simple system for building XML Sitemaps out of posts when saved. Very simple and efficient.
 * Version:           2.0.0
 * Author:            Jupitercow
 * Author URI:        http://Jupitercow.com/
 * Contributor:       Jake Snyder
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sewn-xml-sitemap
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$class_name = 'Sewn_Xml_Sitemap';
if (! class_exists($class_name) ) :

class Sewn_Xml_Sitemap
{
	/**
	 * The unique prefix for Sewn In.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $prefix         The string used to uniquely prefix for Sewn In.
	 */
	protected $prefix;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $settings       The array used for settings.
	 */
	protected $settings;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    2.0.0
	 */
	public function __construct()
	{
		$this->prefix      = 'sewn';
		$this->plugin_name = strtolower(__CLASS__);
		$this->version     = '2.0.0';
		$this->settings    = array(
			'add_checkbox' => true,
			'post_types'   => array('post','page'),
			'field_groups'      => array (
				array(
					'id'              => $this->plugin_name,
					'title'           => __( 'XML Sitemap', $this->plugin_name ),
					'fields'          => array (
						array(
							'label'        => __( 'Exclude from XML Sitemap', $this->plugin_name ),
							'name'         => 'xml_sitemap_exclude',
							'type'         => 'true_false',
							'instructions' => __( 'This will keep the page from showing in the XML sitemap.', $this->plugin_name ),
						),
					),
					'location'        => array (),
					'menu_order'      => 0,
					'context'         => 'normal',
					'priority'        => 'low',
					'label_placement' => 'top',
				),
			),
		);
		$this->settings = apply_filters( "{$this->prefix}/sitemap/settings", $this->settings );
	}

	/**
	 * Load the plugin.
	 *
	 * @since	2.0.0
	 * @return	void
	 */
	public function run()
	{
		add_action( 'plugins_loaded', array($this, 'plugins_loaded') );
		add_action( 'init',           array($this, 'init') );
	}

	/**
	 * On plugins_loaded test if this can be combined with Sewn In Simple SEO.
	 *
	 * @since	1.0.0
	 * @return	void
	 */
	public function plugins_loaded()
	{
		if ( class_exists('Sewn_Seo') ) {
			$this->settings['add_checkbox'] = false;
		} elseif ( ! class_exists('Sewn_Meta') ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/sewn-meta/sewn-meta.php';
		}
	}

	/**
	 * Initialize the plugin once during run.
	 *
	 * @since	1.0.0
	 * @return	void
	 */
	public function init()
	{
		add_action( 'save_post',                              array($this, 'create_sitemap') );
		add_filter( 'wp_list_pages_excludes',                 array($this, 'wp_list_pages_excludes') );

		add_filter( "{$this->prefix}/sitemap/exclude_field",  array($this, 'get_field') );

		if ( $this->settings['add_checkbox'] ) {
			$this->register_field_groups();
		}
	}

	/**
	 * Get post types.
	 *
	 * @since	2.0.0
	 * @return	void
	 */
	public function post_types()
	{
		return apply_filters( "{$this->prefix}/sitemap/post_types", apply_filters( "{$this->prefix}/seo/post_types", apply_filters( 'sewn_seo/post_types', $this->settings['post_types'] ) ) );
	}

	/**
	 * Create sitemap.xml file in the root directory.
	 *
	 * @since	1.0.0
	 * @return	void
	 */
	public function create_sitemap()
	{
		$postsForSitemap = get_posts( array(
			'numberposts' => -1,
			'orderby'     => 'modified',
			'post_type'   => $this->post_types(),
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

		$sitemap  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" count="' . count($postsForSitemap) . '">' . "\n";

		foreach ( $postsForSitemap as $post )
		{
			setup_postdata($post);
			$postdate  = explode( ' ', $post->post_modified );
			$permalink = get_permalink( $post->ID );
			$frequency = 'monthly';
			$sitemap  .= "\t<url>\n" .
				"\t\t<loc>{$permalink}</loc>\n" .
				"\t\t<lastmod>{$postdate[0]}</lastmod>\n" .
				"\t\t<changefreq>{$frequency}</changefreq>\n" .
				"\t</url>\n";
		}

		$sitemap .= '</urlset>';

		$filename = 'sitemap.xml';
		if ( is_multisite() ) {
			list($name,$ext) = explode( '.', $filename );
			$filename = "{$name}_" . sanitize_title(get_option('blogname')) . ".{$ext}";
		}

		$root = apply_filters( "{$this->prefix}/sitemap/root", $_SERVER["DOCUMENT_ROOT"] );

		$fp = fopen( "$root/$filename", 'w' );
		if ( $fp )
		{
			fwrite($fp, $sitemap);
			fclose($fp);
		}
	}

	/**
	 * Remove items from wp_list_pages() by default.
	 *
	 * @since	1.0.2
	 * @return	void
	 */
	public function wp_list_pages_excludes( $exclude_array )
	{
		if ( apply_filters( "{$this->prefix}/sitemap/wp_list_pages", true ) )
		{
			global $wpdb;
			$sitemap_excludes = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} LEFT JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) WHERE {$wpdb->posts}.post_type = 'page' AND ({$wpdb->postmeta}.meta_key='xml_sitemap_exclude' AND {$wpdb->postmeta}.meta_value!=0)" );

			if ( $sitemap_excludes ) {
				$exclude_array = array_merge( $exclude_array, $sitemap_excludes );
			}
		}

		return $exclude_array;
	}

	/**
	 * Add the meta box.
	 *
	 * @since	2.0.0
	 * @return	void
	 */
	public function register_field_groups()
	{
		foreach ( $this->settings['field_groups'] as $field_group ) {
			do_action( "{$this->prefix}/meta/register_field_group", $field_group );
		}
	}

	/**
	 * Get the checkbox settings.
	 *
	 * @since	2.0.0
	 * @return	void
	 */
	public function get_field()
	{
		return $this->settings['field_groups'][0]['fields'][0];
	}
}

$$class_name = new $class_name;
$$class_name->run();
unset($class_name);

endif;