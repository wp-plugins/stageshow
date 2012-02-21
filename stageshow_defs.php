<?php

$siteurl = get_option('siteurl');
define('STAGESHOW_FOLDER', dirname(plugin_basename(__FILE__)));
define('STAGESHOW_URL', $siteurl.'/wp-content/plugins/' . STAGESHOW_FOLDER .'/');
define('STAGESHOW_IMAGES_URL', STAGESHOW_URL . 'images/');
define('STAGESHOW_ADMIN_URL', STAGESHOW_URL . 'admin/');
define('STAGESHOW_ADMIN_IMAGES_URL', STAGESHOW_ADMIN_URL . 'images/');

if (!defined('STAGESHOW_STYLESHEET_URL'))
	define('STAGESHOW_STYLESHEET_URL', STAGESHOW_URL.'css/stageshow.css');

define('STAGESHOW_FILE_PATH', dirname(STAGESHOW_PLUGIN_FILE).'/');
define('STAGESHOW_DIR_NAME', basename(STAGESHOW_FILE_PATH));
define('STAGESHOW_ADMIN_PATH', STAGESHOW_FILE_PATH . '/admin/');
define('STAGESHOW_INCLUDE_PATH', STAGESHOW_FILE_PATH . '/include/');
define('STAGESHOW_ADMINICON_PATH', STAGESHOW_ADMIN_PATH . 'images/');

define('STAGESHOW_CODE_PREFIX', 'stageshow');
define('STAGESHOW_DOMAIN_NAME', 'stageshow');
define('STAGESHOW_SHORTCODE_PREFIX', 'sshow');

define('STAGESHOW_OPTIONS_NAME', 'stageshowsettings');

define('STAGESHOW_DEFAULT_SETUPUSER', 'administrator');

define('STAGESHOW_VALIDATEUSER_ROLE', 'StageShow_Validate');
define('STAGESHOW_SALESUSER_ROLE', 'StageShow_Sales');
define('STAGESHOW_ADMINUSER_ROLE', 'StageShow_Admin');
define('STAGESHOW_SETUPUSER_ROLE', 'StageShow_Setup');
define('STAGESHOW_DEVUSER_ROLE', 'StageShow_Testing');

define('STAGESHOW_SHORTNOTE_TEXTLEN', 60);
define('STAGESHOW_NOTE_COLCOUNT', 60);
define('STAGESHOW_NOTE_ROWCOUNT', 4);

if (!defined('STAGESHOW_NEWS_UPDATE_INTERVAL'))
	define('STAGESHOW_NEWS_UPDATE_INTERVAL', 10);

if (!defined('PAYPAL_APILIB_OPTIONS_NAME'))
	define('PAYPAL_APILIB_OPTIONS_NAME', STAGESHOW_OPTIONS_NAME);

?>