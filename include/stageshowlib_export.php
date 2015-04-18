<?php
/* 
Description: Core Library OFX Export functions
 
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

if (!class_exists('StageShowLibExportAdminClass')) 
{
	
	class StageShowLibExportAdminClass // Define class
	{
		var $myDBaseObj;
		var $fieldNames;
		
		var $fileName;
		var $fileExtn = 'txt';
		
		function __construct($myDBaseObj) //constructor	
		{
			$this->myDBaseObj = $myDBaseObj;
			$this->myDomain = $this->myDBaseObj->get_domain();
			
			$this->fileName = $this->myDomain;
			$this->DispositionExtras = "";	// was attachment;
			$myDBaseObj->CheckAdminReferer('stageshowlib_export.php');
		}
			
		function Export($application, $charset = 'utf-8', $content = '')
		{
			$this->output_downloadHeader($application, $charset);
			echo $content;
		}
			
		function GetFields()
		{
			return array();
		}
			
		function SelectFields($dbFields)
		{
			$fieldNames = $this->GetFields();
					
			if ($dbFields != '')
			{
				$validDbFields = explode(',', $dbFields);
				$ourFieldNames = array();				
				foreach ($validDbFields as $validDbField)
				{
					$ourFieldNames[$validDbField] = $fieldNames[$validDbField];
				}
				$fieldNames = $ourFieldNames;
			}
			
			return $fieldNames;
		}
			
		function DecodeField($fieldID, $fieldVal, $dbEntry)
		{
			return $fieldVal;
		}
			
		function header($content)
		{
			if ( $this->myDBaseObj->isDbgOptionSet('Dev_ShowSQL')
				|| $this->myDBaseObj->isDbgOptionSet('Dev_ShowDBOutput') )
				echo $content."<br>\n";
			else
				header($content);				
		}
		
		function output_downloadHeader($application, $charset = 'utf-8')
		{
			$this->header( 'Content-Description: File Transfer' );
			$this->header( 'Content-Disposition:'.$this->DispositionExtras.' filename=' . $this->fileName.'.'. $this->fileExtn );
			$this->header( "Content-Type: $application; charset=$charset" );	
		}

		function exportDB($dbList, $exportHTML = false)
		{
			$doneHeader = false;
			$header = '';
			$line = '';
			
			foreach($dbList as $dbEntry)
			{
				if ($exportHTML) 
					$line .= '"';
				
				foreach ($dbEntry as $key => $option)
				{
					if (!isset($this->fieldNames[$key]))
						continue;
						
					if ($this->fieldNames[$key] == '')
						continue;
								
					if (!$doneHeader)
					{
						if (!$exportHTML && isset($this->fieldNames[$key]))
						{
							if ($this->fieldNames[$key] == '')
								continue;
							$header .= $this->fieldNames[$key];
						}
						else
							$header .= $key;
						$header .= "\t";
					}
				
					$option = str_replace("\r\n",",",$option);	
					$option = str_replace("\r",",",$option);	
					$option = str_replace("\n",",",$option);	// Remove any CRs in the db entry ... i.e. in Address Fields
					
					$option = $this->DecodeField($key, $option, $dbEntry);
					$line .= "$option\t";
				}
				
				$doneHeader = true;
				
				if ($exportHTML) 
					$line .= '",';
				$line .= "\n";
			}
			
			if ($exportHTML) 
			{
				$header = '"'.$header.'",';
			}
			$header .= "\n";

			echo $header.$line;
		}


	}
}

?>
