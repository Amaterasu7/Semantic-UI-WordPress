<?php
/*
This files does the setup for SUI.
[comeback] switch to Object callbacks, and get away
from Bones prefixed functions

Author: Nicholas Jordon
*/

// we're firing all out initial functions at the start
add_action( 'after_setup_theme', 'sui_cleanup', 16 );

function sui_cleanup() {

		// launching operation cleanup
		add_action( 'init', 'sui_head_cleanup' );
		// remove WP version from RSS
		add_filter( 'the_generator', 'sui_rss_version' );
		// remove pesky injected css for recent comments widget
		add_filter( 'wp_head', 'sui_widget_recent_comments_style', 1 );
		// clean up comment styles in the head
		add_action( 'wp_head', 'sui_remove_recent_comments_style', 1 );
		// clean up gallery output in wp
		add_filter( 'gallery_style', 'sui_gallery_style' );

		// enqueue base scripts and styles
		add_action( 'wp_enqueue_scripts', 'sui_scripts_and_styles', 999 );
		// ie conditional wrapper

		// launching this stuff after theme setup
		sui_theme_support();

		// adding sidebars to Wordpress (these are created in functions.php)
		add_action( 'widgets_init', 'sui_register_sidebars' );
		// adding the bones search form (created in functions.php)
		add_filter( 'get_search_form', 'sui_search' );

		// cleaning up random code around images
		add_filter( 'the_content', 'sui_filter_ptags_on_images' );
		// cleaning up excerpt
		add_filter( 'excerpt_more', 'sui_excerpt_more' );

} /* end bones ahoy */

/*********************
WP_HEAD GOODNESS
The default wordpress head is
a mess. Let's clean it up by
removing all the junk we don't
need.
*********************/

function sui_head_cleanup() {
	// EditURI link
	remove_action( 'wp_head', 'rsd_link' );
	// windows live writer
	remove_action( 'wp_head', 'wlwmanifest_link' );
	// index link
	remove_action( 'wp_head', 'index_rel_link' );
	// previous link
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
	// start link
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
	// links for adjacent posts
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
	// WP version
	remove_action( 'wp_head', 'wp_generator' );
	// remove WP version from css
	add_filter( 'style_loader_src', 'sui_remove_wp_ver_css_js', 9999 );
	// remove Wp version from scripts
	add_filter( 'script_loader_src', 'sui_remove_wp_ver_css_js', 9999 );

} /* end bones head cleanup */

// remove WP version from RSS
function sui_rss_version() { return ''; }

// remove WP version from scripts
function sui_remove_wp_ver_css_js( $src ) {
		if ( strpos( $src, 'ver=' ) )
				$src = remove_query_arg( 'ver', $src );
		return $src;
}

// remove injected CSS for recent comments widget
function sui_widget_recent_comments_style() {
	 if ( has_filter( 'wp_head', 'wp_widget_recent_comments_style' ) ) {
			remove_filter( 'wp_head', 'wp_widget_recent_comments_style' );
	 }
}

// remove injected CSS from recent comments widget
function sui_remove_recent_comments_style() {
	global $wp_widget_factory;
	if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments'])) {
		remove_action( 'wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style') );
	}
}

// remove injected CSS from gallery
function sui_gallery_style($css) {
	return preg_replace( "!<style type='text/css'>(.*?)</style>!s", '', $css );
}


/*********************
SCRIPTS & ENQUEUEING
*********************/

// loading modernizr and jquery, and reply script
function sui_scripts_and_styles() {
	global $wp_styles; // call global $wp_styles variable to add conditional wrapper around ie stylesheet the WordPress way
	if (!is_admin()) {
		
		// css
		wp_register_style( 'semantic-css', get_stylesheet_directory_uri() . '/lib/css/semantic.min.css', array(), '', 'all' );
		wp_register_style( 'theme-specific', get_stylesheet_directory_uri() . '/lib/css/wp-theme-specific.css', array(), '', 'all' );
		
		// js
		wp_register_script( 'head.js', get_stylesheet_directory_uri() . '/lib/javascript/head.min.js', array(), '0.99', false );
		wp_register_script( 'modernizr', get_stylesheet_directory_uri() . '/library/js/libs/modernizr.custom.min.js', array(), '2.5.3', false );
		wp_register_script( 'semantic-js', get_stylesheet_directory_uri() . '/lib/javascript/semantic.min.js', array(), '', false );
		
		// ie-only
		// wp_register_style( 'ie-only', get_stylesheet_directory_uri() . '/library/css/ie.css', array(), '' );
		
		// enqueue styles and scripts
		// wp_enqueue_style( 'ie-only' );
		wp_enqueue_style( 'semantic-css' );
		wp_enqueue_style( 'theme-specific' );
		wp_enqueue_script( 'head.js'); // This will fetch the other js
		// wp_enqueue_script( 'modernizr' );
		// wp_enqueue_script( 'jquery-1.10.2');
		// wp_enqueue_script( 'semantic-js' );
		
		
		// $wp_styles->add_data( 'ie-only', 'conditional', 'lt IE 9' ); // add conditional wrapper around ie stylesheet
		
	}
}

/*********************
THEME SUPPORT
*********************/

// Adding WP 3+ Functions & Theme Support
function sui_theme_support() {

	// wp thumbnails (sizes handled in functions.php)
	add_theme_support( 'post-thumbnails' );

	// default thumb size
	set_post_thumbnail_size(150, 150, true);

	// rss thingy
	add_theme_support('automatic-feed-links');

	// adding post format support
	add_theme_support( 'post-formats',
		array(
			'aside',             // title less blurb
			'gallery',           // gallery of images
			'link',              // quick link to other site
			'image',             // an image
			'quote',             // a quick quote
			'status',            // a Facebook like status update
			'video',             // video
			'audio',             // audio
			'chat'               // chat transcript
		)
	);

	// wp menus
	add_theme_support( 'menus' );

	// registering wp3+ menus
	register_nav_menus(
		array(
			'main-nav' => __( 'The Main Menu', 'bonestheme' ),   // main nav in header
			'footer-links' => __( 'Footer Links', 'bonestheme' ) // secondary nav in footer
		)
	);
} /* end theme support */


/*********************
MENUS & NAVIGATION
*********************/

// the main menu
function sui_main_nav() {
	// display the wp3 menu if available
		wp_nav_menu(array(
			'container'       => false,                                    // remove nav container
			'container_class' => '',                                       // class of container (should you choose to use it)
			'menu'            => __( 'The Main Menu', 'bonestheme' ),      // nav name
			'menu_class'      => 'ui secondary pointing menu',             // adding custom nav class
			'theme_location'  => 'main-nav',                               // where it's located in the theme
			'items_wrap'      => '<div id="%1$s" class="%2$s">%3$s</div>',
			'before'          => '<span class="item">',                    // before the menu
			'after'           => '</span,>',                               // after the menu
			'link_before'     => '',                                       // before each link
			'link_after'      => '',                                       // after each link
			'depth'           => 0,                                        // limit the depth of the nav
			'fallback_cb'     => 'sui_main_nav_fallback'                 // fallback function
	));
} /* end bones main nav */

// the footer menu (should you choose to use one)
function sui_footer_links() {
	// display the wp3 menu if available
		wp_nav_menu(array(
			'container' => '',                              // remove nav container
			'container_class' => 'footer-links clearfix',   // class of container (should you choose to use it)
			'menu' => __( 'Footer Links', 'bonestheme' ),   // nav name
			'menu_class' => 'nav footer-nav clearfix',      // adding custom nav class
			'theme_location' => 'footer-links',             // where it's located in the theme
			'before' => '',                                 // before the menu
				'after' => '',                                  // after the menu
				'link_before' => '',                            // before each link
				'link_after' => '',                             // after each link
				'depth' => 0,                                   // limit the depth of the nav
			'fallback_cb' => 'sui_footer_links_fallback'  // fallback function
	));
} /* end footer links */

// this is the fallback for header menu [comeback]
function sui_main_nav_fallback() {
	wp_page_menu( array(
		'show_home' => true,
			'menu_class' => 'nav top-nav clearfix',      // adding custom nav class
		'include'     => '',
		'exclude'     => '',
		'echo'        => true,
				'link_before' => '',                            // before each link
				'link_after' => ''                             // after each link
	) );
}

// this is the fallback for footer menu
function sui_footer_links_fallback() {
	/* you can put a default here if you like */
}


// Related Posts
function sui_related_posts() {
	echo '<ul id="related-posts">';
	global $post;
	$tags = wp_get_post_tags( $post->ID );
	if($tags) {
		foreach( $tags as $tag ) { 
			$tag_arr .= $tag->slug . ',';
		}
				$args = array(
					'tag' => $tag_arr,
					'numberposts' => 5, /* you can change this to show more */
					'post__not_in' => array($post->ID)
			);
				$related_posts = get_posts( $args );
				if($related_posts) {
					foreach ( $related_posts as $post ) : setup_postdata( $post ); ?>
							<li class="related_post"><a class="entry-unrelated" href="<?php the_permalink() ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
					<?php endforeach; }
			else { ?>
						<?php echo '<li class="no_related_post">' . __( 'No Related Posts Yet!', 'bonestheme' ) . '</li>'; ?>
		<?php }
	}
	wp_reset_query();
	echo '</ul>';
} /* end bones related posts function */

// Numeric Page Navi
function sui_page_navi($before = '', $after = '') {
	global $wpdb, $wp_query;
	$request = $wp_query->request;
	$posts_per_page = intval(get_query_var('posts_per_page'));
	$paged = intval(get_query_var('paged'));
	$numposts = $wp_query->found_posts;
	$max_page = $wp_query->max_num_pages;
	if ( $numposts <= $posts_per_page ) { return; }
	if(empty($paged) || $paged == 0) {
		$paged = 1;
	}
	$pages_to_show = 5;
	$pages_to_show_minus_1 = $pages_to_show-1;
	$half_page_start = floor($pages_to_show_minus_1/2);
	$half_page_end = ceil($pages_to_show_minus_1/2);
	$start_page = $paged - $half_page_start;
	if($start_page <= 0) {
		$start_page = 1;
	}
	$end_page = $paged + $half_page_end;
	if(($end_page - $start_page) != $pages_to_show_minus_1) {
		$end_page = $start_page + $pages_to_show_minus_1;
	}
	if($end_page > $max_page) {
		$start_page = $max_page - $pages_to_show_minus_1;
		$end_page = $max_page;
	}
	if($start_page <= 0) {
		$start_page = 1;
	}
	echo $before.'<nav class="ui pagination menu" role="navigation">';
	if ($start_page >= 2 && $pages_to_show < $max_page) {
		$first_page_text = __( "First", 'bonestheme' );
		echo '<a class="item" href="'.get_pagenum_link().'" title="'.$first_page_text.'">'.$first_page_text.'</a>';
	}
	if ($start_page >= 3) {
		echo '<a class="icon item" href="'.get_pagenum_link(($i-1)).'"><i class="icon left arrow"></i></a>';
	}
	for($i = $start_page; $i  <= $end_page; $i++) {
		if($i == $paged) {
			echo '<a class="active item" href="'.get_pagenum_link($i).'">'.$i.'</a>';
		} else {
			echo '<a class="item" href="'.get_pagenum_link($i).'">'.$i.'</a>';
		}
	}
	if ($i != $end_page) {
		echo '<a class="icon item" href="'.get_pagenum_link($i).'"><i class="icon right arrow"></i></a>';
	}
	if ($end_page < $max_page) {
		$last_page_text = __( "Last", 'bonestheme' );
		echo '<a class="item" href="'.get_pagenum_link($max_page).'" title="'.$last_page_text.'">'.$last_page_text.'</a>';
	}
	echo '</nav>'.$after."";
} /* end page navi */

/*********************
RANDOM CLEANUP ITEMS
*********************/

// remove the p from around imgs (http://css-tricks.com/snippets/wordpress/remove-paragraph-tags-from-around-images/)
function sui_filter_ptags_on_images($content){
	 return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
}

// This removes the annoying […] to a Read More link
function sui_excerpt_more($more) {
	global $post;
	// edit here if you like
	return '...  <a class="ui mini blue button article read more" href="'. get_permalink($post->ID) . '" title="'. __( 'Read', 'bonestheme' ) . get_the_title($post->ID).'">'. __( 'Continue Reading &raquo;', 'bonestheme' ) .'</a>';
}

/*
 * This is a modified the_author_posts_link() which just returns the link.
 *
 * This is necessary to allow usage of the usual l10n process with printf().
 */
function sui_get_the_author_posts_link() {
	global $authordata;
	if ( !is_object( $authordata ) )
		return false;
	$link = sprintf(
		'<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
		get_author_posts_url( $authordata->ID, $authordata->user_nicename ),
		esc_attr( sprintf( __( 'Posts by %s' ), get_the_author() ) ), // No further l10n needed, core will take care of this one
		get_the_author()
	);
	return $link;
}

?>
