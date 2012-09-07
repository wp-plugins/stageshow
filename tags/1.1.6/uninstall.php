<?php

	if (!defined('WP_UNINSTALL_PLUGIN')) 
	{
		echo "Access Denied.";
		die;
	}

	define('STAGESHOW_PLUGIN_FILE', __FILE__);
	include 'stageshow_defs.php';      

	if ( file_exists(STAGESHOW_INCLUDE_PATH.'stageshowplus_dbase_api.php') )
		include STAGESHOW_INCLUDE_PATH.'stageshowplus_dbase_api.php';

	if (!class_exists('StageShowDBaseClass'))
		require_once STAGESHOW_INCLUDE_PATH.'stageshow_dbase_api.php';      
      
	$stageShowDBaseClass = STAGESHOW_DBASE_CLASS;
	$myDBaseObj = new $stageShowDBaseClass();

	$myDBaseObj->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);
           
  // Delete any PayPal Hosted buttons in the Performance Table
	$results = $myDBaseObj->GetAllPerformancesList();
	
	//echo "<br>Class of myDBaseObj is ".get_class($myDBaseObj)."<br>\n";
	//echo "<br>Performances:<br>\n"; print_r($results);
	//die;

	foreach($results as $result)
	{
		$myDBaseObj->payPalAPIObj->DeleteButton($result->perfPayPalButtonID);
	}
      
	$myDBaseObj->uninstall();
	
	// Remove templates and images folders in Uploads folder
	if (is_dir(STAGESHOW_UPLOADS_PATH))
		MJSLibUtilsClass::deleteDir(STAGESHOW_UPLOADS_PATH);
	
	// Remove any StageShow entries from Wordpress options 
	delete_option(STAGESHOW_OPTIONS_NAME);
	
	// Output debug message
	//$debugMsg .= "Uninstall complete!\r\n";
	//$myDBaseObj->LogToFile(__DIR__.'\..\debug.log', $debugMsg, $myDBaseObj->ForAppending);
