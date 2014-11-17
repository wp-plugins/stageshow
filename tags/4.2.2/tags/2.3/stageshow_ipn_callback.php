<?php

define('IPN_CALLBACK', 'STAGESHOW_VARIANT');

if (!defined('STAGESHOW_DBASE_CLASS'))
{
	// Include wp-config.php - This will include wp settings and plugins ...
	include '../../../wp-config.php';	
}

include 'include/stageshowlib_ipn_callback.php';

new IPNNotifyClass(STAGESHOW_DBASE_CLASS, __FILE__);	

?>