<?php
/* 
Description: Log File Utilities
 
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

if (!class_exists('StageShowLibLogFileClass')) 
{
	class StageShowLibLogFileClass // Define class
	{
		const ForReading = 1;
		const ForWriting = 2;
		const ForAppending = 8;

		var	$LogsFolderPath;
		
		function __construct($LogsFolderPath = '')
		{
			$this->LogsFolderPath = $LogsFolderPath;
		}

		function GetLogFilePath($Filename)
		{
			if (defined('ABSPATH'))
			{
				$absRoot = str_replace("\\", "/", ABSPATH);
			}
			else
			{
				$absRoot = str_replace("\\", "/", __FILE__);
				$endPath = strpos($absRoot, '/wp-content');
				$absRoot = substr($absRoot, 0, $endPath+1);
			}

			$Filepath = str_replace("\\", "/", $Filename);
			if (substr($Filepath, 0, strlen($absRoot)) != $absRoot)
			{
				// Add the "default" logs path folder
				$Filepath = $this->LogsFolderPath.'/'.$Filepath;
				$Filepath = str_replace("\\", "/", $Filepath);
				$Filepath = str_replace("//", "/", $Filepath);
				if (substr($Filepath, 0, strlen($absRoot)) != $absRoot)
				{
					// Add the base folder of the site
					$Filepath = $absRoot.$Filepath;
					$Filepath = str_replace("\\", "/", $Filepath);
					$Filepath = str_replace("//", "/", $Filepath);
				}
			}
						
			return $Filepath;			
		}

		function StampedLogToFile($Filename, $LogLine, $OpenMode = self::ForAppending, $LogHeader = '')
		{			
			$LogStamp  = 'Log Timestamp: '.date(DATE_RFC822)."\n";
			$LogStamp .= 'Content Length: '.strlen($LogLine)."\n";
			$LogStamp .= "Content: \n";
			
			$LogLine  = $LogStamp.$LogLine;
			$LogLine .= "\n---------------------------------------------\n\n";
		
			$this->LogToFile($Filename, $LogLine, $OpenMode, $LogHeader);			
		}
		
		function LogToFile($Filename, $LogLine, $OpenMode = self::ForAppending, $LogHeader = '')
		{
			$Filepath = $this->GetLogFilePath($Filename);			
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
		
		function DumpToFile($Filename, $dataId, $dataToDump)
		{
			$Filepath = $this->GetLogFilePath($Filename);	
					
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
			self::LogToFileAbs($Filepath, 'Log Time/Date:'.date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT)."\n");
			//self::LogToFileAbs($Filepath, 'Request URL:'.$_SERVER['REQUEST_URI']."\n");
			self::LogToFileAbs($Filepath, self::ShowCallStack(false));
			self::LogToFileAbs($Filepath, $LogLine."\n");
		}
		
	}
}

?>