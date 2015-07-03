<?php
/* 
Description: Code for Development Testing
 
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

$folder = dirname(dirname(__FILE__));
include $folder.'/include/stageshowlib_gateway_callback.php';

if (!class_exists('StageShowLib_Test_logs')) 
{
	class StageShowLib_Test_logs extends StageShowLibTestBaseClass // Define class
	{
		function __construct($env) //constructor	
		{
			parent::__construct($env);
		}
		
		function Show()
		{			
			$this->Test_LogsReport();
		}
		
		static function GetOrder()
		{
			return 1;	// Determines order tests are output
		}
		
		function ShowLog($name, $id, $fileName)
		{
			$showId = 'ShowLog_'.$id;
			$clearId = 'ClearLog_'.$id;

			if (!isset($_POST[$showId]) && !isset($_POST[$clearId])) 
				return;
			
			$filePath = $this->LogsFolder.$fileName;
			if (!file_exists($filePath))
			{
				echo "Log file does not exist ($fileName)<br>";
				return;
			}
			
			if (isset($_POST[$showId]))
			{
				$logText = $this->myDBaseObj->ReadTemplateFile($filePath);			
				
				$logText = htmlspecialchars($logText);
				$logText = str_replace("\n", "<br>\n", $logText);
				
				echo "<h2>$name</h2>";
				echo $logText;				
				echo '<input class="button-primary" type="submit" name="'.$clearId.'" value="Clear '.$name.' Log" /><br><br>';
			}
			else
			{
				unlink($filePath);
				echo "Log File Cleared<br>\n";
			}
		}
		
		function Test_LogsReport()
		{
			$myDBaseObj = $this->myDBaseObj;

			echo '<h3>Test Logs</h3>';
					
			$this->LogsFolder = $myDBaseObj->adminOptions['LogsFolderPath'].'/';
			if (!strpos($this->LogsFolder, ':'))
				$this->LogsFolder = ABSPATH . $this->LogsFolder;
			
			$this->ShowLog('Last Call From Gateway', 'LastGatewayCall', STAGESHOWLIB_FILENAME_LASTGATEWAYCALL);
			$this->ShowLog('Gateway Notifications',  'Notify',          STAGESHOWLIB_FILENAME_GATEWAYNOTIFY);
			
?>
		<input class="button-primary" type="submit" name="ShowLog_Notify" value="Show Notify Log" />
		<input class="button-primary" type="submit" name="ShowLog_LastGatewayCall" value="Show Last Gateway Call Log" />
<?php		
		}
		

	}
}

?>