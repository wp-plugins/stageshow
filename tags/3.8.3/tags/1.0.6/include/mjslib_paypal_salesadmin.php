<?php
/* 
Description: Code for Sales Admin Page
 
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

include 'mjslib_admin.php';      

if (!class_exists('PayPalSalesAdminClass')) 
{
	class PayPalSalesAdminListClass extends MJSLibAdminListClass // Define class
	{		
		function GetHiddenRowsDefinition()	// TODO - Sales Hidden Rows Disabled for Distribution
		{
			$ourOptions = array(
				array('Label' => 'Name',	                     'Id' => 'saleName',      'Type' => 'view'),
				array('Label' => 'EMail',	                     'Id' => 'saleEMail',     'Type' => 'view'),
				array('Label' => 'PayPal Username',	           'Id' => 'salePPName',    'Type' => 'view'),
				array('Label' => PAYPAL_APILIB_STREET_LABEL,	 'Id' => 'salePPStreet',  'Type' => 'view'),
				array('Label' => PAYPAL_APILIB_CITY_LABEL,	   'Id' => 'salePPCity',    'Type' => 'view'),
				array('Label' => PAYPAL_APILIB_STATE_LABEL,	   'Id' => 'salePPState',   'Type' => 'view'),
				array('Label' => PAYPAL_APILIB_ZIP_LABEL,	     'Id' => 'salePPZip',     'Type' => 'view'),
				array('Label' => PAYPAL_APILIB_COUNTRY_LABEL,	 'Id' => 'salePPCountry', 'Type' => 'view'),
				array('Label' => 'Paid',                       'Id' => 'salePaid',      'Type' => 'view'),
				array('Label' => 'Transaction Date/Time',      'Id' => 'saleDateTime',  'Type' => 'view'),
				array('Label' => 'Transaction ID',             'Id' => 'saleTxnId',     'Type' => 'view'),						
			);
			
			$ourOptions = array_merge(parent::GetHiddenRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
	}
}

if (!class_exists('PayPalSalesAdminClass')) 
{
	class PayPalSalesAdminClass extends MJSLibAdminClass // Define class
	{		
		var $addingSale;
		var $detailsSaleId;
		var $results;
		var $payPalAPIObj;
		
		function __construct($env, $myPluginObj = null, $myDBaseObj = null, $payPalObj = null) //constructor	
		{
			// Call base constructor
			parent::__construct($env);
			
			if (!is_array($env))
			{
				$this->myPluginObj = $myPluginObj;
				$this->myDBaseObj = $myDBaseObj;
 				$this->payPalAPIObj = $payPalObj;
			}
			else
			{
				$myPluginObj = $this->myPluginObj;
				$myDBaseObj = $this->myDBaseObj;				
				$payPalObj = $myDBaseObj->payPalAPIObj;
				
 				$this->payPalAPIObj = $payPalObj;
			}
     
			$this->detailsSaleId = 0;
			$this->addingSale = false;
			$this->salesFor = '';
			
			if (isset($_POST['emailsale']))
			{
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$this->detailsSaleId = $_POST['id'];
				$myDBaseObj->EMailSale($this->detailsSaleId);

				$_GET['action'] = 'details';			
				$_GET['id'] = $this->detailsSaleId;			
			}
			
			if (isset($_GET['action']))
			{
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$this->DoActions();
			}

			if ( isset( $_POST['action'] ) && (-1 != $_POST['action']) )
				$bulkAction = $_POST['action'];
			else if ( isset( $_POST['action2'] ) && (-1 != $_POST['action2']) )
				$bulkAction =  $_POST['action2'];
			else
				$bulkAction = '';
				
			if (($bulkAction !== '') && isset($_POST['rowSelect']))
			{
				// Bulk Action Apply button actions
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$actionMsg = '';
					
				switch ($bulkAction)
				{
					case 'delete':		
						$actionCount = 0;			
							
						foreach($_POST['rowSelect'] as $saleID)
						{
							$myDBaseObj->DeleteSale($saleID);
							//echo "Deleted EventID: $saleID <br>\n";
							$actionCount++;
						}
						if ($actionCount > 0)		
						{
							$actionMsg = ($actionCount == 1) ? __("1 Sale has been deleted", $this->pluginName) : $actionCount.' '.__("Sales have been deleted", $this->pluginName); 
							echo '<div id="message" class="updated"><p>'.$actionMsg.'</p></div>';
						}
						else
						{
							$actionMsg = __("Nothing to Delete", $this->pluginName);
							echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
						}
						break;						
				}
			}
			
			if (isset($_POST['savesale']))
			{
				// Set "addingSale" flag on error ...
				// TODO-IMPROVEMENT - Adding Manual Sale - Check for address errors 
				
				$invalidQtyId = 0;
				$salePrice = 0;
				
				$pricesList = $myDBaseObj->GetPricesList(null);
				foreach($pricesList as $pricesEntry)
				{
					$itemID = $this->GetItemID($pricesEntry);
					$inputID = 'addSaleItem'.$itemID;
					if (isset($_POST[$inputID]) && !is_numeric($_POST[$inputID]))
					{
						echo '<div id="message" class="error"><p>'.__('Invalid Quantity', $this->pluginName).' - '.$_POST[$inputID].'.</p></div>';
						$invalidQtyId = $itemID;
						break;
					}
					
					$salePrice += intval($_POST[$inputID]) * $this->GetItemPrice($pricesEntry);
				}
				
				if ($invalidQtyId == 0)
				{
					$saleName = isset($_POST['saleName']) ? stripslashes($_POST['saleName']) : "";
					$saleEMail = isset($_POST['saleEMail']) ? stripslashes($_POST['saleEMail']) : "";
					$salePPStreet = isset($_POST['salePPStreet']) ? stripslashes($_POST['salePPStreet']) : "";
					$salePPCity = isset($_POST['salePPCity']) ? stripslashes($_POST['salePPCity']) : "";
					$salePPState = isset($_POST['salePPState']) ? stripslashes($_POST['salePPState']) : "";
					$salePPZip = isset($_POST['salePPZip']) ? stripslashes($_POST['salePPZip']) : "";
					$salePPCountry = isset($_POST['salePPCountry']) ? stripslashes($_POST['salePPCountry']) : "";
					
					// Add Transaction Number (from timestamp)
					$saleTxnid = 'MAN-'.time();	
					
					$saleID = $myDBaseObj->AddSale(date(MJSLibDBaseClass::MYSQL_DATETIME_FORMAT), $saleName, $saleEMail, $salePrice, $saleTxnid, 'Completed', $saleName, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry);				
					//$pricesList = $myDBaseObj->GetPricesList();
					
					$salesTESTCounts = array();
					$salesLIVECounts = array();
					
					foreach($pricesList as $pricesEntry)
					{
						$itemID = $this->GetItemID($pricesEntry);
						$inputID = 'addSaleItem'.$itemID;
						if (isset($_POST[$inputID]))
						{
							$qty = intval($_POST[$inputID]);
							if ($qty > 0)
							{
								$myDBaseObj->AddSaleItem($saleID, $itemID, $qty);
								
								$ButtonID = $this->GetButtonID($pricesEntry);
								if ($ButtonID !== '')
									$salesLIVECounts[$ButtonID] = isset($salesLIVECounts[$ButtonID]) ? $salesLIVECounts[$ButtonID] + $qty : $qty;
							}
						}
					}
					
					$siteurl = get_option('siteurl');
					foreach ($salesLIVECounts as $key => $salesCount)
					{
						// Update Inventory for Live PayPal server
						if ($this->payPalAPIObj->GetInventory($key, $quantity) === 'OK')
						{
							if ($quantity > 0)
							{
								$quantity -= $salesCount;
								$this->payPalAPIObj->UpdateInventory($key, $quantity, $siteurl);								
							}
						}
					}
					
					$this->detailsSaleId = $saleID;
					$this->results = $myDBaseObj->GetSale($this->detailsSaleId);	// Get list of items for a single sale
				}
				else
				{
					// Show form to user ... with their values
					$this->addingSale = true;
				}
			}
					
			if (isset($_POST['addsale']))
			{
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$this->addingSale = true;
			}
			
			if (!isset($this->results))	
				$this->results = $myDBaseObj->GetAllSalesList();		// Get list of sales (one row per sale)
				
			echo '<div class="wrap">';

			// HTML Output - Start 
?>
		<div class="wrap">
				<div id="icon-<?php echo $this->pluginName; ?>" class="icon32"></div>
			<h2>
					<?php echo $myPluginObj->pluginName.' '.$this->salesFor.' - '.__('Sales Log', $this->pluginName); ?>
			</h2>
				<form method="post" action="admin.php?page=<?php echo $this->pluginName; ?>_sales">
				<h3>
					<?php 
	if ($this->addingSale)
				_e('Add Sale', $this->pluginName); 
	else if ($this->detailsSaleId > 0)
				_e('Sale Details', $this->pluginName); 
	else
				_e('Summary', $this->pluginName); 
?>
				</h3>
				<?php
			if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename($this->caller));
			if ($this->addingSale)
			{
				$saleName = isset($_POST['saleName']) ? stripslashes($_POST['saleName']) : "";
				$saleEMail = isset($_POST['saleEMail']) ? stripslashes($_POST['saleEMail']) : "";
				$salePPStreet = isset($_POST['salePPStreet']) ? stripslashes($_POST['salePPStreet']) : "";
				$salePPCity = isset($_POST['salePPCity']) ? stripslashes($_POST['salePPCity']) : "";
				$salePPState = isset($_POST['salePPState']) ? stripslashes($_POST['salePPState']) : "";
				$salePPZip = isset($_POST['salePPZip']) ? stripslashes($_POST['salePPZip']) : "";
				$salePPCountry = isset($_POST['salePPCountry']) ? stripslashes($_POST['salePPCountry']) : "";
	
				$this->results = $myDBaseObj->GetPricesList(null);
?>
				<table>
					<tr>
						<td><?php _e('Name', $this->pluginName) ?>:</td>
						<td><input name="saleName" size="<?php echo PAYPAL_APILIB_PPSALENAME_EDITLEN ?>" autocomplete="off" value="<?php echo($saleName) ?>"></td>
					</tr>
					<tr>
						<td><?php _e('EMail', $this->pluginName) ?>:</td>
						<td><input name="saleEMail" size="<?php echo PAYPAL_APILIB_PPSALEEMAIL_EDITLEN ?>" autocomplete="off" value="<?php echo($saleEMail) ?>"></td>
					</tr>
					<tr>
						<td><?php _e(PAYPAL_APILIB_STREET_LABEL, $this->pluginName) ?>:</td>
						<td><input name="salePPStreet" size="<?php echo PAYPAL_APILIB_PPSALEPPSTREET_EDITLEN ?>" autocomplete="off" value="<?php echo($salePPStreet) ?>"></td>
					</tr>
					<tr>
						<td><?php _e(PAYPAL_APILIB_CITY_LABEL, $this->pluginName) ?>:</td>
						<td><input name="salePPCity" size="<?php echo PAYPAL_APILIB_PPSALEPPCITY_EDITLEN ?>" autocomplete="off" value="<?php echo($salePPCity) ?>"></td>
					</tr>
					<tr>
						<td><?php _e(PAYPAL_APILIB_STATE_LABEL, $this->pluginName) ?>:</td>
						<td><input name="salePPState" size="<?php echo PAYPAL_APILIB_PPSALEPPSTATE_EDITLEN ?>" autocomplete="off" value="<?php echo($salePPState) ?>"></td>
					</tr>
					<tr>
						<td><?php _e(PAYPAL_APILIB_ZIP_LABEL, $this->pluginName) ?>:</td>
						<td><input name="salePPZip" size="<?php echo PAYPAL_APILIB_PPSALEPPZIP_EDITLEN ?>" autocomplete="off" value="<?php echo($salePPZip) ?>"></td>
					</tr>
					<tr>
						<td><?php _e(PAYPAL_APILIB_COUNTRY_LABEL, $this->pluginName) ?>:</td>
						<td><input name="salePPCountry" size="<?php echo PAYPAL_APILIB_PPSALEPPCOUNTRY_EDITLEN ?>" autocomplete="off" value="<?php echo($salePPCountry) ?>"></td>
					</tr>
				</table>
				<?php						

				$this->OutputSalesDetailsList($env, true);		
}
			else if(count($this->results) == 0)
{
				echo "<div class='noconfig'>".__('NO Sales', $this->pluginName)."</div>\n";
}
else 
{
	if ($this->detailsSaleId > 0)
	{
		// TODO-WISHLIST - Add display of local date/time to sales summary
?>
		<input type="hidden" name="id" value="<?php echo $this->detailsSaleId ?>"/>
								<table>
									<tr valign="top" id="tags">
			<td><?php _e('Name', $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->saleName) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('EMail', $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->saleEMail) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('PayPal Username', $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->salePPName) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(PAYPAL_APILIB_STREET_LABEL, $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->salePPStreet) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(PAYPAL_APILIB_CITY_LABEL, $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->salePPCity) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(PAYPAL_APILIB_STATE_LABEL, $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->salePPState) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(PAYPAL_APILIB_ZIP_LABEL, $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->salePPZip) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(PAYPAL_APILIB_COUNTRY_LABEL, $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->salePPCountry) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('Paid', $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->salePaid) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('Transaction Date/Time', $this->pluginName) ?>:&nbsp;</td>
			<td><?php echo($this->results[0]->saleDateTime).'&nbsp;UTC'; ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('Transaction ID', $this->pluginName) ?>:</td>
			<td><?php echo($this->results[0]->saleTxnId) ?></td>
									</tr>
								</table>
								<br></br>
								<?php						
		$this->OutputSalesDetailsList($env);
	}
	else
	{
		$this->OutputSalesList($env);
	}
}

	if ($this->addingSale)
      echo '
			<input class="button-primary" type="submit" name="savesale" value="'.__('Save Sale', $this->pluginName).'">
			';
	else if ($this->detailsSaleId <= 0)
      echo '
			<input class="button-secondary" type="submit" name="addsale" value="'.__('Add Sale', $this->pluginName).'">
			';
	else
      echo '
			<input class="button-secondary" type="submit" name="showsales" value="'.__('Back to Sales Summary', $this->pluginName).'">
			&nbsp;
      <input class="button-secondary" type="submit" name="emailsale" value="'.__('Send Confirmation EMail', $this->pluginName).'">
			';
	echo "<br></br>\n";
?>
							</form>
		</div>

		<?php
        // HTML Output - End
		}	
		
		function DoActions()
		{
			$rtnVal = false;

			switch ($_GET['action'])
			{
				case 'details':
					$this->detailsSaleId = $_GET['id']; 
					$this->results = $this->myDBaseObj->GetSale($this->detailsSaleId);	// Get list of items for a single sale
					$rtnVal = true;
					break;
			}
				
			return $rtnVal;
		}
		
		function OutputSalesList($env)
		{
		}
		
		function OutputSalesDetailsList($env, $isInput = false)
		{
		}
	}
} 
		 
?>