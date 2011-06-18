<?php

if (!defined('WP_UNINSTALL_PLUGIN')) 
{
	echo "Access Denied.";
	die;
}

include 'stageshow_defs.php';      

include 'admin/stageshow_paypal_api.php';      
include 'admin/stageshow_dbase_api.php';      
      
if ( file_exists(STAGESHOW_ADMIN_PATH.'/stageshow_extns.php') )
{
	include(STAGESHOW_ADMIN_PATH.'/stageshow_extns.php');
}
			
	global $stageShowDBaseObj;
	//$stageShowDBaseObj = new StageShowDBaseClass();
	
	global $myPayPalAPILiveObj;
	global $myPayPalAPITestObj;
           
	$myPayPalAPITestObj->SetLoginParams(
				$stageShowDBaseObj->adminOptions['PayPalAPITestUser'], 
				$stageShowDBaseObj->adminOptions['PayPalAPITestPwd'], 
				$stageShowDBaseObj->adminOptions['PayPalAPITestSig']);
				
	$myPayPalAPILiveObj->SetLoginParams(
				$stageShowDBaseObj->adminOptions['PayPalAPILiveUser'], 
				$stageShowDBaseObj->adminOptions['PayPalAPILivePwd'], 
				$stageShowDBaseObj->adminOptions['PayPalAPILiveSig']);
           
  // Delete any PayPal Hosted buttons in the Performance Table
	$results = $stageShowDBaseObj->GetAllPerformancesList();
	foreach($results as $result)
	{
		$myPayPalAPITestObj->DeleteButton($result->perfPayPalTESTButtonID);
		$myPayPalAPILiveObj->DeleteButton($result->perfPayPalLIVEButtonID);
	}
      
	$stageShowDBaseObj->uninstall();
	
	// Remove any StageShow entries from Wordpress options 
	update_option(STAGESHOW_OPTIONS_NAME, array());
	
	// Output debug message
	//$debugMsg = "Uninstall complete!\r\n";
	//$stageShowDBaseObj->LogToFile(__DIR__.'\..\debug.log', $debugMsg, $stageShowDBaseObj->ForAppending);
