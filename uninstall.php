<?php

	if (!defined('WP_UNINSTALL_PLUGIN')) 
	{
		echo "Access Denied.";
		die;
	}

	define('STAGESHOW_PLUGIN_FILE', __FILE__);
	include 'stageshow_defs.php';      

	if ( file_exists(STAGESHOW_INCLUDE_PATH.'stageshow_extns.php') )
		include('include/stageshow_extns.php');

	if (!class_exists('StageShowDBaseClass'))
		include 'include/stageshow_dbase_api.php';      
      
	global $stageShowObj;
	$myDBaseObj = $stageShowObj->myDBaseObj;

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
	
	// Remove any StageShow entries from Wordpress options 
	update_option(STAGESHOW_OPTIONS_NAME, array());
	
	// Output debug message
	//$debugMsg = "Uninstall complete!\r\n";
	//$myDBaseObj->LogToFile(__DIR__.'\..\debug.log', $debugMsg, $myDBaseObj->ForAppending);
