<?php
/* 
Description: Code for Data Export functionality
 
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

include '../../../../wp-config.php';
  
$stageShowDBaseClass = STAGESHOW_DBASE_CLASS;

if ( isset( $_GET['download'] ) )
{
	switch ( $_GET['export_format'] )
	{
		case 'tdt':
			include 'stageshow_tdt_export.php';      
			new StageShowTDTExportAdminClass(new $stageShowDBaseClass(__FILE__));
			break;
	}
} 


?>
