<?php
/* 
Description: Log File Utilities
 
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

if (!class_exists('MJSLibLogFileClass')) 
{
	class MJSLibLogFileClass // Define class
	{
		const ForReading = 1;
		const ForWriting = 2;
		const ForAppending = 8;

		var	$LogsFolderPath;
		
		function __construct($LogsFolderPath)
		{
			$this->LogsFolderPath = $LogsFolderPath;
		}

		function GetLogFilePath($Filepath)
		{
			$Filepath = str_replace("\\", "/", $Filepath);			
			if (strpos($Filepath, "/") === false)
			{
				$Filepath = $this->LogsFolderPath . "/" . $Filepath;
			}
			$Filepath = str_replace("//", "/", $Filepath);
			
			return $Filepath;			
		}

		function LogToFile($Filepath, $LogLine, $OpenMode = self::ForAppending, $LogHeader = '')
		{
			$Filepath = $this->GetLogFilePath($Filepath);			
			return self::LogToFileAbs($Filepath, $LogLine, $OpenMode, $LogHeader);			
		}

		static function LogToFileAbs($Filepath, $LogLine, $OpenMode = self::ForAppending, $LogHeader = '')
		{
			//echo "$Filepath<br>\n";
			
			// Create a filesystem object
			if ($OpenMode == self::ForAppending)
			{
				$fopenMode = "ab";
			}
			else
			{
				$fopenMode = "wb";
			}
			$logFile = fopen($Filepath, $fopenMode);

			// Write log entry
			if ($logFile != 0)
			{
				//echo "Open Mode: $fopenMode<br>\n";
				//$LogLine = "Open Mode: $fopenMode\n" . $LogLine;
				if ($LogHeader !== '')
				{
					fseek($logFile, 0, SEEK_END);
					if (ftell($logFile)	=== 0)				
						fwrite($logFile, $LogHeader, strlen($LogHeader));
				}
									
				fwrite($logFile, $LogLine, strlen($LogLine));
				fclose($logFile);

				$rtnStatus = true;
			}
			else
			{
				echo "Error writing to $Filepath<br>\n";
				//echo "Error was $php_errormsg<br>\n";
				$rtnStatus = false;
			}

			return $rtnStatus;
		}
		
		function DumpToFile($Filepath, $dataId, $dataToDump)
		{
			$Filepath = $this->GetLogFilePath($Filepath);	
					
			if (is_array($dataToDump))
			{
				$arrayData = '';
				foreach($dataToDump as $key => $value)
					$arrayData .= "[$key]".$value;
				$dataToDump = $arrayData;
			}
			
			$dataLen = strlen($dataToDump);
			
			$dumpOutput = $dataId."\n";
			for ($i=0;;$i++)
			{
				if (($i % 16) == 0)
				{
					if ($i > $dataLen) break;
					$hexOutput = sprintf("%04x ", $i);
					$asciiOutput = " ";
				}
				
				$nextChar = substr($dataToDump, $i, 1);
				if ($i < $dataLen)
				{
					$hexOutput .= sprintf("%02x ", ord($nextChar));
					if ((ord($nextChar) >= 0x20) && (ord($nextChar) <= 0x7f))
						$asciiOutput .= $nextChar;
					else
						$asciiOutput .= ".";
				}
				else
				{
					$hexOutput .= "   ";
					$asciiOutput .= " ";
				}

				if (($i % 16) == 15)
					$dumpOutput .= $hexOutput.$asciiOutput."\n";
			}				
			
			$this->LogToFileAbs($Filepath, $dumpOutput);
		}
		
		function AddToTestLog($LogLine)			
		{
			$Filepath = "testlog.txt";
			
			self::LogToFileAbs($Filepath, "------------------------------------------------\n");
			self::LogToFileAbs($Filepath, 'Log Time/Date:'.date("Y-m-d H:i:s")."\n");
			//self::LogToFileAbs($Filepath, 'Request URL:'.$_SERVER['REQUEST_URI']."\n");
			self::LogToFileAbs($Filepath, self::ShowCallStack(false));
			self::LogToFileAbs($Filepath, $LogLine."\n");
		}
		
	}
}

?>