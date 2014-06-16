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
include STAGESHOW_INCLUDE_PATH.'mjslib_table.php';

if (!class_exists('StageShowShowsAdminListClass')) 
{
	class StageShowShowsAdminListClass extends MJSLibAdminListClass // Define class
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
			return array(
				array('Label' => 'Show Name',    'Id' => 'showName',   'Type' => MJSLibTableClass::TABLEENTRY_TEXT,   'Len' => STAGESHOW_SHOWNAME_TEXTLEN, ),
				array('Label' => 'Tickets Sold', 'Id' => 'totalQty',   'Type' => MJSLibTableClass::TABLEENTRY_VALUE,  'Link' => 'admin.php?page='.STAGESHOW_MENUPAGE_SALES.'&action=show&id=', ),						
				array('Label' => 'State',        'Id' => 'showState',  'Type' => MJSLibTableClass::TABLEENTRY_VALUE,  'Decode' => 'GetShowState', ),						
			);
		}
		
		function GetShowState($result)
		{
			$perfState = $this->myDBaseObj->IsStateActive($result->showState) ? __("Active", STAGESHOW_DOMAIN_NAME) : __("INACTIVE", STAGESHOW_DOMAIN_NAME);
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

if (!class_exists('StageShowShowsAdminClass')) 
{
	class StageShowShowsAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			// Call base constructor
			parent::__construct($env);
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
      
			if (isset($_GET['action']))
			{
				$this->CheckAdminReferer();
				
				$actionID = $_GET['action'];
				switch ($actionID)
				{
					case 'note':
						$noteID = $_GET['id']; 
						break;
				}
			}

			$showsMsg = '';
				 
			echo '<div class="wrap">';
			if (isset($_POST['saveshowbutton']))
			{
				// Save Settings Request ....
				$results = $myDBaseObj->GetAllShowsList();
						
				// Verify that show names are unique 				
        if(count($results) > 0)
        {
	        foreach($results as $result)
					{
							$showEntry = stripslashes($_POST['showName'.$result->showID]);
							if (isset($entriesList[$showEntry]))
							{
								$showsMsg = __('Duplicated Show Name', STAGESHOW_DOMAIN_NAME).' ('.$showEntry.')';
								break;
							}
							$entriesList[$showEntry] = true;
					}
				}				
				
				if ($showsMsg !== '')
				{
					echo '<div id="message" class="error"><p>'.__('Settings have NOT been saved', STAGESHOW_DOMAIN_NAME).'. '.$showsMsg.'</p></div>';
				}
        else 
				{
					if (count($results) > 0)
					{
						$classId = $env['PluginObj']->adminClassPrefix.'ShowsAdminListClass';
						$adminTableObj = new $classId($env);		
						
						// Get the extended settings array
						$settings = $adminTableObj->GetDetailsRowsDefinition();
						$dbOpts = $adminTableObj->ExtendedSettingsDBOpts();
			
						foreach($results as $result)
						{
							$newShowName = stripslashes($_POST['showName'.$result->showID]);
							if ($newShowName != $result->showName)
							{
								$myDBaseObj->UpdateShowName($result->showID, $newShowName);
							}
				
							// Save option extensions
							$this->UpdateHiddenRowValues($result, $result->showID, $settings, $dbOpts);											
						}
					}
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
			}
      
			if (isset($_POST['addshowbutton']))
			{
				// Add Show with unique Show Name 
				$showID = $myDBaseObj->AddShow('');
				
				if ($showID == 0)
					echo '<div id="message" class="error"><p>'.__('Cannot add a new show - Only one show allowed', STAGESHOW_DOMAIN_NAME).'</p></div>';
				else
					echo '<div id="message" class="updated"><p>'.__('Default entry added - Edit and Save to update it.', STAGESHOW_DOMAIN_NAME).'</p></div>';
			}

?>
				<div class="wrap">
					<div id="icon-stageshow" class="icon32"></div>
					<h2><?php echo $myPluginObj->pluginName.' - '.__('Show Editor', STAGESHOW_DOMAIN_NAME); ?></h2>
					<form method="post" action="admin.php?page=<?php echo STAGESHOW_MENUPAGE_SHOWS ?>">
					<?php
	$this->WPNonceField();

	if (isset($noteID))
	{
		$results = $myDBaseObj->GetShowsList($noteID);
		echo '<div class="stageshow-noteform">';	
		if(count($results) > 0)
		{
			$result = $results[0];
?>						
      <input type="hidden" name="id" value="<?php echo $result->showID; ?>"/>
				<table>
					<tr>
						<td><?php _e('Show', STAGESHOW_DOMAIN_NAME); ?></td>
						<td><?php echo $result->showName ?></td>
					</tr>
				</table>
				
				<br></br>
				<input class="button-primary" type="submit" name="savenotebutton" value="<?php _e('Save Settings', STAGESHOW_DOMAIN_NAME); ?>">
				&nbsp;
<?php				
		}
?>				
				<input class="button-secondary" type="submit" name="backtoshows" value="<?php _e('Back to Shows Summary', STAGESHOW_DOMAIN_NAME); ?>">
				<br></br>
			</div>
<?php
			}
			else
			{
				$this->Output_MainPage($env, $showsMsg !== '');
			}
		}

		function Output_MainPage($env, $updateFailed)	
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (!$myDBaseObj->CheckIsConfigured())
				return;

			$results = $myDBaseObj->GetAllShowsList();
			if(count($results) == 0)
			{
				echo "<div class='noconfig'>".__('No Show Configured', STAGESHOW_DOMAIN_NAME)."</div>\n";
			}
			else
			{
				$classId = $env['PluginObj']->adminClassPrefix.'ShowsAdminListClass';
				$adminTableObj = new $classId($env);		
				$adminTableObj->OutputList($results, $updateFailed);	
			}
			
			if ($myDBaseObj->CanAddShow()) 
				$myDBaseObj->OutputButton("addshowbutton", "Add New Show");
				
			if(count($results) > 0)
				$myDBaseObj->OutputButton("saveshowbutton", "Save Changes", "button-primary");
?>
		</form>
</div>

<?php
		} // End of function Output_MainPage()
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (!isset($this->errorCount)) $this->errorCount = 0;
			if (!isset($this->blockCount)) $this->blockCount = 0;
						
			switch ($bulkAction)
			{
				case 'delete':		
					// Don't delete if any tickets have been sold for this performance
					$delShowEntry = $myDBaseObj->GetShowsList($recordId);
					if (count($delShowEntry) == 0)
						$errorCount++;
					else if (!$myDBaseObj->CanDeleteShow($delShowEntry[0]))
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
					// Get a list of performances
					$results = $myDBaseObj->GetPerformancesListByShowID($recordId);
					
					foreach($results as $result)
					{
						// Get ID of performance to delete
						$delperfId = $result->perfID;
							
						// Delete all prices for this performance
						$myDBaseObj->DeletePriceByPerfID($delperfId);
								
						// Delete any PayPal buttons ....
						$myDBaseObj->payPalAPIObj->DeleteButton($result->perfPayPalButtonID);	
										
						// Delete a performances entry
						$myDBaseObj->DeletePerformanceByPerfID($delperfId);
					}			
							
					// Now delete the entry in the SHOWS table
					$delShowName = $myDBaseObj->DeleteShowByShowID($recordId);					
					return true;

				case 'activate':
					$actionCount = 0;
					$showEntry = $myDBaseObj->GetShowsList($recordId);
					if ($myDBaseObj->IsStateActive($showEntry[0]->showState))
						$myDBaseObj->SetShowActivated($recordId, 'deactivate');
					else
						$myDBaseObj->SetShowActivated($recordId, 'activate');
						
					// TODO-BEFORE-RELEASE - Update Inventory Settings for Performance Buttons
					break;

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
						$actionMsg = ($this->errorCount == 1) ? __("1 Show has a Database Error", $this->pluginName) : $errorCount.' '.__("Shows have a Database Error", $this->pluginName); 
					else if ($this->blockCount > 0)
						$actionMsg = ($this->blockCount == 1) ? __("1 Show cannot be deleted - Tickets already sold!", $this->pluginName) : $this->blockCount.' '.__("Shows cannot be deleted - Tickets already sold!", $this->pluginName); 
					else if ($actionCount > 0)		
						$actionMsg = ($actionCount == 1) ? __("1 Show has been deleted", $this->pluginName) : $actionCount.' '.__("Shows have been deleted", $this->pluginName); 
					else
						$actionMsg = __("Nothing to Delete", $this->pluginName);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>