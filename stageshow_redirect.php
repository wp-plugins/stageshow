<?php
/* 
Description: Redirect for PayPal Express Callback
 
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
	// MJS - SC Mod - Register to use SESSIONS
	session_start();
}	

// Pass PayPal Express callback Params in $_SESSION variable
$_SESSION['REDIRECTED_GET'] = serialize($_GET);
$url = urldecode($_GET['url']);

//echo "<br><br>Redirect Request: $url<br><br>\n";
Redirect($url, true);

function Redirect($url, $permanent = false)
{
    header('Location: ' . $url, true, $permanent ? 301 : 302);
    die;
}

?>