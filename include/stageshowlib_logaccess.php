<?php

function StageShowLib_ITNTestLogging($filename)
{
	$IPNRxdMsg  = 'ITNReq at ' . date(DATE_RFC822) . "\n";
	$IPNRxdMsg .= 'Params: ' . print_r($_REQUEST, true) . "\n";

	$IPNRxdMsg  = str_replace("\n", "<br>\n", $IPNRxdMsg);

	StageShowLib_ITNTestLogToFile($filename, $IPNRxdMsg);
}

function StageShowLib_ITNTestLogToFile($filename, $IPNRxdMsg)
{
	$logFilePath = __FILE__;
	$logFilePathLen = strpos($logFilePath, 'wp-content');
	$logFilePath = substr($logFilePath, 0, $logFilePathLen).'wp-content/uploads/logs';

$perms = fileperms($logFilePath);
//echo "perms: ".sprintf('0%o', $perms)." <br>\n";
if (($perms & 0077) != 0)	
{
	$reqPerms = 0700;
	$rtnVal = chmod($logFilePath, $reqPerms);
//	echo "perms Updated To: ".sprintf('0%o', $reqPerms)." <br>\n";
//	echo "rtnVal: $rtnVal <br>\n";
}

	$logFilePath .= '/'.$filename.'_'.date('Ymd').'.log';
	
	$IPNRxdMsg .= 'logFilePath: ' . $logFilePath . "<br>\n";
	$IPNRxdMsg .= "\n";

	$logFile = fopen($logFilePath, 'ab');
	
	fwrite($logFile, $IPNRxdMsg, strlen($IPNRxdMsg));
	fclose($logFile);
	
// echo $IPNRxdMsg;	
}

StageShowLib_ITNTestLogging('ITNCalls');

?>
