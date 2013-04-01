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
				// Get ID of Shows in order of first performance
				// TODO - Use SQL to only get "active" shows
				$shows = $myDBaseObj->GetAllShowsList();
	      
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
			
			$results = $myDBaseObj->GetPricesListByShowID($showID);
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
			parent::GetOnlineStoreRowHiddenTags($result);
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
				
		function OutputContent_OnlineTrolleyRow($priceEntry, $qty)
		{
			$showName = $priceEntry->showName;
			$perfDateTime = $this->myDBaseObj->FormatDateForDisplay($priceEntry->perfDateTime);
			$priceType = $priceEntry->priceType;
			$priceValue = $this->GetOnlineStoreItemPrice($priceEntry);
			$total = $this->myDBaseObj->FormatCurrency($priceValue * $qty);
			$shipping = 0.0;
						
			echo '<td class="'.$this->cssTrolleyBaseID.'-show">'.$showName.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-datetime">'.$perfDateTime.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-type">'.$priceType.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">'.$qty.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">'.$total.'</td>'."\n";

			return $total;
		}
		
		
	}
} //End Class StageShowSalesPluginClass

?>