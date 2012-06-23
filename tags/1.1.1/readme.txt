=== StageShow ===
Contributors: Malcolm-OPH
Donate link: http://www.corondeck.co.uk/StageShow/donate.html
Tags: admin, calendar, cart, e-commerce, events, pages, payments, paypal, posts, theater, theatre, tickets, user
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 1.1.1

The StageShow plugin adds an online Box-Office for websites of Small Theatres and Amateur Drama Groups.

== Description ==

Any number of Shows can be specified, each with an unlimited number of performances. For each performance the start date/time, maximum number of places, ticket types and prices can be specified.  

A [sshow-boxoffice] tag added to a Page on the website adds the Box Office entry to the site.

StageShow uses the PayPal API to interface to PayPal to create "Saved Buttons" which are used to collect ticket payments and to control the maximum number of tickets sold for each performance. PayPal IPN (Instant Payment Notification) is used to verify payments and to collect buyer information. 

Each sale is fully recorded, with contact and payment details, tickets purchased and PayPal transaction number all saved to the Wordpress database. Confirmation emails, which can be customised as required, are sent to each purchaser and copied to the system administrator.

EMails are in MIME format and have both HTML and Text content. The PayPal transaction number if included in the standard email for validation purposes, both as text and as a Code39 barcode.  StageShow includes the facility on the admin pages to verify the transaction number (either entered via the keyboard or with a barcode reader) for use at show time 

StageShow includes the facility to export sales to a "TAB Separated Text" file for further analysis or processing by other programs (i.e. Spreadsheets etc.).

Features Summary

* Adds a online BoxOffice for unlimited number of Shows  
* Unlimited Performances with Specified start Date/Time and Maximum Number of Tickets 
* Unlimited number of Ticket Types for each performance with individually defined prices
* Integrated PayPal Payment Collection
* EMail confirmation of Booking to Client and Administrator
* Optional Barcode of Transaction ID in EMails  
* Manual entry of ticket sales for telephone sales etc.
* Online Transaction ID validation
* Export of Ticket Sales and Settings as "TAB Separated Text" format file

== Installation ==

First Time Installation

* Download the stageshow plugin archive
* Open the Wordpress Dashboard for you site
* Select the "Upload" option 
* Click "Add New" under the "Plugins" menu 
* Under "Install a plugin in .zip format" browse to the stageshow plugin archive file you downloaded
* Click Install Now.
* After it has installed, activate the plugin.
* Add sale details to the StageShow+ Auto Update Settings to enable Auto Update 

Upgrade

* On the WP Plugins Page deactivate StageShow
* Using FTP (or your ISPs file manager) delete the stageshow plugins folder in wp-content/plugins folder
* Now Proceed as for the First Time Installation

== Frequently Asked Questions ==

= How do I set up StageShow? =

* Install the plugin and activate it
* Go to the StageShow - Settings page and enter your PayPal details and click "Save Settings"
* Now go to the show, performance and prices pages and set up your show!
* Create a page on your website for the Box Office (or edit an existing one) and add the tag [sshow-boxoffice] to it
		
= What PayPal settings are required? =

PayPal API Access must be enabled - and the associated User, Password, Signature and EMail entries added to "Stageshow" settings. 
		
IPN Notification must be enabled for Sales to be recorded by the PlugIn. Payment will still be accepted and the sale will be recorded by PayPal if IPN is disabled.

StageShow can be used with a PayPal developer account (the "SandBox"). Select "SandBox" as the Environment option, and then enter the PayPal account parameters in the usual way.
	
= Why can't I edit the PayPal settings? =

PayPal Login details cannot be changed if one or more performance entries are present. 

The StageShow plugin creates a PayPal "Saved Button" when a performance is added to the show. There is currently no mechanism to recreate these buttons if the PayPal configuration is changed, hence the limitation.

= What WordPress settings are required? =

StageShow needs the WordPress setting of TimeZone to be correctly set-up (i.e. to a City) for time-sensitive functionality to operate correctly. The current value can be found on the Settings->General admin page.
	
= Why can't I delete a show or performances? =

A performance cannot be deleted if there are sales recorded for it and the show start time has not yet been reached. A show cannot be deleted if performances are still configured for it.

= How do I add a Booking Form to my site? =

Add the tag [sshow-boxoffice] to either a new or existing page on your site. This will be replaced by the booking form when the page is viewed by a user.

= How can I customise the EMails? =

The EMails generated by the StageShow plugin are defined by a template file. By default the template file is {Plugins Folder}/stageshow/templates/stageshow_EMail.php

The template file can be modified as required. A number of "Tags" can be included in the EMail template, which will be replaced by data relating to the sale extracted from the database. 

= What tags can be used in the EMail template? =

The following tags can be used in the EMail template:

* [salePPName]	Buyer PayPal Account Details: Name
* [salePPStreet]	Buyer PayPal Account Details: Street
* [salePPCity]	Buyer PayPal Account Details: City
* [salePPState]	Buyer PayPal Account Details: State
* [salePPZip]	Buyer PayPal Account Details: Zip/Post Code
* [salePPCountry]	Buyer PayPal Account Details: Country

* [saleDateTime]	Sale Details: Date and Time
* [saleName]	Sale Details: Buyer Name
* [saleEMail]	Sale Details: Buyer EMail
* [salePaid]	Sale Details: Paid
* [saleTxnId]	Sale Details: PayPal Transaction ID (TxnId)
* [saleStatus]	Sale Details: PayPal Transaction Status
* [saleBarcode] Sale Details: PayPal Transaction ID converted to a Barcodes 

* [startloop]	Marker for the start of a loop for each ticket type purchased
* [endloop]	Marker for the end of the loop 
* [ticketName]	Sale Details: Ticket Name
* [ticketType]	Sale Details: Ticket Type ID
* [ticketQty]	Sale Details: Ticket Quantity

* [organisation]	The Organsiation ID (as on the Settings Page)
* [adminEMail]	The Admin EMail (as on the Settings Page)
* [url]	The Site Home Page URL

== Screenshots ==

1. Screenshot 1: Stageshow and PayPal Settings Page 
2. Screenshot 2: Overview Page 
3. Screenshot 3: Shows Setup (Showing Options)
4. Screenshot 4: Price Plans Setup 
5. Screenshot 5: Performances Setup 
6. Screenshot 6: Performances Setup (Showing Options)
7. Screenshot 7: Ticket Types and Prices Setup 
8. Screenshot 8: Sales Log Summary 
9. Screenshot 9: Sales Log Summary (Showing Details)
10. Screenshot 10: Sales Log Show Summary 
11. Screenshot 11: Sales Log Show Summary (Showing Details)
12. Screenshot 12: Sales Log Performance Summary 
13. Screenshot 13: Sales Log Performance Summary (Showing Details)
14. Screenshot 14: Admin Tools Page 
15. Screenshot 15: Shows Box Office Page 
16. Screenshot 16: Sample EMail 

== Changelog ==

= 0.9 =
* First public release

= 0.9.1 =
* Added Pagination to Sales Summary
* Added Uninstall
* Added Activate/Deactivate options for shows and performances

= 0.9.2 =
* Bug Fix: Malformed &ltform&gt tag on BoxOffice page fixed
* BoxOffice time/date format now uses WordPress settings
* (Note: Private release)

= 0.9.3 =
* Fixed Distribution Error in ver0.9.1
* Added Style Sheet (stageshow.css)
* Added styles to BoxOffice page and updated default style

= 0.9.3.1 =
* Fixed "Function name must be a string" error when changing Admin EMail ( stageshow_manage_settings.php)

= 0.9.4 =
* Added StageShow specific capabilities (StageShow_Sales, StageShow_Admin and StageShow_Settings) to WP roles 
* Added Facility to manually add a sale
* Added Facility to activate/deactivate selected performances
* Box Office page elements formatted by stageshow.css stylesheet
* Duplicate dates on BoxOffice output supressed (STAGESHOW_BOXOFFICE_ALLDATES overrides)
* Added Facility to activate/deactivate selected shows 
* Added Transaction ID Barcode to HTML Email 
* Added Plugin update from custom server 
* Added facility to add a "Note" to any Show 

= 0.9.5 =
* Bug Fix: Preserves show name when upgrading to StageShow-Plus 
* Dual PayPal Credentials merged - Live or Test (Sandbox) mode must be set before adding performances
* StageShow-Plus renamed StageShow+

= 1.0.0 =
* Bug Fix: Call to wp_enqueue_style() updated for compatibility with WP 3.3
* AutoComplete disabled on Settings page
* PayPal Account EMail address added to settings (PayPal may not report it correctly)
* Shortcodes Summary added to Overview page
* Added support of "User Roles" to admin pages
* Added Ticket Sale Reference Validation to "Tools" Admin page
* Added Pagination to all admin screen lists

= 1.0.1 =
* Bug Fix: include folder missing from archive in 1.0.0

= 1.0.2 =
* Items per page added to settings
* Performance expire limit time added to settings 
* Negative/Non-Numeric max number of seats converted to unlimited (displayed as infinite)
* New Performance defaults to unlimited number of seats
* Number of seats available can be set to unlimited using negative value (default value)

= 1.0.3 =
* Show Notes text is displayed or hidden under control of a Show/Hide button on the edit shows page 
* Bug Fix: Input Edit box size value fixed
* Bug Fix: Box Office shortcode output was always at top of page.
* Max Ticket Count added to settings

= 1.0.4 =
* Implemented option of a "Note" for each show output above entry in BoxOffice (supports HTML markup) 
* Implemented option of a "Note" for each performance output above or below entry in BoxOffice(supports HTML markup) 
* Added Currency Symbols to currency options
* Added option to output currency symbol in Box Office
* Added "Price Plans" setup for new performances 
* Cosmetic: Added separator line between admin screen entries
* Bug Fix: HTML Select element options on some admin screens not retrieved
* Bug Fix: Undefined variable error generated on Performances update error
* Class of BoxOffice output HTML elements changed from boxoffice-**** to stageshow-boxoffice-****

= 1.0.5 =
* Bug Fix: "Add New Show" not displayed on Shows Admin Page (Add New Price ahown instead)
* Implemented Hidden Rows for Extended Fields in Admin Screens 

= 1.0.6 =
* Implemented "Hidden Rows" for Details Fields in Sales Screen
* Bug Fix: Inventory Control not working in v1.0.5 - Fixed (includes PayPal buttons update on activation)
* Added check for zero ticket prices - new price entries initialised to 1.00
* Bug Fix: Show note was not saved 

= 1.0.7 =
* Bug Fix: Sale Ticket Type was always recorded as the same type
* Renamed "Presets" as "Price Plans"
* Coding of Admin Pages restructured
* Added sale editor 
* Error notifications on Admin Pages improved
* Price Plans included in Sample entries 
* StageShow specific styles now defined in stageshow-admin.css
* File Names rationised
* Bug Fix: "Empty" Price Plans were not deleted 
* Bug Fix: "Validate" Capability implemented

= 1.1.0 =
* Bug Fix: Activate/Deactivate Show not working ...
* Compatible with WP 3.4

= 1.1.1 =
* Bug Fix: Undefined function call error on Save from Edit Sales with an invalid entry  
* Added Performance Sales Summaries to Overview Page
* Added "booking confirmed" message to email template
* Missing Configuration messages now include links to the relevant admin page
* References to "Group" changed to "Price Plan" 
* Updates default Email template to HTML on activation 
* Sales page accessible (non-edit) to users with StageShow_Validate capability
 
== Upgrade Notice ==

= 0.9 =
* First public release

= 1.0.0 =
* Earlier versions not compatible with WP 3.3 - Style sheets may not load

= 1.0.7 =
* Bug Fix: Sales were always recorded as the same Ticket Type
