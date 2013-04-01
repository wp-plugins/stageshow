<?php
/* 
Description: Code for Managing Performances Configuration

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

include STAGESHOW_INCLUDE_PATH . 'stageshowlib_table.php';

if (!class_exists('StageShowPerformancesAdminListClass'))
{
	class StageShowPerformancesAdminListClass extends StageShowLibAdminListClass // Define class
	{
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			// FUNCTIONALITY: Performances - Bulk Actions - Activate/Deactivate and Delete
			$this->bulkActions = array(
				StageShowLibAdminListClass::BULKACTION_TOGGLE => __('Activate/Deactivate', $this->myDomain),
				StageShowLibAdminListClass::BULKACTION_DELETE => __('Delete', $this->myDomain)
			);
			
			$updateFailed = false;
		}
		
		function GetTableID($result)
		{
			return "showtab" . $result->showID;
		}
		
		function GetRecordID($result)
		{
			return $result->perfID;
		}
		
		function GetMainRowsDefinition()
		{
			// FUNCTIONALITY: Performances - Lists Performance Date & Time, Reference, Max Seats, Tickets Sold Count and Activation State
			return array(
				array(self::TABLEPARAM_LABEL => 'Date & Time',  self::TABLEPARAM_ID => 'perfDateTime', self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,  self::TABLEPARAM_LEN => 28, ),
				array(self::TABLEPARAM_LABEL => 'Reference',    self::TABLEPARAM_ID => 'perfRef',      self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,  self::TABLEPARAM_LEN => STAGESHOW_PERFREF_TEXTLEN, ),
				array(self::TABLEPARAM_LABEL => 'Max Seats',    self::TABLEPARAM_ID => 'perfSeats',    self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,  self::TABLEPARAM_DECODE =>'GetPerfMaxSeats',  self::TABLEPARAM_LEN => 4, ),						
				array(self::TABLEPARAM_LABEL => 'Tickets Sold', self::TABLEPARAM_ID => 'soldQty',      self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, self::TABLEPARAM_LINK =>'admin.php?page='.STAGESHOW_MENUPAGE_SALES.'&action=perf&id=', ),						
				array(self::TABLEPARAM_LABEL => 'State',        self::TABLEPARAM_ID => 'perfState',    self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, self::TABLEPARAM_DECODE =>'GetPerfState'),						
			);
		}
		
		function GetPerfMaxSeats($perfSeats)
		{
			// FUNCTIONALITY: Performances - Negative Max Seats shown as infinity
			if ($perfSeats < 0)
				$perfSeats = '&#8734';
			return $perfSeats;
		}
		
		function GetPerfState($perfState)
		{
			// FUNCTIONALITY: Performances - Activation State shown as "Active" or "INACTIVE"
			$perfStateText = $this->myDBaseObj->IsStateActive($perfState) ? __("Active", $this->myDomain) : __("INACTIVE", $this->myDomain);
			return $perfStateText;
		}
		
		function OutputList($results, $updateFailed)
		{
			// FUNCTIONALITY: Performances - Reset Shows form on update failure
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
		
	}
}

include STAGESHOW_INCLUDE_PATH . 'stageshowlib_admin.php';

if (!class_exists('StageShowPerformancesAdminClass'))
{
	class StageShowPerformancesAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Performance Editor';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			$delperfId = 0;
			
			$perfsMsg = '';
			
			// FUNCTIONALITY: Performances - Save Changes
			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();
				
				// Save Settings Request ....
				$showID  = $_POST['showID'];
				$results = $myDBaseObj->GetPerformancesListByShowID($showID);
				
				// Verify that performance Refs are unique 
				
				if (count($results) > 0)
				{
					foreach ($results as $result)
					{
						$perfDateTime = stripslashes($_POST['perfDateTime' . $result->perfID]);
						// FUNCTIONALITY: Performances - Reject Duplicate Performance Date & Time
						if (isset($datesList[$perfDateTime]))
						{
							$perfsMsg = __('Duplicated Performance Date', $this->myDomain) . ' (' . $perfDateTime . ')';
							break;
						}
						$datesList[$perfDateTime] = true;
						
						$perfRef = stripslashes($_POST['perfRef' . $result->perfID]);
						if ( ($perfRef != $result->perfRef) && !$myDBaseObj->IsPerfRefUnique($perfRef) )
						{
							$perfsMsg = __('Duplicated Performance Reference', $this->myDomain) . ' (' . $perfRef . ')';
							break;
						}
						
						// FUNCTIONALITY: Performances - Validate Performance Date/Time
						// Verify that the date value is not empty
						if (strlen($perfDateTime) == 0)
						{
							$perfsMsg = __('Empty Date Entry', $this->myDomain);
							break;
						}
						
						// Verify that the date value is valid
						if (strtotime($perfDateTime) == FALSE)
						{
							$perfsMsg = __('Invalid Date Entry', $this->myDomain) . ' (' . $perfDateTime . ')';
							break;
						}
					}
				}
				
				if ($perfsMsg !== '')
				{
					echo '<div id="message" class="error"><p>' . __('Settings have NOT been saved', $this->myDomain) . '. ' . $perfsMsg . '</p></div>';
				}
				else
				{
					$perfsList = array();
					
					if (count($results) > 0)
					{
						$classId       = $this->GetAdminListClass();
						$adminTableObj = new $classId($this->env);
						
						// Get the extended settings array
						$settings = $adminTableObj->GetDetailsRowsDefinition();
						$dbOpts   = $adminTableObj->ExtendedSettingsDBOpts();
						
						foreach ($results as $result)
						{
							$perfUpdated     = false;
							$newPerfDateTime = stripslashes($_POST['perfDateTime' . $result->perfID]);
							
							// Reformat date & time to correct for for MySQL
							$reformattedPerfDateTime = strftime('%Y-%m-%d %H:%M:%S', strtotime($newPerfDateTime));
							//if ($newPerfDateTime !== $reformattedPerfDateTime)
							//	echo "Reformatted $newPerfDateTime to $reformattedPerfDateTime <br>\n";
							$newPerfDateTime         = $reformattedPerfDateTime;
							
							// FUNCTIONALITY: Performances - Save Performance Date/Time, Ref and Max Seats
							if ($newPerfDateTime != $result->perfDateTime)
							{
								$myDBaseObj->UpdatePerformanceTime($result->perfID, $newPerfDateTime);
								$result->perfDateTime = $newPerfDateTime;
								$perfUpdated          = true;
							}
							
							$newPerfRef = stripslashes($_POST['perfRef' . $result->perfID]);
							if ($newPerfRef != $result->perfRef)
							{
								$myDBaseObj->UpdatePerformanceRef($result->perfID, $newPerfRef);
								$result->perfRef = $newPerfRef;
								$perfUpdated     = true;
							}
							
							$newPerfSeats = stripslashes($_POST['perfSeats' . $result->perfID]);
							if (!is_numeric($newPerfSeats) || ($newPerfSeats < 0))
								$newPerfSeats = -1;
							if ($newPerfSeats != $result->perfSeats)
							{
								$myDBaseObj->UpdatePerformanceSeats($result->perfID, $newPerfSeats);
								$result->perfSeats = $newPerfSeats;
								$perfUpdated       = true;
							}
							
							// Save option extensions
							$this->UpdateHiddenRowValues($result, $result->perfID, $settings, $dbOpts);
							
							if ($perfUpdated)
								$perfsList[count($perfsList)] = $result;
						} // End of foreach($results as $result)
						
						// Add this entry to the list of entries to be updated
						if (count($perfsList) > 0)
							$myDBaseObj->UpdateCartButtons($perfsList);
					}
					echo '<div id="message" class="updated"><p>' . __('Settings have been saved', $this->myDomain) . '</p></div>';
				}
			}
			else if (isset($_POST['addperfbutton']) && isset($_POST['showID']))
			{
				// FUNCTIONALITY: Performances - Add Performance 
				$this->CheckAdminReferer();
				
				$showID = $_POST['showID'];
				
				$statusMsg = '';
				$newPerfID = $myDBaseObj->CreateNewPerformance($statusMsg, $showID, date(StageShowDBaseClass::MYSQL_DATETIME_FORMAT, current_time('timestamp')));
				
				$statusMsgClass = ($newPerfID > 0) ? 'updated' : 'error';
				echo '<div id="message" class="' . $statusMsgClass . '"><p>' . $statusMsg . '</p></div>';
			}
		}
		
		function GetAdminListClass()
		{
			return 'StageShowPerformancesAdminListClass';
		}
		
		function OutputButton($buttonId, $buttonText, $buttonClass = "button-secondary")
		{
			parent::OutputButton($buttonId, $buttonText, $buttonClass);
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			// Stage Show Performances HTML Output - Start 
			$showLists = $myDBaseObj->GetAllShowsList();
			if (count($showLists) == 0)
			{
				// FUNCTIONALITY: Performances - Show Link to Settings page if PayPal settings required
				if ($myDBaseObj->CheckIsConfigured())
				{
					$showsPageURL = get_option('siteurl') . '/wp-admin/admin.php?page=' . STAGESHOW_MENUPAGE_SHOWS;
					echo "<div class='error'><p>" . __('No Show Configured', $this->myDomain) . ' - <a href=' . $showsPageURL . '>' . __('Add one Here', $this->myDomain) . '</a>' . "</p></div>\n";
				}
			}
			
			foreach ($showLists as $showList)
			{
				$results = $myDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
	<div class="stageshow-admin-form">
	<form method="post">
	<h3><?php echo($showList->showName); ?></h3>
<?php
				$this->WPNonceField();
				if (count($results) == 0)
				{
					echo __('Show has No Performances', $this->myDomain) . "<br>\n";
				}
				else
				{
					$thisUpdateFailed = (($updateFailed) && ($showList->showID == $_POST['showID']));
					$classId          = $this->GetAdminListClass();
					$adminTableObj    = new $classId($this->env);
					$adminTableObj->OutputList($results, $thisUpdateFailed);
				} // End of if (count($results) == 0) ... else ...
				
?>
      <input type="hidden" name="showID" value="<?php echo $showList->showID; ?>"/>
<?php
				if ($myDBaseObj->CanAddPerformance())
				{
					// FUNCTIONALITY: Performances - Output "Add New Show" Button (if valid)
					$this->OutputButton("addperfbutton", __("Add New Performance", $this->myDomain));
				}
				
				if (count($results) > 0)
				{
					// FUNCTIONALITY: Performances - Output "Save Changes" Button (if there are entries)
					$this->OutputButton("savechanges", __("Save Changes", $this->myDomain), "button-primary");					
				}
?>
</form>
</div>		
<?php
			} // End of foreach ($showLists as $showList) ..

			// Stage Show Performances HTML Output - End 
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
				case StageShowLibAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Performances - Bulk Action Delete - Block if tickets sold
					// Don't delete if any tickets have been sold for this performance
					$delPerfEntry = $myDBaseObj->GetPerformancesListByPerfID($recordId);
					if (count($delPerfEntry) == 0)
						$this->errorCount++;
					else if (!$myDBaseObj->CanDeletePerformance($delPerfEntry[0]))
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
					// FUNCTIONALITY: Performances - Bulk Action Delete - Remove Prices, Hosted Buttons and Performance
					// Delete all prices for this performance
					$myDBaseObj->DeletePriceByPerfID($recordId);
					
					// Get the performance entry
					$results = $myDBaseObj->GetPerformancesListByPerfID($recordId);

					if (!$myDBaseObj->UseIntegratedTrolley())					
					{
						// Delete any PayPal buttons ....
						$myDBaseObj->payPalAPIObj->DeleteButton($results[0]->perfPayPalButtonID);
					}
					
					// Delete a performance entry
					$myDBaseObj->DeletePerformanceByPerfID($recordId);
					return true;
				
				case StageShowLibAdminListClass::BULKACTION_TOGGLE:
					// FUNCTIONALITY: Performances - Bulk Action Activate/Deactivate
					$perfEntry = $myDBaseObj->GetPerformancesListByPerfID($recordId);
					if ($myDBaseObj->IsStateActive($perfEntry[0]->perfState))
						$myDBaseObj->SetPerfActivated($recordId, STAGESHOW_STATE_INACTIVE);
					else
						$myDBaseObj->SetPerfActivated($recordId, STAGESHOW_STATE_ACTIVE);
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
					// FUNCTIONALITY: Performances - Bulk Action Delete - Output Action Status Message
					if ($this->errorCount > 0)
						$actionMsg = ($this->errorCount == 1) ? __("1 Performance has a Database Error", $this->myDomain) : $errorCount . ' ' . __("Performances have a Database Error", $this->myDomain);
					else if ($this->blockCount > 0)
						$actionMsg = ($this->blockCount == 1) ? __("1 Performance cannot be deleted", $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain) : $this->blockCount . ' ' . __("Performances cannot be deleted", $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain);
					else if ($actionCount > 0)
						$actionMsg = ($actionCount == 1) ? __("1 Performance has been deleted", $this->myDomain) : $actionCount . ' ' . __("Performances have been deleted", $this->myDomain);
					else
						$actionMsg = __("Nothing to Delete", $this->myDomain);
					break;
				
				case StageShowLibAdminListClass::BULKACTION_TOGGLE:
					// FUNCTIONALITY: Performances - Bulk Action Activate/Deactivate - Output Action Status Message
					if ($actionCount > 0)
						$actionMsg = ($actionCount == 1) ? __("1 Performance has been Activated/Deactivated", $this->myDomain) : $actionCount . ' ' . __("Performances have been Activated/Deactivated", $this->myDomain);
					else
						$actionMsg = __("Nothing to Delete", $this->myDomain);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>