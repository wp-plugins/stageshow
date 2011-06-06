<?php
/* 
Description: Code for Data Export functionality
 
Copyright 2011 Malcolm Shergold

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
  
  // Get the value of the "Delete Orphans" option and save it
	$stageShowDBaseObj->adminOptions['DeleteOrphans'] = (isset($_GET['sshow_delete_orphans']) && ($_GET['sshow_delete_orphans'] == 1));
	$stageShowDBaseObj->saveOptions();
	
	if ( isset( $_GET['download'] ) ) 
	{
		$filename = 'stageshow.txt';
		
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/plain; charset=utf-8' );		

    switch ($_GET['sshow_ex_type'])
		{          
      case 'settings':
					export_shows();
          break;
          
      case 'tickets':
					export_tickets();
          break;          

			case 'all':
			default :
					export_shows();
					export_tickets();
          break;
    }
       
		die();
	}
	
function export_sshow($dbList)
{
	$header = '';
	$line = '';
	
	foreach($dbList as $dbEntry)
	{
		$header = '';
		
		foreach ($dbEntry as $key => $option)
		{
			$option = str_replace("\r\n",",",$option);	
			$option = str_replace("\r",",",$option);	
			$option = str_replace("\n",",",$option);	// Remove any CRs in the db entry ... i.e. in Address Fields
			
			$header .= "$key\t";
			$line .= "$option\t";
		}
		$header .= "\n";
		$line .= "\n";
		}
	
	echo $header.$line;
}

function export_shows()
{
	global $stageShowDBaseObj;
				
	export_sshow($stageShowDBaseObj->GetSettings());
}

function export_tickets()
{
	global $stageShowDBaseObj;
	
	export_sshow($stageShowDBaseObj->GetAllTicketsList());
}

?>
