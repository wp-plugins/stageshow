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

include STAGESHOW_INCLUDE_PATH . 'mjslib_table.php';

if (!class_exists('StageShowPricesAdminListClass'))
{
	class StageShowPricesAdminListClass extends MJSLibAdminListClass // Define class
	{
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			// FUNCTIONALITY: Prices - Bulk Actions - Delete
			$this->bulkActions = array(
				'delete' => __('Delete', $this->myDomain),
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
		
		function GetMainRowsDefinition()
		{
			// FUNCTIONALITY: Prices - Lists Performance, Type and Price
			return array(
				array(self::TABLEPARAM_LABEL => 'Performance',  self::TABLEPARAM_ID => 'perfID',    self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_SELECT, self::TABLEPARAM_FUNC => 'PerfDates'),
				array(self::TABLEPARAM_LABEL => 'Type',         self::TABLEPARAM_ID => 'priceType', self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => STAGESHOW_PRICETYPE_TEXTLEN, ),						
				array(self::TABLEPARAM_LABEL => 'Price',        self::TABLEPARAM_ID => 'priceValue',self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => 9, ),						
			);
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
		
		function OutputList($results, $updateFailed)
		{
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
	}
}

include STAGESHOW_INCLUDE_PATH . 'mjslib_admin.php';

if (!class_exists('StageShowPricesAdminClass'))
{
	class StageShowPricesAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Prices';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			$showID = 0;
			
			//echo '<div class="wrap">';
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
						$newPerfID     = $_POST['perfID' . $result->priceID];
						$newPriceType  = stripslashes($_POST['priceType' . $result->priceID]);
						$newPriceValue = stripslashes($_POST['priceValue' . $result->priceID]);
						
						// Generate an entry that consists of the PerformanceID and the Price Type
						$priceEntry = $newPerfID . '-' . $newPriceType;
						// FUNCTIONALITY: Prices - Reject Duplicate Price Refs
						if (isset($entriesList[$priceEntry]))
						{
							// Convert the perfID to a Performance Date & Time to display to the user
							$perfsList = $myDBaseObj->GetPerformancesListByPerfID($newPerfID);
							
							$this->adminMsg = __('Duplicated Price Entry', $this->myDomain) . ' (' . $perfsList[0]->perfDateTime . ' - ' . $newPriceType . ')';
							break;
						}
						
						// Verify that the price value is not empty
						if (strlen($newPriceValue) == 0)
						{
							$this->adminMsg = __('Price Not Specified', $this->myDomain) . ' (' . $perfsList[0]->perfDateTime . ' - ' . $newPriceType . ')';
							break;
						}
						
						// Verify that the price value is a numeric value
						if (!is_numeric($newPriceValue))
						{
							$this->adminMsg = __('Invalid Price Entry', $this->myDomain) . ' (' . $newPriceValue . ')';
							break;
						}
						
						// Verify that the price value is non-zero
						if ($newPriceValue == 0.0)
						{
							$this->adminMsg = __('Price Entry cannot be zero', $this->myDomain);
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
					$perfsList = array();
					
					if (count($results) > 0)
					{
						foreach ($results as $result)
						{
							$pricesUpdated = false;
							
							// FUNCTIONALITY: Prices - Save Performance Date/Time, Ref and Price
							$newPerfID = $_POST['perfID' . $result->priceID];
							if ($newPerfID != $result->perfID)
							{
								$myDBaseObj->UpdatePricePerfID($result->priceID, $newPerfID);
								$result->perfID = $newPerfID;
								$pricesUpdated  = true;
							}
							
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
							
							if ($pricesUpdated)
								$perfsList[count($perfsList)] = $result;
							
						} // End foreach
						
						if (count($perfsList) > 0)
							$myDBaseObj->UpdateCartButtons($perfsList);
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
				$myDBaseObj->AddPrice($perfID, '', '0.00');
				
				echo '<div id="message" class="updated"><p>' . __('Settings have been saved', $this->myDomain) . '</p></div>';
			}
		}
		
		function GetAdminListClass()
		{
			return 'StageShowPricesAdminListClass';			
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
?>
      <input type="hidden" name="showID" value="<?php echo $showList->showID; ?>"/>
      <input type="hidden" name="perfID" value="<?php echo $perfsLists[0]->perfID; ?>"/>
<?php
					{
						// FUNCTIONALITY: Prices - Output "Add New Price" Button (if valid)
						$this->OutputButton("addpricebutton", __("Add New Price", $this->myDomain));
					}
				
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
				case 'delete':
					// Don't delete if any tickets have been sold for this performance
					$results = $myDBaseObj->GetSalesListByPriceID($recordId);
					if (count($results) > 0)
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
				case 'delete':
					// FUNCTIONALITY: Prices - Action Bulk Action Delete
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
				case 'delete':
					// FUNCTIONALITY: Prices - Bulk Action Delete - Output Action Status Message
					if ($this->errorCount > 0)
						$actionMsg = ($this->errorCount == 1) ? __("1 Price has a Database Error", $this->myDomain) : $errorCount . ' ' . __("Prices have a Database Error", $this->myDomain);
					else if ($this->blockCount > 0)
						$actionMsg = ($this->blockCount == 1) ? __("1 Price cannot be deleted", $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain) : $this->blockCount . ' ' . __("Prices cannot be deleted", $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain);
					else if ($actionCount > 0)
						$actionMsg = ($actionCount == 1) ? __("1 Price has been deleted", $this->myDomain) : $actionCount . ' ' . __("Prices have been deleted", $this->myDomain);
					else
						$actionMsg = __("Nothing to Delete", $this->myDomain);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>