<?php
/* 
Description: Code for TBD
 
Copyright 2014 Malcolm Shergold

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if(!isset($_SESSION)) 
{
	// Must be Registered to use SESSIONS 
	session_start();
}	

/*
include 'stageshow_nowp_defs.php';

global $wpdb;
$wpdb->prefix = isset($table_prefix) ? $table_prefix : 'wp_';

if (!defined('StageShowLibAdminClass'))
{
	class StageShowLibAdminClass
	{
		var $myDomain = 'stageshow';
	
		function __construct($env, $inForm = false) //constructor	
		{
			$this->env = $env;
		}
		
		function WPNonceField($referer = '', $name = '_wpnonce', $echo = true)
		{
			//$this->myDBaseObj->WPNonceField($referer, $name, $echo);
		}

	}
}
*/

include '../../../../wp-config.php';

include STAGESHOW_INCLUDE_PATH.STAGESHOW_FOLDER.'_dbase_api.php';      
include STAGESHOW_INCLUDE_PATH.'stageshowlib_httpio.php';      
include STAGESHOW_INCLUDE_PATH.'stageshowlib_nonce.php';      

//StageShowLibUtilsClass::print_r($_POST, '$_POST');
$DBClass = STAGESHOW_PLUGIN_NAME.'DBaseClass';
$env = array();
$env['caller'] = __FILE__;
$env['PluginObj'] = null;
$env['DBaseObj'] = new $DBClass(__FILE__);
$env['Domain'] = 'stageshow';

$targetFile = basename(__FILE__);
$callerNOnce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : "";	
$ourNOnce = StageShowLibNonce::GetStageShowLibNonce($targetFile);
if ($callerNOnce != $ourNOnce)
{
	echo "Authorisation Failed";
	//if (current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN))
	{
		echo "<br>ourNOnce: $ourNOnce<br>";
	}
	exit;
}
	
if (!defined('StageShowSendEMailJqueryClass'))
{
	class StageShowSendEMailJqueryClass
	{
		function SendEMailRequest()
		{
			if (!$this->SendEMail())
			{
				echo "Send EMail - FAILED!";
			}
			
			die;
		}
				
		function SendEMail()
		{
			$myDBaseObj = $this->myDBaseObj;
				 
			if (!isset($_REQUEST['saleID']))
				return false;
				
			if (!isset($_REQUEST['saleEMail']))
				return false;
			
			if (!isset($_REQUEST['saleTxnId']))
				return false;
			
			$saleID = StageShowLibHTTPIO::GetRequestedInt('saleID');
			$saleEMail = StageShowLibHTTPIO::GetRequestedString('saleEMail');
			$saleTxnId = StageShowLibHTTPIO::GetRequestedString('saleTxnId');
			
			$salesList = $myDBaseObj->GetSale($saleID);
			$saleDetails = $salesList[0];

			if ($saleID != $saleDetails->saleID)
				return false;
			
			if ($saleEMail != $saleDetails->saleEMail)
				return false;
			
			if ($saleTxnId != $saleDetails->saleTxnId)
				return false;
			
			$myDBaseObj->EMailSaleRecord($salesList);			
			echo __('Confirmation EMail sent to', $myDBaseObj->get_domain())." $saleEMail";
			
			return true;
		}
	}
}			

//$SaleClass = STAGESHOW_PLUGIN_NAME.'SaleValidateClass';
$SaleClass = 'StageShowSendEMailJqueryClass';
$valObj = new $SaleClass($env);
$valObj->myDBaseObj = $env['DBaseObj'];
$valObj->SendEMailRequest();

?>