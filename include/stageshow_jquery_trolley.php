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

/*
ini_set( 'error_reporting', E_STRICT );
error_reporting(E_ALL);
ini_set("display_errors", 1);
*/
include 'stageshow_nowp_defs.php';

global $wpdb;
$wpdb->prefix = isset($table_prefix) ? $table_prefix : 'wp_';

include STAGESHOW_FILE_PATH.STAGESHOW_FOLDER.'_trolley.php';

if (!class_exists('StageShowJQueryTrolley')) 
{
	include 'stageshowlib_nonce.php';
	
	class StageShowJQueryTrolley
	{
		function __construct()
		{
			$atts = array();
			
			$cartObjClassName = STAGESHOW_PLUGIN_NAME.'CartPluginClass';
			$cartObj = new $cartObjClassName(__FILE__);
			
			$myDBaseObj = $cartObj->myDBaseObj;
			if ($myDBaseObj->isDbgOptionSet('Dev_ShowGET'))
			{
				StageShowLibUtilsClass::print_r($_GET, '$_GET');
			}
			if ($myDBaseObj->isDbgOptionSet('Dev_ShowPOST'))
			{
				StageShowLibUtilsClass::print_r($_POST, '$_POST');
			}		
			if ($myDBaseObj->isDbgOptionSet('Dev_ShowSESSION'))
			{
				StageShowLibUtilsClass::print_r($_SESSION, '$_SESSION');
			}	
				
        	$ourNOnce = StageShowLibNonce::GetStageShowLibNonce(STAGESHOWLIB_UPDATETROLLEY_TARGET);
			if (!isset($_POST['_wpnonce']) || ($_POST['_wpnonce'] != $ourNOnce))
			{
				die;
			}		
			
			// Convert JQuery call parameters to original format params
			if (isset($_POST['buttonid']))			
			{
				$buttonId = $_POST['buttonid'];
				$_REQUEST[$buttonId] = $_POST[$buttonId] = 'submit';
				$qtyId = str_replace('AddTicketSale_', 'quantity_', $buttonId);
				$_REQUEST[$qtyId] = $_POST[$qtyId] = $_POST['qty'];
				
				//StageShowLibUtilsClass::print_r($_POST, '$_POST - Updated');
			}
			
			$atts = array();
			$scattMarker = 'scatt_';
			$scattMarkerLen = strlen($scattMarker);
			foreach ($_POST as $key => $val)
			{
				if (substr($key, 0, $scattMarkerLen) == $scattMarker)
				{
					$key = substr($key, $scattMarkerLen);
					$atts[$key] = $val;
				}
			}
			 		
			ob_start();
			//$trolleyContent = $this->Cart_OnlineStore_GetCheckoutDetails();	
			$hasActiveTrolley = $cartObj->Cart_OnlineStore_HandleTrolley();
			$trolleyContent = ob_get_contents();
			ob_end_clean();

			ob_start();
			$cartObj->Cart_OutputContent_OnlineStoreMain($atts);
			$boxofficeContent = ob_get_contents();
			ob_end_clean();		
			
			$outputContent = $boxofficeContent;		

			if ($myDBaseObj->getOption('ProductsAfterTrolley'))
			{
				$outputContent = $trolleyContent.$boxofficeContent;
			}
			else
			{
				$outputContent = $boxofficeContent.$trolleyContent;
			}
			
			echo $outputContent;
		}
	}
	
	new StageShowJQueryTrolley();
}

?>