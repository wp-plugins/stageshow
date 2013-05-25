<?php
/* 
Description: Code for Managing Prices Configuration
 
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
	
add_filter('site_transient_update_plugins', 'dd_block_stageshowplus_update');

function dd_block_stageshowplus_update($value) 
{
	$stageshowplusFile = 'stageshow/stageshow-plus.php';
	$active_plugins = get_option( 'active_plugins', array() );

	if ( !in_array( $stageshowplusFile, (array) get_option( 'active_plugins', array() ) ) )
		unset($value->response[$stageshowplusFile]);
	
	return $value;
}

?>