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
ini_set( 'error_reporting', E_STRICT );
error_reporting(E_ALL);
ini_set("display_errors", 1);
*/
include 'stageshow_nowp_defs.php';

global $wpdb;
$wpdb->prefix = isset($table_prefix) ? $table_prefix : 'wp_';

include STAGESHOW_FILE_PATH.STAGESHOW_FOLDER.'_trolley.php';

include 'stageshowlib_jquery_trolley.php';

if (!class_exists('StageShowJQueryTrolley')) 
{
	class StageShowJQueryTrolley extends StageShowLibJQueryTrolley
	{
	}
	
	new StageShowJQueryTrolley(STAGESHOW_PLUGIN_NAME.'CartPluginClass');
}

?>