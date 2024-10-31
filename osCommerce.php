<?php
/*
Plugin Name: osCommerce
Plugin URI: http://localhost/wordpress/wp-cpntent/plugins/index.php
Description: Pulls the categories and products from an osCommerce system that has been defined in the admin section.
Version: 1.0
Author: Tal Orlik
Author URI: http://everything-about-everything-else.blogspot.com/2008/11/oscommerce-pulginwidget-for-wordpress.html

------------------------------------------------------------------------------
Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : talorlik@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
------------------------------------------------------------------------------
*/
define('OSCOMMERCEVERS', 'Version: 1.0');
define('OSCOMMERCEPATH', ABSPATH. 'wp-content/plugins/osCommerce');
define('OSCOMMERCECLASSPATH', OSCOMMERCEPATH. '/classes');
define('OSCOMMERCEURL', get_option('siteurl'). '/wp-content/plugins/osCommerce');
define('OSCOMMERCEJSURL', OSCOMMERCEURL. '/js');
define('OSCOMMERCECSSURL', OSCOMMERCEURL. '/css');
define('OSCOMMERCEIMAGESURL', OSCOMMERCEURL. '/images');
define('ABSWPINCLUDE', ABSPATH.WPINC);

require_once(OSCOMMERCECLASSPATH .'/osc_db.class.php');
require_once(OSCOMMERCECLASSPATH .'/osc_widget.class.php');
require_once(OSCOMMERCECLASSPATH .'/osc_management.class.php');

/* INIT LOCALISATION ----------------------------------------------------------*/
load_default_textdomain();
require_once(ABSWPINCLUDE.'/locale.php');
load_plugin_textdomain('osCommerce', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/lang');
/*-----------------------------------------------------------------------------*/

if(!isset($_GET['paged']) || empty($_GET['paged']) || !is_numeric($_GET['paged']))
	$_GET['paged'] = 1;
	
if(isset($_GET['osc_action']) && $_GET['osc_action'] == 'osc_delete')
{
	$db = new osc_db();

	$db->osc_delete($_GET['intID']);
	
	unset($db);
}

function osc_activate()
{
	$db = new osc_db();
  	$db->create_tbl();
	unset($db);
}

function osc_init()
{
	$svr_uri = $_SERVER['REQUEST_URI'];
	$inadmin = strstr($svr_uri, 'wp-admin');

	if($inadmin)
		wp_enqueue_script('java_script', '/wp-content/plugins/osCommerce/js/java_script.js');
	
	if(!$inadmin || ($inadmin && strstr($svr_uri, 'widget')))
	{
		$widget     = new osc_widget();
		$management = new osc_management();

		if(!function_exists('register_sidebar_widget')) return;

		register_sidebar_widget(__('osCommerce', 'osCommerce'), array(&$widget, 'display'));
		register_widget_control(__('osCommerce', 'osCommerce'), array(&$management, 'widget_control'));
		
		unset($widget);
	}
}

// action function for above hook
function osc_management_init()
{
    $management = new osc_management();
  	add_menu_page(__('osCommerce', 'osCommerce'), __('osCommerce', 'osCommerce'), 8, 'osCommerce', array(&$management, 'display'));

  	if(isset($_GET['page']) && strstr($_GET['page'], 'osCommerce'))
	{
    	global $loc_lang;
    	wp_enqueue_script('java_script', '/wp-content/plugins/osCommerce/js/java_script.js');
    	add_submenu_page('osCommerce', __('osCommerce', 'osCommerce'), __('Listing', 'osCommerce'), 8, 'osCommerce', array(&$management, 'osc_listing'));
    	add_submenu_page('osCommerce', __('osCommerce','osCommerce'), __('Add Shop','osCommerce'), 8, 'osCommerce-add-form', array(&$management, 'osc_add_form'));
		
		if(isset($_GET['osc_action']) && $_GET['osc_action'] == 'osc_edit')
			add_submenu_page('osCommerce', __('osCommerce','osCommerce'), __('Edit Shop','osCommerce'), 8, 'osCommerce-edit-form', array(&$management, 'osc_edit_form'));
  	}
}

function osCommerceHeaderScript()
{
?>
	<link type="text/css" rel="stylesheet" href="<?=OSCOMMERCECSSURL;?>/osc_front.css" />
<?php
}

function osCommerceAdminHeaderScript()
{
	if(isset($_GET['page']) && substr($_GET['page'], 0, 10) == 'osCommerce')
  	{
?>
	<link type="text/css" rel="stylesheet" href="<?=OSCOMMERCECSSURL;?>/osc_management.css" />
<?php
  	}
}

function osc_strstr($haystack, $needle, $before_needle = false)
{
	if(($pos = strpos($haystack, $needle)) === false) return false;

 	if($before_needle) return substr($haystack, 0, $pos);
  	else return substr($haystack, $pos + strlen($needle));
}

function filterosCommerceProductListing($content)
{
	if(preg_match('[oscProductListing]', $content))
	{
		$db = new osc_db();

    	$osc_products = new osc_products();

    	$osc_match_filter = '[[oscProductListing]]';

    	$before_product_listing = osc_strstr($content, $osc_match_filter, true);

    	$content = osc_strstr($content, $osc_match_filter, true);

		$osc_products->osc_list_products($db, $_GET['shopID'], $_GET['catID']);

		unset($db);
  	}

  	return $content;
}

register_activation_hook(__FILE__, 'osc_activate');
register_deactivation_hook(__FILE__, 'osc_activate');
add_action('plugins_loaded', 'osc_init');
add_action('admin_menu', 'osc_management_init');
add_action('wp_head', 'osCommerceHeaderScript');
add_action('admin_head', 'osCommerceAdminHeaderScript');
add_filter('the_content', 'filterosCommerceProductListing');
?>
