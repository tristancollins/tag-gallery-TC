<?php
/*
 * Plugin Name: Tag Gallery TC
 * Plugin URI: http://johnnypeck.com
 * Version: 0.2.1
 * Author: Johnny Peck with additions by Tristan Collins
 * Description: A very simple short-code plugin gallery, similar to gallery shortcode, but gets images by tag from media library. It was created quickly to solve a simple problem. If it gets popular, I'll spend some more time on it, clean it up, etc. Absolutely requires Paul Menard's Media Tags plugin and optionally, Justin Tadlock's Cleaner Gallery which is highly recommended.  Much of this plugin was inspired by Cleaner Gallery and includes that plugins CSS.
 */

/**
 * Make sure we get the correct directory.
 */
if ( !defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( !defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( !defined( 'WP_PLUGIN_URL' ) )
	define('WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( !defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	
define( TAGGALLERY, WP_PLUGIN_DIR . '/tag-gallery' );
define( TAGGALLERY_URL, WP_PLUGIN_URL . '/tag-gallery');

// Add the shortcode to wordpress
add_shortcode('tag-gallery', 'tag_gallery_shortcode');

// Enqueue our styles from Cleaner Gallery if available, otherwise use our local style.
if(!is_admin()) {
	// TODO There must be a function to check if a style is enqueued already.. ?? 
	if(file_exists(WP_PLUGIN_DIR . '/cleaner-gallery/cleaner-gallery.php')) {		
		wp_enqueue_style( 'cleaner-gallery', WP_PLUGIN_URL . '/cleaner-gallery/cleaner-gallery.css', false, 0.7, 'all' );
	} else {
/* 		wp_enqueue_style( 'tag-gallery', TAGGALLERY_URL . '/cleaner-gallery.css', false, 0.7, 'all' ); */
//TC added to use Theme's stylesheet
	  wp_enqueue_style( 'tag-gallery',get_stylesheet_uri(), false, 0.7, 'all' );
	}
}

/**
 * The tag-gallery short-code implementation.
 * 
 * @param Array $attr
 * @return String - HTML
 */
function tag_gallery_shortcode($attr) {
	global $post;
	
	static $instance = 0;
	$instance++;
	
	$defaults = array(
		'tag' => 'tag',
		'id' 		 => $post->ID,
		'itemtag'    => 'dl',
		'icontag'    => 'dt',
		'captiontag' => 'dd',
		'captionson' => 'false',
		'columns'    => 6,
		'theight'    => 150,
		'twidth'     => 150,
	       	'tctagslug'  => 'false', /* TC added */
	);
	
	extract(shortcode_atts( $defaults, $attr));
	
	$columns = intval( $columns );
	$id = intval( $id );	
	$tags = $tag;
	
	$itemtag = tag_escape( $itemtag );
	$icontag = tag_escape( $icontag );
	$captiontag = tag_escape( $captiontag );
		
	$theight = intval( $theight );
	$twidth  = intval( $twidth );

        $homeurl = get_home_url();
	
	$itemwidth = $columns > 0 ? floor( 100 / $columns ) : 100;
	$i = 0;
	
	$gallery_id = tag_gallery_id( $id );
	// TODO There must be a better way to find out if a plugin is installed!
	if(file_exists(WP_PLUGIN_DIR . '/cleaner-gallery/cleaner-gallery.php')) {
		include_once WP_PLUGIN_DIR . '/cleaner-gallery/cleaner-gallery.php';
		$attributes = cleaner_gallery_link_attributes( $gallery_id );
	} else {
		// For now, there are no default attributes, maybe in the next version.
		$attributes = '';
	}
	
	if(function_exists('get_attachments_by_media_tags')) {
	  //TC added to display images tagged with the post slug
	  if( $tctagslug == 'true' ) {
	    $tcslug=$post->post_name;
	    $images = tag_gallery_get_images($tcslug);
/* 	    echo "this is a true route"; */
/* 	    echo $tcslug; */
	  } else {
	    $images = tag_gallery_get_images($tag);
/* 	    echo "this is a false route"; */
/* 	    echo $TCtagslug; */
	  }
	} else {
		// We aren't able to get the images by tag so the plugin is useless here, so just return control to WP.
	  return;
	}
	
	// Start the output of the tag gallery.
	$output = "\t\t\t" . '<div id="gallery-' . $gallery_id . '" class="gallery gallery-' . $id . '">';
	
	// Make sure we actually have images to go through.
	if(null != $images) {
	// Loop through the images array.
	foreach($images as $media => $image) {
		
		$caption = esc_html( $image['caption'] );
		$title   = esc_html( $image['title'] );
		$description = esc_html( $image['description'] );
		$src = $image['src'];
		$thumb = tg_generateTimThumbUrl( $src, $twidth, $theight );
                $attid = $image['attid'];
		
		/* Open each gallery row. */
		if ( $columns > 0 && $i % $columns == 0 )
			$output .= "\n\t\t\t\t<div class='gallery-row clear'>";

		/* Open each gallery item. */
		$output .= "\n\t\t\t\t\t<{$itemtag} class='gallery-item col-$columns'>";

		/* Open the element to wrap the image. */
		$output .= "\n\t\t\t\t\t\t<{$icontag} class='gallery-icon'>";
		
		/*	$output .= '<a href="' . $src . '" title="' . $title . '"' . $attributes . '>';*/

		// TC altered to try and get jquery to work properly - added "rel" as per fancybox instructions. The top line is for fancybox, the one after is for attachment pages
                /*	$output .= '<a href="' . $src . '" title="' . $title . '"' . $attributes .  ' rel="gallery-' . $gallery_id . '"' . '>'; */
		$output .= '<a href="' . $homeurl . '?attachment_id=' . $attid . '" title="' . $title . '"' . $attributes .  ' rel="attachment wp-att-' . $attid . '"' . '>';
		// TC added 'class="attachment-thumbnail"' to match hatch-pro
		$output .= '<img src="' . $thumb . '" alt="' . $title . '" title="' . $title . '" class="attachment-thumbnail" />';
		$output .= '</a>';
		
		/* Close the image wrapper. */
		$output .= "</{$icontag}>";
		
		// Caption Stuff
		if( $captionson == 'true' ) {
			$output .= "\n\t\t\t\t\t\t<$captiontag class='gallery-caption'>";
			$output .= '<a href="' . $src . '" title="' . $title . '">' . $caption . '</a>';
			$output .= "</$captiontag>";
		}
		/* Close individual gallery item. */
		$output .= "\n\t\t\t\t\t</{$itemtag}>";

		/* Close gallery row. */
		if ( $columns > 0 && ++$i % $columns == 0 )
			$output .= "\n\t\t\t\t</div>";
	}
	
	/* Close gallery and return it. */
	if ( $columns > 0 && $i % $columns !== 0 )
		$output .= "\n\t\t\t</div>";

	$output .= "\n\t\t\t</div>\n";

	return $output;
	}
	return;
}

/**
 * Generates the Image src uri to get the image via timThumb.
 * TODO Find another solution for this. It's not terribly efficient.
 * 
 * @param  String  $image
 * @param  Integer $width
 * @param  Integer $height
 * @return String
 * @since  0.1
 */
function tg_generateTimThumbUrl( $image, $width = null, $height = null )
{
	$timThumb = TAGGALLERY_URL . '/timthumb/timthumb.php?src=' . $image . '&h=' . $height . '&w=' . $width;
	return $timThumb;
}

/**
 * Multiple galleries on single page with valid id.
 * Got this from Justin Tadlocks cleaner-gallery.
 * 
 * @param  Integer $id
 * @return Integer
 * @since  0.1
 */
function tag_gallery_id( $id = 0 ) {
	global $tag_gallery_id, $tag_gallery_num;

	if ( $tag_gallery_id == $id ) :
		++$tag_gallery_num;
		$id = $id . '-' . $tag_gallery_num;
	else :
		$tag_gallery_num = 0;
		$tag_gallery_id = $id;
	endif;

	return $id;
}

/**
 * Get our images array from the media library. 
 * 
 * @param  String $tag
 * @return Array
 * @since  0.1
 */
function tag_gallery_get_images($tags)
{
	$image_tags = 'media_tags='. $tags;
	$tagged_images = get_attachments_by_media_tags($image_tags);
	if(empty($tagged_images))
		return;

	$images = array();
	foreach($tagged_images as $key => $value) {
		$value = (array) $value;
		$images[$key] = array();
		$images[$key]['src'] = $value['guid'];
		$images[$key]['caption'] = $value['post_excerpt'];
		$images[$key]['description'] = $value['post_content'];
		$images[$key]['title'] = $value['post_title'];
		$images[$key]['attid'] = pn_get_attachment_id_from_url($value['guid']); /* This gets the attachmentID and stores it in attid */
	}
	return $images;
	
}

/**
 * Code to get the attachment ID from the image URL
https://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
*
*/

function pn_get_attachment_id_from_url( $attachment_url = '' ) {
 
	global $wpdb;
	$attachment_id = false;
 
	// If there is no url, return.
	if ( '' == $attachment_url )
		return;
 
	// Get the upload directory paths
	$upload_dir_paths = wp_upload_dir();
 
	// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
	if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {
 
		// If this is the URL of an auto-generated thumbnail, get the URL of the original image
		$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
 
		// Remove the upload path base directory from the attachment URL
		$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );
 
		// Finally, run a custom database query to get the attachment ID from the modified attachment URL
		$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );
 
	}
 
	return $attachment_id;
}