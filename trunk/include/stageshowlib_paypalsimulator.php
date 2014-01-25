<?php
if (!defined('DB_NAME'))
{
	include '../../../../wp-config.php';
}

if (!class_exists('PayPalSimulator')) 
{
	class PayPalSimulator
	{
		function __construct($notifyDBaseClass, $saleId = 0) 
		{
	  		$this->transactionID = current_time('timestamp');		
			$this->myDBaseObj = new $notifyDBaseClass(__FILE__);
			
			$this->totalSale = 0.00;

			$formHTML = '';
			
			$formHTML .= $this->OutputHeader();

			$devMode = true;
			if (isset($_GET['id']))
			{
				$saleId = $_GET['id'];
				$devMode = false;
			}
			elseif (isset($_POST['id']))
			{
				$saleId = $_POST['id'];
			}
				
			if ($saleId > 0)
			{
				if (defined('RUNSTAGESHOWDEMO'))
				{
					$actionHTML = '';
				}
				else
				{
					$notifyURL = $this->myDBaseObj->PayPalNotifyURL;
					$actionHTML = ($notifyURL != '') ? 'action="'.$notifyURL.'" ' : '';
				}

				$formHTML .=  '<form name="ipntest" '.$actionHTML.' method="post">';			
				$formHTML .=  $this->OutputSaleForm($saleId);
			}
			else
			{
				$formHTML .=  '<form name="ipntest" method="post">';			
				$formHTML .=  $this->OutputSaleSelect(); 
			}
			$formHTML .=  '</form>';			
			
			echo $formHTML;
	   	}

		function OutputSaleForm($saleId)
		{
			$this->paramIDs = '';
			
			$formHTML  = ''; 
			$formHTML .= '<br>'; 
			$formHTML .= '<h2>Sale Details:</h2><div class="paypalsim_saledetails">'; 
			$formHTML .= $this->OutputSaleDetails($saleId); 
			$formHTML .= '</div>'; 
			
			$formHTML .= '<div class="paypalsim_purchaserdetails">'."\n";			
			$formHTML .= "<h2>Purchaser Details:</h2>\n"; 
			$formHTML .= "<table>\n";			

			$formHTML .= $this->PayPalTags($this->myDBaseObj->opts['CfgOptionsID'], true);
			$formHTML .= $this->OutputActionsTable();	
			$formHTML .= "</table>\n";			
			$formHTML .= "<div>\n";			
			
			$formHTML .= $this->PayPalTags($this->myDBaseObj->opts['CfgOptionsID'], false);
			
	        $formHTML .= '<input type="hidden" name="paramIDs" value="'.$this->paramIDs.'">'."\n";
			
			return $formHTML;
		}
			
		function OutputHeader() 
		{
			$header = '
				<!-- OutputHeader function not defined! -->
			';

			return $header;
	    }
		
		function OutputSaleSelect() 
		{
			$sqlFilters = array();
			$saleList = $this->myDBaseObj->GetSalesList($sqlFilters);
			if (count($saleList) == 0)
			{
				$siteURL = get_option('siteurl');
				$selectHTML  = '<p>No pending sales - Checkout a sale to add one<p>'."\n";
				$selectHTML .= 'Go to <a href="'.$siteURL.'">site</a>'."\n";
				return $selectHTML;
			}
			
			$selectHTML = '
			<p>
			<table width="100%" border="0">
			<tr>
				<td class="paypalsim_formFieldID">Select Sale:&nbsp; </td>
				<td>&nbsp;</td>
				<td class="paypalsim_formFieldValue" colspan="2">
					<div align="left">
						<select name="id">
			';
			
			$lastSaleID = -1;
			foreach ($saleList as $sale)
			{
				if ($lastSaleID == $sale->saleID)
					continue;
				$lastSaleID = $sale->saleID;
				
				if (($sale->saleTxnId == '0') || (strlen($sale->saleTxnId) == 0))
				{
					$selectHTML .= '<option value="'.$sale->saleID.'" selected="">'.$sale->saleCheckoutTime.'</option>'."\n";
				}
			}
			
			$selectHTML .= '
						</select>
					</div>
				</td>
				</tr>
				<tr><td colspan="4">&nbsp;</td></tr>
				<tr>
	                <td colspan="4">
						<div align="center">
							<input class="button-primary" type="submit" name="selectSale" value="SELECT"/>
						</div>
					</td>
				</tr>
	            </table>
			';
			
			return $selectHTML;
	    }
		
		function OutputSaleDetails($saleId) 
		{
			$saleList = $this->myDBaseObj->GetSale($saleId);
			return  $this->OutputItemsTable($saleList); 
	    }
		
		function OutputItemsTableHeader() 
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'OutputItemsTableHeader');
	    }
		
		function OutputItemsTableRow($indexNo, $result) 
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'OutputItemsTableRow');
	    }
		
		function OutputItemsTable($results) 
		{
			$html = '';
			
			if (count($results) == 0) return '';
				
			$html .= $this->AddJavascript(count($results));
			
			$PayPalLogoImageFile = '';
			$PayPalHeaderImageFile = '';

			$hiddenTags  = "\n";
			$hiddenTags .= '<input type="hidden" name="cmd" value="_s-xclick"/>'."\n";
			if (strlen($PayPalLogoImageFile) > 0) 
			{
	        	$hiddenTags .= '<input type="hidden" name="image_url" value="'.$this->myDBaseObj->getImagesURL().$PayPalLogoImageFile.'"/>'."\n";
			}
			if (strlen($PayPalHeaderImageFile) > 0) 
			{
				$hiddenTags .= '<input type="hidden" name="cpp_header_image" value="'.$this->myDBaseObj->getImagesURL().$PayPalHeaderImageFile.'"/>'."\n";
			}

			$hiddenTags .= '<input type="hidden" name="on0" value="TicketType"/>'."\n";      
			$hiddenTags .= '<input type="hidden" name="SiteURL" value="'.get_site_url().'"/>'."\n";
	             
			$html .= $this->OutputItemsTableHeader();
			$indexNo = 0;
			foreach($results as $result) 
			{
				$indexNo++;
				$html .= $this->OutputItemsTableRow($indexNo, $result);
			}

			$fee = round(0.20 + ($this->totalSale * 0.034), 2);
			
			$customVal = $results[0]->saleID;
			$html .= '			
		  		</table>
				</div>
				<input type="hidden" id="num_cart_items" name="num_cart_items" value="'.$indexNo.'"/>
				<input type="hidden" name="custom" value="'.$customVal.'"/>
				<input type="hidden" name="mc_fee" value="'.$fee.'"/>
			'; 
			
			return $html;
	    }
		
		function OutputActionsTable() 
		{
			$actionsHTML = '';
			$actionsHTML .= '
				<tr class="paypalsim_formRow">
					<td class="paypalsim_formFieldID">EMail:&nbsp;</td>
					<td class="paypalsim_formFieldValue" colspan="2">
						<input name="payer_email" id="payer_email" type="text" maxlength="30" size="31" value="test@corondeck.co.uk"  />
					</td>
				</tr>
				<tr class="paypalsim_formRow">
					<td class="paypalsim_formFieldID">Total:&nbsp;</td>
					<td class="paypalsim_formFieldValue" colspan="2">
						<input name="mc_gross" id="mc_gross" type="text" maxlength="6" size="6" value="'.$this->totalSale.'" readonly="readonly" />
					</td>
				</tr>
			';
			if (!defined('RUNSTAGESHOWDEMO'))
			{
				$actionsHTML .= '
				<tr class="paypalsim_formRow">
					<td class="paypalsim_formFieldID">Transaction ID:&nbsp;</td>
					<td class="paypalsim_formFieldValue">
						<input name="txn_id" id="txn_id" type="text" maxlength="32" size="32" value="'.$this->transactionID.'" />
					</td>
					<td class="paypalsim_formFieldButton"><input class="button-primary" type="button" name="refreshbutton" value="Refresh" onClick=onclickrefresh() /></td>
				</tr>
				<tr class="paypalsim_formRow">
					<td class="paypalsim_formFieldID">Payment Status:&nbsp; </td>
					<td class="paypalsim_formFieldValue" colspan="2">
						<select name="payment_status">
							<option value="Completed" selected="">Completed</option>
							<option value="Pending">Pending</option>
							<option value="ERROR">ERROR</option>
						</select>
					</td>
				</tr>
				';			
			}
			else
			{
				$actionsHTML .= $this->AddHiddenTag('txn_id', $this->transactionID);
				$actionsHTML .= $this->AddHiddenTag('payment_status', 'Completed');
			}
			$actionsHTML .= '
				<tr class="paypalsim_formRow"><td>&nbsp;</td></tr>
				<tr class="paypalsim_formRow">
					<td class="ButtonCol" colspan="3">
						<input class="button-primary" type="submit" name="SUBMIT_simulatePayPal" value="SUBMIT"/>
					</td>
				</tr>
			';
			
			return $actionsHTML;
	    }
		
	    function AddHiddenTag($tagName, $tagValue, $editable = false, $state = false) 
		{
			if ($editable != $state) return;
			
			if (!$editable)
	        	return "<input type=\"hidden\" name=\"$tagName\" value=\"$tagValue\">\n";
			
			$tagTitle = str_replace("_", " ", $tagName);
			$paramID = 'PayPalVal_'.$tagName;
			
			$sessionVar = 'StageShowSim_'.$tagName;
			$tagValue = isset($_SESSION[$sessionVar]) ? $_SESSION[$sessionVar] : '';
			if ($tagValue != '')
			{
				$tagValue = $tagValue.'';
			}
			if ($this->paramIDs != '') $this->paramIDs .= ',';
			$this->paramIDs .= $tagName;
			
			return '
				<tr class="paypalsim_formRow">
					<td class="paypalsim_formFieldID">'.$tagTitle.':&nbsp;</td>
					<td class="paypalsim_formFieldValue" colspan="2">
						<input name="'.$tagName.'" id="'.$tagName.'" type="text" maxlength="50" size="50" value="'.$tagValue.'" />
					</td>
				</tr>
			';
			
	    }

	    function PayPalTags($optionsID, $state) 
		{			
			$currOptions = get_option($optionsID);
			$receiverEMail = $currOptions['PayPalAPIEMail'];
			
			$tags = '';
			
			// TODO - Test with Zollstockgürtel
	        $tags .= $this->AddHiddenTag('charset', 'windows-1252', false, $state);		
			
	        $tags .= $this->AddHiddenTag('address_name', '(Unused)', $state);
			
	        $tags .= $this->AddHiddenTag('address_status', 'unconfirmed', $state);
	        $tags .= $this->AddHiddenTag('business', 'wibble%40stageshow.org.uk', $state);

	        $tags .= $this->AddHiddenTag('contact_phone', '01234 567890', $state);
			
	        $tags .= $this->AddHiddenTag('mc_currency', 'GBP', $state);

	        // $tags .= $this->AddHiddenTag('payer_email', 'buyer%40punter.com', $state);
	        $tags .= $this->AddHiddenTag('payer_status', 'unverified', $state);
	        $tags .= $this->AddHiddenTag('payment_date', '23%3A03%3A08+Sep+26%2C+2010+PDT', $state);
	        $tags .= $this->AddHiddenTag('receiver_email', $receiverEMail, $state);				
	        $tags .= $this->AddHiddenTag('verify_sign', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123.abcdefghijklmnopqrstuvwxy', $state);
				
	        $tags .= $this->AddHiddenTag('first_name', 'My', true, $state);
	        $tags .= $this->AddHiddenTag('last_name', 'Tester', true, $state);
			
	        $tags .= $this->AddHiddenTag('address_street', 'Highgrove House', true, $state);
	        $tags .= $this->AddHiddenTag('address_city', 'Sometown', true, $state);
	        $tags .= $this->AddHiddenTag('address_state', 'Gloucestershire', true, $state);
	        $tags .= $this->AddHiddenTag('address_zip', 'GL8 8TN', true, $state);
	        $tags .= $this->AddHiddenTag('address_country', 'United Kingdom', true, $state);
	        $tags .= $this->AddHiddenTag('address_country_code', 'GB', false, $state);
			
			return $tags;
	    }
		
		function AddJavascript($itemsCount) 
		{
			$code = '
	<script language="JavaScript">
	<!--
			function onclickqty() {
			var total = 0.0;
	';
			for ($indexNo = 1; $indexNo <= $itemsCount; $indexNo++)
				$code .= 'total += (parseFloat(document.ipntest.mc_gross_'.$indexNo.'.value) * document.ipntest.quantity'.$indexNo.".selectedIndex);\n";
			$code .= '
			document.ipntest.mc_gross.value = total;
			document.ipntest.mc_fee.value = (Math.floor((total*3.4) + 20))/100;
			}

			function onclickrefresh() {
			var now = new Date();
			var transactionID;

			transactionID = Math.floor(now.getTime()/1000);
			document.ipntest.txn_id.value = transactionID;
			}

	//-->
	</script>

			';
			
			return $code;
		}
			

	}
}

?>