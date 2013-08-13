<?php
/* 
Description: Code for Managing Show Configuration

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
include STAGESHOW_INCLUDE_PATH . 'stageshow_admin.php';

if (!class_exists('StageShowShowsAdminListClass'))
{
	class StageShowShowsAdminListClass extends StageShowAdminListClass // Define class
	{
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			$this->SetRowsPerPage(self::STAGESHOWLIB_EVENTS_UNPAGED);
			
			// FUNCTIONALITY: Shows - Bulk Actions - Activate/Deactivate and Delete
			$this->bulkActions = array(
				StageShowLibAdminListClass::BULKACTION_TOGGLE => __('Activate/Deactivate', $this->myDomain),
				StageShowLibAdminListClass::BULKACTION_DELETE => __('Delete', $this->myDomain),
			);
		}
		
		function GetTableID($result)
		{
			return "showtab";
		}
		
		function GetRecordID($result)
		{
			return $result->showID;
		}
		
		function GetMainRowsDefinition()
		{
			// FUNCTIONALITY: Shows - Lists Show Names, Tickets Sold (with link to Show Sales page) and Show "State""
			return array(
				array(self::TABLEPARAM_LABEL => 'Show Name',    self::TABLEPARAM_ID => 'showName',   self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => STAGESHOW_SHOWNAME_TEXTLEN, ),
				array(self::TABLEPARAM_LABEL => 'Tickets Sold', self::TABLEPARAM_ID => 'soldQty',    self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE,  self::TABLEPARAM_LINK =>'admin.php?page='.STAGESHOW_MENUPAGE_SALES.'&action=show&id=', ),						
				array(self::TABLEPARAM_LABEL => 'State',        self::TABLEPARAM_ID => 'showState',  self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE,  self::TABLEPARAM_DECODE =>'GetShowState', ),						
			);
		}
		
		function GetShowState($showState)
		{
			// FUNCTIONALITY: Shows - Report show state
			return $this->myDBaseObj->StateActiveText($showState);
		}
		
		function OutputList($results, $updateFailed)
		{
			// FUNCTIONALITY: Shows - Reset Shows form on update failure
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
		
	}
}

include STAGESHOW_INCLUDE_PATH . 'stageshowlib_admin.php';

if (!class_exists('StageShowShowsAdminClass'))
{
	class StageShowShowsAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Show Editor';
			
			// Call base constructor
			parent::__construct($env);			
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			// FUNCTIONALITY: Shows - Save Changes
			if (isset($_POST['savechanges']))
			{
				// Save Settings Request ....
				$results = $myDBaseObj->GetAllShowsList();
				
				// Verify that show names are unique 				
				if (count($results) > 0)
				{
					foreach ($results as $result)
					{
						$showEntry = stripslashes($_POST['showName' . $result->showID]);
						// FUNCTIONALITY: Shows - Reject Duplicate or Empty Show Name
						if (strlen($showEntry) == 0)
						{
							$this->adminMsg = __('Empty Show Name Entry', $this->myDomain);
							break;
						}
						
						if (isset($entriesList[$showEntry]))
						{
							$this->adminMsg = __('Duplicated Show Name', $this->myDomain) . ' (' . $showEntry . ')';
							break;
						}
						$entriesList[$showEntry] = true;
					}
				}
				
				if ($this->adminMsg !== '')
				{
					echo '<div id="message" class="error"><p>' . __('Settings have NOT been saved', $this->myDomain) . '. ' . $this->adminMsg . '</p></div>';
				}
				else
				{
					if (count($results) > 0)
					{
						$classId       = $this->GetAdminListClass();
						$adminTableObj = new $classId($this->env);
						
						// Get the extended settings array
						$settings = $adminTableObj->GetDetailsRowsDefinition();
						$dbOpts   = $adminTableObj->ExtendedSettingsDBOpts();
						
						foreach ($results as $result)
						{
							$newShowName = stripslashes($_POST['showName' . $result->showID]);
							if ($newShowName != $result->showName)
							{
								$myDBaseObj->UpdateShowName($result->showID, $newShowName);
							}
							
							// FUNCTIONALITY: Shows - Save "Options" settings
							// Save option extensions
							$this->UpdateHiddenRowValues($result, $result->showID, $settings, $dbOpts);
						}
					}
					echo '<div id="message" class="updated"><p>' . __('Settings have been saved', $this->myDomain) . '</p></div>';
				}
			}
			
			if (isset($_POST['addshowbutton']))
			{
				// FUNCTIONALITY: Shows - Add a new show
				// Add Show with unique Show Name 
				$showID = $myDBaseObj->AddShow('');
				
				if ($showID == 0)
					echo '<div id="message" class="error"><p>' . __('Cannot add a new show - Only one show allowed', $this->myDomain) . '</p></div>';
				else
					echo '<div id="message" class="updated"><p>' . __('Default entry added - Edit and Save to update it.', $this->myDomain) . '</p></div>';
			}
			
		}
		
		function GetAdminListClass()
		{
			return 'StageShowShowsAdminListClass';			
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// FUNCTIONALITY: Shows - Show Link to Settings page if PayPal settings required
			if (!$myDBaseObj->CheckIsConfigured())
				return;
			
?>
	<div class="stageshow-admin-form">
	<form method="post">
<?php

			$this->WPNonceField();
			
			$results = $myDBaseObj->GetAllShowsList();
			if (count($results) == 0)
			{
				echo "<div class='noconfig'>" . __('No Show Configured', $this->myDomain) . "</div>\n";
			}
			else
			{
				$classId       = $this->GetAdminListClass();
				$adminTableObj = new $classId($this->env);
				$adminTableObj->OutputList($results, $updateFailed);
			}
			
			if ($myDBaseObj->CanAddShow())
			{
				// FUNCTIONALITY: Shows - Output "Add New Show" Button (if valid)
				$this->OutputButton("addshowbutton", __("Add New Show", $this->myDomain));
			}
			
			if (count($results) > 0)
			{
				// FUNCTIONALITY: Shows - Output "Save Changes" Button (if there are entries)
				$this->OutputButton("savechanges", __("Save Changes", $this->myDomain), "button-primary");
			}
?>
	</form>
	</div>
<?php
		} // End of function Output_MainPage()
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Reset error count etc. on first pass
			if (!isset($this->errorCount)) $this->errorCount = 0;
			if (!isset($this->blockCount)) $this->blockCount = 0;
			
			switch ($bulkAction)
			{
				case StageShowLibAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Shows - Bulk Action Delete - Block if tickets sold
					// Don't delete if any tickets have been sold for this performance
					$delShowEntry = $myDBaseObj->GetShowsList($recordId);
					if (count($delShowEntry) == 0)
						$errorCount++;
					else if (!$myDBaseObj->CanDeleteShow($delShowEntry[0]))
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
					// FUNCTIONALITY: Shows - Bulk Action Delete - Remove Prices, Hosted Buttons, Performances and Show		
					// Get a list of performances
					$results = $myDBaseObj->GetPerformancesListByShowID($recordId);
					
					foreach ($results as $result)
					{
						// Get ID of performance to delete
						$delperfId = $result->perfID;
						
						// Note: Prices are deleted by Database Cleanup - $myDBaseObj->DeletePriceByPerfID($delperfId);
						
						if (!$myDBaseObj->UseIntegratedTrolley())					
						{
							// Delete any PayPal buttons ....
							$myDBaseObj->payPalAPIObj->DeleteButton($result->perfPayPalButtonID);
						}
												
						// Delete a performances entry (Marks entry as deleted)
						$myDBaseObj->DeletePerformanceByPerfID($delperfId);
					}
					
					// Now delete the entry in the SHOWS table
					$delShowName = $myDBaseObj->DeleteShowByShowID($recordId);
					$myDBaseObj->PurgeDB();
					return true;
				
				case StageShowLibAdminListClass::BULKACTION_TOGGLE:
					// FUNCTIONALITY: Shows - Bulk Action Activate/Deactivate Show		
					$actionCount = 0;
					$showEntry   = $myDBaseObj->GetShowsList($recordId);
					if ($myDBaseObj->IsStateActive($showEntry[0]->showState))
						$myDBaseObj->SetShowActivated($recordId, STAGESHOW_STATE_INACTIVE);
					else
						$myDBaseObj->SetShowActivated($recordId, STAGESHOW_STATE_ACTIVE);
					
					// TODO-PRIORITY - Update Inventory Settings for Performance Buttons
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
					// FUNCTIONALITY: Shows - Bulk Action Delete - Output Action Status Message
					if ($this->errorCount > 0)
						$actionMsg = ($this->errorCount == 1) ? __("1 Show has a Database Error", $this->myDomain) : $errorCount . ' ' . __("Shows have a Database Error", $this->myDomain);
					else if ($this->blockCount > 0)
						$actionMsg = ($this->blockCount == 1) ? __("1 Show cannot be deleted", $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain) : $this->blockCount . ' ' . __("Shows cannot be deleted", $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain);
					else if ($actionCount > 0)
						$actionMsg = ($actionCount == 1) ? __("1 Show has been deleted", $this->myDomain) : $actionCount . ' ' . __("Shows have been deleted", $this->myDomain);
					else
						$actionMsg = __("Nothing to Delete", $this->myDomain);
					break;
					
				case StageShowLibAdminListClass::BULKACTION_TOGGLE:
					// FUNCTIONALITY: Shows - Bulk Action Delete - Output Action Status Message
					if ($actionCount > 0)
						$actionMsg = ($actionCount == 1) ? __("1 Show has been Activated/Deactivated", $this->myDomain) : $actionCount . ' ' . __("Shows have been Activated/Deactivated", $this->myDomain);
					else
						$actionMsg = __("Nothing to Activate/Deactivate", $this->myDomain);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>