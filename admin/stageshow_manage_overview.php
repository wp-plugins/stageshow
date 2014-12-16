<?php
/* 
Description: Code for Overview Page
 
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

if (file_exists(STAGESHOW_INCLUDE_PATH.'stageshow_contributors.php'))
	include STAGESHOW_INCLUDE_PATH.'stageshow_contributors.php';

if (!class_exists('StageShowWPOrgOverviewAdminListClass')) 
{
	class StageShowWPOrgOverviewAdminListClass extends StageShowLibSalesAdminListClass // Define class
	{		
		function __construct($env) //constructor
		{
			// Call base constructor
			$editMode = false;
			parent::__construct($env, $editMode);
				
			$this->SetRowsPerPage(self::STAGESHOWLIB_EVENTS_UNPAGED);
			
			$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "overviewtab";
		}
		
		function GetRecordID($result)
		{
			return $result->showID;
		}
		
		function GetMainRowsDefinition()
		{
			// FUNCTIONALITY: Overview - Shows Performances Count, Ticket sales quantity (with link to Show Sales page) and Sales Values
			return array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show',         StageShowLibTableClass::TABLEPARAM_ID => 'showName',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Performances', StageShowLibTableClass::TABLEPARAM_ID => 'perfCount',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, ),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Tickets Sold', StageShowLibTableClass::TABLEPARAM_ID => 'soldQty',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE,  StageShowLibTableClass::TABLEPARAM_LINK =>'admin.php?page='.STAGESHOW_MENUPAGE_SALES.'&action=show&id=', ),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Sales Value',  StageShowLibTableClass::TABLEPARAM_ID => 'soldValue',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, ),						
			);
		}
		
		function GetDetailsRowsDefinition()
		{
			$ourOptions = array(
//				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Name',	                     StageShowLibTableClass::TABLEPARAM_ID => 'showName',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALENAME_TEXTLEN,      StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALENAME_EDITLEN, ),
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function GetDetailsRowsFooter()
		{
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_ID => 'saleDetails', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_FUNCTION, StageShowLibTableClass::TABLEPARAM_FUNC => 'ShowSaleDetails'),						
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsFooter(), $ourOptions);
			
			return $ourOptions;
		}
		
		function ShowSaleDetails($result, $saleResults)
		{		
			// FUNCTIONALITY: Overview - Output Peforrmances List
			$env = $this->env;
			$salesList = new StageShowWPOrgOverviewAdminDetailsListClass($env, $this->editMode);	
			
			// Set Rows per page to disable paging used on main page
			$salesList->enableFilter = false;
			
			ob_start();	
			$salesList->OutputList($saleResults);	
			$saleDetailsOoutput = ob_get_contents();
			ob_end_clean();

			return $saleDetailsOoutput;
		}
		
		function GetListDetails($result)
		{
			return $this->perfsList[$result->showID];
		}
		
		function OutputList($results, $updateFailed = false)
		{
			// FUNCTIONALITY: Overview - Calculate Performances Count for each show
			foreach ($results as $key=>$result)
			{
				// Save Performances Lists in class object so it can be reused by ShowSaleDetails() function
				$sqlFilter['salesCompleted'] = true;
				$this->perfsList[$result->showID] = $this->myDBaseObj->GetPerformancesListByShowID($result->showID, $sqlFilter);				
				$results[$key]->perfCount = count($this->perfsList[$result->showID]);
			}
			
			parent::OutputList($results, $updateFailed);
		}
		
	}
}

if (!class_exists('StageShowWPOrgOverviewAdminDetailsListClass')) 
{
	class StageShowWPOrgOverviewAdminDetailsListClass extends StageShowLibSalesAdminListClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_TOP;
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
			// FUNCTIONALITY: Overview - Show button lists performances, sales (with link) and value
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Performance',  StageShowLibTableClass::TABLEPARAM_ID => 'perfDateTime', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, StageShowLibTableClass::TABLEPARAM_DECODE => 'FormatDateForAdminDisplay', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Tickets Sold', StageShowLibTableClass::TABLEPARAM_ID => 'soldQty',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, StageShowLibTableClass::TABLEPARAM_LINK =>'admin.php?page='.STAGESHOW_MENUPAGE_SALES.'&action=perf&id=', ),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Sales Value',  StageShowLibTableClass::TABLEPARAM_ID => 'soldValue',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VALUE, ),						
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
	}
}

include STAGESHOW_INCLUDE_PATH.'stageshowlib_admin.php';      

if (!class_exists('StageShowWPOrgOverviewAdminClass')) 
{
	class StageShowWPOrgOverviewAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env)
		{
			$this->pageTitle = 'Overview';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			// FUNCTIONALITY: Overview - Action "Create Sample" Button
			if(isset($_POST['createsample']))
			{
				$myPluginObj->CreateSample();
			}
		}
		
		function Output_MainPage($updateFailed)
		{
			// Stage Show Overview HTML Output - Start 
			$this->Output_Overview();
			$this->Output_Help();
			$this->Output_TrolleyAndShortcodesHelp();
			$this->Output_UpdateServerHelp();
			$this->Output_UpdateInfo();
			$this->Output_Contributors();
		}
		
		
		function Output_Overview()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$isConfigured = $myDBaseObj->CheckIsConfigured();
						
			$results = $myDBaseObj->GetAllShowsList();
						
?>
	<br>
	<h2><?php _e('Shows', $this->myDomain); ?></h2>
<?php	
	
			if(count($results) == 0)
			{
				// FUNCTIONALITY: Overview - Show Link to Settings page if Payment Gateway settings required
				if ($isConfigured)
				{
					// FUNCTIONALITY: Overview - Show message and "Create Sample" button if no shows configured
					echo "<div class='noconfig'>".__('No Show Configured', $this->myDomain)."</div>\n";
					echo '
					<form method="post" action="admin.php?page='.STAGESHOW_MENUPAGE_ADMINMENU.'">
					<br>
						<input class="button-primary" type="submit" name="createsample" value="'.__('Create Sample', $this->myDomain).'"/>
					<br>
					</form>';
				}
			}
			else
			{
				$classId       = $this->GetAdminListClass();
				$overviewList = new $classId($this->env);
				$overviewList->OutputList($results);		
			}
		}
		
		function Output_Help()
		{
			$myDBaseObj = $this->myDBaseObj;
?>
	<br>			
	<h2><?php _e('Help', $this->myDomain); ?></h2>
<?php
			$help_url  = get_option('siteurl');
			$pluginID  = STAGESHOW_FOLDER;		
			$help_url .= '/wp-content/plugins/'.$pluginID.'/docs/StageShowHelp.pdf';
			
			echo __('User Guide is Available', $this->myDomain).' <a href="'.$help_url.'">'.__('Here', $this->myDomain).'</a> (PDF)<br>';
		}
		
		function Output_TrolleyAndShortcodesHelp()
		{
			echo '<br><h2>'.__("Plugin Info & Shortcodes", $this->myDomain)."</h2>\n";
			
			$this->myDBaseObj->Output_PluginHelp();
			
			echo '<br>'.__('StageShow generates output to your Wordpress pages for the following shortcodes:', $this->myDomain)."<br><br>\n";
	
			$shortcode = $this->env['PluginObj']->shortcode;
			$this->Output_ShortcodeHelp($shortcode);
		}
		
		function Output_ShortcodeHelp($shortcode)
		{
			// FUNCTIONALITY: Overview - Show Help for Shortcode(s))
?>
			<div class="stageshow-overview-info">
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Shortcode', $this->myDomain); ?></th>
						<th><?php _e('Description', $this->myDomain); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>[<?php echo $shortcode; ?>]</td>
						<td><?php _e('Add Box Office for all performances', $this->myDomain); ?></td>
					</tr>
				</tbody>
			</table>
			</div>
<?php
		}

		function Output_Contributors()
		{
			echo '<br><h2>'.__("Contributors", $this->myDomain)."</h2>\n";
			
?>
<div class="stageshow-overview-info">
<table class="widefat" cellspacing="0">
<thead><tr class="stageshow-overview"><th>Name</th><th>Contribution</th><th>URL</th></tr></thead>
<tbody>
<?php
			$contributorsObj = new StageShowWPOrgContributorsClass();
			$contributorsList = $contributorsObj->GetContributors();
			foreach ($contributorsList as $contributor)
			{
				echo '<tr>';
				echo '<td>'.$contributor->name.'</td>';
				echo '<td>'.$contributor->contribution.'</td>';
				echo '<td>'.$contributor->url.'</td>';
				echo '</tr>';
			}
?>
</tbody>
</table>
</div>
<?php
		}
		
		function Output_UpdateServerHelp()
		{
			// FUNCTIONALITY: Overview - Output Update Server 
			if (defined('STAGESHOW_INFO_SERVER_URL'))
			{
				$msg = "<strong>Using Custom Update Server</strong> - Root URL=".STAGESHOW_INFO_SERVER_URL."<br>\n";
				echo '<br><div id="cust-update-error" class="error inline" onclick=ShowOrHideSubmenu(this) ><p>'.$msg.'</p></div>';
			}			
		}
		
		function Output_UpdateInfo()
		{
			// FUNCTIONALITY: Overview - Get and Output News from Update Server
			// Get News entry from server
			$myDBaseObj = $this->myDBaseObj;
			$latest = $myDBaseObj->GetLatestNews();

			// Deal with "Not Found" error ....
			if ($latest === '')
				return;
			
			echo '
				<br><h2>'.__('StageShow Updates', $this->myDomain).'</h2>
					<table class="widefat" cellspacing="0">
						<thead>
							<tr>
								<th>'.__('Latest Updates', $this->myDomain).'</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>'.$latest.'
								</td>
							</tr>
						</tbody>
					</table>
			';
		}

	}
}

?>
