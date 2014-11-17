<?php

if (defined('STAGESHOW_FOLDER')) return;

$siteurl = get_option('siteurl');
define('STAGESHOW_FOLDER', dirname(plugin_basename(__FILE__)));
define('STAGESHOW_URL', $siteurl.'/wp-content/plugins/' . STAGESHOW_FOLDER .'/');
define('STAGESHOW_ADMIN_URL', STAGESHOW_URL . 'admin/');
define('STAGESHOW_ADMIN_IMAGES_URL', STAGESHOW_ADMIN_URL . 'images/');

if (!defined('STAGESHOW_STYLESHEET_URL'))
	define('STAGESHOW_STYLESHEET_URL', STAGESHOW_URL.'css/stageshow.css');

if (!defined('STAGESHOW_PAYPAL_IPN_NOTIFY_URL'))
	define('STAGESHOW_PAYPAL_IPN_NOTIFY_URL', STAGESHOW_URL.'stageshow_ipn_callback.php');

define('STAGESHOW_FILE_PATH', dirname(__FILE__).'/');
define('STAGESHOW_DIR_NAME', basename(STAGESHOW_FILE_PATH));
define('STAGESHOW_ADMIN_PATH', STAGESHOW_FILE_PATH . '/admin/');
define('STAGESHOW_INCLUDE_PATH', STAGESHOW_FILE_PATH . '/include/');
define('STAGESHOW_ADMINICON_PATH', STAGESHOW_ADMIN_PATH . 'images/');
define('STAGESHOW_TEST_PATH', STAGESHOW_FILE_PATH . '/test/');

define('STAGESHOW_DEFAULT_TEMPLATES_PATH', STAGESHOW_FILE_PATH . 'templates/');
define('STAGESHOW_LANG_RELPATH', STAGESHOW_FOLDER . '/lang/');

if (!defined('STAGESHOW_SHORTCODE_PREFIX'))
	define('STAGESHOW_SHORTCODE_PREFIX', 'sshow');

define('STAGESHOW_OPTIONS_NAME', 'stageshowsettings');

define('STAGESHOW_DEFAULT_SETUPUSER', 'administrator');

define('STAGESHOW_CAPABILITY_VALIDATEUSER', 'StageShow_Validate');
define('STAGESHOW_CAPABILITY_SALESUSER', 'StageShow_Sales');			// A user that can view and edit sales
define('STAGESHOW_CAPABILITY_ADMINUSER', 'StageShow_Admin');			// A user that can edit shows, performances
define('STAGESHOW_CAPABILITY_SETUPUSER', 'StageShow_Setup');			// A user that can edit stageshow settings
define('STAGESHOW_CAPABILITY_DEVUSER', 'StageShow_Testing');			// A user that can use test pages

if (!defined('STAGESHOW_CODE_PREFIX'))
	define('STAGESHOW_CODE_PREFIX', 'stageshow');

define('STAGESHOW_MENUPAGE_ADMINMENU', STAGESHOW_CODE_PREFIX.'_adminmenu');
define('STAGESHOW_MENUPAGE_OVERVIEW', STAGESHOW_CODE_PREFIX.'_overview');
define('STAGESHOW_MENUPAGE_SHOWS', STAGESHOW_CODE_PREFIX.'_shows');
define('STAGESHOW_MENUPAGE_PERFORMANCES', STAGESHOW_CODE_PREFIX.'_performances');
define('STAGESHOW_MENUPAGE_PRICES', STAGESHOW_CODE_PREFIX.'_prices');
define('STAGESHOW_MENUPAGE_PRICEPLANS', STAGESHOW_CODE_PREFIX.'_priceplans');
define('STAGESHOW_MENUPAGE_SALES', STAGESHOW_CODE_PREFIX.'_sales');
define('STAGESHOW_MENUPAGE_BUTTONS', STAGESHOW_CODE_PREFIX.'_buttons');
define('STAGESHOW_MENUPAGE_SETTINGS', STAGESHOW_CODE_PREFIX.'_settings');
define('STAGESHOW_MENUPAGE_TOOLS', STAGESHOW_CODE_PREFIX.'_tools');
define('STAGESHOW_MENUPAGE_TEST', STAGESHOW_CODE_PREFIX.'_test');
define('STAGESHOW_MENUPAGE_DEBUG', STAGESHOW_CODE_PREFIX.'_debug');
define('STAGESHOW_MENUPAGE_TESTSETTINGS', STAGESHOW_CODE_PREFIX.'_testsettings');

define('STAGESHOW_FILEPATH_TEXTLEN',255);
define('STAGESHOW_FILEPATH_EDITLEN', 95);

define('STAGESHOW_URL_TEXTLEN',110);
	
if (!defined('STAGESHOW_NEWS_UPDATE_INTERVAL'))
	define('STAGESHOW_NEWS_UPDATE_INTERVAL', 10);

if (!defined('STAGESHOW_MAXTICKETCOUNT'))
	define('STAGESHOW_MAXTICKETCOUNT', 4);

if (!defined('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH', 'stageshow_EMail.php');

if (!defined('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH', 'stageshow_SaleTimeoutEMail.php');

?>