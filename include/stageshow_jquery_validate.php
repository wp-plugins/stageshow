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

include 'stageshow_nowp_defs.php';

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

include STAGESHOW_INCLUDE_PATH.STAGESHOW_FOLDER.'_salevalidate.php'; 
include STAGESHOW_INCLUDE_PATH.STAGESHOW_FOLDER.'_validate_api.php';      
include STAGESHOW_INCLUDE_PATH.'stageshowlib_nonce.php';      

//StageShowLibUtilsClass::print_r($_POST, '$_POST');
$DBClass = STAGESHOW_PLUGIN_NAME.'ValidateDBaseClass';
$env = array();
$env['caller'] = __FILE__;
$env['PluginObj'] = null;
$env['DBaseObj'] = new $DBClass();
$env['Domain'] = 'stageshow';

$callerNOnce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : "";	
$ourNOnce = StageShowLibNonce::GetStageShowLibNonce(STAGESHOW_SALEVALIDATE_TARGET);
if ($callerNOnce != $ourNOnce)
{
	echo "Authorisation Failed";
	exit;
}
	
$SaleClass = STAGESHOW_PLUGIN_NAME.'SaleValidateClass';
$valObj = new $SaleClass($env);
$valObj->myDBaseObj = $env['DBaseObj'];
$valObj->ValidateSaleForm();

?>