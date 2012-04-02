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

if (!class_exists('StageShowAdminPerformancesListClass')) 
{
	class StageShowAdminPerformancesListClass extends MJSLibAdminListClass // Define class
	{				
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;
			
			$this->showDBIds = $myDBaseObj->adminOptions['Dev_ShowDBIds'];					

			$this->SetRowsPerPage($myDBaseObj->adminOptions['PageLength']);
			$this->hasHiddenRows = $myDBaseObj->HasHiddenRows();
			
			$this->bulkActions = array(
				'activate' => __('Activate/Deactivate', STAGESHOW_DOMAIN_NAME),
				'delete'   => __('Delete', STAGESHOW_DOMAIN_NAME),
				);

			$columns = array(
		    'perfDateTime' => __('Date & Time', STAGESHOW_DOMAIN_NAME),
		    'perfRef' => __('Reference', STAGESHOW_DOMAIN_NAME),
		    'perfSeats' => __('Max Seats', STAGESHOW_DOMAIN_NAME),
		    'perfSales' => __('Tickets Sold', STAGESHOW_DOMAIN_NAME),
		    'perfState' => __('State', STAGESHOW_DOMAIN_NAME),
			);			
			$this->SetListHeaders('stageshow_sales_list', $columns);
			
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
		
		function OutputList($results, $updateFailed)
		{
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
		
		function AddResult($result)
		{
			$myDBaseObj = $this->myDBaseObj;

			if ($this->updateFailed)
			{
				// Get value(s) from form controls
				$perfDateTime = stripslashes($_POST['perfDateTime'.$result->perfID]);
				$perfRef = stripslashes($_POST['perfRef'.$result->perfID]);
				$perfSeats = stripslashes($_POST['perfSeats'.$result->perfID]);
			}
			else
			{
				// Get value(s) from database
				$perfDateTime = $result->perfDateTime;
				$perfRef = $result->perfRef;
				$perfSeats = $result->perfSeats;
			}
			
			if ($perfSeats < 0) $perfSeats = '&#8734';
			
			if ($result->totalQty > 0)
			{
				$perfSalesLink = 'admin.php?page=stageshow_sales&action=perf&id='.$result->perfID;
				$perfSalesLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($perfSalesLink, plugin_basename($this->caller)) : $perfSalesLink;
				$perfSalesLink = '<a href="'.$perfSalesLink.'">'.$result->totalQty.'</a>';
			}
			else
				$perfSalesLink = '0';
				
			$perfState = $myDBaseObj->IsStateActive($result->perfState) ? __("Active", STAGESHOW_DOMAIN_NAME) : __("INACTIVE", STAGESHOW_DOMAIN_NAME);
			
			$this->NewRow($result);
			
			$this->AddInputToTable($result, 'perfDateTime', 28, $perfDateTime);
			$this->AddInputToTable($result, 'perfRef', STAGESHOW_PERFREF_TEXTLEN, $perfRef);
			$this->AddInputToTable($result, 'perfSeats', 4, $perfSeats);
			$this->AddToTable($result, $perfSalesLink);
			$this->AddToTable($result, $perfState);

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
			
      $columns = array(
		    'perfDateTime' => __('Date & Time', STAGESHOW_DOMAIN_NAME),
		    'perfRef' => __('Reference', STAGESHOW_DOMAIN_NAME),
		    'perfSeats' => __('Max Seats', STAGESHOW_DOMAIN_NAME),
		    'perfSales' => __('Tickets Sold', STAGESHOW_DOMAIN_NAME),
		    'perfDelete' => __(' ', STAGESHOW_DOMAIN_NAME)
	    );
	
			if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
			{
				// Add the ID column
				$columns = array_merge(array('perfID' => __('ID', STAGESHOW_DOMAIN_NAME)), $columns); 
			}
			
      //TODO-Remove register_column_headers('sshow_perfs_list', $columns);	

			$perfsMsg = '';
				 
			echo '<div class="wrap">';
			if (isset($_POST['savebutton']))
			{
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
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
								
							// TODO-BEFORE-RELEASE Save option extensions
							$myDBaseObj->UpdateExtendedSettings($result, $result->perfID);											
							
							if ($perfUpdated)
								$perfsList[count($perfsList)] = $result;									
						} // End of foreach($results as $result)
						
						// Add this entry to the list of entries to be updated
						if (count($perfsList) > 0)
							$myDBaseObj->UpdateCartButtons($perfsList);
					} 
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}			
			else if (isset($_POST['addperfbutton']) && isset($_POST['showID']))
			{
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$showID = $_POST['showID'];
				
				$statusMsg = '';
				$myDBaseObj->CreateNewPerformance($statusMsg, $showID, date(StageShowDBaseClass::MYSQL_DATETIME_FORMAT));				
				echo '<div id="message" class="updated"><p>'.$statusMsg.'.</p></div>';		// TODO - Check return status "class"
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
						$errorCount = 0;
						$blockCount = 0;							
						foreach($_POST['rowSelect'] as $perfID)
						{
							// Don't delete if any tickets have been sold for this performance
							$delPerfEntry = $myDBaseObj->GetPerformancesListByPerfID($perfID);
							if (count($delPerfEntry) == 0)
								$errorCount++;
							else if (!$myDBaseObj->CanDeletePerformance($delPerfEntry[0]))
								$blockCount++;
						}
						
						if (($errorCount > 0) || ($blockCount > 0))
						{
							if ($errorCount > 0)
							{
								$actionMsg = ($errorCount == 1) ? __("1 Performance has a Database Error", $this->pluginName) : $errorCount.' '.__("Performances have a Database Error", $this->pluginName); 
								echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
							}
							if ($blockCount > 0)
							{
								$actionMsg = ($blockCount == 1) ? __("1 Performance cannot be deleted - Tickets already sold!", $this->pluginName) : $blockCount.' '.__("Performances cannot be deleted - Tickets already sold!", $this->pluginName); 
								echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
							}
							break;
						}
						
						// Delete performances
						$actionCount = 0;
						foreach($_POST['rowSelect'] as $perfID)
						{
							// Delete all prices for this performance
							$myDBaseObj->DeletePriceByPerfID($perfID);
							
							// Get the performance entry
							$results = $myDBaseObj->GetPerformancesListByPerfID($perfID);
					
							// Delete any PayPal buttons ....
							$myDBaseObj->payPalAPIObj->DeleteButton($results[0]->perfPayPalButtonID);	
										
							// Delete a performance entry
							$myDBaseObj->DeletePerformanceByPerfID($perfID);

							$actionCount++;
						}
						
						if ($actionCount > 0)
						{
							$actionMsg = ($actionCount == 1) ? __("1 Performance has been deleted", $this->pluginName) : $actionCount.' '.__("Performances have been deleted", $this->pluginName); 
							echo '<div id="message" class="updated"><p>'.$actionMsg.'</p></div>';
						}
						else
						{
							$actionMsg = __("Nothing to Delete", $this->pluginName);
							echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
						}
						break;
						
					case 'activate':
						$actionCount = 0;
						foreach($_POST['rowSelect'] as $perfID)
						{
							$perfEntry = $myDBaseObj->GetPerformancesListByPerfID($perfID);
							if ($myDBaseObj->IsStateActive($perfEntry[0]->perfState))
								$myDBaseObj->SetPerfActivated($perfID, 'deactivate');
							else
								$myDBaseObj->SetPerfActivated($perfID, 'activate');
						}
						break;
				}
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
		echo "<div class='noconfig'>".__('No Show Configured', STAGESHOW_DOMAIN_NAME)."</div>\n";
}
foreach ($showLists as $showList)
{
	$results = $myDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
	<form method="post" action="admin.php?page=stageshow_performances">
	<h3><?php echo($showList->showName); ?></h3>
		<?php 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename($this->caller));
	if (count($results) == 0) 
	{ 
		echo __('Show has NO Performances', STAGESHOW_DOMAIN_NAME)."<br>\n";
	} 
	else 
	{ 
		$thisUpdateFailed = (($updateFailed) && ($showList->showID == $showID));
		$perfsList = new StageShowAdminPerformancesListClass($env);		
		$perfsList->OutputList($results, $thisUpdateFailed);		
	} // End of if (count($results) == 0) ... else ...

?>
      <input type="hidden" name="showID" value="<?php echo $showList->showID; ?>"/>
				<?php if ($myDBaseObj->CanAddPerformance()) { ?>
			<input class="button-secondary" type="submit" name="addperfbutton" value="<?php _e('Add New Performance', STAGESHOW_DOMAIN_NAME) ?>"/>
						<?php } ?>
						<?php
	if(count($results) > 0)
	{
		echo '<input class="button-primary" type="submit" name="savebutton" value="'.__('Save Settings', STAGESHOW_DOMAIN_NAME).'"/>';
	}
?>
					</form>
	<?php
} // End of foreach ($showLists as $showList) ..
?>
</div>
<?php
			// Stage Show Performances HTML Output - End 
		}
	}
}

?>