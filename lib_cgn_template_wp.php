<?php
Cgn::loadLibrary("lib_cgn_template");

class Cgn_Template_Wp extends Cgn_Template {


	public function Cgn_Template_Wp() {
	}

	public function parseTemplate($templateStyle = 'index') {

		$templateName = Cgn_ObjectStore::getString("config://template/default/name");
		$baseDir = Cgn_ObjectStore::getString("config://template/base/dir");

		//scope
		$t =& Cgn_ObjectStore::getArray("template://variables/");

		$req = Cgn_SystemRequest::getCurrentRequest();
		if ($req->isAjax) {
			$this->doEncodeJson($t);
			return false;
		}

		if (isset($_SESSION['_debug_template']) &&  $_SESSION['_debug_template'] != '') { 
			$systemHandler =& Cgn_ObjectStore::getObject("object://defaultSystemHandler");
			if ( is_object($systemHandler->currentRequest)) {
				$templateName = $_SESSION['_debug_template'];
				Cgn_ObjectStore::storeConfig("config://template/default/name",$templateName);
			}
		}

		//Get the current user into the scope of the upcoming template include.
		$u =& $req->getUser();

		$templateIncluded = FALSE;
		if ($templateStyle=='' || $templateStyle=='index') {
			if(@include( $baseDir. $templateName.'/index.html.php')) {
				$templateIncluded = TRUE;
			}
		} else {
			//try special style, if not fall back to index
			if (!include( $baseDir. $templateName.'/'.$templateStyle.'.php') ) {
				//eat the error
				//failed include
				$e = Cgn_ErrorStack::pullError('php');
				//file not found
				$e = Cgn_ErrorStack::pullError('php');

				//try WP named files first
				if(!$templateIncluded && include( $baseDir. $templateName.'/index.php')) {
					$templateIncluded = TRUE;
				}

				if(!$templateIncluded && @include( $baseDir. $templateName.'/index.html.php')) {
					$templateIncluded = TRUE;
				}

			} else {
				$templateIncluded = TRUE;
			}
		}
		if (!$templateIncluded) {
			$errors = array();
			$errors[] = 'Cannot include template.';
			echo $this->doShowMessage($errors);
		}

		//clean up session variables, this is done with the whole page here
		if (isset($_SESSION['_debug_frontend']) && @$_SESSION['_debug_frontend'] === true) { 
			$systemHandler =& Cgn_ObjectStore::getObject("object://defaultSystemHandler");
			//default system handler handles all front end requests
			if ( is_object($systemHandler->currentRequest)) {
				$_SESSION['_debug_frontend'] = false;
				$_SESSION['_debug_template'] = '';
			}
		}
	}

	public function getSingleCssUrl() {
		$ret = '';
		$handler = Cgn_Template::getDefaultHandler();
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			$templateUrl = 'https://'.Cgn_Template::url();
		} else {
			$templateUrl = 'http://'.Cgn_Template::url();
		}
		foreach ($handler->styleSheets as $s) {
			$ret = $templateUrl.$s;
			break;
		}
		return $ret;
	}
}

if (!function_exists('get_header')) {
	function get_header() {

		$obj = Cgn_ObjectStore::getObject('object://defaultOutputHandler');
		$obj->parseTemplate('header');
	}
}
if (!function_exists('have_posts')) {
	function have_posts() {

echo "have_posts";
return false;
	}
}
if (!function_exists('get_sidebar')) {
	function get_sidebar() {
		$obj = Cgn_ObjectStore::getObject('object://defaultOutputHandler');
		$obj->parseTemplate('sidebar');
		return true;
	}
}
if (!function_exists('get_footer')) {
	function get_footer() {

		$obj = Cgn_ObjectStore::getObject('object://defaultOutputHandler');
		$obj->parseTemplate('footer');
		return true;
	}
}
if (!function_exists('dynamic_sidebar')) {
	function dynamic_sidebar() {
return false;
}
}

if (!function_exists('bloginfo')) {
	function bloginfo($key) {
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			$templateUrl = 'https://'.Cgn_Template::url();
		} else {
			$templateUrl = 'http://'.Cgn_Template::url();
		}

		$obj = Cgn_ObjectStore::getObject('object://defaultOutputHandler');
		if ($key === 'stylesheet_url') {
			echo $templateUrl.'style.css';
		}

		if ($key === 'url') {
			echo cgn_url();
		}
		if ($key === 'name') {
			echo Cgn_Template::siteName();
		}
		if ($key === 'description') {
			echo Cgn_Template::siteTagLine();
		}


	}

}
if (!function_exists('is_home')) {
	function is_home() {
		return false;
	}
}

if (!function_exists('get_settings')) {
// Use get_option().
function get_settings($option) {
	return get_option($option);
}
}

if (!function_exists('get_option')) {
	function get_option($setting) {
		$alloptions = array();

		$value = $alloptions[$setting];

		// If home is not set use siteurl.
		if ( 'home' == $setting && '' == $value )
			return get_option('siteurl');

		if ('siteurl' == $setting) {
			//wp doesn't include trailing slash by default, so every
			//template adds an extra one
			return substr(cgn_url(), 0, -1);
		}

		/*
		if ( in_array($setting, array('siteurl', 'home', 'category_base', 'tag_base')) )
			$value = untrailingslashit($value);
		 */

		return $value;
		return apply_filters( 'option_' . $setting, maybe_unserialize($value) );
	}
}


if (!function_exists('get_bloginfo')) {
/**
 * Note: some of these values are DEPRECATED. Meaning they could be
 * taken out at any time and shouldn't be relied upon. Options
 * without "// DEPRECATED" are the preferred and recommended ways
 * to get the information.
 */
function get_bloginfo($show = '', $filter = 'raw') {

	$output = '';
	switch($show) {
		case 'url' :
		case 'home' : // DEPRECATED
		case 'siteurl' : // DEPRECATED
			$output = get_option('home');
			break;
		case 'wpurl' :
			$output = get_option('siteurl');
			break;
		case 'description':
			$output = get_option('blogdescription');
			break;
		case 'rdf_url':
//			$output = get_feed_link('rdf');
			break;
		case 'rss_url':
//			$output = get_feed_link('rss');
			break;
		case 'rss2_url':
//			$output = get_feed_link('rss2');
			break;
		case 'atom_url':
//			$output = get_feed_link('atom');
			break;
		case 'comments_atom_url':
//			$output = get_feed_link('comments_atom');
			break;
		case 'comments_rss2_url':
//			$output = get_feed_link('comments_rss2');
			break;
		case 'pingback_url':
			$output = get_option('siteurl') .'/xmlrpc.php';
			break;
		case 'stylesheet_url':
			$output = get_stylesheet_uri();
			break;
		case 'stylesheet_directory':
			$output = get_stylesheet_directory_uri();
			break;
		case 'template_directory':
		case 'template_url':
			$output = get_template_directory_uri();
			break;
		case 'admin_email':
			$output = get_option('admin_email');
			break;
		case 'charset':
			$output = get_option('blog_charset');
			if ('' == $output) $output = 'UTF-8';
			break;
		case 'html_type' :
			$output = get_option('html_type');
			break;
		case 'version':
			global $wp_version;
			$output = $wp_version;
			break;
		case 'language':
			$output = get_locale();
			$output = str_replace('_', '-', $output);
			break;
		case 'text_direction':
			global $wp_locale;
			$output = $wp_locale->text_direction;
			break;
		case 'name':
		default:
			$output = get_option('blogname');
			break;
	}

	$url = true;
	if (strpos($show, 'url') === false &&
		strpos($show, 'directory') === false &&
		strpos($show, 'home') === false)
		$url = false;

	if ( 'display' == $filter ) {
		if ( $url )
			$output = apply_filters('bloginfo_url', $output, $show);
		else
			$output = apply_filters('bloginfo', $output, $show);
	}

	return $output;
}
}

if (!function_exists('wp_title')) {
	function wp_title() {
		return Cgn_Template::siteName();
	}
}

if (!function_exists('_e')) {
	function _e($key) {
		echo $key;
	}
}
if (!function_exists('get_locale')) {
	function get_locale() {
		return 'en_US';
	}
}
if (!function_exists('wp_list_pages')) {
	function wp_list_pages() {
		return 'en_US';
	}
}

if (!function_exists('wp_specialchars')) {
	function wp_specialchars($s) {
		return htmlspecialchars($s);
	}
}

if (!function_exists('previous_posts_link')) {
	function previous_posts_link($x, $template) {
		return $x;
//		var_dump(func_get_args());
	}
}
if (!function_exists('next_posts_link')) {
	function next_posts_link($x, $template) {
		return $x;
//		var_dump(func_get_args());
	}
}


if (!function_exists('__')) {
	function __($word, $template) {
		return $word;
	}
}
/*
if (!function_exists('get_row')) {
	function get_row($x, $y) {
		echo "get row ";
		var_dump(func_get_args());
	}
}
 */
if (!function_exists('get_posts')) {
	function get_posts() {
		return false;
	}
}
if (!function_exists('wp_tag_cloud')) {
	function wp_tag_cloud() {
		return false;
	}
}

if (!function_exists('wp_list_cats')) {
	function wp_list_cats() {
		return false;
	}
}
if (!function_exists('wp_list_bookmarks')) {
	function wp_list_bookmarks() {
		return false;
	}
}
if (!function_exists('wp_get_archives')) {
	function wp_get_archives() {
		return false;
	}
}
if (!function_exists('wp_register')) {
	function wp_register() {
		return false;
	}
}
if (!function_exists('wp_loginout')) {
	function wp_loginout() {
		return false;
	}
}

if (!function_exists('wp_footer')) {
	function wp_footer() {
		return false;
	}
}

if (!function_exists('is_single')) {
	function is_single() {
		return false;
	}
}

global $currentPost;
if (!function_exists('setup_postdata')) {
	function setup_postdata($post) {
		global $currentPost;
		$currentPost = $post;
	}
}
if (!function_exists('get_permalink')) {
	function get_permalink() {
		global $currentPost;
		return '#';
	}
}
if (!function_exists('get_the_title')) {
	function get_the_title() {
		global $currentPost;
		return 'get_the_title()';
	}
}

global $wpdb;
$wpdb = Cgn_Db_Connector::getHandle();

