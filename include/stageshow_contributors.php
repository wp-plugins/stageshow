<?php

if (!class_exists('StageShowWPOrgContributorsClass')) 
{
	class StageShowWPOrgContributorsClass
	{
		static function GetContributors()
		{
			// Array of StageShow Contributors
			// The array that follows is an array of comma separated entries
			// Format: [Name],[Contribution],[URL]
			$conDefs = array(
				'TengYong Ng,			Date & Time Picker,		http://www.rainforestnet.com/datetimepicker/datetimepicker.htm',
				'David Tufts,			Barcode Generator,   	http://davidscotttufts.com/2009/03/31/how-to-create-barcodes-in-php/',
				'Deltalab,				QR Code Generator,   	http://phpqrcode.sourceforge.net/',
				'Nicholas Collinson,	French Translation, 	http://jouandassou.fr',
				'Andrew Kurtis,			Spanish Translation, 	',
			);
			
			$contributorsList = array();
			foreach ($conDefs as $conDef)
			{
				$conEntries = explode(',', $conDef);
				
				$url = trim($conEntries[2]);
				if ($url == '')
				{
					$url = 'n/a';
				}
				else
				{
					$url = "<a href=\"$url\" target=\"_blank\">$url</a>";
				}
				
				$ackEntry = new stdClass();
				$ackEntry->name = trim($conEntries[0]);
				$ackEntry->contribution = trim($conEntries[1]);
				$ackEntry->url = $url;
				
				$contributorsList[] = $ackEntry;
			}
			
			return $contributorsList;
		}
	}
}

?>