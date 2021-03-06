<?php

/*
Description: Gateway Callback Functions

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

if (!defined('DB_NAME'))
{
	// Include wp-config.php - This will include wp settings and plugins ...
	$rootPath = __FILE__;
	$folder = 'wp-content';
	$index = strpos($rootPath, $folder);
	$rootPath = substr($rootPath, 0, $index);
	
	include $rootPath.'/wp-config.php';	
}

include 'stageshowlib_logfile.php';
			      
if (!class_exists('StageShowLibGatewayCallbackClass')) 
{
	if (!defined('STAGESHOWLIB_FILENAME_LASTGATEWAYCALL'))
		define('STAGESHOWLIB_FILENAME_LASTGATEWAYCALL', 'LastGatewayCall.txt');
		
	if (!defined('STAGESHOWLIB_FILENAME_GATEWAYNOTIFY'))
		define('STAGESHOWLIB_FILENAME_GATEWAYNOTIFY', 'GatewayNotify.txt');
		
	if (!defined('STAGESHOWLIB_GATEWAYCALLBACK_VERIFYRETRIES'))
		define('STAGESHOWLIB_GATEWAYCALLBACK_VERIFYRETRIES', 4);
		
	class StageShowLibGatewayCallbackClass // Define class
	{
	    // Class variables:
	    var		$notifyDBaseObj;			//  Database access Object
    	var		$charset = 'windows-1252';

		function __construct($targetDBaseClass, $callerPath)
		{
			$this->notifyDBaseObj = new $targetDBaseClass($callerPath);
			
			$this->emailSent = false;			
			$this->HTTPError = false;			
			$this->ourOptions = $this->notifyDBaseObj->adminOptions;

	  		// FUNCTIONALITY: IPN Notify - Logs Folder uses ABSPATH if no ':' is included
			$this->LogsFolder = $this->ourOptions['LogsFolderPath'].'/';
			if (!strpos($this->LogsFolder, ':'))
				$this->LogsFolder = ABSPATH . $this->LogsFolder;
				
			$this->LogMessage = '';

			if (defined('CORONDECK_RUNASDEMO'))
			{
				$this->displayIPNs = true;
				$this->skipIPNServer = true;
			}
			else
			{
				$this->displayIPNs   = $this->notifyDBaseObj->isDbgOptionSet('Dev_IPNDisplay');
				$this->skipIPNServer = $this->notifyDBaseObj->isDbgOptionSet('Dev_IPNSkipServer');
			}
			
			$this->charset = $this->QueryParam('charset', 'windows-1252');

			$this->DoCallback();
			
			if ($this->notifyDBaseObj->isDbgOptionSet('Dev_IPNLogRequests'))
			{
				$this->LogDebugToFile(STAGESHOWLIB_FILENAME_GATEWAYNOTIFY, $this->LogMessage);
			}

			if (!$this->emailSent || $this->HTTPError)
			{
				$this->GatewayDiagnosticEMail("Payment Verification HTTP Error", $this->LogMessage);
			}							
		}
		
		function DoCallback()
		{
			// Default Callback Function ... only run if not defined is parent class
			$LogMessage = '';
			foreach ($_REQUEST as $reqKey => $reqValue)
			{
				$LogMessage .= "$reqKey: $reqValue \n";
			}
			
			$fileName = get_class($this).STAGESHOWLIB_FILENAME_GATEWAYNOTIFY;
			$logFileObj = new StageShowLibLogFileClass($this->LogsFolder);
			$logFileObj->LogToFile($fileName, $LogMessage, StageShowLibDBaseClass::ForAppending);
			
			echo str_replace("\n", "<br>\n", $LogMessage);
		}
		
		function VerifyGatewayCallback($VerifyURL, $pfParamString)
		{
			$retries = 1;
			$maxRetries = STAGESHOWLIB_GATEWAYCALLBACK_VERIFYRETRIES;
			do
			{
				$gatewayResponse = $this->notifyDBaseObj->HTTPPost($VerifyURL, $pfParamString);					
				$HTTPStatusMsg = "Gateway Response ($retries/$maxRetries): Status=".$gatewayResponse['APIStatus']." (".$gatewayResponse['APIStatusMsg'].")";
				$this->AddToLog($HTTPStatusMsg);
				
				if ($gatewayResponse['APIStatus'] == 200 )
				{
					break;
				}
				
				$this->HTTPError = true;
				
				$retries++;
			}
			while ($retries <= $maxRetries);
			
			return $gatewayResponse;
		}
		
		function GatewayErrorEMail($subject, $LogMessage)
		{
			$to = $this->notifyDBaseObj->getOption('AdminEMail');	
			$this->GatewayEMail($subject, $LogMessage, $to);		
		}
		
		function GatewayDiagnosticEMail($subject, $LogMessage)
		{
			$to = $this->notifyDBaseObj->getDbgOption('Dev_GatewayEMailAlerts');	
			$this->GatewayEMail($subject, $LogMessage, $to);					
		}
		
		function GatewayEMail($subject, $LogMessage, $to = '')
		{
			$from = $to;
			if ($to != '')
			{
				$headers  = "From: $from";	
				$headers .= "\r\nReply-To: $from";	
										
				$orgId = $this->ourOptions['OrganisationID'];
				$subject .= " ($orgId)";
				
				//send the email
				wp_mail($to, $subject, $LogMessage, $headers);
			}		
		}
		
		function GetQueryString()
		{
			// If this was a POST call ... get the QUERY_STRING from the POST request
			if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST'))
			{
				// Strip "slashes" from escaped POST data 
				//   Note: Wordpress adds escapes even if magic_quotes is OFF - see call to add_magic_quotes($_POST) in load.php
				$req = stripslashes_deep($_POST);
			}
			else
			{
				$req = '';
				if (array_key_exists ('QUERY_STRING', $_SERVER))
				{
					parse_str($_SERVER['QUERY_STRING'], $req);
				}
			}
			//print_r($req);
			return $req;
		}

		function LogDebugToFile($LogNotifyFile, $DebugMessage)
		{
			$logFileObj = new StageShowLibLogFileClass($this->LogsFolder);
			$logFileObj->LogToFile($LogNotifyFile, $DebugMessage, StageShowLibDBaseClass::ForAppending);
		}

		function AddToLog($LogLine)
		{
			if ($this->displayIPNs)
			{
	  			// FUNCTIONALITY: IPN Notify - Log IPN Messages to Screen if Dev_IPNDisplay set
				echo "$LogLine<br>\n";
			}
		  
			if ($this->notifyDBaseObj->isDbgOptionSet('Dev_IPNLogRequests'))
			{
	  			// FUNCTIONALITY: IPN Notify - Log IPN Messages to file if Dev_IPNLogRequests set
				$this->LogMessage .= $LogLine . "\n";
			}
		}

		function QueryParam($paramId, $default = '')
		{
			if (isset($_GET[$paramId]))
				$HTTPParam = $_GET[$paramId];	
			elseif (isset($_POST[$paramId]))
				$HTTPParam = $_POST[$paramId];	
			else
				return $default;
				
			return $HTTPParam;
		}

		function HTTPParam($paramId)
		{
			$HTTPParam = $this->QueryParam($paramId);
			if (strlen($HTTPParam) > 0)
				$HTTPParam = urldecode($HTTPParam);
			
			// Convert from IPN Charset to UTF-8
			return iconv($this->charset, "UTF-8", $HTTPParam);
		}

	}
}

?>
