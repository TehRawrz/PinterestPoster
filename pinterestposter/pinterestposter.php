<?php
/**
* Plugin Name: Pinterest Poster
* Plugin URI: http://mypluginuri.com/
* Description: A brief description about your plugin.
* Version: 1.0 or whatever version of the plugin (pretty self explanatory)
* Author: Rawrz
* Author URI: Author's website
* License: A "Slug" license name e.g. GPL12
*/
/**
 * A function used to programmatically create a post in WordPress. The slug, author ID, and title
 * are defined within the context of the function.
 *
 * @returns -1 if the post was never created, -2 if a post with the same title exists, or the ID
 *          of the post if successful.
 */
function get_title($url){
	$str = file_get_contents($url);
	if(strlen($str)>0){
		$str = trim(preg_replace('/\s+/', ' ', $str)); // supports line breaks inside <title>
		preg_match("/\<title\>(.*)\<\/title\>/i",$str,$title); // ignore case
		return $title[1];
	}
}
function programmatically_create_post() {
	$url='http://widgets.pinterest.com/v3/pidgets/boards/bradleyblose/my-stuff/pins/';
	$json_O=json_decode(file_get_contents($url),true);
	$id = $json_O['data']['pins'][0]['id'];
	$titlelink = 'https://www.pinterest.com/pin/' . $id .'/';
	$title = get_title($titlelink);
	var_dump($title);
	$original = $json_O['data']['pins'][0]['images']['237x']['url'];
	$image_url = preg_replace('/237x/', '736x', $original);
	$description = $json_O['data']['pins'][0]['description'];
	// Initialize the page ID to -1. This indicates no action has been taken.
	$post_id = -1;
	// Setup the author, slug, and title for the post
	$author_id = 1;
	$mytitle = get_page_by_title($title, OBJECT, 'post');
	var_dump($mytitle);
	// If the page doesn't already exist, then create it
	if( NULL == get_page_by_title($title, OBJECT, 'post') ) {
		// Set the post ID so that we know the post was created successfully
		$post_id = wp_insert_post(
			array(
				'comment_status'	=>	'closed',
				'ping_status'		=>	'closed',
				'post_author'		=>	$author_id,
				'post_name'			=>	$title,
				'post_title'		=>	$title,
				'post_content'		=> 	$description,
				'post_status'		=>	'publish',
				'post_type'			=>	'post'
			)
		);
		//upload featured image
		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents($image_url);
		$filename = basename($image_url);
			if(wp_mkdir_p($upload_dir['path'])){
				$file = $upload_dir['path'] . '/' . $filename;
				$path = $upload_dir['path'] . '/';
			}else{
				$file = $upload_dir['basedir'] . '/' . $filename;
				$path = $upload_dir['basedir'] . '/';
			}
				file_put_contents($file, $image_data);
		//edit featured image to correct specs to fit theme
			$pngfilename = $filename . '.png';
			$targetThumb = $path . '/' . $pngfilename;
			$img = new Imagick($file);
			$img->scaleImage(250,250,true);
			$img->setImageBackgroundColor('None');
			$w = $img->getImageWidth();
			$h = $img->getImageHeight();
			$img->extentImage(250,250,($w-250)/2,($h-250)/2);
			$img->writeImage($targetThumb);
			unlink($file);
			//Attach featured image
			$wp_filetype = wp_check_filetype($pngfilename, null );
				$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => sanitize_file_name($pngfilename),
				'post_content' => '',
				'post_status' => 'inherit'
				);
		$attach_id = wp_insert_attachment( $attachment, $targetThumb, $post_id );
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $targetThumb );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		set_post_thumbnail( $post_id, $attach_id );
	// Otherwise, we'll stop
	} else {

    		// Arbitrarily use -2 to indicate that the page with the title already exists
    		$post_id = -2;
	} // end if

} // end programmatically_create_post
//Chron function to run every minute (change to hours/days after testing)
/*
add_filter('cron_schedules', 'add_per_min');

function add_per_min() {
    return array(
    'perminute' => array('interval' => 60, 'display' => 'Every Minute'),
    );
}

if (!wp_next_scheduled('the_name_of_my_custom_interval')) {
    wp_schedule_event(time(), 'perminute', 'the_name_of_my_custom_interval' );
}

add_action('the_name_of_my_custom_interval', 'programmatically_create_post');
*/
?>