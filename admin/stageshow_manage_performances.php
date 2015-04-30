<?php
/* 
Description: Code for Managing Performances Configuration

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

include STAGESHOW_INCLUDE_PATH.'stageshowlib_salesadmin.php';

if (!class_exists('StageShowWPOrgPerformancesAdminListClass'))
{
	class StageShowWPOrgPerformancesAdminListClass extends StageShowLibSalesAdminListClass // Define class
	{
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			$this->dateTimeMode = 'datetime';	// Don't display seconds
			
			$this->SetRowsPerPage(self::STAGESHOWLIB_EVENTS_UNPAGED);
			
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
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Date & Time',  StageShowLibTableClass::TABLEPARAM_ID => 'perfDateTime', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibAdminListClass::TABLEENTRY_DATETIME,  StageShowLibTableClass::TABLEPARAM_DECODE => 'FormatDateForAdminDisplay', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Reference',    StageShowLibTableClass::TABLEPARAM_ID => 'perfRef',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,  StageShowLibTableClass::TABLEPARAM_LEN => STAGESHOW_PERFREF_TEXTLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Max Seats',    StageShowLibTableClass::TABLEPARAM_ID => 'perfSeats',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,  StageShowLibTableClass::TABLEPARAM_DECODE =>'GetPerfMaxSeats',  StageShowLibTableClass::TABLEPARAM_LEN => 4, ),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Tickets Sold', StageShowLibTableClass::TABLEPARAM_ID => 'soldQty',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, StageShowLibTableClass::TABLEPARAM_LINK =>'admin.php?page='.STAGESHOW_MENUPAGE_SALES.'&action=perf&id=', ),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'State',        StageShowLibTableClass::TABLEPARAM_ID => 'perfState',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, StageShowLibTableClass::TABLEPARAM_DECODE =>'GetPerfState'),						
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
			switch ($perfState)
			{
				case STAGESHOW_STATE_ACTIVE:
				default:
					$perfStateText =__("Active", $this->myDomain);
					break;
					
				case STAGESHOW_STATE_INACTIVE:
					$perfStateText =__("INACTIVE", $this->myDomain);
					break;
					
				case STAGESHOW_STATE_DELETED:
					$perfStateText ='('.__("Deleted", $this->myDomain).')';
					break;
					
			}
			
			return $perfStateText;
		}
		
		function OutputList($results, $updateFailed = false)
		{
			// FUNCTIONALITY: Performances - Reset Shows form on update failure
			$this->updateFailed = $updateFailed;
			parent::OutputList($results, $updateFailed);
		}
		
	}
}

include STAGESHOW_INCLUDE_PATH . 'stageshowlib_admin.php';

if (!class_exists('StageShowWPOrgPerformancesAdminClass'))
{
	class StageShowWPOrgPerformancesAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Performances';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ValidateEditPerformances($result)
		{
			// FUNCTIONALITY: Performances - Save Changes
			$perfDateTime = stripslashes($_POST['perfDateTime' . $result->perfID]);
			
			// FUNCTIONALITY: Performances - Verify that the date value is valid
			if (strlen($perfDateTime) == 0)
			{
				return __('Blank Date Entry', $this->myDomain) . ' (' . $perfDateTime . ')';
			}
			if (strtotime($perfDateTime) == FALSE)
			{
				return __('Invalid Date Entry', $this->myDomain) . ' (' . $perfDateTime . ')';
			}
						
			// FUNCTIONALITY: Performances - Reject Duplicate Performance Date & Time
			if (isset($this->datesList[$perfDateTime]))
			{
				return __('Duplicated Performance Date', $this->myDomain) . ' (' . $perfDateTime . ')';
			}
			$this->datesList[$perfDateTime] = true;
						
			$perfRef = stripslashes($_POST['perfRef' . $result->perfID]);
			if ( ($perfRef != $result->perfRef) && !$this->myDBaseObj->IsPerfRefUnique($perfRef) )
			{
				return __('Duplicated Performance Reference', $this->myDomain) . ' (' . $perfRef . ')';
			}
						
			// FUNCTIONALITY: Performances - Validate Performance Date/Time
			// Verify that the date value is not empty
			if (strlen($perfDateTime) == 0)
			{
				return __('Empty Date Entry', $this->myDomain);
			}
						
			return '';
		}
		
		function SavePerformance(&$result)
		{
			$perfUpdated = false;
			$myDBaseObj = $this->myDBaseObj;
			
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
				$perfUpdated = true;
			}

			$newPerfRef = stripslashes($_POST['perfRef' . $result->perfID]);
			if ($newPerfRef != $result->perfRef)
			{
				$myDBaseObj->UpdatePerformanceRef($result->perfID, $newPerfRef);
				$result->perfRef = $newPerfRef;
				$perfUpdated = true;
			}

			$newPerfSeats = stripslashes($_POST['perfSeats' . $result->perfID]);
			if (!is_numeric($newPerfSeats) || ($newPerfSeats < 0))
				$newPerfSeats = -1;
			if ($newPerfSeats != $result->perfSeats)
			{
				$myDBaseObj->UpdatePerformanceSeats($result->perfID, $newPerfSeats);
				$result->perfSeats = $newPerfSeats;
				$perfUpdated = true;
			}
			
			return $perfUpdated;
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
						$perfsMsg = $this->ValidateEditPerformances($result);
						if ($perfsMsg != '')
							break;												
					}
				}
				
				if ($perfsMsg !== '')
				{
					echo '<div id="message" class="error"><p>' . __('Settings have NOT been saved', $this->myDomain) . '. ' . $perfsMsg . '</p></div>';
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
							$perfUpdated = $this->SavePerformance($result);
							
							// Save option extensions
							$this->UpdateHiddenRowValues($result, $result->perfID, $settings, $dbOpts);
						} // End of foreach($results as $result)
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
				$newPerfID = $myDBaseObj->CreateNewPerformance($statusMsg, $showID, date(StageShowWPOrgDBaseClass::MYSQL_DATETIME_FORMAT, current_time('timestamp')));
				
				$statusMsgClass = ($newPerfID > 0) ? 'updated' : 'error';
				echo '<div id="message" class="' . $statusMsgClass . '"><p>' . $statusMsg . '</p></div>';
			}
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
				// FUNCTIONALITY: Performances - Show Link to Settings page if Payment Gateway settings required
				if ($myDBaseObj->CheckIsConfigured())
				{
					$showsPageURL = get_option('siteurl') . '/wp-admin/admin.php?page=' . STAGESHOW_MENUPAGE_SHOWS;
					echo "<div class='error'><p>" . __('No Show Configured', $this->myDomain) . ' - <a href=' . $showsPageURL . '>' . __('Add one Here', $this->myDomain) . '</a>' . "</p></div>\n";
				}
			}
			
					echo "
<script>
var PerfIDList = new Array();

StageShowLib_addWindowsLoadHandler(stageshow_OnLoadPerformances); 

function stageshow_OnLoadPerformances()
{
	for (var index=0; index<PerfIDList.length; index++)
	{
		var seatingSetObjId = 'perfSeatingID' + PerfIDList[index];
		var seatingSetObj = document.getElementById(seatingSetObjId);
		stageshow_OnClickSeatingID(seatingSetObj);	
	}
}
</script>
						";
						
			foreach ($showLists as $showList)
			{
				$results = $myDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
	<div class="stageshow-admin-form">
	<form method="post">
	<h3><?php echo($showList->showName); ?></h3>
<?php
				foreach ($results as $result)
				{
					echo '
<script>
PerfIDList.push('.$result->perfID.');
</script>
						';
				}
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
					// Note: Prices are deleted by Database Cleanup - $myDBaseObj->DeletePriceByPerfID($recordId);
					
					// Get the performance entry
					$results = $myDBaseObj->GetPerformancesListByPerfID($recordId);

					// Delete a performance entry
					$myDBaseObj->DeletePerformanceByPerfID($recordId);
					$myDBaseObj->PurgeDB();
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
						$actionMsg = $this->errorCount . ' ' . _n("Performance does not exist in Database", "Performances do not exist in Database", $this->errorCount, $this->myDomain);
					else if ($this->blockCount > 0)
						$actionMsg = $this->blockCount . ' ' . _n("Performance cannot be deleted", "Performances cannot be deleted", $this->blockCount, $this->myDomain).' - '.__("Tickets already sold!", $this->myDomain);
					else if ($actionCount > 0)
						$actionMsg = $actionCount . ' ' . _n("Performance has been deleted", "Performances have been deleted", $actionCount, $this->myDomain);
					else
						$actionMsg = __("Nothing to Delete", $this->myDomain);
					break;
				
				case StageShowLibAdminListClass::BULKACTION_TOGGLE:
					// FUNCTIONALITY: Performances - Bulk Action Activate/Deactivate - Output Action Status Message
					if ($actionCount > 0)
						$actionMsg = $actionCount . ' ' . _n("Performance has been Activated/Deactivated", "Performances have been Activated/Deactivated", $actionCount, $this->myDomain);
					else
						$actionMsg = __("Nothing to Activate/Deactivate", $this->myDomain);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>