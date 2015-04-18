<?php

if (defined('STAGESHOW_FOLDER')) 
{
	if (STAGESHOW_FOLDER != basename(dirname(__FILE__)))
	{
		echo "ERROR Activating ".basename(dirname(__FILE__))."<br>\n";
		echo "Deactivate ".STAGESHOW_FOLDER." First<br>\n";
		die;
	}
	return;
}

define('STAGESHOW_FILE_PATH', dirname(__FILE__).'/');

/*
------------------------------------------------------------------------
	This section contains definitions that are usually set by
	Wordpress, but are set here when included by JQuery callbacks.
------------------------------------------------------------------------
*/

if (!defined('WP_CONTENT_DIR'))
	define ('WP_CONTENT_DIR', dirname(dirname(STAGESHOW_FILE_PATH)));

if (!isset($siteurl)) $siteurl = get_option('siteurl');
if (is_ssl())
	$siteurl = str_replace('http://', 'https://', $siteurl);
else
	$siteurl = str_replace('https://', 'http://', $siteurl);

define('STAGESHOW_FOLDER', basename(STAGESHOW_FILE_PATH));
define('STAGESHOW_URL', $siteurl.'/wp-content/plugins/' . STAGESHOW_FOLDER .'/');
define('STAGESHOW_UPLOADS_URL', $siteurl.'/wp-content/uploads/' . STAGESHOW_FOLDER .'/');
define('STAGESHOW_ADMIN_URL', STAGESHOW_URL . 'admin/');
define('STAGESHOW_ADMIN_IMAGES_URL', STAGESHOW_ADMIN_URL . 'images/');
if (!defined('STAGESHOW_UPLOADS_PATH'))
{
	define('STAGESHOW_UPLOADS_PATH', WP_CONTENT_DIR.'/uploads/'.STAGESHOW_FOLDER);				
}

define('STAGESHOWLIB_URL', STAGESHOW_URL);
define('STAGESHOWLIB_ADMIN_URL', STAGESHOW_ADMIN_URL);

if (STAGESHOW_FOLDER == 'stageshowgold')
	define('STAGESHOW_PLUGIN_NAME', 'StageShowGold');
else if (STAGESHOW_FOLDER == 'stageshowplus')
	define('STAGESHOW_PLUGIN_NAME', 'StageShowPlus');
else if (STAGESHOW_FOLDER == 'stageshow')
	define('STAGESHOW_PLUGIN_NAME', 'StageShowWPOrg');

if (!defined('STAGESHOW_STYLESHEET_URL'))
	define('STAGESHOW_STYLESHEET_URL', STAGESHOW_URL.'css/stageshow.css');

define('STAGESHOW_DIR_NAME', basename(STAGESHOW_FILE_PATH));
if (!defined('STAGESHOW_INCLUDE_PATH'))
{
	define('STAGESHOW_ADMIN_PATH', STAGESHOW_FILE_PATH . 'admin/');
	define('STAGESHOW_INCLUDE_PATH', STAGESHOW_FILE_PATH . 'include/');
	define('STAGESHOW_ADMINICON_PATH', STAGESHOW_ADMIN_PATH . 'images/');
	define('STAGESHOW_TEST_PATH', STAGESHOW_FILE_PATH . 'test/');
		
	define('STAGESHOWLIB_INCLUDE_PATH', STAGESHOW_INCLUDE_PATH);
}

define('STAGESHOW_DEFAULT_TEMPLATES_PATH', STAGESHOW_FILE_PATH . 'templates/');
define('STAGESHOW_LANG_RELPATH', STAGESHOW_FOLDER . '/lang/');

if (!defined('STAGESHOW_SHORTCODE_PREFIX'))
	define('STAGESHOW_SHORTCODE_PREFIX', 'sshow');

define('STAGESHOW_DEFAULT_SETUPUSER', 'administrator');

define('STAGESHOWLIB_CAPABILITY_RESERVEUSER', 'StageShow_Reservations');	// A user that can reserve seats without paying online
define('STAGESHOWLIB_CAPABILITY_VALIDATEUSER', 'StageShow_Validate');
define('STAGESHOWLIB_CAPABILITY_SALESUSER', 'StageShow_Sales');			// A user that can view and edit sales
define('STAGESHOWLIB_CAPABILITY_ADMINUSER', 'StageShow_Admin');			// A user that can edit shows, performances
define('STAGESHOWLIB_CAPABILITY_SETUPUSER', 'StageShow_Setup');			// A user that can edit stageshow settings
define('STAGESHOWLIB_CAPABILITY_VIEWSETTINGS', 'StageShow_ViewSettings');	// A user that can view stageshow settings
define('STAGESHOWLIB_CAPABILITY_DEVUSER', 'StageShow_Testing');			// A user that can use test pages

if (!defined('STAGESHOW_CODE_PREFIX'))
	define('STAGESHOW_CODE_PREFIX', 'stageshow');

define('STAGESHOW_MENUPAGE_ADMINMENU', STAGESHOW_CODE_PREFIX.'_adminmenu');
define('STAGESHOW_MENUPAGE_OVERVIEW', STAGESHOW_CODE_PREFIX.'_overview');
define('STAGESHOW_MENUPAGE_SEATING', STAGESHOW_CODE_PREFIX.'_seating');
define('STAGESHOW_MENUPAGE_SHOWS', STAGESHOW_CODE_PREFIX.'_shows');
define('STAGESHOW_MENUPAGE_PERFORMANCES', STAGESHOW_CODE_PREFIX.'_performances');
define('STAGESHOW_MENUPAGE_PRICES', STAGESHOW_CODE_PREFIX.'_prices');
define('STAGESHOW_MENUPAGE_PRICEPLANS', STAGESHOW_CODE_PREFIX.'_priceplans');
define('STAGESHOW_MENUPAGE_SALES', STAGESHOW_CODE_PREFIX.'_sales');
define('STAGESHOW_MENUPAGE_SETTINGS', STAGESHOW_CODE_PREFIX.'_settings');
define('STAGESHOW_MENUPAGE_TOOLS', STAGESHOW_CODE_PREFIX.'_tools');
define('STAGESHOW_MENUPAGE_DEVTEST', STAGESHOW_CODE_PREFIX.'_devtest');
define('STAGESHOW_MENUPAGE_DIAGNOSTICS', STAGESHOW_CODE_PREFIX.'_diagnostics');
define('STAGESHOW_MENUPAGE_TESTSETTINGS', STAGESHOW_CODE_PREFIX.'_testsettings');

define('STAGESHOW_FILEPATH_TEXTLEN',255);
define('STAGESHOW_FILEPATH_EDITLEN', 95);

define('STAGESHOW_URL_TEXTLEN',110);
	
define('STAGESHOW_PRICE_UNKNOWN',-100);

if (defined('STAGESHOW_DATETIME_BOXOFFICE_FORMAT'))
{
	define('STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT',STAGESHOW_DATETIME_BOXOFFICE_FORMAT);
}

/*
------------------------------------------------------------------------
	This section contains definitions that have default values
	set here, but which can have site specific values defined 
	by an entry in the wp-config.php file which will then 
	replace this default value.
------------------------------------------------------------------------
*/
if (!defined('STAGESHOW_NEWS_UPDATE_INTERVAL'))
	define('STAGESHOW_NEWS_UPDATE_INTERVAL', 10);

if (!defined('STAGESHOW_MAXTICKETCOUNT'))
	define('STAGESHOW_MAXTICKETCOUNT', 4);	// Default value for "Max Ticket Qty" in settings

if (!defined('STAGESHOW_MAX_TICKETSEATS'))
	define('STAGESHOW_MAX_TICKETSEATS', 8);	// Maximum number of tickets in drop down quantity selector (Prices and Price Plans pages)

if (!defined('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH', 'stageshow_SaleTimeoutEMail.php');

if (!defined('STAGESHOWLIB_DATETIME_ADMIN_FORMAT'))
	define('STAGESHOWLIB_DATETIME_ADMIN_FORMAT', 'Y-m-d H:i');

?>