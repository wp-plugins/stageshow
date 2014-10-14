<?php
/* 
Description: StageShow Plugin Top Level Code
 
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

/*
	This file lists examples of definitions of Advanced Customisation constants
	and is never loaded when running StageShow
*/

/*
------------------------------------------------------------------------------------------------
	STAGESHOW_MAX_TICKETSEATS
	
	Define the maximum number of tickets in the Prices and Price Plans admin pages.
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_MAX_TICKETSEATS', 8);

/*
------------------------------------------------------------------------------------------------
	STAGESHOW_STYLESHEET_URL
	
	Define the URL of the stylesheet loaded by StageShow

	If  specified  all  formatting  of  StageShow  output  will  be  determined  by  the 
	replacement stylesheet.
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_STYLESHEET_URL', 'wp-content/plugins/stageshowgold/css/stageshow.css');

/*
------------------------------------------------------------------------------------------------
	STAGESHOW_*******BUTTON_URL
	
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
define('STAGESHOW_CONFIRMANDPAYBUTTON_URL', 'wp-content/plugins/stageshowgold/images/stageshow_CommandAndPay.gif');

/* --------------------------------------------------------------------------------
	STAGESHOW_PAYPALEXPRESSBUTTON_URL
	
	The URL of the "Checkout with PayPal" button
	
-------------------------------------------------------------------------------- */
define('STAGESHOW_PAYPALEXPRESSBUTTON_URL', 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif');		

/*
------------------------------------------------------------------------------------------------
	STAGESHOW_BOXOFFICECOL_********
	
	Constants to redefine column titles on Box-Office pages
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_BOXOFFICECOL_NAME', 'Event');			// Default: Show
define('STAGESHOW_BOXOFFICECOL_DATETIME', 'Date');		// Default: Date & Time
define('STAGESHOW_BOXOFFICECOL_TICKET', 'Category');	// Default: Ticket Type
define('STAGESHOW_BOXOFFICECOL_PRICE', 'Value');		// Default:	Price
define('STAGESHOW_BOXOFFICECOL_QTY', 'Places');			// Default: Quantity
define('STAGESHOW_BOXOFFICECOL_CARTQTY', 'Seats');		// Default: Quantity/Seat

/*
------------------------------------------------------------------------------------------------
	STAGESHOW_BOXOFFICE_ALLDATES  
	
	Define this constant to disable the hiding of the date/time output  on  the 
	Box-Office page lines where it is the same as the line above. 
	
------------------------------------------------------------------------------------------------
*/
define('STAGESHOW_BOXOFFICE_ALLDATES', 'true');

/* --------------------------------------------------------------------------------
	STAGESHOW_DATETIME_BOXOFFICE_FORMAT
	
	Box-Office Output Date & Time Format
	
	This value must comply with the specification for the "format" parameter
	of the PHP date function. COnsult the PHP documentation for details.
	
	Some typical values and the corresponding output are as follows:
								
	  Format                      Output
    d-m-Y H:i                23-12-2013 23:57    (default)
      d-m-Y                     23-12-2013      (date only)
    jS F Y H:i             23rd July 2014 23:57	
	
-------------------------------------------------------------------------------- */
define('STAGESHOW_DATETIME_BOXOFFICE_FORMAT', 'd-m-Y H:i');

/* --------------------------------------------------------------------------------
	STAGESHOWLIB_IMAGESURL
	
	Base URL for images used by StageShow (i.e. EMail Header etc.)
	
-------------------------------------------------------------------------------- */
define('STAGESHOWLIB_IMAGESURL', 'http://asite.com/images/');

/* --------------------------------------------------------------------------------
	STAGESHOWLIB_NOTETOSELLER_ROWS
	
	This value specifies the number of Rows in the "Message to Sender" text entry
	box in the Shopping Trolley (default value 2)
	
-------------------------------------------------------------------------------- */
define('STAGESHOWLIB_NOTETOSELLER_ROWS', 5);

/*
------------------------------------------------------------------------------------------------
	STAGESHOW_VERIFYLOG_DUPLICATEACTION
	
	This value determines what action will be taken on attempting to verify a sale that has been
	previously verified.
	'ignore' - The previous verification is ignored
	'hide'   - Only the place & date/time of the first verification are shown
	default  - Both Ticket Details and Verification Details are shown
	
------------------------------------------------------------------------------------------------
*/

define('STAGESHOW_VERIFYLOG_DUPLICATEACTION', 'ignore');
define('STAGESHOW_VERIFYLOG_DUPLICATEACTION', 'hide');

?>