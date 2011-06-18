<?php

$siteurl = get_option('siteurl');
define('STAGESHOW_PLUGINNAME', 'StageShow');
define('STAGESHOW_FOLDER', dirname(plugin_basename(__FILE__)));
define('STAGESHOW_URL', $siteurl.'/wp-content/plugins/' . STAGESHOW_FOLDER .'/');
define('STAGESHOW_IMAGES_URL', STAGESHOW_URL . 'images/');
define('STAGESHOW_ADMIN_URL', STAGESHOW_URL . 'admin/');
define('STAGESHOW_ADMIN_IMAGES_URL', STAGESHOW_ADMIN_URL . 'images/');

define('STAGESHOW_FILE_PATH', dirname(__FILE__).'/');
define('STAGESHOW_DIR_NAME', basename(STAGESHOW_FILE_PATH));
define('STAGESHOW_ADMIN_PATH', STAGESHOW_FILE_PATH . '/admin/');
define('STAGESHOW_ADMINICON_PATH', STAGESHOW_ADMIN_PATH . 'images/');

define('STAGESHOW_CODE_PREFIX', 'sshow');
define('STAGESHOW_DOMAIN_NAME', 'stageshow');

define('STAGESHOW_OPTIONS_NAME', 'stageshowsettings');

?>