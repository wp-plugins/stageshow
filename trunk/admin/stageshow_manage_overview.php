<?php
/* 
Description: Code for Overview Page
 
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

if (!class_exists('StageShowOverviewListClass')) 
{
	class StageShowOverviewListClass extends MJSLibAdminListClass // Define class
	{		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;
			
			$this->showDBIds = $myDBaseObj->adminOptions['Dev_ShowDBIds'];					

			$columns = array(
		    'showName' => __('Show', STAGESHOW_DOMAIN_NAME),
		    'perfCount' => __('Performances', STAGESHOW_DOMAIN_NAME),
		    'showSales' => __('Tickets Sold', STAGESHOW_DOMAIN_NAME)
			);			
			$this->SetListHeaders($this->pluginName.'_overview_list', $columns, MJSLibTableClass::HEADERPOSN_TOP);
		}
		
		function GetRecordID($result)
		{
			return $result->saleID;
		}
		
		function AddResult($result)
		{
			$myDBaseObj = $this->myDBaseObj;

			$results2 = $myDBaseObj->GetPerformancesListByShowID($result->showID);
			$showSales = $myDBaseObj->GetSalesQtyByShowID($result->showID);
			
			$rowAttr = '';
			$this->NewRow($result, $rowAttr);

			$this->AddToTable($result, $result->showName);
			$this->AddToTable($result, count($results2));
			$this->AddToTable($result, $showSales);
		}		
	}
}

include STAGESHOW_INCLUDE_PATH.'mjslib_admin.php';      

if (!class_exists('StageShowOverviewAdminClass')) 
{
	class StageShowOverviewAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env)
		{
			parent::__construct($env);

			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
?>			
			<div class="wrap">
			<div id="icon-stageshow" class="icon32"></div>
			<h2>
				<?php echo $myPluginObj->pluginName.' - '.__('Overview', STAGESHOW_DOMAIN_NAME); ?>
			</h2>
<?php

			if(isset($_POST['createsample']))
			{
				$myPluginObj->CreateSample();
			}
			
			// Stage Show Overview HTML Output - Start 
			$this->Output_Overview($env);
			$this->Output_ShortcodeHelp();
			$this->Output_UpdateInfo();
			
			echo '</div>';
		}
		
		function Output_Overview($env)
		{
			$myDBaseObj = $this->myDBaseObj;
			$results = $myDBaseObj->GetAllShowsList();
						
?>
		<br>
			<h2>Shows</h2>
			<br>
				<?php	
	
			if(count($results) == 0)
			{
				echo __('No Show Configured', STAGESHOW_DOMAIN_NAME)."<br>\n";
				echo '
				<form method="post" action="admin.php?page=stageshow_adminmenu">
				<br>
					<input class="button-primary" type="submit" name="createsample" value="'.__('Create Sample', STAGESHOW_DOMAIN_NAME).'"/>
				<br>
				</form>';
			}
			else
			{
				$overviewList = new StageShowOverviewListClass($env);		
				$overviewList->OutputList($results);		
			}
		}
		
		function Output_ShortcodeHelp()
		{
?>
			<br>			
				<h2>Shortcodes</h2>
				StageShow generates output to your Wordpress pages for the following shortcodes:
			<br><br>
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th>Shortcode</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>[sshow-boxoffice]</td>
						<td>Add Box Office for all performances</td>
					</tr>
				</tbody>
			</table>
<?php
		}

		function Output_UpdateInfo()
		{
			// Get News entry from server
			$myDBaseObj = $this->myDBaseObj;
			$latest = $myDBaseObj->GetLatestNews();

			// Deal with "Not Found" error ....
			if ($latest === '')
				return;
			
?>
		<br>
			<h2>StageShow Updates</h2>
			<br>
				<br>
					<table class="widefat" cellspacing="0">
						<thead>
							<tr>
								<th>Latest Updates</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
<?php
				echo $latest;
?>
								</td>
							</tr>
						</tbody>
					</table>
<?php
		}

	}
}

?>
