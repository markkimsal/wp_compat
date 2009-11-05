<?php
Cgn::loadLibrary("lib_cgn_template");


class Cgn_Template_Wp extends Cgn_Template {


	public function Cgn_Template_Wp() {
	}

	public function parseTemplate($templateStyle = 'index') {
		global $wpdb;
$wpdb = Cgn_Db_Connector::getHandle();
wp();

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
			if(@include( $baseDir. $templateName.'/index.php')) {
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
			$errors[] = 'Cannot include template. ';
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
			echo substr(cgn_url(), 0, -1);
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

/**
 * Load a simple user object
 */
if ( !function_exists('get_userdata') ) :
	function get_userdata( $user_id ) {
		$finder = new Cgn_DataItem('cgn_user');
		$finder->load($user_id);
		return $finder;
	}
endif;

/**
 * Load a simple user object
 */
if ( !function_exists('is_user_logged_in') ) :
	function is_user_logged_in( $user_id ) {

		$req = Cgn_SystemRequest::getCurrentRequest();
		$u = $req->getUser();
		return !$u->isAnonymous;
	}
endif;

/**
 * Load a simple user object
 */
if ( !function_exists('current_user_can') ) :
	function current_user_can( $user_id ) {
		return false;
		$req = Cgn_SystemRequest::getCurrentRequest();
		$u = $req->getUser();
		return !$u->isAnonymous;
	}
endif;

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
$postcache = array();
global $post, $postcache;

if (!function_exists('get_posts')) {
	function get_posts() {
		global $postcache;
		global  $wp_query, $wp_the_query;
		$finder = new Cgn_DataItem('cgn_blog_entry_publish');
		$postcache = $finder->findAsArray();
		$wp_the_query->post_count = count($postcache);
		return $postcache;
		return false;
	}
}

if (!function_exists('get_post')) {
	function get_post($id, $output, $filter) {
		global $postcache;
		$idx = array_keys($postcache);
		$p = $postcache[ $idx[$id] ];
		return $p;
		return false;
	}
}

if (!function_exists('have_posts')) {
	function have_posts() {
		global $postcache;
		return (current($postcache) !== FALSE);
		return true;
	}
}

if (!function_exists('the_post')) {
	function the_post() {
		global $postcache;
		global $wp_query, $wp_the_query;
		global $post;
		$wp_the_query->in_the_loop = true;
		$post = current($postcache);
		setup_postdata($post);
		next($postcache);
	}
}
if (!function_exists('the_ID')) {
	function the_ID() {
		global $postcache;
		global $wp_query, $wp_the_query;
		global $post;
		return $post->cgn_blog_entry_publish_id;
	}
}

if (!function_exists('setup_postdata')) {
	function setup_postdata($post) {
		global $id, $postdata, $authordata, $day, $currentmonth, $page, $pages, $multipage, $more, $numpages, $wp_query;
		global $pagenow;

		$id = (int) $post['cgn_blog_entry_publish_id'];

		$authordata = get_userdata($post->post_author);

		$day = mysql2date('d.m.y', $post->post_date);
		$currentmonth = mysql2date('m', $post->post_date);
		$numpages = 1;
		$page = get_query_var('page');
		if ( !$page )
			$page = 1;
		if ( is_single() || is_page() )
			$more = 1;
		$content = $post->post_content;
		if ( preg_match('/<!--nextpage-->/', $content) ) {
			if ( $page > 1 )
				$more = 1;
			$multipage = 1;
			$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
			$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
			$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);
			$pages = explode('<!--nextpage-->', $content);
			$numpages = count($pages);
		} else {
			$pages[0] = $post->post_content;
			$multipage = 0;
		}
		return true;
	}
}

if (!function_exists('update_post_caches')) {
	function update_post_caches($posts) {
		return true;
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
		return true;
		return false;
	}
}

if (!function_exists('get_permalink')) {
	function get_permalink($id=0) {
		global $currentPost, $post;
		$entry = $post;
		if (empty($entry)) {
			$entry = get_post($id);
		}

		return cgn_appurl('blog','entry'). sprintf('%03d',$entry['cgn_blog_id']).'/'.date('Y',$entry['posted_on']).'/'.date('m',$entry['posted_on']).'/'.$entry['link_text'].'_'.sprintf('%05d',$entry['cgn_blog_entry_publish_id']).'.html';
		return '#';
	}
}
if (!function_exists('get_the_title')) {
	function get_the_title($postID=NULL) {
		global $postcache, $id;
		if ($post === NULL) {
			$post = $postcache[$id];
		} else {
			$post = $postcache[$postID];
		}
		return $post['title'];
	}
}
if (!function_exists('the_title')) {
	function the_title($id=0) {
		global $post;
		echo $post['title'];
	}
}
if (!function_exists('the_content')) {
	function the_content($readmore) {
		global $post;
		echo $post['content'];
	}
}

if (!function_exists('the_time')) {
	function the_time($id=0) {
		global $post;
		echo date('m-d-Y', $post['posted_on']);
	}
}

if (!function_exists('edit_post_link')) {
	function edit_post_link($id=0) {
		global $post;
		return $post['posted_on'];
	}
}

if (!function_exists('comments_popup_link')) {
	function comments_popup_link($id=0) {
		global $post;
		return $post['posted_on'];
	}
}


if (!function_exists('the_permalink')) {
	function the_permalink($id=0) {
		return '#';
	}
}



//mysql2date
if (!function_exists('mysql2date')) {
	function mysql2date($dateformatstring, $mysqlstring, $translate = true) {
		global $wp_locale;
		$m = $mysqlstring;
		if ( empty($m) ) {
			return false;
		}
		$i = mktime(
			(int) substr( $m, 11, 2 ), (int) substr( $m, 14, 2 ), (int) substr( $m, 17, 2 ),
			(int) substr( $m, 5, 2 ), (int) substr( $m, 8, 2 ), (int) substr( $m, 0, 4 )
		);

		if( 'U' == $dateformatstring )
			return $i;

		if ( -1 == $i || false == $i )
			$i = 0;

		if ( !empty($wp_locale->month) && !empty($wp_locale->weekday) && $translate ) {
			$datemonth = $wp_locale->get_month(date('m', $i));
			$datemonth_abbrev = $wp_locale->get_month_abbrev($datemonth);
			$dateweekday = $wp_locale->get_weekday(date('w', $i));
			$dateweekday_abbrev = $wp_locale->get_weekday_abbrev($dateweekday);
			$datemeridiem = $wp_locale->get_meridiem(date('a', $i));
			$datemeridiem_capital = $wp_locale->get_meridiem(date('A', $i));
			$dateformatstring = ' '.$dateformatstring;
			$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit($dateweekday_abbrev), $dateformatstring);
			$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth), $dateformatstring);
			$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday), $dateformatstring);
			$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit($datemonth_abbrev), $dateformatstring);
			$dateformatstring = preg_replace("/([^\\\])a/", "\\1".backslashit($datemeridiem), $dateformatstring);
			$dateformatstring = preg_replace("/([^\\\])A/", "\\1".backslashit($datemeridiem_capital), $dateformatstring);

			$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
		}
		$j = @date($dateformatstring, $i);
		if ( !$j ) {
			// for debug purposes
			//  echo $i." ".$mysqlstring;
		}
		return $j;
	}

}


include_once('query.php');
include_once('plugin.php');
global $wp_query, $wp, $wp_rewrite, $wp_the_query;
global $wpdb;
unset($GLOBALS['wp_query']);

$GLOBALS['wp_query'] =& new WP_Query();
$wp_query     = $GLOBALS['wp_query'];
$wp_the_query = $GLOBALS['wp_query'];


//$wp_rewrite   =& new WP_Rewrite();
//$wp           =& new WP();
//$GLOBALS['wp'] = $wp;

function wp($query_vars = '') {
	global  $wp_query, $wp_the_query;

	get_posts();
//	$wp_the_query->query('');
	/*
		var_dump($this->query_string);exit();
	$wp->main($query_vars);
	 */

	if( !isset($wp_the_query) )
		$wp_the_query = $wp_query;
}


