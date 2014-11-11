<?php
/* 
Description: Code for Uninstalling StageShow
 
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
	
if (!defined('WP_UNINSTALL_PLUGIN')) 
{
	echo "Access Denied.";
	die;
}

include 'stageshow_defs.php';      

include STAGESHOW_INCLUDE_PATH.'stageshow_dbase_api.php';      

$stageShowDBaseClass = STAGESHOW_DBASE_CLASS;
$myDBaseObj = new $stageShowDBaseClass(__FILE__);

$myDBaseObj->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);

$myDBaseObj->uninstall();

// FUNCTIONALITY: Uninstall - Delete StageShow entries from Wordpress options 
delete_option($myDBaseObj->StageshowOptionsName);
delete_option($myDBaseObj->StageshowDbgoptionsName);

?>