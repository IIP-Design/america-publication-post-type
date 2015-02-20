<?php
/**********************************************************************************************************************************

 Creates Publication Custom Post Type and related features
  
 Plugin Name: 	  America Publication Post Type
 Description:     This plugin creates the publication custom post type and associated taxonmies and shortcodes
 Version:         0.0.1
 Author:          Office of Design, Bureau of International Information Programs
 License:         GPL-2.0+
 Text Domain:     america
 Domain Path:     /languages
 
 ********************************************************************************************************************************** */

 
//* Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

// wrap in if statement
class America_Publication_Post_Type {

	private $appt_publication_settings;
	
	const VERSION = '0.0.1';

	function bootstrap() {
		$plugin = plugin_basename( __FILE__ );

  		register_activation_hook( __FILE__, 		 	array( $this, 'appt_activate' ) );

		add_action( 'init', 						 	array( $this, 'appt_load_plugin_textdomain' ) );
		add_action( 'init',							 	array( $this, 'appt_init' ) );
		add_action( 'admin_init',					 	array( $this, 'appt_admin_init' ) );
		add_action( 'pre_get_posts', 				 	array( $this, 'appt_publication_items' ) );
				
		add_filter( 'genesis_post_info', 			 	array( $this, 'appt_publication_post_info_meta' ) );
		add_filter( 'genesis_post_meta', 			 	array( $this, 'appt_publication_post_info_meta' ) );
		add_filter( 'rwmb_meta_boxes', 				 	array( $this, 'appt_register_meta_boxes' ) );
		add_filter( 'pre_get_posts', 				 	array( $this, 'appt_add_publication_to_archive' ) );
		add_filter( 'plugin_action_links_' . $plugin, 	array( $this, 'appt_action_links' ) );

		// add_action( 'widgets_init', 					array( $this, 'appt_register_widget_featured_category' ) );
		// add_action( 'widgets_init', 					array( $this, 'appt_register_widget_featured_custom_post' ) );

		//* Initialize Settings
		require_once( dirname( __FILE__ ) . '/inc/class-america-publication-settings.php' );
		$this->appt_publication_settings = new America_Publication_Settings();
    }


	/**
	 * Check for plugin dependenciise
	 * @return void
	 */
	function appt_check_dependencies() {
		$latest = '2.0.2';

		if ( 'genesis' != get_option( 'template' ) ) {

			//* Deactivate ourself
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( sprintf( __( 'Sorry, you can\'t activate unless you have installed <a href="%s">Genesis</a>', 'america' ), 'http://my.studiopress.com/themes/genesis/' ) );

		}

		if ( version_compare( wp_get_theme( 'genesis' )->get( 'Version' ), $latest, '<' ) ) {

			//* Deactivate ourself
			deactivate_plugins( plugin_basename( __FILE__ ) ); 
			wp_die( sprintf( __( 'Sorry, you cannot activate without <a href="%s">Genesis %s</a> or greater', 'america' ), 'http://www.studiopress.com/support/showthread.php?t=19576', $latest ) );

		}

        /* 
        // do nothing if class meta-box exists - TODO: remove fatal error msg (turn off debugging) - tgm library
        if ( ! class_exists( 'RW_Meta_Box' ) ) {
            trigger_error( 'This plugin requires meta-box. Please install and activate meta-box before activating this plugin.', E_USER_ERROR );
        }
        */
	}

	/* Deactivate if meta-box is deactivated or removed (NOTE: works intermittently - test thoroughly before turning on)

	add_action( 'plugins_loaded', array( $this, 'appt_maybe_self_deactivate' ) );
	function appt_maybe_self_deactivate() { 
		if ( ! class_exists( 'RW_Meta_Box' ) ) {
           require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
           deactivate_plugins( plugin_basename( __FILE__ ) );
           add_action( 'admin_notices', array( $this, 'appt_self_deactivate_notice' ) );
       }
   }

   // Show user message
   function appt_self_deactivate_notice() {
   	 echo '<div class="error"><p>America Publication Post Type Plugin has deactivated itself because meta-box is no longer active. Please reactivate the Meta-Box plugin.</p></div>';
   }
	*/

 	/**
 	 * Activate plugin - check for dependencies & create post ype
 	 * flush-rewrite rules is behaving oddly - re-save permalinks in admin until fixed
 	 * 
 	 * @return function call
 	 */
	function appt_activate() {
		$this->appt_check_dependencies();
		//
		if ( ! post_type_exists( 'publication' ) ) {
			$this->appt_create_publication_post_type();
		}

		// Flush rewrite rules so that users can access custom post types on the front-end right away
		// flush_rewrite_rules( false );  
	} 

	//* Deactivate the plugin
	function appt_deactivate() {
		// Do nothing
	}

	//* Internationalization
	function appt_load_plugin_textdomain () {
		load_plugin_textdomain ( 'america', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Initialize plugin
	 * 
	 * @return function calls
	 */
	function appt_init() {
		$this->appt_create_publication_post_type();
		$this->appt_create_publication_type_taxonomy();
		$this->appt_activate_shortcode();
		$this->appt_add_image();
	}

	/**
	 * Run general admin initialization methods
	 * 
	 * @return function call
	 */
	function appt_admin_init() {
		$this->appt_check_for_user();
	}

	/**
	 * Only add action after user with correct privilegeds is verified
	 * 
	 * @return function call
	 */
	function appt_check_for_user() {
	    if ( !current_user_can( 'activate_plugins' ) ) {
	        add_action('add_meta_boxes', 'appt_publication_remove_meta', 99);
	    }
	}

	/**
	 * Add Settings link under plugin on plugins page (link also appears on General Settings menu)
	 * 
	 * @param  array $links Current links
	 * @return array        Current links + Settings link
	 */
	function appt_action_links( $links ) {
		$settings_link = '<a href="'. get_admin_url(null, 'options-general.php?page=publication-settings-admin') .'">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

 	/**
 	 * Create Publication custom post type
 	 * 
 	 * @return function call
 	 */
	function appt_create_publication_post_type() { 
	    
	    $labels = array(
	        'name'          		=> __( 'Publications', 'america' ),
			'singular_name' 		=> __( 'Publication', 'america' ),
			'add_new'				=> __( 'Add New', 'publication'),
			'add_new_item'  		=> __( 'Add New Publication' ),
			'edit_item'				=> __( 'Edit Publication' ),
			'new_item'				=> __( 'New Publication' ),
			'all_items'				=> __( 'All Publications' ),
			'view_item'				=> __( 'View Publication' ),
			'search_items'  		=> __( 'Search Publications' ),
			'not_found'				=> __( 'No publications found' ),
			'not_found_in_trash'	=> __( 'No publications found in the Trash' ),
			'parent_item_colon'		=> '',
			'name_admin_bar'        => 'Publication',
			'menu_name'				=> 'Publications',
	    );
	    
	    $args = array(
	        'labels'             => $labels,
	        'public'             => true,
	        'publicly_queryable' => true,
	        'menu_icon'    		 => get_stylesheet_directory_uri() . '/lib/icons/publications.png',
	        'query_var'          => true,
	        'rewrite'            => array( 'slug' => 'publication', 'with_front' => false ),
	        'capability_type'    => 'post',
	        'has_archive'        => true,
	        'hierarchical'       => true,
	        'menu_position'      => 3,
	        'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields', 'revisions', 'genesis-seo', 'genesis-cpt-archives-settings' ),
	        'taxonomies'   	     => array( 'publication-type', 'category', 'post_tag' ),
	    );
		
		register_post_type( 'publication', $args );
	}

	/**
	 * Create Publication Type custom taxonomy
	 * 
	 * @return function call
	 */
	function appt_create_publication_type_taxonomy() {  // TODO: this should handle multiple custom taxonomies
	   
	    $labels = array(
	        'name'                       => _x( 'Types', 'Publication types', 'america' ),
	        'singular_name'              => __( 'Type', 'america' ),
	        'add_new_item'               => __( 'Add New Publication Type', 'america' ),
	        'new_item_name'              => __( 'New Publication Type', 'america' ),
	    );

	    $args = array(
	        'hierarchical'          => true,
	        'labels'                => $labels,
	        'show_ui'               => true,
	        'rewrite'               => array( 'slug' => 'publications', 'with_front' => false ),
	        'exclude_from_search'   => true,
	        'has_archive'           => true,
	        'show_tagcloud'         => false,
	        'show_admin_column'     => true,
	    );

		register_taxonomy( 'publication-type', 'publication', $args );
	}

	/**
	 * Hide unnecessary meta boxes from publication new and edit admin screen
	 */
	function appt_publication_remove_meta() {
	    remove_meta_box( 'postcustom', 'publication', 'normal' );
	}

	/**
	 * Customize Publication post info and post meta
	 * 
	 */
	function appt_publication_post_info_meta( $output ) {

	    if ( 'publication' == get_post_type() ) {
	        return '';
	    }
	    return $output;
	}

	/**
	 * Add Custom Meta Box to Publication Post Type to associate a file for download
	 * (pdf to start with, since that's all that's on IIP Digital)
	*/ 
	function appt_register_meta_boxes( $meta_boxes ) {
	    $prefix = 'rw_';

	    // Publication file meta box
	    $meta_boxes[] = array(
	        'id'       => 'publication_file',
	        'title'    => 'Publication File',
	        'pages'    => array( 'publication' ),
	        'context'  => 'normal',
	        'priority' => 'high',

	        'fields' => array(
	            array(
	                'name'  => 'A publication file for download',
	                'desc'  => 'Usually, a PDF file',
	                'id'    => $prefix . 'publication_file',
	                'type'  => 'file_advanced',
	                'std'   => '',
	                'class' => 'thickbox',
	                'clone' => false,
	            ),
	        )
	    );

	    return $meta_boxes;
	}

	/**
	 * Init Publication Type shortcode
	 */
	function appt_activate_shortcode() {
		//include_once( plugin_dir_path( __FILE__ ) . 'templates/america-publication-type-shortcode.php' );  //TODO: make this a class
	    add_shortcode( 'publication_type', array( $this, 'appt_publication_type_shortcode' ) );
	}

	/**
	 * Add publication image size 
	 * This should be made configurable
	 */
	function appt_add_image() {
		add_image_size( 'publication', 424, 530, TRUE );  // TODO: should be a setting
	}


	/** 
	 * Include Publication Post Type in author, category, and tag archive pages
	 */
	function appt_add_publication_to_archive( &$query ) {
		$post_types = array( 'post', 'publication' );
		$archive_types = array( 'is_author', 'is_tag', 'is_category' );

		foreach ( $archive_types as $key => $val ) {
			if ( $query->$val ) {
				$query->set( 'post_type', $post_types );
				remove_action( 'pre_get_posts', 'set_cpt_for_archives' );
			}
		}
	}

 	/** 
 	 * Change the number of publication items to be displayed (props Bill Erickson)
 	 * Added configurable pubs per page -- fetched from settings class
 	 */
	function appt_publication_items( $query ) {
		$settings = $this->appt_publication_settings;
		
		if( ! empty( $settings ) ) {
			$num_pubs = $settings->get_pubs_per_page();	
		}

	    if( $query->is_main_query() && !is_admin() && is_post_type_archive( 'publication' ) ) {
	        $query->set( 'posts_per_page', $num_pubs );
	    }
	}

	/**
	 * Add custom Featured Post Widget
	 * Currently not active - activated in theme functions file
	 * Needs to be made more generic
	 */
	function appt_register_widget_featured_custom_post () {
		include_once( plugin_dir_path( __FILE__ ) . '/inc/class-widget-featured-custom-post.php' );

		unregister_widget( 'Genesis_Featured_Post' );
		register_widget( 'America_Featured_Custom_Post' );
	}

	/**
	 * Add custom Featured Category Widget
	 * urrently not active - activated in theme functions file
	 * Needs to be made more generic
	 */
	function appt_register_widget_featured_category () {
		include_once( plugin_dir_path( __FILE__ ) . '/inc/class-widget-featured-category.php' );

		register_widget( 'America_Featured_Category' );
	}

	/**
	* Produces the linked post custom Publication type taxonomy terms list.
	*
	* Supported shortcode attributes are:
	*   after (output after link, default is empty string),
	*   before (output before link, default is 'Tagged With: '),
	*   sep (separator string between tags, default is ', '),
	*   taxonomy (name of the taxonomy, default is 'category').
	*
	* Output passes through 'genesis_post_terms_shortcode' filter before returning.
	*
	* @since 1.6.0
	*
	* @param array|string $atts Shortcode attributes. Empty string if no attributes.
	* @return string|boolean Shortcode output or false on failure to retrieve terms
	*/
	function appt_publication_type_shortcode( $atts ) {
		$defaults = array(
		  'after'    => '',
		  'before'   => __( 'Type: ', 'genesis' ),
		  'sep'      => ', ',
		  'taxonomy' => 'publication-type',
		);

		$atts = shortcode_atts( $defaults, $atts, 'publication_type' );

		$types = get_the_term_list( get_the_ID(), $atts['taxonomy'], $atts['before'], trim( $atts['sep'] ) . ' ', $atts['after'] );

		if ( is_wp_error( $types ) )
		  return;

		if ( empty( $types ) )
		  return;

		if ( genesis_html5() )   
		  $output = sprintf( '<span %s>', genesis_attr( 'entry-publication-type' ) ) . $types . '</span>';
		else
		  $output = '<span class="entry-publication-type">' . $terms . '</span>';

		return apply_filters( 'genesis_post_terms_shortcode', $output, $types, $atts );

	}

} 

// Initialize America_Publication_Post_Type plugin
global $america_publication_post_type;
$america_publication_post_type = new America_Publication_Post_Type();
$america_publication_post_type->bootstrap();

