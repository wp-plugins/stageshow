<?php
/* 
Description: Generic Plugin Uninstaller
 
Copyright 2012 Malcolm Shergold

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

$pluginPath = dirname(__FILE__);
$pluginID = basename($pluginPath);

if (file_exists($pluginPath.MJSLIB_UNINSTALL_TESTCODE))
{
	include $pluginPath.MJSLIB_UNINSTALL_TESTCODE;
}
	
$uninstallerPath = $pluginID.'_uninstall.php';
include $uninstallerPath;

?>