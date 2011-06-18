<?php

// Include wp-config.php - This will include wp settings and plugins ...
include '../../../wp-config.php';

if (defined('PAYPAL_TEST_VERIFY_URL'))
	define ('STAGESHOW_PAYPAL_TEST_NVPTARGET_URL', PAYPAL_TEST_VERIFY_URL);
	
$LogNotifyFile = 'IPNNotify.txt';
$LogIPNCallFile = 'LastIPNCall.txt';

$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);

$LogsFolder = $ourOptions['LogsFolderPath'].'/';
if (!strpos($LogsFolder, ':'))
	$LogsFolder = ABSPATH . '/' . $LogsFolder;
	
$LogMessage = '';

if (defined('NOTIFYURL_CALLER'))
{
	AddToLog("IPN Request Called: " . NOTIFYURL_CALLER);
}

function GetQueryString()
{
	// If this was a POST call ... get the QUERY_STRING from the POST request
	if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST'))
	{
		$req = $_POST;
	}
	else
	{
		$req = '';
		if (array_key_exists ('QUERY_STRING', $_SERVER))
		{
			$req = $_SERVER['QUERY_STRING'];
			parse_str($req, $_POST);
		}
	}
	//print_r($req);
	return $req;
}

function LogDebugToFile($DebugMessage)
{
	global $stageShowDBaseObj;
  global $LogsFolder;
  global $LogNotifyFile;
  //global $ForAppending;
	
  $stageShowDBaseObj->LogToFile($LogsFolder . $LogNotifyFile, $DebugMessage, $stageShowDBaseObj->ForAppending);
}

function AddToLog($LogLine)
{
	global $LogMessage;
	
  if (defined('STAGESHOW_NOTIFYLOG_ON_SCREEN'))
  {
    echo "$LogLine<br>\n";
  }
  
	if (defined('STAGESHOW_ENABLE_IPNLOG'))
		$LogMessage .= $LogLine . "\n";
}

function QueryParam($paramId)
{
	if (isset($_GET[$paramId]))
		$HTTPParam = $_GET[$paramId];	
	elseif (isset($_POST[$paramId]))
		$HTTPParam = $_POST[$paramId];	
	else
		return '';
		
  return $HTTPParam;
}

function HTTPParam($paramId)
{
	$HTTPParam = QueryParam($paramId);
	if (strlen($HTTPParam) > 0)
		$HTTPParam = urldecode($HTTPParam);
	
  return $HTTPParam;
}

// read post from PayPal server and add 'cmd'
$URLParamsArray = GetQueryString();

AddToLog('IPN Request Received at ' . date(DATE_RFC822));

// Add 'cmd' parameter to URL params array
$URLParamsArray['cmd'] = '_notify-validate';

// Choose PayPal target environment
if (isset($_POST['test_ipn']))
{
	$notifyPayPalAPIObj = $myPayPalAPITestObj;
	$PayPalNotifyEMail = $ourOptions['PayPalAPITestEMail'];
	AddToLog('PayPal Environment: TEST(Sandbox)' );
}
else
{
	$notifyPayPalAPIObj = $myPayPalAPILiveObj;
	$PayPalNotifyEMail = $ourOptions['PayPalAPILiveEMail'];
	AddToLog('PayPal Environment: LIVE' );
}

$notifyPayPalAPIObj->URLParamsArray = $URLParamsArray;
if (defined('STAGESHOW_LOG_IPNPARAMS'))
{
	$decodedParams = '';
	foreach ($URLParamsArray as $key => $param)
	{
		$decodedParams .= "$key=$param\n";
	}
	
	$stageShowDBaseObj->LogToFile($LogsFolder . $LogIPNCallFile, "IPN Verify Request Parameters: \n" . $decodedParams, $stageShowDBaseObj->ForWriting);
}

if (defined('STAGESHOW_IPNPARAMS_ON_SCREEN'))
{
	$decodedParams = str_replace("\n", "<br>\n", $decodedParams);
	echo $decodedParams."<br>\n";
}

// Get URL to send verify message to PayPal
$VerifyURL = $notifyPayPalAPIObj->PayPalVerifyURL;
$notifyPayPalAPIObj->HTTPAction($VerifyURL, 'VerifyURL');

AddToLog('IPN Verify URL: ' . $VerifyURL);

// assign posted variables to local variables
$Payment_status = HTTPParam('payment_status');
$Payment_amount = HTTPParam('mc_gross');
$Payment_currency = HTTPParam('mc_currency');
$Txn_id = HTTPParam('txn_id');
$Receiver_email = HTTPParam('receiver_email');
$Payer_email = HTTPParam('payer_email');
$Payer_name = HTTPParam('first_name') . ' ' . HTTPParam('last_name');

AddToLog('---------------------------------------------');
AddToLog('Name:   ' . $Payer_name);
AddToLog('EMail:  ' . $Payer_email);
AddToLog('TXN No: ' . $Txn_id);
AddToLog('Payment:' . $Payment_amount);

// Check notification validation
if ($notifyPayPalAPIObj->APIStatus != 200 )
{
    AddToLog("IPN Response: Status=$notifyPayPalAPIObj->APIStatus");
    // HTTP error handling
}
else if ($notifyPayPalAPIObj->APIResponseText === 'VERIFIED')
{
    AddToLog('IPN Response: VERIFIED');
		AddToLog('    Payment_status: ' . $Payment_status);
    AddToLog('    Txn_id:         ' . $Txn_id);
		AddToLog('    Receiver_email: ' . $Receiver_email);
    
		$IPNError = '';
		
 		if ($IPNError === '')
		{
			// Check that $Payment_status and deal with "Pending" payment status
			if (($Payment_status !== 'Completed') && ($Payment_status !== 'Pending'))
				$IPNError = 'Payment_status not completed';	
		}

 		if ($IPNError === '')
		{
			// Check that $Receiver_email is the EMail we expected
			if ((strlen($PayPalNotifyEMail)>0) && ($Receiver_email != $PayPalNotifyEMail))
			{
				//$IPNError = 'Receiver Email INVALID';
				AddToLog('Receiver Email INVALID');
				AddToLog('APIEmail: '.$PayPalNotifyEMail);
			}
		}
		 
		$txnStatus = '';
		if ($IPNError === '')
		{
	    // Check that $Txn_id has not been previously processed
			$txnStatus = $stageShowDBaseObj->GetTxnStatus($Txn_id);
			if (($txnStatus === $Payment_status) || ($txnStatus === 'Completed'))
				$IPNError = 'Txn_ID Already Processed';		// Entry with matching Txn_Id found
		}
		
		if ($IPNError === '')
		{
			if ($txnStatus !== '')
				$stageShowDBaseObj->UpdateSaleStatus($Txn_id, $Payment_status);
			else
			{
				$txdDate = date(STAGESHOW_DATETIME_MYSQL_FORMAT);
				$saleID = $stageShowDBaseObj->LogSale($txdDate);
				AddToLog('Sale Logged - SaleID: '.$saleID);
				$emailStatus = $stageShowDBaseObj->EMailSale($saleID);
				AddToLog('EMail Status: '.$emailStatus);
			}
			echo "OK<br>\n";
		}
		else
		{
			AddToLog('IPN Rejected: '.$IPNError);
			echo "$IPNError<br>\n";
		}
}
else if ($notifyPayPalAPIObj->APIResponseText == 'INVALID')
{
    // log for manual investigation
    AddToLog('IPN Response: INVALID');
		echo "INVALID<br>\n";
}
else
{
    // error
    AddToLog("IPN Response: Unknown Response (len=" . strlen($notifyPayPalAPIObj->APIResponseText) . ")" . substr($notifyPayPalAPIObj->APIResponseText, 0, 80));
}

AddToLog("---------------------------------------------------------------------");

if (defined('STAGESHOW_ENABLE_IPNLOG'))
	LogDebugToFile($LogMessage);
	
?>
