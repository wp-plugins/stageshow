<?php

if (!defined('PAYPAL_APILIB_NVPTARGET_URL'))
	define ( 'PAYPAL_APILIB_NVPTARGET_URL', 'https://www.paypal.com/cgi-bin/webscr' );
	
include 'mjslib_logfile.php';
			      
if (!class_exists('NotifyURLClass')) 
{
	class NotifyURLClass // Define class
	{
    // Class variables:
    var		$notifyDBaseObj;			//  Database access Object
    var		$notifyPayPalAPIObj;	//	PayPal API access Object
    
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
			$logFileObj = new MJSLibLogFileClass($this->LogsFolder);
			$logFileObj->LogToFile($LogNotifyFile, $DebugMessage, MJSLibDBaseClass::ForAppending);
		}

		function AddToLog($LogLine)
		{
			global $LogMessage;
			
			if (defined('PAYPAL_APILIB_NOTIFYLOG_ON_SCREEN'))
			{
				echo "$LogLine<br>\n";
			}
		  
			if (defined('PAYPAL_APILIB_ENABLE_IPNLOG'))
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
			$HTTPParam = $this->QueryParam($paramId);
			if (strlen($HTTPParam) > 0)
				$HTTPParam = urldecode($HTTPParam);
			
			return $HTTPParam;
		}

		function __construct($ourDBaseObj)
		{
			$this->notifyDBaseObj = $ourDBaseObj;
			$this->notifyPayPalAPIObj = $ourDBaseObj->payPalAPIObj;

			$LogIPNCallFile = 'LastIPNCall.txt';

			$ourOptions = $this->notifyDBaseObj->adminOptions;

			$this->LogsFolder = $ourOptions['LogsFolderPath'].'/';
			if (!strpos($this->LogsFolder, ':'))
				$this->LogsFolder = ABSPATH . $this->LogsFolder;
				
			//$LogsFolder = ABSPATH . '/logs/';
				
			global $LogMessage;
			$LogMessage = '';

			if (defined('NOTIFYURL_CALLER'))
			{
				$this->AddToLog("IPN Request Called: " . NOTIFYURL_CALLER);
			}

			// read post from PayPal server and add 'cmd'
			$URLParamsArray = $this->GetQueryString();
			
			if (defined('PAYPAL_APILIB_ENABLE_IPNDUMP'))
			{
				$logFileObj = new MJSLibLogFileClass($this->LogsFolder);
				$rawPostData = file_get_contents('php://input');
				$logFileObj->DumpToFile("IPNPacket.txt", "IPN_RAW_POST_Data", $rawPostData);
				$logFileObj->DumpToFile("IPNPacket.txt", "IPN_RX_Data", $URLParamsArray);
			}			

			$IPNRxdMsg = 'IPN Request Received at ' . date(DATE_RFC822);
			$this->AddToLog($IPNRxdMsg);

			// Add 'cmd' parameter to URL params array
			$URLParamsArray['cmd'] = '_notify-validate';
			
			// Choose PayPal target environment
			if (isset($_POST['test_ipn']))
			{
				$this->AddToLog('PayPal Environment: TEST(Sandbox)' );
			}
			else
			{
				$this->AddToLog('PayPal Environment: LIVE' );
			}
			$PayPalNotifyEMail = $ourOptions['PayPalAPIEMail'];

			if (defined('PAYPAL_APILIB_LOG_IPNPARAMS'))
			{
				$decodedParams = '';
				foreach ($URLParamsArray as $key => $param)
				{
					$decodedParams .= "$key=$param\n";
				}
				
				$LogIPNContent = "IPN Verify Request Parameters: \n" . $decodedParams . "\n";
				$this->LogDebugToFile('LastIPNCall.txt', $LogIPNContent);
			}

			if (defined('PAYPAL_APILIB_IPNPARAMS_ON_SCREEN'))
			{
				echo "PAYPAL_APILIB_IPNPARAMS_ON_SCREEN flag set - Dumping URLParamsArray:<br>\n";
				foreach ($URLParamsArray as $key => $param)
					echo "$key=$param<br>\n";
				echo "<br>\n";
			}

			if (defined('PAYPAL_APILIB_ENABLE_IPNDUMP'))
			{
				$logFileObj = new MJSLibLogFileClass($this->LogsFolder);
				$logFileObj->DumpToFile("IPNPacket.txt", "IPN_TX_Data", $URLParamsArray);
			}			

			// Get URL to send verify message to PayPal
			$VerifyURL = $this->notifyPayPalAPIObj->PayPalVerifyURL;
			$payPalResponse = $this->notifyDBaseObj->HTTPAction($VerifyURL, $URLParamsArray);

			$this->AddToLog('IPN Verify URL: ' . $VerifyURL);

			// assign posted variables to local variables
			$Payment_status = $this->HTTPParam('payment_status');
			$Payment_amount = $this->HTTPParam('mc_gross');
			$Payment_currency = $this->HTTPParam('mc_currency');
			$Txn_id = $this->HTTPParam('txn_id');
			$Receiver_email = $this->HTTPParam('receiver_email');
			$Payer_email = $this->HTTPParam('payer_email');
			$Payer_name = $this->HTTPParam('first_name') . ' ' . $this->HTTPParam('last_name');

			$this->AddToLog('---------------------------------------------');
			$this->AddToLog('Name:   ' . $Payer_name);
			$this->AddToLog('EMail:  ' . $Payer_email);
			$this->AddToLog('TXN No: ' . $Txn_id);
			$this->AddToLog('Payment:' . $Payment_amount);

			// Check notification validation
			if ($payPalResponse['APIStatus'] != 200 )
			{
					$this->AddToLog("IPN Response: Status=".$payPalResponse['APIStatus']);
					// HTTP error handling
			}
			else if ($payPalResponse['APIResponseText'] === 'VERIFIED')
			{
					$this->AddToLog('IPN Response: VERIFIED');
					$this->AddToLog('    Payment_status: ' . $Payment_status);
					$this->AddToLog('    Txn_id:         ' . $Txn_id);
					$this->AddToLog('    Receiver_email: ' . $Receiver_email);
			    
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
							$this->AddToLog('Receiver Email INVALID');
							$this->AddToLog('APIEmail: '.$PayPalNotifyEMail);
						}
					}
					 
					$txnStatus = '';
					if ($IPNError === '')
					{
						// Check that $Txn_id has not been previously processed
						$txnStatus = $this->notifyDBaseObj->GetTxnStatus($Txn_id);
						if (($txnStatus === $Payment_status) || ($txnStatus === 'Completed'))
							$IPNError = 'Txn_ID Already Processed';		// Entry with matching Txn_Id found
					}
					
					if ($IPNError === '')
					{
						if ($txnStatus !== '')
							$this->notifyDBaseObj->UpdateSaleStatus($Txn_id, $Payment_status);
						else
						{
							$results['Txnid'] = $this->HTTPParam('txn_id');
							$results['saleName'] = $this->HTTPParam('first_name') . ' ' . $this->HTTPParam('last_name');
							$results['saleEmail'] = $this->HTTPParam('payer_email');
							$results['saleStatus'] = $this->HTTPParam('payment_status');
							$results['salePrice'] = $this->HTTPParam('mc_gross');
							$results['salePPName'] = $this->HTTPParam('address_name');
							$results['salePPStreet'] = $this->HTTPParam('address_street');
							$results['salePPCity'] = $this->HTTPParam('address_city');
							$results['salePPState'] = $this->HTTPParam('address_state');
							$results['salePPZip'] = $this->HTTPParam('address_zip');
							$results['salePPCountry'] = $this->HTTPParam('address_country');
			
							$itemNo = 0;
							$lineNo = 1;
							while (true)
							{
								$itemNo++;
								$itemID = $this->HTTPParam('item_number' . $itemNo);
								if (strlen($itemID) == 0)
									break;
									
								$qty = $this->HTTPParam('quantity' . $itemNo);
								if ($qty == 0)
									continue;
									
								$results['itemID' . $lineNo] = $this->HTTPParam('item_number' . $itemNo);
								$results['itemName' . $lineNo] = $this->HTTPParam('item_name' . $itemNo);
								$results['itemRef' . $lineNo] = $this->HTTPParam('item_number' . $itemNo);
								$results['itemOption' . $lineNo] = $this->HTTPParam('option_selection1_' . $itemNo);
								$results['qty' . $lineNo] = $this->HTTPParam('quantity' . $itemNo);
								
								$this->AddToLog('---------------------------------------------');
								$this->AddToLog('Line ' . $lineNo);
								$this->AddToLog('Item Name:    ' . $results['itemName' . $lineNo]);
								$this->AddToLog('Item Ref:     ' . $results['itemRef' . $lineNo]);
								$this->AddToLog('Item Option:  ' . $results['itemOption' . $lineNo]);
								$this->AddToLog('Quantity:     ' . $results['qty' . $lineNo]);
			    
								$lineNo++;
							}
						
							$results['TxdDate'] = date(MJSLibDBaseClass::MYSQL_DATETIME_FORMAT);
							$saleID = $this->notifyDBaseObj->LogSale($results);
							$this->AddToLog('Sale Logged - SaleID: '.$saleID);
							
							$emailStatus = $this->notifyDBaseObj->EMailSale($saleID);
							$this->AddToLog('EMail Status: '.$emailStatus);
						}
						echo "OK<br>\n";
					}
					else
					{
						$this->AddToLog('IPN Rejected: '.$IPNError);
						echo "$IPNError<br>\n";
					}
			}
			else if ($payPalResponse['APIResponseText'] == 'INVALID')
			{
					// log for manual investigation
					$this->AddToLog('IPN Response: INVALID');
					echo "INVALID<br>\n";
			}
			else
			{
					// error
					$this->AddToLog("IPN Response: Unknown Response (len=" . strlen($payPalResponse['APIResponseText']) . ")" . substr($payPalResponse['APIResponseText'], 0, 80));
			}

			$this->AddToLog("---------------------------------------------------------------------");

			if (defined('PAYPAL_APILIB_ENABLE_IPNLOG'))
				$this->LogDebugToFile('IPNNotify.txt', $LogMessage);
		}
	}
}
	
?>
