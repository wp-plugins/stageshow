<?php
/* 
Description: StageShow Plugin Top Level Code
 
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

require_once 'include/stageshow_dbase_api.php';      
      
include 'include/stageshowlib_salesplugin.php';
	
if (!class_exists('StageShowSalesPluginClass')) 
{
	class StageShowSalesPluginClass  extends SalesPluginBaseClass 
	{
		function __construct()
		{
			$this->cssBaseID = "stageshow-boxoffice";
			
			$this->nameColID = 'Date/Time';
			$this->cssNameColID = "datetime";

			$this->refColID = 'Ticket Type';
			$this->cssRefColID = "type";
			
			$this->shortcode = STAGESHOW_SHORTCODE_PREFIX."-boxoffice";
			
			parent::__construct();
			
		}
		
		function OutputContent_OnlineStoreMain($reqRecordId = '')
		{
			$myDBaseObj = $this->myDBaseObj;

			if ($reqRecordId != '')
			{
				parent::OutputContent_OnlineStoreMain($reqRecordId);
			}			
		    else
			{
				// Get ID of "active" Shows in order of first performance
				$shows = $myDBaseObj->GetActiveShowsList();
	      
		  		// Count can be used to limit the number of Shows displayed
				if (isset($atts['count']))
					$count = $atts['count'];
				else
					$count = count($shows);
					
				foreach ( $shows as $show )
				{
					if (!$myDBaseObj->IsShowEnabled($show))
						continue;
						
					parent::OutputContent_OnlineStoreMain($show->showID);
					if (--$count == 0)
						break;
				}
			}
			
		}
		
		function IsOnlineStoreItemEnabled($result)
		{
			$myDBaseObj = $this->myDBaseObj;

			return $myDBaseObj->IsPerfEnabled($result);
		}
				
		function GetOnlineStoreProductDetails($priceID)
		{
			return $this->myDBaseObj->GetPricesListByPriceID($priceID);
		}
		
		function GetOnlineStoreProducts($showID = 0)
		{
			$myDBaseObj = $this->myDBaseObj;

			if ($showID == 0)
			{
				return $myDBaseObj->GetPricesList(null);
			}
			
			// Get the prices list for a single show
			$results = $myDBaseObj->GetPricesListByShowID($showID, true);
			$myDBaseObj->prepareBoxOffice($showID);			
			if (count($results) == 0)
			{
				echo "<!-- StageShow BoxOffice - No Output for ShowID=$showID -->\n";
			}
      
			return $results;
		}
		
		function GetOnlineStoreButtonID($result)
		{
			if ($this->myDBaseObj->UseIntegratedTrolley())
				return $result->priceID;
			else
				return $result->perfPayPalButtonID;
		}
			
		function GetOnlineStoreStockID($result)
		{
			return $result->perfID;
		}
			
		function GetOnlineStoreItemID($result)
		{
			return $result->priceID;
		}
			
		function GetOnlineStoreItemName($result)
		{
			$showName = $result->showName;
			$perfDateTime = $result->perfDateTime;
			$priceType = $result->priceType;
						
			$fullName = $showName.'-'.$perfDateTime.'-'.$priceType;
			
			return $fullName;
		}

		function GetOnlineStoreMaxSales($result)
		{
			return $result->perfSeats;
		}
			
		function GetOnlineStoreItemPrice($result)
		{
			return $result->priceValue;
		}
			
		function IsOnlineStoreItemSoldOut($result)
		{
			$perfSaleQty  = $this->myDBaseObj->GetSalesQtyByPerfID($result->perfID);	
			$seatsAvailable = $result->perfSeats;
			if ( ($seatsAvailable > 0) && ($seatsAvailable <= $perfSaleQty) ) 
			{
				return true;
			}
			
			return false;
		}
			
		function IsOnlineStoreItemAvailable($totalSales, $maxSales)
		{
			$ParamsOK = true;
			
			// Check quantities before we commit 
			foreach ($totalSales as $perfID => $qty)
			{						
				$perfSaleQty  = $this->myDBaseObj->GetSalesQtyByPerfID($perfID);	
				$perfSaleQty += $qty;
				$seatsAvailable = $maxSales[$perfID];
				if ( ($seatsAvailable > 0) && ($seatsAvailable <= $perfSaleQty) ) 
				{
					$ParamsOK = false;
				}
			}
			
			return $ParamsOK;
		}
			
		function GetOnlineStoreItemNote($result, $posn)
		{
			if ((strlen($result->perfNote) > 0) && ($result->perfNotePosn === $posn))
			{
				echo '<tr><td class="stageshow-boxoffice-perfnote">'.$result->perfNote . "<td><tr>\n"; 
			}					
		}
			
		function GetOnlineStoreHiddenTags()
		{
			$myDBaseObj = $this->myDBaseObj;
			$hiddenTags  = parent::GetOnlineStoreHiddenTags();

			if (!$myDBaseObj->UseIntegratedTrolley())
			{
				$hiddenTags .= '<input type="hidden" name="on0" value="TicketType"/>'."\n";      
			}
    
			return $hiddenTags;
		}
		
		function GetOnlineStoreRowHiddenTags($result)
		{
			$hiddenTags = parent::GetOnlineStoreRowHiddenTags($result);
			
			if (!$this->myDBaseObj->UseIntegratedTrolley())
			{
				$hiddenTags .= '<input type="hidden" name="os0" value="'.$result->priceType.'"/>'."\n";      
			}
			
			return $hiddenTags;
		}		

		function OutputContent_OnlineStoreTitle($result)
		{
			echo '<h2>'.$result->showName."</h2>\n";					

			if (isset($result->showNote) && ($result->showNote !== ''))
			{
				echo '<div class="stageshow-boxoffice-shownote">'.$result->showNote . "</div><br>\n";
			}
		}
			
		function OutputContent_OnlineStoreHeader($result)
		{
			parent::OutputContent_OnlineStoreHeader($result);
		}
				
		function OutputContent_OnlineStoreRow($result)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Sales Summary from PerfID
			if (!isset($this->salesSummary))
			{
				$lastShowID = 0;
				$lastPerfID = 0;
				$this->lastPerfDateTime = '';
			}
			else
			{
				$lastShowID = $this->salesSummary[0]->showID;
				$lastPerfID = $this->salesSummary[0]->perfID;
			}
				
			if ($result->perfID != $lastPerfID)
			{
				// New performance - Get sales figures
				$this->salesSummary = $myDBaseObj->GetPerformancesListByPerfID($result->perfID);
			}			
			
			$soldOut = ( ($result->perfSeats >=0) && ($this->salesSummary[0]->totalQty >= $result->perfSeats) );
			
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', $this->myDomain);
						
			if ($myDBaseObj->UseIntegratedTrolley())
				$perfPayPalButtonID = $result->priceID;
			else
				$perfPayPalButtonID = $result->perfPayPalButtonID;
					
			$separator = '';
			if (($this->lastPerfDateTime !== $result->perfDateTime) || defined('STAGESHOW_BOXOFFICE_ALLDATES'))
			{
				$formattedPerfDateTime = $myDBaseObj->FormatDateForDisplay($result->perfDateTime);
				if ($lastPerfID != 0) $separator = "\n".'<tr><td class="stageshow-boxoffice-separator">&nbsp;</td></tr>';
			}
			else
			{
				$formattedPerfDateTime = '&nbsp;';
			}
						
			echo '
				<table width="100%" cellspacing="0">'.$separator.'
				<tr>
				<td class="stageshow-boxoffice-datetime">'.$formattedPerfDateTime.'</td>
				<td class="stageshow-boxoffice-type">'.$result->priceType.'</td>
				<td class="stageshow-boxoffice-price">'.$myDBaseObj->FormatCurrency($result->priceValue).'</td>
				';
																
			if (!$soldOut)
			{
				echo '
					<td class="stageshow-boxoffice-qty">
					<select name="quantity">
					<option value="1" selected="">1</option>
					';
				for ($no=2; $no<=$myDBaseObj->adminOptions['MaxTicketQty']; $no++)
					echo '<option value="'.$no.'">'.$no.'</option>'."\n";
				echo '
					</select>
					</td>
					<td class="stageshow-boxoffice-add">
					<input type="submit" value="Add"  alt="'.$altTag.'"/>
					</td>
				';
			}
			else
			{
				echo '
					<td class="stageshow-boxoffice-soldout" colspan=2>'.__('Sold Out', $this->myDomain).'</td>
					';
			}
					
			echo '
				</tr>
				</table>
				';
				
			$this->lastPerfDateTime = $result->perfDateTime;
		}
		
		function OutputContent_OnlineTrolleyHeader($result)
		{
			echo '<tr class="'.$this->cssTrolleyBaseID.'-titles">'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-show">Show</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-datetime">Date/Time</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-type">Type</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">Quantity</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">Price</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-remove">&nbsp;</td>'."\n";
			echo "</tr>\n";
			
			$this->trolleyHeaderCols = 6;	// Count of the number of columns in the header
		}
				
		function OutputContent_OnlineCheckoutButton()
		{
			if ($this->myDBaseObj->UseIntegratedTrolley())
			{
				if ($this->myDBaseObj->getOption('EnableReservations') && current_user_can(STAGESHOW_CAPABILITY_RESERVEUSER))
				{
					echo '<input class="button-primary" type="submit" name="reserve" value="'.__('Reserve', $this->myDomain).'"/>';
					echo '&nbsp;&nbsp;&nbsp;&nbsp;';
				}				
			}
			
			if ($this->myDBaseObj->PayPalConfigured())
			{
				parent::OutputContent_OnlineCheckoutButton();
			}
		}
		
		function OutputContent_OnlineTrolleyRow($priceEntry, $qty)
		{
			$showName = $priceEntry->showName;
			$perfDateTime = $this->myDBaseObj->FormatDateForDisplay($priceEntry->perfDateTime);
			$priceType = $priceEntry->priceType;
			$priceValue = $this->GetOnlineStoreItemPrice($priceEntry);
			$total = $priceValue * $qty;
			$formattedTotal = $this->myDBaseObj->FormatCurrency($total);
			$shipping = 0.0;
						
			echo '<td class="'.$this->cssTrolleyBaseID.'-show">'.$showName.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-datetime">'.$perfDateTime.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-type">'.$priceType.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">'.$qty.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">'.$formattedTotal.'</td>'."\n";

			return $total;
		}
				
		function GetUserInfo($user_metaInfo, $fieldId, $fieldSep = '')
		{
			if (isset($this->myDBaseObj->adminOptions[$fieldId]))
			{
				$metaField = $this->myDBaseObj->adminOptions[$fieldId];
			}
			else
			{
				$metaField = $fieldId;
			}
			
			if ($metaField == '')
				return '';
				
			if (!isset($user_metaInfo[$metaField][0]))
				return $fieldSep == '' ? __('Unknown', $this->myDomain) : '';
			
			$userInfoVal = 	$user_metaInfo[$metaField][0];
			return $fieldSep.$userInfoVal;
		}
		
		function OnlineStore_ProcessCheckout()
		{
			$myDBaseObj = $this->myDBaseObj;
				
			if (isset($_POST['reserve']))	// 'checkout' without online payment
			{
				if (!current_user_can(STAGESHOW_CAPABILITY_RESERVEUSER))
					return;
						
				$checkoutRslt = $this->OnlineStore_ScanCheckoutSales();
				if (isset($checkoutRslt->checkoutError)) return;
				
				// Lock tables so we can commit the pending sale
				$this->myDBaseObj->LockSalesTable();
					
				// Check quantities before we commit 
				$ParamsOK = $this->IsOnlineStoreItemAvailable($checkoutRslt->totalSales, $checkoutRslt->maxSales);
					
				if ($ParamsOK)
	  			{
					$saleDateTime = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT);
						
					// Get User details from User DB
					$loggedInUser = wp_get_current_user();										
					$user_metaInfo = get_user_meta($loggedInUser->ID);

					// TODO - Make sure that TxnID is unique
					$saleTxnid = 'RES-'.time();	
					
					$checkoutRslt->saleDetails['saleName'] = $this->GetUserInfo($user_metaInfo, 'first_name').$this->GetUserInfo($user_metaInfo, 'last_name', ' ');	
					$checkoutRslt->saleDetails['saleEMail'] = $loggedInUser->data->user_email;
					$checkoutRslt->saleDetails['saleTxnid'] = $saleTxnid;

					$checkoutRslt->saleDetails['salePaid'] = $checkoutRslt->totalDue;
					$checkoutRslt->saleDetails['saleFee'] = '0.0';
								
					$checkoutRslt->saleDetails['saleDateTime'] = $saleDateTime;
					$checkoutRslt->saleDetails['saleStatus'] = STAGESHOW_SALESTATUS_RESERVED;
									
					$checkoutRslt->saleDetails['salePPName'] = $checkoutRslt->saleDetails['saleName'];
					
					$checkoutRslt->saleDetails['salePPStreet']  = $this->GetUserInfo($user_metaInfo, 'UserAddress1');
					$checkoutRslt->saleDetails['salePPStreet'] .= $this->GetUserInfo($user_metaInfo, 'UserAddress2', "\n");
					$checkoutRslt->saleDetails['salePPStreet'] .= $this->GetUserInfo($user_metaInfo, 'UserAddress3', "\n");
						
					$checkoutRslt->saleDetails['salePPCity'] = $this->GetUserInfo($user_metaInfo, 'UserCity');
					$checkoutRslt->saleDetails['salePPState'] = $this->GetUserInfo($user_metaInfo, 'UserCounty');
					$checkoutRslt->saleDetails['salePPZip'] = $this->GetUserInfo($user_metaInfo, 'UserPostcode');
					$checkoutRslt->saleDetails['salePPCountry'] = $this->GetUserInfo($user_metaInfo, 'UserCountry');
			
					// Log sale to DB
					$saleId = $this->myDBaseObj->LogSale($checkoutRslt->saleDetails);
					$emailStatus = $this->myDBaseObj->EMailSale($saleId);
						
					$_SESSION['$this->trolleyid'] = array();	// Clear the shopping cart
					
					$this->checkoutError = __('Tickets reserved - Confirmation EMail sent to ', $this->myDomain).$checkoutRslt->saleDetails['saleEMail'];					
				}
						
				// Release Tables
				$this->myDBaseObj->UnLockTables();					
			}
					
			parent::OnlineStore_ProcessCheckout();
		}
		
		
	}
} //End Class StageShowSalesPluginClass

?>