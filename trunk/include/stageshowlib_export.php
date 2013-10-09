<?php
/* 
Description: Core Library OFX Export functions
 
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

if (!class_exists('StageShowLibExportAdminClass')) 
{
	
	class StageShowLibExportAdminClass // Define class
	{
		var $myDBaseObj;
		var $fieldNames;
		
		var $filename;
		var $fileExtn = 'txt';
		
		function __construct($myDBaseObj) //constructor	
		{
			$this->myDBaseObj = $myDBaseObj;
			$this->myDomain = $this->myDBaseObj->get_domain();
			
			$this->filename = $this->myDomain;
		}
			
		function GetFields()
		{
			return array();
		}
			
		function header($content)
		{
			if ( $this->myDBaseObj->isOptionSet('Dev_ShowSQL')
				|| $this->myDBaseObj->isOptionSet('Dev_ShowDBOutput') )
				echo $content."<br>\n";
			else
				header($content);				
		}
		
		function output_downloadHeader($application, $charset = 'utf-8')
		{
			$this->header( 'Content-Description: File Transfer' );
			$this->header( 'Content-Disposition: attachment; filename=' . $this->filename.'.'. $this->fileExtn );
			$this->header( "Content-Type: $application; charset=$charset" );	
		}

		function exportDB($dbList, $exportHTML = false)
		{
			$header = '';
			
			foreach($dbList as $dbEntry)
			{
				foreach ($dbEntry as $key => $option)
				{
					if (!$exportHTML && isset($this->fieldNames[$key]))
						$header .= $this->fieldNames[$key];
					else
						$header .= $key;
					$header .= "\t";
				}
				if ($exportHTML) 
				{
					$header = '"'.$header.'",';
				}
				$header .= "\n";
				break;
			}
			
			$line = '';
			foreach($dbList as $dbEntry)
			{
				if ($exportHTML) 
					$line .= '"';
				
				foreach ($dbEntry as $key => $option)
				{
					$option = str_replace("\r\n",",",$option);	
					$option = str_replace("\r",",",$option);	
					$option = str_replace("\n",",",$option);	// Remove any CRs in the db entry ... i.e. in Address Fields
					
					$line .= "$option\t";
				}
				
				if ($exportHTML) 
					$line .= '",';
				$line .= "\n";
			}
			
			echo $header.$line;
		}


	}
}

?>
