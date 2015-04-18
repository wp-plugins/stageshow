<?php

/*
Description: PayPal API Functions

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

if (!defined('PAYPAL_APILIB_NVPTARGET_URL'))
	define ( 'PAYPAL_APILIB_NVPTARGET_URL', 'https://www.paypal.com/cgi-bin/webscr' );
	
include 'stageshowlib_gateway_callback.php';
			      
if (!class_exists('StageShowLib_paypal_CallbackClass')) 
{
	class StageShowLib_paypal_CallbackClass extends StageShowLibGatewayCallbackClass // Define class
	{
		function DoCallback()
		{
			$IPNError = '';
				
			$gatewayResponse = array();

			// read post from PayPal server and add 'cmd'
			$URLParamsArray = $this->GetQueryString();

			$IPNRxdMsg = ' Gateway Callback Received at ' . date(DATE_RFC822);
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
			$PayPalNotifyEMail = trim($this->ourOptions['PayPalAPIEMail']);

			if ($this->notifyDBaseObj->isDbgOptionSet('Dev_IPNLogRequests'))
			{
				$decodedParams = '';
				foreach ($URLParamsArray as $key => $param)
				{
					$decodedParams .= "$key=$param\n";
				}
				
				$LogIPNContent = "Gateway Verify Request Parameters: \n" . $decodedParams . "\n";
				$this->LogDebugToFile(STAGESHOWLIB_FILENAME_LASTGATEWAYCALL, $LogIPNContent);
			}

			if ($this->displayIPNs)
			{
				echo "Display Gateway Callback option set - Dumping URLParamsArray:<br>\n";
				foreach ($URLParamsArray as $key => $param)
					echo "$key=$param<br>\n";
				echo "<br>\n";
			}
			
			// Get URL to send verify message to PayPal
			if ($this->skipIPNServer)
			{
				$VerifyURL = '{Skipped}';
				$gatewayResponse['APIStatus'] = 200;
				$gatewayResponse['APIResponseText'] = 'VERIFIED';
			}
			else
			{
				$VerifyURL = $this->notifyDBaseObj->gatewayObj->PayPalVerifyURL;
				$gatewayResponse = $this->VerifyGatewayCallback($VerifyURL, $URLParamsArray);
			}

			$this->AddToLog("Gateway Verify URL: $VerifyURL");

			// assign posted variables to local variables
			$Payment_status = $this->HTTPParam('payment_status');
			$Payment_amount = $this->HTTPParam('mc_gross');
			$Payment_fee = $this->HTTPParam('mc_fee');
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
			$this->AddToLog('Fee:    ' . $Payment_fee);

			// Check notification validation
			if ( ($gatewayResponse['APIStatus'] != 200 ) || ($gatewayResponse['APIResponseText'] === 'VERIFIED') )
			{
				if ($gatewayResponse['APIStatus'] != 200 )
				{
					$this->AddToLog("Gateway Response: Status=".$gatewayResponse['APIStatus']." (".$gatewayResponse['APIStatusMsg'].")");

					if ($Payment_status == PAYMENT_API_SALESTATUS_COMPLETED)
						$Payment_status = PAYMENT_API_SALESTATUS_UNVERIFIED;					
				}
				else
				{
					$this->AddToLog('Gateway Response: VERIFIED');
				}
				// HTTP error handling

				$this->AddToLog('    Payment_status: ' . $Payment_status);
				$this->AddToLog('    Txn_id:         ' . $Txn_id);
				$this->AddToLog('    Receiver_email: ' . $Receiver_email);

				if ($IPNError === '')
				{
					// Check $Payment_status and deal with "Pending" payment status
					if (($Payment_status !== PAYMENT_API_SALESTATUS_COMPLETED) && ($Payment_status !== 'Pending'))
						$IPNError = 'Payment_status not completed';
				}
				if ($IPNError === '')
				{
					// Check that $Receiver_email is the EMail we expected
					if ((strlen($PayPalNotifyEMail)>0) && (strcasecmp(trim($Receiver_email), $PayPalNotifyEMail) != 0))
					{
						$this->AddToLog('Receiver Email INVALID');
						$this->AddToLog('APIEmail: '.$PayPalNotifyEMail);
					}
				}
				$txnStatus = '';
				if ($IPNError === '')
				{
					// Check that $Txn_id has not been previously processed
					$txnStatus = $this->notifyDBaseObj->GetTxnStatus($Txn_id);
					if (($txnStatus === $Payment_status) || ($txnStatus === PAYMENT_API_SALESTATUS_COMPLETED))
						$IPNError = 'Txn_ID Already Processed';		// Entry with matching Txn_Id found
				}
				if ($IPNError === '')
				{
					if ($txnStatus !== '')
					{
						$saleID = $this->notifyDBaseObj->UpdateSaleStatus($Txn_id, $Payment_status);
					}
					else
					{
						$results['saleTxnId'] = $Txn_id;
						$results['saleFirstName'] = $this->HTTPParam('first_name');
						$results['saleLastName'] = $this->HTTPParam('last_name');
						$results['saleEMail'] = $Payer_email;
						$results['saleStatus'] = $Payment_status;
						$results['salePaid'] = $Payment_amount;
						$results['saleFee'] = $Payment_fee;
						$results['salePPName'] = $this->HTTPParam('address_name');
						$results['salePPStreet'] = $this->HTTPParam('address_street');
						$results['salePPCity'] = $this->HTTPParam('address_city');
						$results['salePPState'] = $this->HTTPParam('address_state');
						$results['salePPZip'] = $this->HTTPParam('address_zip');
						$results['salePPCountry'] = $this->HTTPParam('address_country');
						$results['salePPPhone'] = $this->HTTPParam('contact_phone');
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
							$results['itemPaid' . $lineNo] = $this->HTTPParam('mc_gross_' . $itemNo);
							$this->AddToLog('---------------------------------------------');
							$this->AddToLog('Line ' . $lineNo);
							$this->AddToLog('Item Name:    ' . $results['itemName' . $lineNo]);
							$this->AddToLog('Item Ref:     ' . $results['itemRef' . $lineNo]);
							$this->AddToLog('Item Option:  ' . $results['itemOption' . $lineNo]);
							$this->AddToLog('Quantity:     ' . $results['qty' . $lineNo]);
							$lineNo++;
						}
						$results['saleDateTime'] = current_time('mysql');
						$results['saleID'] = $this->HTTPParam('custom');
						
	  					// FUNCTIONALITY: Gateway Notify - Log Sale to DB
						$saleID = $this->notifyDBaseObj->LogSale($results, StageShowLibSalesDBaseClass::STAGESHOWLIB_LOGSALEMODE_PAYMENT);
					}
						
					if ($saleID > 0)
					{
						$this->AddToLog('Sale Logged - SaleID: '.$saleID);
												
	  					// FUNCTIONALITY: Gateway Notify - Send Sale EMail to buyer (and admin))
						if ($Payment_status == PAYMENT_API_SALESTATUS_COMPLETED)
						{
							$emailStatus = $this->notifyDBaseObj->EMailSale($saleID);
							$this->AddToLog('EMail Status: '.$emailStatus);
							$this->emailSent = true;
						}
					}
					else if ($saleID < 0)
					{
						// Send Sale Rejected EMail - No Matching Rows
						$this->AddToLog('Sale Rejected (Checkout Timed Out) - SaleID: '.$saleID);
						
						$templatePath = $this->notifyDBaseObj->GetEmailTemplatePath('TimeoutEMailTemplatePath');
						$emailTo = $this->notifyDBaseObj->GetEmail($this->notifyDBaseObj->adminOptions);
						
						$emailData[0] = new stdClass();
						foreach ($results as $key => $result)
						{
							if (is_numeric(substr($key, -1, 1)))
								continue;
								
							$emailData[0]->$key = $result;
						}
						
						for ($i=1; $i<$itemNo; $i++)
						{
							$elemId = 'itemName' . $lineNo;
							$emailData[0]->$elemId = $results[$elemId];
							$elemId = 'itemRef' . $lineNo;
							$emailData[0]->$elemId = $results[$elemId];
							$elemId = 'itemOption' . $lineNo;
							$emailData[0]->$elemId = $results[$elemId];
							$elemId = 'qty' . $lineNo;
							$emailData[0]->$elemId = $results[$elemId];
							$elemId = 'itemPaid' . $lineNo;
							$emailData[0]->$elemId = $results[$elemId];
						}

						$emailStatus = $this->notifyDBaseObj->SendEMailFromTemplate($emailData, $templatePath, $emailTo);
						$this->AddToLog('EMail Status: '.$emailStatus);
						$this->emailSent = true;
					}
					else
					{
						// Error in LogSale()
						$IPNError = 'DB Error in LogSale';
					}
				}
				
				if ($IPNError === '')
				{
					echo "OK<br>\n";
				}
				else
				{
					$this->AddToLog('Gateway Callback Rejected: '.$IPNError);
					echo "$IPNError<br>\n";
				}
			}
			else if ($gatewayResponse['APIResponseText'] == 'INVALID')
			{
				// log for manual investigation
				$this->AddToLog('Gateway Response: INVALID');
				echo "INVALID<br>\n";
			}			
			else
			{
				// error
				$this->AddToLog("Gateway Response: Unknown Response (len=" . strlen($gatewayResponse['APIResponseText']) . ")" . substr($gatewayResponse['APIResponseText'], 0, 80));
			}
				
			$this->AddToLog("---------------------------------------------------------------------");
			if ($this->notifyDBaseObj->isDbgOptionSet('Dev_IPNLogRequests'))
			{
				//$this->LogMessage = print_r($_POST, true)."\n".$this->LogMessage;
				$this->LogDebugToFile(STAGESHOWLIB_FILENAME_GATEWAYNOTIFY, $this->LogMessage);
			}
			
			$this->AddToLog("---------------------------------------------------------------------");

		}
	}
}

new StageShowLib_paypal_CallbackClass(STAGESHOWLIB_DBASE_CLASS, __FILE__);	

?>
