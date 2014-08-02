<?php
/* 
Description: Code for Managing Prices Configuration

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

include STAGESHOW_INCLUDE_PATH.'stageshowlib_salesadmin.php';

if (!class_exists('StageShowPricesAdminListClass'))
{
	class StageShowPricesAdminListClass extends StageShowLibSalesAdminListClass // Define class
	{
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			$this->SetRowsPerPage(self::STAGESHOWLIB_EVENTS_UNPAGED);
			
			$this->bulkActions = array(
				StageShowLibAdminListClass::BULKACTION_DELETE => __('Delete', $this->myDomain),
			);
		}
		
		function GetTableID($result)
		{
			return "showtab" . $result->showID;
		}
		
		function GetRecordID($result)
		{
			return $result->priceID;
		}
		
		function DecodePrice($value, $result)
		{
			if ($value == STAGESHOW_PRICE_UNKNOWN)
			{
				return '';
			}
			
			return $value;
		}
		
		function GetMainRowsDefinition()
		{
			// FUNCTIONALITY: Prices - Lists Performance, Type and Price
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Performance',  StageShowLibTableClass::TABLEPARAM_ID => 'perfDateTime', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW,   StageShowLibTableClass::TABLEPARAM_DECODE => 'FormatDateForAdminDisplay', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Ticket Type',  StageShowLibTableClass::TABLEPARAM_ID => 'priceType',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_LEN => STAGESHOW_PRICETYPE_TEXTLEN, ),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Price',        StageShowLibTableClass::TABLEPARAM_ID => 'priceValue',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_LEN => 9, StageShowLibTableClass::TABLEPARAM_DECODE => 'DecodePrice'),
			);
			
			return $ourOptions;
		}
		
		function PerfDates($result)
		{
			$perfDatesList = array();
			$perfsLists    = $this->myDBaseObj->GetPerformancesListByShowID($result->showID);
			foreach ($perfsLists as $perfsEntry)
			{
				$perfDatesList[$perfsEntry->perfID] = $perfsEntry->perfDateTime; // .'|' . $result->perfID;				
			}
			
			return $perfDatesList;
		}
		
		function OutputList($results, $updateFailed = false)
		{
			$this->updateFailed = $updateFailed;
			parent::OutputList($results, $updateFailed);
		}
	}
}

include STAGESHOW_INCLUDE_PATH . 'stageshowlib_admin.php';

if (!class_exists('StageShowPricesAdminClass'))
{
	class StageShowPricesAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Prices';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function SavePriceEntry($result)
		{
			$myDBaseObj  = $this->myDBaseObj;
			$pricesUpdated = false;
							
			// FUNCTIONALITY: Prices - Save Price Ref and Price
			$newPriceType = stripslashes($_POST['priceType' . $result->priceID]);
			if ($newPriceType != $result->priceType)
			{
				$myDBaseObj->UpdatePriceType($result->priceID, $newPriceType);
				$result->priceType = $newPriceType;
				$pricesUpdated     = true;
			}
							
			$newPriceValue = stripslashes($_POST['priceValue' . $result->priceID]);
			if ($newPriceValue != $result->priceValue)
			{
				$myDBaseObj->UpdatePriceValue($result->priceID, $newPriceValue);
				$result->priceValue = $newPriceValue;
				$pricesUpdated      = true;
			}
							
			return $pricesUpdated;
		}
		
		function GetNewPriceReference($result)
		{
			$newPerfID     = $result->perfID;
			$newPriceType  = stripslashes($_POST['priceType' . $result->priceID]);
			
			$priceEntry = $newPerfID . '-' . $newPriceType;
			
			return $priceEntry;
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			$showID = 0;
			
			// FUNCTIONALITY: Prices - Save Changes
			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();
				
				$showID  = $_POST['showID'];
				$results = $myDBaseObj->GetPricesListByShowID($showID);
				
				// Verify that Price Types are unique for each performance								
				if (count($results) > 0)
				{
					foreach ($results as $result)
					{
						$newPriceType  = stripslashes($_POST['priceType' . $result->priceID]);
						$newPriceValue = stripslashes($_POST['priceValue' . $result->priceID]);
						
						// Generate an entry that consists of the PerformanceID and the Price Type
						$priceEntry = $this->GetNewPriceReference($result);
						// FUNCTIONALITY: Prices - Reject Duplicate Price Refs
						if (isset($entriesList[$priceEntry]))
						{
							// Convert the perfID to a Performance Date & Time to display to the user
							$this->adminMsg = __('Duplicated Price Type', $this->myDomain) . ' (' . $result->perfDateTime . ' - ' . $newPriceType . ')';
							break;
						}
						
						$this->adminMsg = $myDBaseObj->IsPriceValid($newPriceValue, $result);
						if ($this->adminMsg !== '')
						{
							$this->adminMsg .= ' (' . $result->perfDateTime . ' - ' . $newPriceType . ')';
							break;				
						}
						
						$entriesList[$priceEntry] = true;
					}
				}
				
				if ($this->adminMsg !== '')
				{
					echo '<div id="message" class="error"><p>'.__('Settings have NOT been saved', $this->myDomain).'. '.$this->adminMsg.'</p></div>';
				}
				else
				{
					if (count($results) > 0)
					{
						foreach ($results as $result)
						{
							$pricesUpdated = $this->SavePriceEntry($result);							
						} // End foreach
					}
					echo '<div id="message" class="updated"><p>' . __('Settings have been saved', $this->myDomain) . '</p></div>';
				}
			}
			else if (isset($_POST['addpricebutton']))
			{
				$this->CheckAdminReferer();
				
				$showID = $_POST['showID'];
				
				// Performance ID of first performance is passed with call - Type ID is null ... AddPrice() will add (unique) value
				$perfID = $_POST['perfID'];		
				$myDBaseObj->AddPrice($perfID, '');
				
				echo '<div id="message" class="updated"><p>' . __('Settings have been saved', $this->myDomain) . '</p></div>';
			}
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			// Stage Show Prices HTML Output - Start 
			$showLists = $myDBaseObj->GetAllShowsList();
			if (count($showLists) == 0)
			{
				// FUNCTIONALITY: Prices - Show Link to Settings page if PayPal settings required
				if ($myDBaseObj->CheckIsConfigured())
				{
					$showsPageURL = get_option('siteurl') . '/wp-admin/admin.php?page=' . STAGESHOW_MENUPAGE_SHOWS;
					echo "<div class='error'><p>" . __('No Show Configured', $this->myDomain) . ' - <a href=' . $showsPageURL . '>' . __('Add one Here', $this->myDomain) . '</a>' . "</p></div>\n";
				}
			}
			foreach ($showLists as $showList)
			{
				$perfsLists = $myDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
	<div class="stageshow-admin-form">
	<form method="post">
<?php
				$this->WPNonceField();
				if (count($perfsLists) == 0)
				{
					$showsPageURL = get_option('siteurl') . '/wp-admin/admin.php?page=' . STAGESHOW_MENUPAGE_PERFORMANCES;
					$showsPageMsg = $showList->showName . ' ' . __('has No Performances', $this->myDomain) . ' - <a href=' . $showsPageURL . '>' . __('Add one Here', $this->myDomain) . '</a>';
?> 
	<div class='error'><p><?php echo $showsPageMsg; ?></p></div>
<?php
				}
				else
				{
?>
		<h3><?php echo($showList->showName); ?></h3>
<?php
					$results = $myDBaseObj->GetPricesListByShowID($showList->showID);
					if (count($results) == 0)
					{
						echo "<div class='noconfig'>" . __('Show has No Prices', $this->myDomain) . "</div>\n";
					}
					else
					{
						$classId = $this->GetAdminListClass();
						$showsList = new $classId($this->env);
						$showsList->OutputList($results, $updateFailed);
					} 

      				echo '<input type="hidden" name="showID" value="'.$showList->showID.'" />'."\n";

					// FUNCTIONALITY: Prices - Output "Add New Price" Button (if valid)
					$this->showID = $showList->showID;
					$this->OutputButton("addpricebutton", __("Add New Price", $this->myDomain));

					// Output Performance Select
					$this->OutputPerformanceSelect('&nbsp; '.__('for performance', $this->myDomain).' &nbsp;');
				
					// FUNCTIONALITY: Prices - Output "Save Changes" Button (if there are entries)
					if (count($results) > 0)
						$this->OutputButton("savechanges", __("Save Changes", $this->myDomain), "button-primary");
				}
?>
		</form>
		</div>
<?php
			}
			// Stage Show Prices HTML Output - End 
		}
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (!isset($this->errorCount))
				$this->errorCount = 0;
			if (!isset($this->blockCount))
				$this->blockCount = 0;
			
			switch ($bulkAction)
			{
				// FUNCTIONALITY: Prices - Bulk Action Delete - Block if tickets sold
				case StageShowLibAdminListClass::BULKACTION_DELETE:
					// Don't delete if any tickets have been sold for this performance
					$priceEntry = $myDBaseObj->GetPricesListByPriceID($recordId);
					$results = $myDBaseObj->GetSalesListByPriceID($recordId);
					if (count($priceEntry) == 0)
						$this->errorCount++;
					else if (count($results) > 0)
						$this->blockCount++;
					return (($this->errorCount > 0) || ($this->blockCount > 0));
			}
			
			return false;
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			switch ($bulkAction)
			{
				case StageShowLibAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Prices - Bulk Action Delete 
					// Now delete the entry in the PRICES table
					$delShowName = $myDBaseObj->DeletePriceByPriceID($recordId);
					return true;
			}
			
			return false;
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			$actionMsg = '';
			
			switch ($bulkAction)
			{
				case StageShowLibAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Prices - Bulk Action Delete - Output Action Status Message
					if ($this->errorCount > 0)
						$actionMsg = $this->errorCount . ' ' . _n("Price does not exist in Database", "Prices do not exist in Database", $this->errorCount, $this->myDomain);
					else if ($this->blockCount > 0)
						$actionMsg = $this->blockCount . ' ' . _n("Price cannot be deleted", "Prices cannot be deleted", $this->blockCount, $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain);
					else if ($actionCount > 0)
						$actionMsg = $actionCount . ' ' . _n("Price has been deleted", "Prices have been deleted", $actionCount, $this->myDomain);
					else
						$actionMsg = __("Nothing to Delete", $this->myDomain);
					break;
			}
			
			return $actionMsg;
		}
		
		function OutputPerformanceSelect($label = '')
		{
			// Output a performance drop-down box
			$myDBaseObj  = $this->myDBaseObj;

			echo $label;
			
			// Get performances list for this show
			$perfsList = $myDBaseObj->GetPerformancesListByShowID($this->showID);
			
			echo '<select name="perfID">'."\n";
			foreach ($perfsList as $perfRecord)
			{
				$perfDateTime = StageShowDBaseClass::FormatDateForAdminDisplay($perfRecord->perfDateTime).'&nbsp;&nbsp;';
				$perfID = $perfRecord->perfID;
				echo "<option value=\"$perfID\">$perfDateTime</option>\n";
			}
			echo '</select>'."\n";
		}
		
	}
}

?>