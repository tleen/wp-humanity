<?php
/**
 * Plugin Name: Humanity
 * Plugin URI: https://github.com/tleen/wp-humanity
 * Description: Serve up an existing humans.txt file from theme or WP root
 * Version: 0.0.0
 * Author: tleen
 * Author URI: http://www.thomasleen.com
 * License: MIT
 * 
 * Humans.txt Compatibility File
 *
 * Supports the serving of a humans.txt file as supplied by a theme author.
 * Wil lseek to serve a humans.txt file present in a child theme, parent theme
 * then WordPress webroot in that order. 
 *
 *
 * Cribbed heavily from example at oneextrapixel.com
 *
 * Author: Anna Ladoshkina
 * Description: Code snippet to incorporate humans.txt functionality into WordPress
 * Author URI: http://about.me/foralien
 *
 * Removed the part about displaying buttons (no need here) and changed embedded file to
 * be loaded from theme root.
 *
 * @link http://www.onextrapixel.com/2011/11/07/how-to-incorporate-humans-txt-into-your-wordpress-site/
 * @link http://humanstxt.org/
 *
 */

defined('ABSPATH') or die;

// get the location of the humans text file (in child or main theme)
function wp_humanity_humans_filepaths(){
  $paths = [
    get_stylesheet_directory(), // child theme
      get_template_directory(), // parent theme
      ABSPATH // webroot
  ];
  // will remove an entry if not a child theme
  $paths = array_unique($paths);

  // create paths to humans.txt
  $paths = array_map(
    function($path){ return join(DIRECTORY_SEPARATOR, array($path, 'humans.txt')); },
      $paths);

  return $paths;
}

$wp_humanity_humans_filepath_saved = false;
function wp_humanity_humans_filepath(){
  
  global $wp_humanity_humans_filepath_saved;
  if($wp_humanity_humans_filepath_saved) return $wp_humanity_humans_filepath_saved;
  
  $paths = wp_humanity_humans_filepaths();
  foreach($paths as $path){
    if(is_readable($path)){
      $wp_humanity_humans_filepath_saved = $path;
      return $wp_humanity_humans_filepath_saved;
    }
  }
  return '';
  
}

function wp_humanity_humans_exists(){
	return ('' !== wp_humanity_humans_filepath());
}

/* Generate humans.txt content */
function do_humans() {

	//let some other plugins do something here
	do_action( 'do_humanstxt' );

	//prepare default content
	if(wp_humanity_humans_exists()){
		//serve correct headers
		header( 'Content-Type: text/plain; charset=utf-8' );

		$content = file_get_contents(wp_humanity_humans_filepath());
		//make it filterable
		$content = apply_filters('humans_txt', $content);

		//correct line ends
		$content = str_replace("\r\n", "\n", $content);
		$content = str_replace("\r", "\n", $content);

		//output
		echo $content;
	}else{
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
		get_template_part( 404 );
	}
}

/* Link to humans.txt for head section of site */
function wp_humanity_humanstxt_link(){

	$url = esc_url(home_url('humans.txt'));
	echo "<link rel='author' href='{$url}' />\n";
}

/* Make WP understand humans.txt url */
function wp_humanity_humanstxt_init(){
	global $wp_rewrite, $wp;

	if(!wp_humanity_humans_exists()) return;

	//root install check
	$homeurl = parse_url(home_url());
	if (isset($homeurl['path']) && !empty($homeurl['path']) && $homeurl['path'] != '/')
		return;

	//check for pretty permalinks
	$permalink_test = get_option('permalink_structure');
	if(empty($permalink_test))
		return;

	//register rewrite rule for humans.txt request
	add_rewrite_rule('humans\.txt$', $wp_rewrite->index.'?humans=1', 'top');

	// flush rewrite rules if 'humans' one is missing
	$rewrite_rules = get_option('rewrite_rules');
	if (!isset($rewrite_rules['humans\.txt$']))
		flush_rewrite_rules(false);

	//add 'humans' query variable to WP
	$wp->add_query_var('humans');

	// display links to humans txt (when it's properly registered)
	if(function_exists('wp_humanity_humanstxt_link'))
		add_action('wp_head', 'wp_humanity_humanstxt_link');
}
add_action('init', 'wp_humanity_humanstxt_init');

/* Conditional tag to check for humans.txt request */
function wp_humanity_is_humans_request(){

	if( 1 == get_query_var('humans'))
	  return true;

	return false;
}


/* Load dynamic content instead or regular WP template */
function wp_humanity_humanstxt_load(){

	if(wp_humanity_is_humans_request() && wp_humanity_humans_exists()){
	  do_humans();
	  die();
	}
}
add_action('template_redirect', 'wp_humanity_humanstxt_load');

?>
