<?php
/* 
Description: Code for Table Management Class
 
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

require_once "stageshowlib_table.php";

if (!class_exists('StageShowLibSalesAdminListClass')) 
{
	class StageShowLibSalesAdminListClass extends StageShowLibAdminListClass // Define class
	{		
		static function FormatDateForAdminDisplay($dateInDB)
		{
			// Get Time & Date formatted for display to user
			return StageShowLibGenericDBaseClass::FormatDateForAdminDisplay($dateInDB);
		}

	}
}

?>