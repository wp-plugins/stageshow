<?php
/* 
Description: Code for Managing Prices Configuration
 
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
	
if (!class_exists('StageShowTestEMailClass')) 
{
	define('STAGESHOW_TESTSALES_LIMIT', 20);
	
	class StageShowTestEMailClass
	{
		function __construct($caller, $inForm = false) //constructor	
		{	
			// FUNCTIONALITY: Tools - Send Test Sale EMail (with optional EMail Divert))
			$myDBaseObj = $caller->myDBaseObj;
			
			echo '<h3>'.__('EMail Sale Test', $myDBaseObj->get_domain()).'</h3>';
			
			if (isset($_POST['DivertEMailTo']))
				$DivertEMailTo = stripslashes($_POST['DivertEMailTo']);
			else if (defined('STAGESHOW_SAMPLE_EMAIL'))
				$DivertEMailTo = STAGESHOW_SAMPLE_EMAIL;
			else
				$DivertEMailTo = '';

			$sqlFilters['limit'] = STAGESHOW_TESTSALES_LIMIT;
			$results = $myDBaseObj->GetSalesList($sqlFilters);		// Get list of sales (one row per sale)
			
			if (isset($_POST['testbutton_EMailSale'])) 
			{
				$caller->CheckAdminReferer();
					
				// Run EMail Test		
				$saleID = $_POST['TestSaleID'];
				$saleResults = $myDBaseObj->GetSale($saleID);
				if(count($saleResults) == 0) {
					echo '<div id="message" class="error"><p>'.__('No Sales', $myDBaseObj->get_domain()).'</p></div>';
				}
				else 
				{
					$myDBaseObj->EMailSale($saleResults[0]->saleID, $DivertEMailTo);
				}	
			}
			
			if (!$inForm) echo '<form method="post">'."\n";
				
?>
	<?php $caller->WPNonceField(); ?>
	<table class="form-table">			
		<tr valign="top">
      		<td><?php _e('Divert EMail To', $myDBaseObj->get_domain()); ?>:</td>
			<td>
				<input name="DivertEMailTo" type="text" maxlength="110" size="50" value="<?php echo $DivertEMailTo; ?>" />
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr valign="top">
      		<td><?php _e('Selected Sale', $myDBaseObj->get_domain()); ?>:</td>
			<td>
				<select name="TestSaleID">
<?php		
foreach($results as $result) {
			echo '<option value="',$result->saleID.'">'.$result->saleEMail.' - '.$result->saleDateTime.'&nbsp;&nbsp;</option>'."\n";
}
?>
				</select>
			</td>
			<td width=25%>
				<input class="button-primary" type="submit" name="testbutton_EMailSale" value="<?php _e('EMail Sale', $myDBaseObj->get_domain()); ?>"/>
			</td>
		</tr>
	</table>

	<?php		
			if (!$inForm) echo '</form>'."\n";
		}
	}
}

?>