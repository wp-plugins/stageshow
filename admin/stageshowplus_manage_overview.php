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

include STAGESHOW_ADMIN_PATH.'stageshow_manage_overview.php';

if (!class_exists('StageShowPlusOverviewAdminClass') && class_exists('StageShowOverviewAdminClass')) 
{
  class StageShowPlusOverviewAdminClass extends StageShowOverviewAdminClass
  {
		function __construct($env)
		{
			parent::__construct($env);
		}
		
		function Output_ShortcodeHelp()
		{
			// FUNCTIONALITY: Overview - Show StaegShow+ Specific Help for Shortcode(s))
?>
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Shortcode', $this->myDomain); ?></th>
						<th><?php _e('Description', $this->myDomain); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>[sshow-boxoffice]</td>
						<td><?php _e('Add Box Office with all shows and performances', $this->myDomain); ?></td>
					</tr>
					<tr>
						<td>[sshow-boxoffice id="n"]</td>
						<td><?php _e('Add Box Office with all performances for show number "n"', $this->myDomain); ?></td>
					</tr>
					<tr>
						<td>[sshow-boxoffice id="Show Name"]</td>
						<td><?php _e('Add Box Office with all performances for show identified by "Show Name"', $this->myDomain); ?></td>
					</tr>
					<tr>
						<td>[sshow-boxoffice count=1]</td>
						<td><?php _e('Add Box Office with the next "count" shows (i.e. count=1 is next show)', $this->myDomain); ?></td>
					</tr>
				</tbody>
			</table>
			<?php
		}	

		function Output_UpdateServerHelp()
		{
			$autoUpdateErrMsg = '';
			
			if ($this->myDBaseObj->adminOptions['AuthTxnId'] == '')
				$autoUpdateErrMsg .= 'Sale Transaction ID '.__('is not set', $this->myDomain)."<br>\n";
			
			if ($this->myDBaseObj->adminOptions['AuthTxnEMail'] == '')
				$autoUpdateErrMsg .= 'Sale Txn EMail Address '.__('is not set', $this->myDomain)."<br>\n";
			
			if ($autoUpdateErrMsg !== '')
			{
				// FUNCTIONALITY: Overview - Show StageShow+ Update Server Error (with settigs page link))
				$settingsURL = "admin.php?page=".STAGESHOW_MENUPAGE_SETTINGS;
				
				echo "<br><h2>".__('Auto-update Server Settings', $this->myDomain)."</h2>\n";

				$settingsPageURL = get_option('siteurl').'/wp-admin/admin.php?page='.STAGESHOW_MENUPAGE_SETTINGS;
				$settingsPageURL .= '&tab=Auto_Update_Settings';
				$actionMsg = __('Plugin Auto-Update is DISABLED - Add registration details', $this->myDomain)." <a href=$settingsPageURL>".__('Here', $this->myDomain).'</a>';
				//echo '<div id="message" class="error" onclick="HideElement(this)"><p>'.$actionMsg.'</p></div>';
				echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
			}
			
			parent::Output_UpdateServerHelp();
			
			//echo '<div id="message" class="notice"><p>'."StageShow+ ".__('Plugin Auto-Update is ENABLED', $this->myDomain).'</p></div>';
		}
		
  }
}
 
?>
