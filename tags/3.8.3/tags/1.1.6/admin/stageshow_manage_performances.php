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

include STAGESHOW_INCLUDE_PATH.'mjslib_table.php';

if (!class_exists('StageShowPerformancesAdminListClass')) 
{
	class StageShowPerformancesAdminListClass extends MJSLibAdminListClass // Define class
	{				
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			$this->bulkActions = array(
				'activate' => __('Activate/Deactivate', STAGESHOW_DOMAIN_NAME),
				'delete'   => __('Delete', STAGESHOW_DOMAIN_NAME),
				);
			
			$updateFailed = false;
		}
		
		function GetTableID($result)
		{
			return "showtab".$result->showID;
		}
		
		function GetRecordID($result)
		{
			return $result->perfID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array('Label' => 'Date & Time',  'Id' => 'perfDateTime', 'Type' => MJSLibTableClass::TABLEENTRY_TEXT,  'Len' => 28, ),
				array('Label' => 'Reference',    'Id' => 'perfRef',      'Type' => MJSLibTableClass::TABLEENTRY_TEXT,  'Len' => STAGESHOW_PERFREF_TEXTLEN, ),
				array('Label' => 'Max Seats',    'Id' => 'perfSeats',    'Type' => MJSLibTableClass::TABLEENTRY_TEXT,  'Decode' => 'GetPerfMaxSeats',  'Len' => 4, ),						
				array('Label' => 'Tickets Sold', 'Id' => 'totalQty',     'Type' => MJSLibTableClass::TABLEENTRY_VALUE, 'Link' => 'admin.php?page='.STAGESHOW_MENUPAGE_SALES.'&action=perf&id=', ),						
				array('Label' => 'State',        'Id' => 'perfState',    'Type' => MJSLibTableClass::TABLEENTRY_VALUE, 'Decode' => 'GetPerfState'),						
			);
		}
		
		function GetPerfMaxSeats($result)
		{
			$perfSeats = $result->perfSeats;
			if ($perfSeats < 0) $perfSeats = '&#8734';
			return $perfSeats;
		}
		
		function GetPerfState($result)
		{
			$perfState = $this->myDBaseObj->IsStateActive($result->perfState) ? __("Active", STAGESHOW_DOMAIN_NAME) : __("INACTIVE", STAGESHOW_DOMAIN_NAME);
			return $perfState;
		}
		
		function OutputList($results, $updateFailed)
		{
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
		
	}
}

include STAGESHOW_INCLUDE_PATH.'mjslib_admin.php';      

if (!class_exists('StageShowPerformancesAdminClass')) 
{
	class StageShowPerformancesAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env) //constructor	
    {
			// Call base constructor
			parent::__construct($env);
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
      
			$delperfId = 0;
			
			$perfsMsg = '';
				 
			echo '<div class="wrap">';
			if (isset($_POST['saveperfbutton']))
			{
				$this->CheckAdminReferer();
				
				// Save Settings Request ....
				$showID = $_POST['showID'];
				$results = $myDBaseObj->GetPerformancesListByShowID($showID);
						
				// Verify that performance Refs are unique 
						
        if(count($results) > 0)
        {
	        foreach($results as $result)
					{
						$perfDateTime = stripslashes($_POST['perfDateTime'.$result->perfID]);
						if (isset($datesList[$perfDateTime]))
						{
							$perfsMsg = __('Duplicated Date Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfDateTime.')';
							break;
						}
						$datesList[$perfDateTime] = true;

						$perfRef = stripslashes($_POST['perfRef'.$result->perfID]);
						if (isset($refsList[$perfRef]))
						{
							$perfsMsg = __('Duplicated Date Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfRef.')';
							break;
						}
						$refsList[$perfRef] = true;
							
						// Verify that the date value is not empty
						if (strlen($perfDateTime) == 0)
						{
							$perfsMsg = __('Empty Date Entry', STAGESHOW_DOMAIN_NAME);
							break;
						}
							
						// Verify that the date value is valid
						if (strtotime($perfDateTime) == FALSE)
						{
							$perfsMsg = __('Invalid Date Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfDateTime.')';
							break;
						}
					}
				}				
						
				if ($perfsMsg !== '')
				{
					echo '<div id="message" class="error"><p>'.__('Settings have NOT been saved', STAGESHOW_DOMAIN_NAME).'. '.$perfsMsg.'</p></div>';
				}
        else 
				{
					$perfsList = array();
				
					if(count($results) > 0)
					{
						$classId = $env['PluginObj']->adminClassPrefix.'PerformancesAdminListClass';
						$adminTableObj = new $classId($env);		
						
						// Get the extended settings array
						$settings = $adminTableObj->GetDetailsRowsDefinition();
						$dbOpts = $adminTableObj->ExtendedSettingsDBOpts();
			
						foreach($results as $result)
						{
							$perfUpdated = false;
							$newPerfDateTime = stripslashes($_POST['perfDateTime'.$result->perfID]);
							
							// Reformat date & time to correct for for MySQL
							$reformattedPerfDateTime = strftime('%Y-%m-%d %H:%M:%S', strtotime($newPerfDateTime));
							//if ($newPerfDateTime !== $reformattedPerfDateTime)
							//	echo "Reformatted $newPerfDateTime to $reformattedPerfDateTime <br>\n";
							$newPerfDateTime = $reformattedPerfDateTime;
							
							if ($newPerfDateTime != $result->perfDateTime)
							{
								$myDBaseObj->UpdatePerformanceTime($result->perfID, $newPerfDateTime);
								$result->perfDateTime = $newPerfDateTime;
								$perfUpdated = true;
							}

							$newPerfRef = stripslashes($_POST['perfRef'.$result->perfID]);
							if ($newPerfRef != $result->perfRef)
							{
								$myDBaseObj->UpdatePerformanceRef($result->perfID, $newPerfRef);
								$result->perfRef = $newPerfRef;
								$perfUpdated = true;
							}
							
							$newPerfSeats = stripslashes($_POST['perfSeats'.$result->perfID]);
							if (!is_numeric($newPerfSeats) || ($newPerfSeats < 0)) $newPerfSeats = -1;
							if ($newPerfSeats != $result->perfSeats)
							{
								$myDBaseObj->UpdatePerformanceSeats($result->perfID, $newPerfSeats);
								$result->perfSeats = $newPerfSeats;
								$perfUpdated = true;
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
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
			}			
			else if (isset($_POST['addperfbutton']) && isset($_POST['showID']))
			{
				$this->CheckAdminReferer();
				
				$showID = $_POST['showID'];
				
				$statusMsg = '';
				$newPerfID = $myDBaseObj->CreateNewPerformance($statusMsg, $showID, date(StageShowDBaseClass::MYSQL_DATETIME_FORMAT, current_time('timestamp')));				

				$statusMsgClass = ($newPerfID > 0) ? 'updated' : 'error';
				echo '<div id="message" class="'.$statusMsgClass.'"><p>'.$statusMsg.'</p></div>';
			}			 

			$this->Output_MainPage($env, $perfsMsg !== '');
		}		
		
		function Output_MainPage($env, $updateFailed)
		{			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// Stage Show Performances HTML Output - Start 
?>
<div class="wrap">
	<div id="icon-stageshow" class="icon32"></div>
      <h2><?php echo $myPluginObj->pluginName.' - '.__('Performance Editor', STAGESHOW_DOMAIN_NAME); ?></h2>
	<?php
$showLists = $myDBaseObj->GetAllShowsList();
if (count($showLists) == 0)
{
	if ($myDBaseObj->CheckIsConfigured())
	{
		$showsPageURL = get_option('siteurl').'/wp-admin/admin.php?page='.STAGESHOW_MENUPAGE_SHOWS;
		echo "<div class='error'><p>".__('No Show Configured', STAGESHOW_DOMAIN_NAME).' - <a href='.$showsPageURL.'>'.__('Add one Here', STAGESHOW_DOMAIN_NAME).'</a>'."</p></div>\n";
	}
}
foreach ($showLists as $showList)
{
	$results = $myDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
	<div class="stageshow-admin-form">
	<form method="post" action="admin.php?page=<?php echo STAGESHOW_MENUPAGE_PERFORMANCES; ?>">
	<h3><?php echo($showList->showName); ?></h3>
		<?php 
	$this->WPNonceField();
	if (count($results) == 0) 
	{ 
		echo __('Show has NO Performances', STAGESHOW_DOMAIN_NAME)."<br>\n";
	} 
	else 
	{ 
		$thisUpdateFailed = (($updateFailed) && ($showList->showID == $_POST['showID']));
		$classId = $env['PluginObj']->adminClassPrefix.'PerformancesAdminListClass';
		$adminTableObj = new $classId($env);		
		$adminTableObj->OutputList($results, $thisUpdateFailed);		
	} // End of if (count($results) == 0) ... else ...

?>
      <input type="hidden" name="showID" value="<?php echo $showList->showID; ?>"/>
<?php 
	if ($myDBaseObj->CanAddPerformance()) 
		$myDBaseObj->OutputButton("addperfbutton", "Add New Performance");

	if(count($results) > 0)
		$myDBaseObj->OutputButton("saveperfbutton", "Save Changes", "button-primary");
?>
</form>
</div>		
	<?php
} // End of foreach ($showLists as $showList) ..
?>
</div>
<?php
			// Stage Show Performances HTML Output - End 
		}
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (!isset($this->errorCount)) $this->errorCount = 0;
			if (!isset($this->blockCount)) $this->blockCount = 0;
						
			switch ($bulkAction)
			{
				case 'delete':		
					// Don't delete if any tickets have been sold for this performance
					$delPerfEntry = $myDBaseObj->GetPerformancesListByPerfID($recordId);
					if (count($delPerfEntry) == 0)
						$this->errorCount++;
					else if (!$myDBaseObj->CanDeletePerformance($delPerfEntry[0]))
						$this->blockCount++;
					return ( ($this->errorCount > 0) || ($this->blockCount > 0) );
			}
				
			return false;
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			switch ($bulkAction)
			{
				case 'delete':		
					// Delete all prices for this performance
					$myDBaseObj->DeletePriceByPerfID($recordId);
							
					// Get the performance entry
					$results = $myDBaseObj->GetPerformancesListByPerfID($recordId);
					
					// Delete any PayPal buttons ....
					$myDBaseObj->payPalAPIObj->DeleteButton($results[0]->perfPayPalButtonID);	
										
					// Delete a performance entry
					$myDBaseObj->DeletePerformanceByPerfID($recordId);
					return true;
					
				case 'activate':
					$perfEntry = $myDBaseObj->GetPerformancesListByPerfID($recordId);
					if ($myDBaseObj->IsStateActive($perfEntry[0]->perfState))
						$myDBaseObj->SetPerfActivated($recordId, 'deactivate');
					else
						$myDBaseObj->SetPerfActivated($recordId, 'activate');
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
					if ($this->errorCount > 0)
						$actionMsg = ($this->errorCount == 1) ? __("1 Performance has a Database Error", $this->pluginName) : $errorCount.' '.__("Performances have a Database Error", $this->pluginName); 
					else if ($this->blockCount > 0)
						$actionMsg = ($this->blockCount == 1) ? __("1 Performance cannot be deleted - Tickets already sold!", $this->pluginName) : $this->blockCount.' '.__("Performances cannot be deleted - Tickets already sold!", $this->pluginName); 
					else if ($actionCount > 0)		
						$actionMsg = ($actionCount == 1) ? __("1 Performance has been deleted", $this->pluginName) : $actionCount.' '.__("Performances have been deleted", $this->pluginName); 
					else
						$actionMsg = __("Nothing to Delete", $this->pluginName);
					break;
					
				case 'activate':		
					if ($actionCount > 0)		
						$actionMsg = ($actionCount == 1) ? __("1 Performance has been Activated/Deactivated", $this->pluginName) : $actionCount.' '.__("Performances have been Activated/Deactivated", $this->pluginName); 
					else
						$actionMsg = __("Nothing to Delete", $this->pluginName);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>