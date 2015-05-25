<?php
/* 
Description: Core Library Admin Page functions
 
Copyright 2015 Malcolm Shergold

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

if (!class_exists('StageShowLibCalendarClass')) 
{
	class StageShowLibCalendarClass 
	{
		var $cssRoot = 'calendar';
		var $secsPerDay;
		var $linksOpenNewTab = true;
		
		var $myDBaseObj;
		
		function __construct($myDBaseObj)
		{
			$this->myDBaseObj = $myDBaseObj;
		}

		function OutputCalender($results)
		{		
			$this->secsPerDay = (60*60*24);
				 
			// Get current day of the week
			$dateNow = strtotime(date(StageShowLibDBaseClass::MYSQL_DATE_FORMAT));
			$weekday = date( "N", $dateNow);

//$htmlOutput .= "Today: ".date(StageShowLibDBaseClass::MYSQL_DATE_FORMAT, $dateNow)."<br>\n";
//$htmlOutput .= "Weekday: ".$weekday."<br>\n";

			// Find date of Last Monday
			$lastDay = 0;							 
			while ($weekday > 1)
			{
				$dateNow -= $this->secsPerDay;
				$weekday = date( "w", $dateNow);
			}
//$htmlOutput .= "Last Monday: ".date(StageShowLibDBaseClass::MYSQL_DATE_FORMAT, $dateNow)."<br>\n";
			 
			// Get the events list (filtered) starting with last monday
			$startDate = date(StageShowLibDBaseClass::MYSQL_DATE_FORMAT, $dateNow);
			
			$eventIndex = 0;
			$eventCount = count($results);
			if ($eventCount > 0)
			{
				$result = $results[0];
			}

			$newMonth = true;
			$htmlOutput = '';
			
			// Loop Round for 52 weeks (max)
			for ($weekNo = 1; $weekNo<=52; $weekNo++)
			{
				if ($newMonth)
				{
					//if ($result === null) break;
				
					if ($weekNo != 1) 
					{
						// Add end of table tag block
						$htmlOutput .=  "</tbody></table></div></div>\n";
					}

					$htmlOutput .=  $this->OutputHeader($dateNow);
					$newMonth = false;
				}
					
				$htmlOutput .=  "<tr>\n";
				
				// Loop Round for 7 days
				for ($dayNo = 1; $dayNo<=7; $dayNo++)
				{				
					$dayOfMonth = date( "j", $dateNow);
					
					// Get default class and text		 
					$cellClass = $this->cssRoot.'Date';
					$cellLink = $dayOfMonth;
					
					$dateNowText = date(StageShowLibDBaseClass::MYSQL_DATE_FORMAT, $dateNow);
					
					// Loop Through all events with the same date
					$cellAltTag = '';
					while ( ($eventIndex < $eventCount) && ($this->GetRecordDate($result) == $dateNowText) )
					{
						$cellClass .= $this->DateTileClass($result);
						
						$cellLink = "<strong>$dayOfMonth</strong>";
						$cellURL = $this->DateTileURL($result);
							
						if ($cellAltTag != '') $cellAltTag .= "\n";
						$cellAltTag .= $this->DateTileTitle($result);
						
						$cellTarget = ($this->linksOpenNewTab) ? ' target="_blank" ' : '';
						// TODO - This link will only go to the last entry in the database that matches
						if ($cellURL !== '') $cellURL = ' href="'.$cellURL.'" ';
						$cellLink =  '<a '.$cellURL.$cellTarget.' alt="'.$cellAltTag.'"  title="'.$cellAltTag.'">'.$cellLink.'</a>';
						
						$eventIndex++;
						if ($eventIndex < $eventCount)
						{
							$result = $results[$eventIndex];
						}
					}
					
					$htmlOutput .=  '<td class="'.$cellClass.'">'.$cellLink.'</td>';
					$htmlOutput .=  "\n";

					if ($lastDay > $dayOfMonth)
						$newMonth = true;
									
					$lastDay = $dayOfMonth;
							 
					$dateNow += $this->secsPerDay;
					
					if (date( "j", $dateNow) == 1)
					{
						$newMonth = true;
						$lastDay = 1;
					}
				}
				$htmlOutput .=  "</tr>\n";
			}			
			$htmlOutput .=  "</tbody></table></div></div>\n";			
			
			return $htmlOutput;
		}
		
		function OutputHeader( $dateNow )
		{	
			static $blockCount = 1;
			
			$htmlOutput  =  '';

			$blockClass = $this->cssRoot.'MonthBlock '.$this->cssRoot.'MonthBlock'.$blockCount++;
			
			$htmlOutput .= '<div class="'.$blockClass.'">'."\n";						
			$htmlOutput .= '<div class="'.$this->cssRoot.'Month">'.date( "F ", $dateNow)."</div>\n";						
			$htmlOutput .= '<div class="'.$this->cssRoot.'">';						
			$htmlOutput .= '<table class="'.$this->cssRoot.'Table"><tbody>'."\n<tr>\n";
			//$htmlOutput = '<table cellspacing="0" cellpadding="2" bordercolor="#CCCCCC" border="1" align="center" width="399"><tbody>';			
			
			// Loop Round for 7 days
			for ($dayNo = 1; $dayNo<=7; $dayNo++)
			{
				$cellLink = date( "D", $dateNow);
							 
				$htmlOutput .=  '<td class="'.$this->cssRoot.'Day">'.$cellLink.'</td>';
				$htmlOutput .=  "\n";

				$dateNow += $this->secsPerDay;
			}
			
			$htmlOutput .=  "</tr>";
			return $htmlOutput;
		}
		
		function GetRecordDate($result)
		{
			return '';
		}
					
		function DateTileClass($result)
		{
			return '';
		}
		
		function DateTileTitle($result)
		{
			return '';
		}
		
		function DateTileURL($result)
		{
			return '';
		}
		
	}
} //End Class StageShowLibCalendarClass

?>