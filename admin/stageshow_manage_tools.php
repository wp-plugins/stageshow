<?php
/* 
Description: Code for Admin Tools
 
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

include STAGESHOW_INCLUDE_PATH.'stageshowlib_admin.php';      
include STAGESHOW_INCLUDE_PATH.'stageshow_sales_table.php';

if ( file_exists(STAGESHOW_INCLUDE_PATH.'stageshowlib_test_emailsale.php') ) 
	include STAGESHOW_INCLUDE_PATH.'stageshowlib_test_emailsale.php'; 
 
include STAGESHOW_INCLUDE_PATH.'stageshow_salevalidate.php'; 
 
if (!class_exists('StageShowToolsAdminClass')) 
{
	class StageShowToolsAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Tools';
			$this->adminClassPrefix = $env['PluginObj']->adminClassPrefix;
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{			
?>
<div class="wrap">
	<div class="stageshow-admin-form">
<?php
			$this->Tools_Validate();
			$this->Tools_Export();
			if (class_exists('StageShowLibTableTestEMailClass') && current_user_can(STAGESHOW_CAPABILITY_DEVUSER)) new StageShowLibTableTestEMailClass($this);
?>
	</div>
</div>
<?php
		}

		function OutputExportFormatOptions()
		{
?>	
	<option value="tdt" selected="selected"><?php _e('Tab Delimited Text', $this->myDomain); ?> </option>
<?php
		}
		
		function OutputExportOptions()
		{
?>
<script type="text/javascript">
	function stageshow_updateExportOptions(obj)
	{
	}
</script>
<?php
		}
		
		function Tools_Export()
		{
			$actionURL = STAGESHOW_ADMIN_URL.STAGESHOW_FOLDER.'_export.php';
				
?>
<script type="text/javascript">
	function stageshow_onSelectExportType(obj)
	{
		SelectControl = document.getElementById("export_format");
		newExportFormat = SelectControl.value;
		isOFXDownload = newExportFormat == "ofx";
		
		exportTypeRow = document.getElementById("stageshow-export_type-row");
		if (isOFXDownload)
		{
			exportTypeRow.style.display = 'none';
			downloadButton = document.getElementById("downloadvalidator");
			downloadButton.style.visibility = 'hidden';
		}
		else
		{
			exportTypeRow.style.display = '';
			stageshow_SetDownloadButtonStyle(obj);
		}
		stageshow_updateExportOptions();		
	}
	
	function stageshow_onSelectDownload(obj)
	{
		stageshow_SetDownloadButtonStyle();
		stageshow_updateExportOptions();		
	}
	
	function stageshow_SetDownloadButtonStyle(obj)
	{
		SelectControl = document.getElementById("export_type");
		newDownloadType = SelectControl.value;
		downloadValidatorEnabled = newDownloadType == "summary";

		downloadButton = document.getElementById("downloadvalidator");
		if (downloadValidatorEnabled)
			downloadButton.style.visibility = 'visible';
		else
			downloadButton.style.visibility = 'hidden';
	}
</script>
<h3><?php _e('Export', $this->myDomain); ?></h3>
<p><?php _e('Export to a "TAB Separated Values" format file on your computer.', $this->myDomain); ?></p>
<p><?php _e('This format can be imported to many applications including spreadsheets and databases.', $this->myDomain); ?></p>
<form action="<?php echo $actionURL; ?>" method="POST">
<?php $this->WPNonceField('stageshowlib_export.php'); ?>
<table class="stageshow-form-table stageshow-export-table">
<tr>
<th><?php _e('Format', $this->myDomain); ?></th>
<td>
<select name="export_format" id="export_format" onchange=stageshow_onSelectExportType(this)>
<?php
	$this->OutputExportFormatOptions();
?>	
</select>
</td>
</tr>
<tr id="stageshow-export_type-row">
<th><?php _e('Type', $this->myDomain); ?></th>
<td>
<select name="export_type" id="export_type" onchange=stageshow_onSelectDownload(this)>
	<?php if (current_user_can(STAGESHOW_CAPABILITY_SETUPUSER)) { ?>
	<option value="settings"><?php _e('Settings', $this->myDomain); ?> </option>
	<?php } ?>
	<option value="tickets"><?php _e('Tickets', $this->myDomain); ?> </option>
	<option value="summary" selected="selected"><?php _e('Sales Summary', $this->myDomain); ?>&nbsp;&nbsp;</option>
</select>
</td>
</tr>

<?php
	$this->OutputExportOptions();
?>	
</table>
<p>
<p class="submit">
<input type="submit" name="downloadexport" class="button" value="<?php esc_attr_e('Download Export File', $this->myDomain); ?>" />
<input type="submit" name="downloadvalidator" id="downloadvalidator" class="button-secondary" value="<?php _e('Download Offline Validator', $this->myDomain); ?>" />
<input type="hidden" name="page" value="stageshow_tools" />
<input type="hidden" name="download" value="true" />
</p>
</form>
<?php
		}

		function Tools_Validate()
		{
			$dbCredsPath = WP_CONTENT_DIR . '/uploads/'.STAGESHOW_FOLDER.'/wp-config-db.php';			
			$this->myDBaseObj->SaveDBCredentials($dbCredsPath);
			
			$classId = $this->adminClassPrefix.'SaleValidateClass';
			new $classId($this->env);
		}

	}
}

?>