<?php
/*

Plugin Name: Genesis Author Pro
Plugin URI:
Description: Adds default Book CPT to any Genesis HTML5 theme.
Version: 1.0
Author: copyblogger
Author URI: http://www.copyblogger.com
Text Domain: genesis-author-pro
Domain Path /languages/

*/

if ( ! defined( 'ABSPATH' ) ) {
	die( "Sorry, you are not allowed to access this page directly." );
}

/**
 * Action on the plugins_loaded hook.
 * Invokes the load_plugin_textdomain() function to support i18 translation strings.
 *
 * @access public
 * @static
 * @return void
 */
function genesis_author_pro_text_domain() {
	load_plugin_textdomain( 'genesis-author-pro', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'genesis_author_pro_text_domain' );

/**
 * Registered autoload function.
 * Used to load class files automatically if they are in the provided array.
 *
 * @access public
 * @param string $class
 * @return void
 */
function gapro_autoloader($class) {

	$classes = array(
		'Genesis_Author_Pro'               => 'class.Genesis_Author_Pro.php',
		'Genesis_Author_Pro_Activation'    => 'class.Genesis_Author_Pro_Activation.php',
		'Genesis_Author_Pro_Book_Meta'     => 'class.Genesis_Author_Pro_Book_Meta.php',
		'Genesis_Author_Pro_CPT'           => 'class.Genesis_Author_Pro_CPT.php',
		'Genesis_Author_Pro_Save'          => 'class.Genesis_Author_Pro_Save.php',
		'Genesis_Author_Pro_Template'      => 'class.Genesis_Author_Pro_Template.php',
		'Genesis_Author_Pro_Term_Meta'     => 'class.Genesis_Author_Pro_Term_Meta.php',
		'Genesis_Author_Pro_Widget'        => 'class.Genesis_Author_Pro_Widget.php',
		'Genesis_Author_Pro_Widget_Admin'  => 'class.Genesis_Author_Pro_Widget_Admin.php',
		'Genesis_Author_Pro_Widget_Output' => 'class.Genesis_Author_Pro_Widget_Output.php',
	);

	if ( isset( $classes[$class] ) ) {
		require_once( GENESIS_AUTHOR_PRO_CLASSES_DIR . $classes[$class] );
	}

}
spl_autoload_register( 'gapro_autoloader' );

register_activation_hook( __FILE__, array( 'Genesis_Author_Pro_Activation', 'activate' ) );

define( 'GENESIS_AUTHOR_PRO_CLASSES_DIR'  , dirname(        __FILE__ ) . '/classes/'   );
define( 'GENESIS_AUTHOR_PRO_FUNCTIONS_DIR', dirname(        __FILE__ ) . '/functions/' );
define( 'GENESIS_AUTHOR_PRO_TEMPLATES_DIR', dirname(        __FILE__ ) . '/templates/' );
define( 'GENESIS_AUTHOR_PRO_RESOURCES_URL', plugin_dir_url( __FILE__ ) . 'resources/'  );

add_action( 'after_setup_theme', array( 'Genesis_Author_Pro_CPT', 'init'            ), 1 );

add_action( 'genesis_init', 'genesis_author_pro_init' );
/**
 * Action added on the genesis_init hook.
 * All actions except the init and activate hook are loaded through this function.
 * This ensures that Genesis is available for any Genesis functions that will be used.
 *
 * @access public
 * @return void
 */
function genesis_author_pro_init(){

	$archive_page_hook = sprintf( 'load-%1$s_page_genesis-cpt-archive-%1$s', 'books' );

	add_filter( 'template_include', array( 'Genesis_Author_Pro_Template', 'maybe_include_template' ) );

	add_action( 'after_setup_theme'         , array( 'Genesis_Author_Pro_CPT', 'maybe_add_image_size'  )        );
	add_action( 'load-post.php'             , array( 'Genesis_Author_Pro'    , 'maybe_do_book_meta'    )        );
	add_action( 'load-post-new.php'         , array( 'Genesis_Author_Pro'    , 'maybe_do_book_meta'    )        );
	add_action( 'load-edit-tags.php'        , array( 'Genesis_Author_Pro'    , 'maybe_enqueue_scripts' )        );
	add_action( $archive_page_hook          , array( 'Genesis_Author_Pro'    , 'maybe_enqueue_scripts' )        );
	add_filter( 'bulk_post_updated_messages', array( 'Genesis_Author_Pro'    , 'bulk_updated_messages' ), 10, 2 );
	add_action( 'save_post'                 , array( 'Genesis_Author_Pro'    , 'maybe_do_save'         ), 10, 2 );
	add_action( 'widgets_init'              , array( 'Genesis_Author_Pro'    , 'widgets_init'          )        );

	add_action( 'admin_enqueue_scripts', 'genesis_author_pro_maybe_do_term_meta' );

}

/**
 * Callback on the `admin_enqueue_scripts` action.
 * Checks to see if the current page is one of the term archive option pages for a GAP term.
 * Then instantiates the Genesis_Author_Pro_Term_Meta object and sets it up.
 *
 * @since 1.1
 *
 * @access public
 * @param  string $hook_suffix
 * @return void
 */
function genesis_author_pro_maybe_do_term_meta( $hook_suffix ) {

	if ( 'edit-tags.php' == $hook_suffix ) {
		global $taxonomy;

		$taxonomies = array(
			'book-authors',
			'book-series',
			'book-tags',
		);

		if ( in_array( $taxonomy, $taxonomies ) ) {
			$Genesis_Author_Pro_Term_Meta = new Genesis_Author_Pro_Term_Meta( $taxonomy );

			$Genesis_Author_Pro_Term_Meta->enqueue_scripts();

			add_action( "{$taxonomy}_edit_form", array( $Genesis_Author_Pro_Term_Meta, 'edit_form' ), 99 );
		}

	}

}

add_filter( 'genesis_term_meta_defaults', 'gap_term_meta_defaults' );
/**
 * Callback on the `genesis_term_meta_defaults` filter.
 * Adds the meta defaults for new term meta options.
 *
 * @since 1.1
 *
 * @access public
 * @param  array $defaults
 * @return array
 */
function gap_term_meta_defaults( $defaults ) {
	$defaults[] = 'book-sort';
	$defaults[] = 'custom-sort';
}

add_action( 'pre_get_posts', 'genesis_author_pro_maybe_modify_term_query' );
/**
 * Callback on `pre_get_posts` hook.
 * Modifies the query if it is the main query, is a taxonomy archive, is one of the GAP taxonomies, and the custom order meta is set.
 * Will change to a custom order.
 *
 * @since 1.1
 *
 * @access public
 * @param  obj $query
 * @return void
 */
function genesis_author_pro_maybe_modify_term_query( $query ) {

	global $wp_the_query;

	if ( $wp_the_query != $query ) {
		return; //only modify the main query
	}

	if ( ! $query->is_tax() ) {
		return; //not a tax archive, keep going
	}

	if ( ! isset( $query->query['book-authors'] ) && ! isset( $query->query['book-series'] ) && ! isset( $query->query['book-tags'] ) ) {
		return; //not a GAP tax.
	}

	$taxonomy  = $query->tax_query->queries[0]['taxonomy'];
	$term_slug = $query->tax_query->queries[0]['terms'][0];
	$term      = get_term_by( 'slug', $term_slug, $taxonomy );

	require_once( GENESIS_AUTHOR_PRO_FUNCTIONS_DIR . 'template.php' );

	if ( genesis_author_pro_get_term_meta( 'custom-sort', $term ) ) {

		$book_order = genesis_author_pro_get_term_meta( 'book-sort', $term );

		$query->set( 'orderby' , 'post__in'  );
		$query->set( 'post__in', $book_order );

	}

}
