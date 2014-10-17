<?php
/*
	This file lists user definable constants used by StageShow
	
	To use any of the constants, copy the relevant line from this file to the
	site wp-config.php file and edit the value as required.
*/

/*
------------------------------------------------------------------------------------------------
	Constants to define custom images for buttons on the Box-Office Page
	
	Note: If the STAGESHOW_REMOVEBUTTON_URL define is set to '' the remove link will be replaced
	by a <div> "button"
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_ADDBUTTON_URL', 'wp-content/plugins/stageshowgold/images/stageshow_Add.gif');
define('STAGESHOW_CHECKOUTBUTTON_URL', 'wp-content/plugins/stageshowgold/images/stageshow_Checkout.gif');
define('STAGESHOW_REMOVEBUTTON_URL', 'wp-content/plugins/stageshowgold/images/stageshow_Remove.gif');
define('STAGESHOW_RESERVEBUTTON_URL', 'wp-content/plugins/stageshowgold/images/stageshow_Reserve.gif');
define('STAGESHOW_SELECTSEATSBUTTON_URL', 'wp-content/plugins/stageshowgold/images/stageshow_SelectSeats.gif');

/*
------------------------------------------------------------------------------------------------
	Constants to redefine column titles on Box-Office pages
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_BOXOFFICECOL_NAME', 'Event');
define('STAGESHOW_BOXOFFICECOL_DATETIME', 'Date');
define('STAGESHOW_BOXOFFICECOL_TICKET', 'Category');
define('STAGESHOW_BOXOFFICECOL_PRICE', 'Value');
define('STAGESHOW_BOXOFFICECOL_QTY', 'Places');
define('STAGESHOW_BOXOFFICECOL_CARTQTY', 'Seats');

/*
------------------------------------------------------------------------------------------------
	Define  STAGESHOW_BOXOFFICE_ALLDATES  to disable the hiding of the date/time output  on  the 
	Box-Office page lines where it is the same as the line above. 
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_BOXOFFICE_ALLDATES', 'true');

/*
------------------------------------------------------------------------------------------------
	Define STAGESHOW_DATETIME_BOXOFFICE_FORMAT to change the format of Date & Time output on the
	Box-Office page. 
	
	This entry uses the date format defined for the PHP date function. Full details can be found
	in the PHP documentation at http://php.net/manual/en/function.date.php
	If this is not defined, StageShow will use 'Y-m-d H:i'
	
	Examples:
		'd/m/Y H:i' => 23/07/2014 08:10
		'jS F Y' => 23rd July 2014 (No time)
		
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_DATETIME_BOXOFFICE_FORMAT', 'd/m/Y H:i');

/*
------------------------------------------------------------------------------------------------
	Define  STAGESHOW_MAX_TICKETSEATS  to change the maximum number of tickets in the Prices and 
	Price Plans admin pages.
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_MAX_TICKETSEATS', 8);

/*
------------------------------------------------------------------------------------------------
	Define STAGESHOW_STYLESHEET_URL to changes the URL of the stylesheet loaded by the StageShow
	plugin. If  specified  all  formatting  of  StageShow  output  will  be  determined  by  the 
	replacement stylesheet.
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_STYLESHEET_URL', 'wp-content/plugins/stageshowgold/css/stageshow.css');
?>