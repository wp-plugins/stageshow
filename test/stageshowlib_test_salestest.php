<?php
/* 
Description: Code for Managing Prices Configuration
 
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
	
if (!class_exists('StageShowLib_Test_salestest')) 
{
	if (!defined('STAGESHOWLIB_REFUND_SALESLIST_LIMIT')) 
		define('STAGESHOWLIB_REFUND_SALESLIST_LIMIT', 20);
		
	class StageShowLib_Test_salestest extends StageShowLibTestBaseClass // Define class
	{
		function __construct($env) //constructor	
		{
			parent::__construct($env);
		}
		
		function Show()
		{			
			if (class_exists('StageShowLibTableTestEMailClass')) new StageShowLibTableTestEMailClass($this, true);
						
			$this->Test_SearchTransactions();	
			$this->Test_GetTransaction();	
			$this->Test_RefundTransaction();	
			$this->Test_IntegratedTrolley();
		}
		
		static function GetOrder()
		{
			return 9;	// Determines order tests are output
		}
		
		function Test_SearchTransactions() 
		{
			echo '<h3>List PayPal Transaction</h3>';
					
			$TransactionId = '';
			
			$myDBaseObj = $this->myDBaseObj;

			$reqStartDate = date(StageShowLibDBaseClass::MYSQL_DATE_FORMAT, strtotime("-1 year"));
				
			if (isset($_POST['testbutton_SearchTransactions']))
			{
				$this->CheckAdminReferer();
				
				$TransactionId = $_POST['txnId'];
								
				$caller = $myDBaseObj->opts['Caller'];
				
				$payPalAPITestObj = new StageShowLib_paypal_APIClass(__FILE__, false);
				
				$payPalAPITestObj->SetLoginParams(
					$myDBaseObj->adminOptions['PayPalAPIUser'], 
					$myDBaseObj->adminOptions['PayPalAPIPwd'], 
					$myDBaseObj->adminOptions['PayPalAPISig']);
				$payPalAPITestObj->SetTestMode(false);
				$payPalAPITestObj->EnableDebug();
				
				if (isset($_POST['SearchTransactions_StartDate']))
				{
					$reqStartDate = $_POST['SearchTransactions_StartDate'];
				}
				$startDate = $reqStartDate.'T12:00:00Z';
				
				$apiStatus = $payPalAPITestObj->GetTransactions($startDate);
				
				if ($apiStatus == 'OK')
				{
					$resFields = array(
						'L_TIMESTAMP', 
						'L_TIMEZONE', 
						'L_TYPE', 
						'L_EMAIL', 
						'L_NAME', 
						'L_TRANSACTIONID',
						'L_STATUS',
						'L_AMT',
						'L_CURRENCYCODE',
						'L_FEEAMT',
						'L_NETAMT',
						);
					
					foreach ($resFields as $resField)
					{
						echo "$resField,";
					}
					echo "<br>\n";
					
					$msgIndex = 0;
					while (isset($payPalAPITestObj->APIResponses[$resFields[0].$msgIndex]))
					{
						foreach ($resFields as $resField)
						{
							$fieldVal = 'n/a';
							if (isset($payPalAPITestObj->APIResponses[$resField.$msgIndex]))
								$fieldVal = $payPalAPITestObj->APIResponses[$resField.$msgIndex];
							echo $fieldVal.',';
						}
						echo "<br>\n";
						$msgIndex++;
					}
				
				}
				
				unset($payPalAPITestObj);
			}
			
?>
			<table class="form-table">
				<tr valign="top">
					<td>Start Date:</td>
					<td>
						<input name="SearchTransactions_StartDate" id="SearchTransactions_StartDate" type="text" maxlength="10" size="10" value="<?php echo $reqStartDate; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<td width=25%>
						<input class="button-primary" type="submit" name="testbutton_SearchTransactions" value="Search Transactions"/>
					</td>
				</tr>
			</table>
<?php		
		}
		
		function Test_GetTransaction() 
		{
			echo '<h3>Get PayPal Transaction</h3>';
					
			$TransactionId = '';
			
			$myDBaseObj = $this->myDBaseObj;

			if (isset($_POST['testbutton_GetTransaction']))
			{
				$this->CheckAdminReferer();
				
				$TransactionId = $_POST['txnId'];
								
				$caller = $myDBaseObj->opts['Caller'];
				
				$payPalAPITestObj = new StageShowLib_paypal_APIClass(__FILE__, false);
				
				$payPalAPITestObj->SetLoginParams(
					$myDBaseObj->adminOptions['PayPalAPIUser'], 
					$myDBaseObj->adminOptions['PayPalAPIPwd'], 
					$myDBaseObj->adminOptions['PayPalAPISig']);
				$payPalAPITestObj->SetTestMode(false);
				$payPalAPITestObj->EnableDebug();
				
				$apiStatus = $payPalAPITestObj->GetTransaction($TransactionId);
				
				unset($payPalAPITestObj);
			}
			
?>
			<table class="form-table">
				<tr valign="top">
					<td>Sale Reference:</td>
					<td>
						<input name="txnId" id="txnId" type="text" maxlength="110" size="75" value="<?php echo htmlspecialchars($TransactionId); ?>" />
					</td>
					<td width=25%>
						<input class="button-primary" type="submit" name="testbutton_GetTransaction" value="Get Transaction"/>
					</td>
				</tr>
			</table>
<?php		
		}
		
		function Test_RefundTransaction() 
		{
			echo '<h3>Refund PayPal Transaction</h3>';
					
			$myDBaseObj = $this->myDBaseObj;

			if (isset($_POST['testbutton_RefundTransaction']))
			{
				$this->CheckAdminReferer();
				
				$saleID = $_POST['RefundSaleID'];
				$saleResults = $myDBaseObj->GetSale($saleID);
				if(count($saleResults) == 0) 
				{
					echo '<div id="message" class="error"><p>'.$this->getTL8('Invalid SaleID', $myDBaseObj->get_domain()).'</p></div>';
				}
				else 
				{
					$TransactionId = $saleResults[0]->saleTxnId;
					
					$caller = $myDBaseObj->opts['Caller'];
					
					$payPalAPITestObj = new StageShowLib_paypal_APIClass(__FILE__);
					
					$payPalAPITestObj->SetLoginParams(
						$myDBaseObj->adminOptions['PayPalAPIUser'], 
						$myDBaseObj->adminOptions['PayPalAPIPwd'], 
						$myDBaseObj->adminOptions['PayPalAPISig']);
					$payPalAPITestObj->SetTestMode(false);
					$payPalAPITestObj->EnableDebug();
					
					$apiStatus = $payPalAPITestObj->RefundTransaction($TransactionId);
					
					unset($payPalAPITestObj);
				}	
			}
			
			$sqlFilters['limit'] = STAGESHOWLIB_REFUND_SALESLIST_LIMIT;
			$results = $myDBaseObj->GetAllSalesList($sqlFilters);		// Get list of sales (one row per sale)
			
?>
			<table class="form-table">
				<tr valign="top">
		      		<td><?php $this->echoTL8('Selected Sale', $myDBaseObj->get_domain()); ?>:</td>
					<td>
						<select name="RefundSaleID">
<?php		
			foreach($results as $result) 
			{
				echo '<option value="',$result->saleID.'">'.$result->saleTxnId.' - '.$result->saleEMail.' - '.$result->saleDateTime.'&nbsp;&nbsp;</option>'."\n";
			}
?>
						</select>
					</td>
					<td width=25%>
						<input class="button-primary" type="submit" name="testbutton_RefundTransaction" value="Refund Transaction"/>
					</td>
				</tr>
			</table>
<?php		
		}
		
		function Test_IntegratedTrolley() 
		{
			$myDBaseObj = $this->myDBaseObj;

			echo '<h3>Integrated Trolley Test</h3>';
				
			if (isset($_POST['testbutton_ClearTrolley'])) 
			{
				$this->myPluginObj->ClearTrolleyContents();	// Clear the Shopping Trolley
				echo "Shopping Trolley Cleared<br>";
			}
					
			if (isset($_POST['testbutton_PurgeTrolley'])) 
			{
				$myDBaseObj->PurgePendingSales(0);
				echo "Pending Sales Purged<br>";
			}
					
?>
			<input class="button-primary" type="submit" name="testbutton_ClearTrolley" value="Clear Trolley"/>
			<input class="button-primary" type="submit" name="testbutton_PurgeTrolley" value="Purge Trolley"/>
		
<?php		
		}
		
	}
}

?>