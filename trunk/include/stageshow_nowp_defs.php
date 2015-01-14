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

include "stageshowlib_nowp_funcs.php";

global $siteurl;
$siteurl = ( is_ssl() ? 'https://' : 'http://' ).$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$endURL = strpos($siteurl, '/wp-content');
if ($endURL == false)
{
	$endURL = strrpos($siteurl, '/');
}
$siteurl = substr($siteurl, 0, $endURL);

include '../stageshow_defs.php';
include STAGESHOW_UPLOADS_PATH.'/wp-config-db.php';			
include "stageshowlib_nowp_dbase.php";


?>