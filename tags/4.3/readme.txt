=== StageShow ===
Contributors: Malcolm-OPH
Donate link: http://www.corondeck.co.uk/StageShow/donate.html
Tags: admin, calendar, cart, e-commerce, events, pages, payments, paypal, posts, theater, theatre, tickets, user
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 4.3

StageShow adds the facility for an online Box-Office for Small Theatres/Drama Groups, records sales, validates tickets and provides sales downloads.

== Description ==

StageShow provides a simple interface to define your Shows, Performances and Prices. Then a single Wordpress shortcode adds a online BoxOffice to your website.

StageShow uses its’ own integrated Shopping Trolley to collect orders, and PayPal to collect payments. Purchasers can pay using either a PayPal account or a credit/debit card. PayPal IPN (Instant Payment Notification) is used to record sales and to collect buyer information. 

Each sale is fully recorded, with contact and payment details, tickets purchased and PayPal transaction number all saved to the Wordpress database. Confirmation emails, which can be customised as required, are sent to each purchaser and can be copied to the system administrator.

EMails are in text only format, and the PayPal transaction number is included for validation purposes.  StageShow includes the facility on the admin pages to verify the transaction number for use at show time.

StageShow includes the facility to export sales to a "TAB Separated Text" file for further analysis or processing by other programs (i.e. Spreadsheets etc.).

An <a href="http://corondeck.co.uk/demo">online demo</a> (for all StageShow Variants) is available <a href="http://corondeck.co.uk/demo">here</a>.

StageShow Features Summary

* Adds a online BoxOffice for a Single Show
* Up to 4 Performances with Specified start Date/Time and Maximum Number of Tickets
* Unlimited number of Ticket Types for each performance with individually defined prices
* Integrated Shopping Trolley
* Integrated PayPal Payment Collection
* Payments accepted using Credit/Debit cards or from PayPal account
* EMail confirmation of Booking to Client and (optionally) to Administrator
* Manual entry of ticket sales for telephone sales etc.
* Online and Offline Sale Transaction ID validation
* Export of Ticket Sales and Settings as "TAB Separated Text" format file
* Access to StageShow Admin pages/features controlled by custom capabilities
* Extensive Help (in PDF format) file shipped with plugin

Additional Features in StageShow+ (available <a href="http://corondeck.co.uk/StageShow/Plus">here</a>)

* No limit on number of Shows or Performances
* Unlimited number of User defined "Price Plans" to set prices when adding a performance
* Optional ticket Reservations for logged in users (i.e. Unpaid ticket sales)
* Allows ticket prices to be defined as "Admin Only" (only available via Admin menus)
* Reservation Client Details captured from Users Profile extended by any 3rd party plugin
* MIME Encoded EMails so HTML/Text mixed format emails supported
* Optional Barcode of Transaction ID in sale confirmation emails
* Logging of Online Ticket Validation attempts
* Multiple Terminal Support for Verification
* Editing of Sale Entries
* Show title output on Box Office page customisable per show (text/HTML) 
* Performance title output on Box Office page customisable per performance (text/HTML) 
* Optional EMail with sales summary (to a specified email address) on each new sale 
* Booking Closing Time can be specified for each performance
* Custom Style Sheets

Additional Features in StageShowGold (available <a href="http://corondeck.co.uk/StageShow/Gold">here</a>)

* Allocated Seating
* Custom Seating Layouts
* PayPal Express Checkout

== Installation ==

First Time Installation

* Download the plugin archive
* Open the Wordpress Dashboard for you site
* Select the "Upload" option 
* Click "Add New" under the "Plugins" menu 
* Under "Install a plugin in .zip format" browse to the plugin archive file you downloaded
* Click Install Now.
* After it has installed, activate the plugin.

Upgrade

* On the WP Plugins Page deactivate (but do <span style="text-decoration: underline;">NOT</span> delete) the current StageShow plugin
* Using FTP (or your ISPs file manager) delete the stageshow plugins folder in the wp-content/plugins folder
* Now Proceed as for the First Time Installation

== Frequently Asked Questions ==

= How do I get help? =

* Read these FAQs
* Read the <a href=http://corondeck.co.uk/downloads/stageshowplus/StageShowHelp.pdf>documentation</a>
* Contact the plugin author <a href=http://corondeck.co.uk/contact-us/>here</a>. Requests for help that are already well documented may get a sharp response!

= How do I set up StageShow? =

* Install the plugin and activate it
* Go to the StageShow - Settings page and enter your PayPal details and click "Save Settings"
* Now go to the show, performance and prices pages and set up your show!
* Create a page on your website for the Box Office (or edit an existing one) and add the tag [sshow-boxoffice] to it
		
= What PayPal settings are required? =

PayPal API Access must be enabled - and the associated User, Password, Signature and EMail entries added to "Stageshow" settings. 
		
IPN (Instant Payment Notification) must be enabled for Sales to be recorded by the PlugIn. Payment will still be accepted and the sale will be recorded by PayPal if IPN is disabled. 
Set the "IPN Listener" URL to http://{Your Site URL}/wp-content/plugins/stageshow/stageshow_NotifyURL.php. 

= Why can't I edit the PayPal settings? =

PayPal Login details cannot be changed if one or more performance entries are present. 

The StageShow plugin creates a PayPal "Saved Button" when a performance is added to the show. There is currently no mechanism to recreate these buttons if the PayPal configuration is changed, hence the limitation.

= What WordPress settings are required? =

StageShow needs the WordPress setting of TimeZone to be correctly set-up (i.e. to a City) for time-sensitive functionality to operate correctly. The current value can be found on the Settings->General admin page.
	
= Why can't I delete a show or performances? =

A performance cannot be deleted if there are sales recorded for it and the show start time has not yet been reached. A show cannot be deleted if performances are still configured for it.

= How do I add a Booking Form to my site? =

Add the tag [sshow-boxoffice] to either a new or existing page on your site. This will be replaced by the booking form when the page is viewed by a user.

= Do my purchasers have to have a PayPal account? =

No. Turning on the "PayPal Account Optional" setting on the sellers PayPal account allows purchasers to use a Credit or Debit card without the need for a PayPal account. Details are in the StageShow help file.

= How can I customise the EMails? =

The EMails generated by the StageShow plugin are defined by a template file. 
Template defaults are in the {Plugins Folder}/{Plugin Name}/templates/email folder, which is copied to the {Uploads Folder}/stageshow/email when the plugin is Activated or Updated. The default email template is stageshow_EMail.php.  
The default template can be copied to new a file in the uploads folder, which can then be used to create a custom template, which can then in turn be selected using the Admin->Settings page.

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
* [saleBarcode] Sale Details: PayPal Transaction ID converted to a Barcodes (Only for StageShow+)

* [startloop]	Marker for the start of a loop for each ticket type purchased
* [endloop]	Marker for the end of the loop 
* [ticketName]	Sale Details: Ticket Name
* [ticketType]	Sale Details: Ticket Type ID
* [ticketQty]	Sale Details: Ticket Quantity

* [organisation]	The Organsiation ID (as on the Settings Page)
* [adminEMail]	The Admin EMail (as on the Settings Page)
* [url]	The Site Home Page URL

= How can I use my own images on the Checkout page? =

Default Images in the {Plugins Folder}/{Plugin Name}/templates/images folder are copied to the {Uploads Folder}/{Plugin Name}/images when the plugin is Activated or Updated. 
Custom images can be copied to this folder (using FTP) and can then be selected using the Admin->Settings page.

= Where is the User Guide? =

A copy of the User Guide, as a pdf file, is included with StageShow distributions. This can be accessed via a link on the Overview page.
The User Guide can also be downloaded or viewed <a href=http://corondeck.co.uk/downloads/stageshowplus/StageShowHelp.pdf>here</a>.

== Screenshots ==

1. Screenshot 1: Overview Page 
2. Screenshot 2: Shows Setup 
3. Screenshot 3: Performances Setup 
4. Screenshot 4: Ticket Types and Prices Setup 
5. Screenshot 5: Sales Log Summary 
6. Screenshot 6: Sales Log Summary (Showing Details) 
7. Screenshot 7: Admin Tools Page (Showing Sale Verification) 
8. Screenshot 8: PayPal Settings Page 
9. Screenshot 9: General Settings Page 
10. Screenshot 10: Advanced Settings Page 
11. Screenshot 11: Shows Box Office Page 
12. Screenshot 12: Sample EMail 

== Changelog ==

* Version History for StageShow Plugins 

= 4.3 (16/12/2014) =
* Bug Fix: Ticket Quantities Breakdown and Ticket Name Columns missing in TDT export
* Buf Fix: Uninstall Plugin failure
* Bug Fix: HTML special characters in shortcode atts not decoded
* Added "PayFast" Payment Gateway (StageShowPlus)
* Added Debug Output option to Send EMail Test 
* Added "Bcc to Admin" option to Send EMail Test 
* Added Anchor Tag for Top of Shopping Trolley to Box-Office output
* Added Anchor Tags for Top of Each Show to Box-Office output
* Added shortcode anchor argument

= 4.2.3 (20/11/2014) =
* Bug Fix: Ticket Validator Failure (StageShow & StageShowPlus)

= 4.2.2 (17/11/2014) =
* Bug Fix: StageShowPluginClass undefined (since 4.2.1)

= 4.2.1 (15/11/2014) =
* Bug Fix: Ticket Validation fails on Unix servers
* Bug Fix: Ticket Validation fails if PHP does not support mysqli_fetch_all()

= 4.2 (11/11/2014) =
* Bug Fix: Logs folder path permissions should be 600
* Bug Fix: Paragraph tag before Validate button should not have a class
* Buf Fix: Exported Seating Template opens in browser (StageShowGold)
* Optimisation: activate() function called twice on first activation
* Optimisation: PayPalImagesUseSSL option used before definition
* Optimisation: Ticket authentication response improved using JQuery 
* Added additional barcode type (Code 128)
* Added contributors list to Overview Page
* Logs folder path default changed to "logs"
* Added JQuery loader
* Copies DB Access defines to wp-config-db.php in uploads folder
* Added translation for Seating Plans Buttons

= 4.1.2 (17/10/2014) =
* Bug Fix: Purchaser Name, Show and Performance missing on Offline Validator
* Bug Fix: Added Seats to Offline Validator (StageShowGold)
* Bug Fix: Seats not decoded in TDT export file (StageShowGold)
* EMail Address and Prices removed from Offline Validator
* Removed redundant Shopping Trolley onClick handlers on admin pages
* Added class to Remove button on admin page

= 4.1.1 (15/10/2014) =
* Bug Fix: Phantom PayPal button and Next button inoperative when editing sales (StageShowGold)

= 4.1 (13/10/2014) =
* Bug Fix: Adding Tickets for same date-time and Ticket Type always adds entry already in Trolley
* Bug Fix: Sites on Secure Server have incorrect URL for admin page filters
* Bug Fix: Sites on Secure Server have incorrect URL for Box-Office reports on Overview
* Bug Fix: Performance Expiry Date not used to determine active Performances by Verification Check
* Bug Fix: Overview Sales values calculated incorrectly for Group tickets
* Added Spanish Translation
* Added details on updating translations to help
* Remove buttons on Trolley changed from links to submit button (in a &lt;form$gt;)
* Removed box shadow from Add buttons
* Added Sale Verification fields to TDT ticket download
* Added option for PayPal Express Checkout (StageShowGold)
* Added Verify Fields to TDT Downloads

= 4.0.2.1 (27/09/2014) =
* Sale Verification code moved to separate class (StageShowSaleValidateClass)
* Added stageshow_direct_validate.php - Direct Sale Verifier (StageShowGold)
* Added Spanish Translation

= 4.0.2 (20/09/2014) =
* Bug Fix: Non-default WP DB table prefix produces empty TDT download 
* Bug Fix: Offline Validator fails with non-default WP DB table prefix  
* Bug Fix: Checkout button with image fails to redirect to PayPal on Firefox/IE but OK with Chrome
* Added "Ticket Paid" values to Offline Validator results
* Automatically creates copy of stageshow-custom.css when selected in settings
* Automatically creates copy of stageshow-custom.js when selected in settings

= 4.0.1 (10/09/2014) =
* Bug Fix: Invalid Comment line in stageshow.css blocks loading of stagehsow-seats.css
* Bug Fix: Cannot see seats in Box-Office seat selection Page (stageshow-seats.css not loaded)
* Added sample CSS for Box-Office button colours to stageshow-custom.css

= 4.0 (07/09/2014) =
* Bug Fix: Error on OFX Export with no sales
* Bug Fix: Non-existent CSS file imported (admin.css)
* Bug Fix: Content-Disposition MIME type should not include attachment
* Bug Fix: Box-Office quantity drop-down limited to 1 when no of seats is unlimited
* Bug Fix: saleNoteToSeller not initialised in empty Shopping Trolley
* Duplicate output of wp_nonce removed
* Added CSS for removing Date/Time column to stageshow-custom.css
* Export settings includes uncompleted Show & Performance entries

= 3.9.4 (26/08/2014) =
* Bug Fix: nonce missing in Remove from Shopping Trolley link
* Added STAGESHOW_***********BUTTON_URL defines to use images for Box-Office buttons
* Added optional "Donation" entry to Shopping Trolley (StageShowPlus)
* SeatingPlans made readonly once prices defined (StageShowGold)
* Added more examples of defines to stageshow_wp-config_sample.php 

= 3.9.3 (19/08/2014) =
* Bug Fix: Invalid Class name in stageshow_export.php on line 34 (since 3.8)
* Added sample wp-config.php file (stageshow_wp-config_sample.php)
* Added $_GET and $_POST to debug output options
* Separated Booking Fee and PayPal Fees in TDT Export

= 3.9.2 (08/08/2014) =
* Bug Fix: Entries with empty filenames added when Custom CSS or JS files are not defined
* Bug Fix: Note to Seller entry lost when "Select Seats" is selected (StageShowGold)
* Added optional define to replace Box-Office "Remove" link with image
* Added stageshow-boxoffice class to Box-Office buttons
* Added optional defines to rename Box-Office column labels
* Added HTTP Error Status to Plugin Auto-Update Status
* Added templates/html/stageshow-custom-defines.php with details of advanced customisations

= 3.9.1 (02/08/2014) =
* Bug Fix: Distribution problem with StageShow plugin v3.9 - GetPluginStatus undefined
* Bug Fix: Box Office Quantity selector can exceed maximum available seats
* Bug Fix: "Checkout Note" shown when editing sale
* Plural forms of system message used instead of singular forms

= 3.9 (01/08/2014) =
* Bug Fix: Parse Error if class redefinition is attempted
* Added "Note to Seller" to Sale Manual Entry/Editor and Sales admin page
* Added onClick() event handler framework to all Box-Office buttons
* Added shortcodes for performance ID and performance Date/Time (StageShowPlus)
* Auto-update disabled settings link sets focus on activation (StageShowPlus)
* Added Auto-update Server Status to Plugins and StageShow Overview pages (StageShowPlus)
* Added option for Custom Javascript file (StageShowPlus)
* Added framework for custom Checkout HTML elements (StageShowPlus)
* Added sample JS code for custom Checkout HTML elements to stageshow-custom.js (StageShowPlus)
* Allocated Seating always enabled - setting option removed (StageShowGold)

= 3.8.7 (21/07/2014) =
* Bug Fix: Sale Editor fails (StageShowPlus)
* Bug Fix: stageshow-boxoffice-seat class missing from seating templates (StageShowGold)
* Bug Fix: saleNoteToSender not shown in sample email templates
* Bug Fix: Settings "Tabs" not selecting entries correctly
* Bug Fix: Reserved status not shown by Sale Editor (StageShowPlus)
* Added "Note To Seller" to sales report
* Box-Office page buttons can use images (Set by defines in wp-config.php)

= 3.8.6 (06/07/2014) =
* Bug Fix: Obsolete JS call to SetSalesInterfaceControls() removed
* Bug Fix: Exported Seating Plans not stripped of automatically created tag parameters (StageShowGold)
* Bug Fix: Seating Template seat tags missing stageshow-boxoffice-seat class (StageShowGold)
* Box Office screen changed to have a single &lt;form&gt; tag (StageShowGold)
* Added optional "Note to Seller" to Checkout
* PayPal API settings made optional
* MerchantID made optional (Uses PayPal Account email if blank)
* Added stageshow-boxoffice-zone-{ZoneRef} to Seating Template seats classes (StageShowGold)

= 3.8.5 (25/06/2014) =
* Added SSL option for PayPal images
* Added option to specify Seating Plan for Price Plans (StageShowGold)
* Bug Fix: Zone selection missing in Price Plans (StageShowGold)

= 3.8.4 (18/06/2014) =
* Bug Fix: Distribution problem with 3.8.3 on Wordpress.org
* Seat Template has Decoded seat names as title tags (StageShowGold)

= 3.8.3 (16/06/2014) =
* Bug Fix: StageShow displays expired performances when there are non-expired performances (StageShow)
* Bug Fix: layoutNames not defined error when reporting zone spec parsing error (StageShowGold)
* Added DecodedSeatIDs as title param in Seat Layout Template seats tags (StageShowGold)

= 3.8.2 (29/05/2014) =
* Bug Fix: Seating Plan "Bulk" Delete gives "Nothing to Delete" Error

= 3.8.1 (27/05/2014) =
* Bug Fix: Number of seats remaining never shown on last box office entry
* Added option for non-allocated seat zones (StageShowGold)

= 3.8 (03/05/2014) =
* Bug Fix: StageShow on WP.org has [ticketSeat] in EMails
* Bug Fix: Error Exporting StageShowGold Seating Templates (StageShowGold)
* Bug Fix: "Admin Only" prices not included in Add/Edit Sale screen
* Bug Fix: Sale Status not included in Tickets download 

= 3.7 (27/04/2014) =
* Bug Fix: Edit Sale only saved when purchaser contact details are changed

= 3.6 (26/04/2014) =
* Bug Fix: Manually Added Sale with Allocated Seats not saved to database (StageShowGold)
* Bug Fix: Add Sale generates "saleStatus undefined" error 
* Checkout Header and Logo file types extended to include GIF, JPG and PNG
* Added "error" and "ok" class to stageshow notifications

= 3.5 (21/04/2014) =
* Bug Fix: PHP Strict standards error on function Export() declaration
* Bug Fix: Some Admin screens have edit text entries set to zero size
* Added show & performance filters to Tools->Export (StageShow+)
* Javascript function names now include plugin name (to help make them unique)

= 3.4 (18/04/2014) =
* Bug Fix: Validate ticket output does not output seats (StageShowGold)
* Bug Fix: Sale Total incorrect for Sample Sale with Allocated Seating (StageShowGold)
* Added Booking Fee to Sample Sales
* TDT Download Filenames changed
* Paid/Fee etc. fields removed from Summary Download
* Added ticketFee entry to Tickets Export
* Rendundant Columns removed from Tickets Export
* PayPal Fees and Booking Fees split between each ticket in Ticket Export
* Tickets Export Fields Rationised

= 3.3 (12/04/2014) =
* Bug Fix: Some HTML &lt;input&gt;tags on Admin pages incorrect size (size attribute is zero)
* Bug Fix: Missing space between tags in Seating Templates (StageShowGold)
* Bug Fix: Max Seats hidden on Performances page once Sales have been made (StageShowGold)
* Added seating Row/Seat "Translator" (StageShowGold)

= 3.2 (02/04/2014) =
* Bug Fix: OutputList() E_STRICT error 
* Bug Fix: Rogue .htaccess file in build (from 3.1) denys access to CSS and JS files
* Bug Fix: View Template output terminated early (StageShowGold)
* Bug Fix: HTML tag deliminator missing in Imported Templates (StageShowGold)
* Implemented Family tickets for Allocated Seating (StageShowGold)

= 3.1 (30/03/2014) =
* Bug Fix: Payment Timeout emails not generated correctly
* Bug Fix: Sale Summary Reports generated for Pending Sales (StageShowPlus)
* Bug Fix: DB Error when attempting to delete seating plan (StageShowGold)
* Bug Fix: Add Seating Plan button missing when list empty (StageShowGold)
* Bug Fix: Edit Performance Fails if Prices have been defined (StageShowGold)
* Bug Fix: SeatingID can be changed once Prices have been defined (StageShowGold)
* Updated for compatability with WP 3.9

= 3.0 (10/03/2014) =
* Bug Fix: Link to settings page from Overview page incorrect
* Bug Fix: Shortcode with count=** attribute not working
* Add/Edit Sales uses same UI as Box-Office page
* Ticket Options removed from Box Office Listing once Allocated Seats all taken (StageShowGold)
* Number of Available Seats for each Zone now shown on Box Office (StageShowGold)
* Edit of Seating Plan blocked when Prices have been defined  (StageShowGold)
* Max Seats edit hidden for Performances with Allocated Seating (StageShowGold)
* Updated Seating Plan stylesheet (StageShowGold)
* Added Number of Seats to Seating Plan Page  (StageShowGold)

= 2.5.3.5
* Implemented Manual Edit/Add for Allocated Seating Sales (StageShowGold)
* Added Seat Number in Confirmation EMails (StageShowGold)

= 2.5.3.3 (19/02/2014) =
* Bug Fix: Errors in Sample Data 
* Sale Editor Updated (but Incomplete) 

= 2.5.3.2 (17/02/2014) =
* Buf Fix: Option for "Box Office Below Trolley" inconsistent
* ZoneRef removed from seating templates
* Added zone limits to JS
* Select Performance screen removed from Box Office
* Partially complete SSG sale editor added

= 2.5.3.1 (15/02/2014) =
* Database Text Field Length definitions updated so they can be defined in wp-config.php

= 2.5.3 (04/02/2014) =
* Bug Fix: Fatal Error on Checkout - STAGESHOWLIB_LOGSALEMODE_CHECKOUT undefined

= 2.5.2 (03/02/2014) =
* Bug Fix: Interaction with other plugins can cause Performance Date & Time Picker to fail
* Bug Fix: Adding border to settings page corrupts other admin pages
* Bug Fix: No of sets in Price Plan not saved in new performance prices  (StageShow+)
* Bug Fix: Allocated Seats availability not checked before commiting sale (StageShowGold)
* Bug Fix: Allocated Seats not saved for Reservations (StageShowGold)
* Added check that seats are still available on checkout (StageShowGold)
* PHP with E_STRICT enabled generates warnings

= 2.5.1 (26/01/2014) =
* Bug Fix: Adding border to settings page corrupts other admin pages
* Bug Fix: Allocated Seats not saved for Reservations (StageShowGold)

= 2.5 (25/01/2014) =
* Updated for WP 3.8.1
* Bug Fix: Incorrect class for blank cells in Trolley "Remove" column
* Added link to SSG in readme
* Added custom stylesheet loader (StageShow+)
* Support for PayPal Sandbox removed
* StageShowGold (Beta) Released

= 2.4.4 (21/01/2014) =
* Bug Fix: Cannot reserve last seat for a performance
* Bug Fix: No error message when attempting to Reserve seats when Sold Out
* Bug Fix: Tickets Reserved Message class is stageshow-error (changed to stageshow-ok)
* Bug Fix: Booking Fee still shown after last row removed from trolley

= 2.4.3 (02/01/2014) =
* Bug Fix: Save Settings Error: Undefined constant TABLEENTRY_DATETIME (since 2.4.2)

= 2.4.2 (30/12/2013) =
* Aborts Activation if another StageShow variant is already activated
* Added Date/Time Picker to Performances Editor
* Added JS for Seat Selector (only used by StageShowGold)
* Modified styles for Sales Editor "View Email" button

= 2.4.1 (16/12/2013) =
* Bug Fix: Undefined Field 'transactionfee' when checking out Reservations
* Removed redundant Javascript

= 2.4 (15/12/2013) =
* Bug Fix: Verification Performance ID incorrect when performance select drop-down is not shown
* Blocks Verification if TxnID is blank
* Added view ticket to Sales Editor and Tools page (opens in separate window)
* Added "Number of Seats" option to Prices (StageShow+)
* Added Updates for WP3.8
* Booking Fee and Total rows realigned in Shopping Trolley output

= 2.3.4 (07/12/2013) =
* Bug Fix: Multiple Checkout output on pages with multiple shortcodes
* Bug Fix: Oversize Barcode in HTML Emails (StageShow+)
* Added "Seats Available" output
* Added &lt;div&gt; tag with border to HTML Emails to define page size (StageShow+)
* Added borderless HTML Email template (StageShow+)

= 2.3.3 (02/12/2013) =
* Bug Fix: id={ShowName} in shortcode not recognised
* Bug Fix: Checkbox settings may update when ReadOnly
* Bug Fix: FirstName and LastName values missing in TDT download
* Bug Fix: Incorrect PayPal URL in "Sandbox" mode
* Added Booking Fee (StageShow+)
* Added "Box Office Below Trolley" option (StageShow+)
* Added Plugin Website link to Box-Office output
* Relabelled "Fees" as "PayPal Fees"

= 2.3.2 (12/11/2013) =
* Bug Fix: Incorrect PluginURI blocks Plugin Upgrade ... server also patched to allow updates
* Bug Fix: T_PAAMAYIM_NEKUDOTAYIM expected error with PHP 5.2
* Added Performance selector to Transaction Validator
* Added custom Styles for Transaction Validator results
* Sample Sale TxnIds changed to 17 characters
* Added PayPal Simulator (for DEMO mode)
* Allocated Seating defaults to enabled (StageShowGold)
* Added HTTP Diagnostics to Plugin Updater (StageShowPlus/Gold)

= 2.3.1 (03/11/2013) =
* Bug Fix: Price Plans admin page generates Class not found error (StageShowGold)

= 2.3 (30/10/2013) =
* Bug Fix: Checkout Complete URL and Checkout Cancelled URL not passed to PayPal Checkout
* Bug Fix: <head> and <body> tags missing in email templates
* Bug Fix: Changed include to requires_once - Fix for "Zend Error" bug in PHP APC
* Bug Fix: Email Logo Image corrupts emails displayed by hotmail 
* Added styles for allocated seating (StageShowGold)
* First Release of StageShowGold - Includes Allocated Seating
* Added code for Demo Mode
* Added STAGESHOW_CAPABILITY_VIEWSETTINGS
* BARCODE_ defines can be set externally
* Checkboxes in mjslib_table display Yes/No when ReadOnly
* Tested with WP 3.7
* Added Seating Plans editor (StageShowGold)

= 2.2.5 (17/10/2013) =
* Bug Fix: DB error generating Box Office output  (since v2.2.4) (StageShow)
* Bug Fix: Tools-Export does not generate output (since v2.2.4)
* Bug Fix: stageshowplus_tdt_export.php missing in Distribution (since v2.2.4) (StageShow+)

= 2.2.4 (08/10/2013) =
* Bug Fix: Performance Expiry Date/Time does not track performance Date/Time Changes (since v2.1.5) (StageShow+)
* Bug Fix: Performance Expiry Date/Time includes seconds (StageShow+)
* StageShowPlus/StageShowGold Specific DB Fields moved to Version Specific Classes
* AddSample********* functions added

= 2.2.3 (06/10/2013) =
* Bug Fix: Test Email Destination not reported if not diverted
* Bug Fix: PayPal IPN fields not converted to UTF-8 (Special Characters not displayed/stored)
* Bug Fix: Email template not updated on upgrade from StageShow to StageShow+
* Bug Fix: Checkout errors not reported
* Bug Fix: Settings label not translated
* Bug Fix: StageShow+ Updates not detected on some servers
* Overview page Trolley Type output replaced by Plugin Type and Version
* Timezone reported on Overview page - with error notification if it is not set
* "Bcc EMails to WP Admin" setting renamed "Bcc EMails to Sales Email"

= 2.2.2 (16/09/2013) =
* Bug Fix: Sales not logged to Database
* Bug Fix: JS onchange for some SELECT and INPUT HTML elements has function name omitted
* Bug Fix: Templates not copied if destination file exists and is readonly
* Bug Fix: Summary Email generated when Checkout selected
* Purchaser name from PayPal split into FirstName and LastName

= 2.2.1 (15/09/2013) =
* Bug Fix: Upgrade from using "PayPal Shopping Cart" can leave uneditable blank Merchant ID
* Bug Fix: Items not added to Shopping Trolley with Version 2.2 distribution 

= 2.2 (07/09/2013) =
* Bug Fix: "Add" button not translated
* Bug Fix: DB error generating SaleSummary email when there are no sales
* Bug Fix: Offline Validator needs keyboard input when used with Barcode reader
* Bug Fix: Offline Validator download filename has incorrect file extension
* Support for PayPal Checkout removed
* StageShow styles loaded after theme style
* Added limited duplicate scan detection to Offline Validator
* Added translations to Offline Validator

= 2.1.6 (15/08/2013) =
* Bug Fix: Translations missing on Box Office and Shopping Trolley output
* Bug Fix: Purge Pending sales ignored daylight saving time
* Now checks WP_LANG_DIR for translation files in addition to plugin 'lang' directory

= 2.1.5 (04/08/2013) =
* Added "Contact Phone" to Sale Log details
* Updated for compatibility with WP 3.6 - depracated split() recoded
* Separated FirstName and LastName in DB and Export files
* Removed seconds from performance time display

= 2.1.4 (31/07/2013) =
* Bug Fix: Admin URLs with _wpnonce arg may have html encoded arg separator
* Added Search Sales facility to sales page

= 2.1.3 (12/07/2013) =
* Bug Fix: Styles did not format Shopping Trolley output on Box Office page
* Bug Fix: Invalid WP Date/Time format gives blank performance dates on Box Office page
* Confirm action on Delete or Set Completed Actions

= 2.1.2 (11/07/2013) =
* Bug Fix: Price Plans not checked for valid prices (StageShow+)
* Bug Fix: Bottom Bulk Action Apply button uses Selected Top Bulk Action when valid
* Zero prices permitted with Integrated Checkout

= 2.1.1 (05/07/2013) =
* Bug Fix: Checkout total is zero when currency symbol is enabled

= 2.1 (04/07/2013) =
* Bug Fix: Performance name not shown when Performance sales log has no sales
* Bug Fix: Shows Lists have inoperative pagination controls (sometimes)
* Bug Fix: Performance Lists have inoperative pagination controls (sometimes)
* Bug Fix: Sales Lists have inoperative pagination controls (sometimes)
* Bug Fix: Show name not shown when Show sales log has no sales
* Bug Fix: Bulk actions do not report error if nothing changed
* Bug Fix: Status message not shown for Activate/Deactivate Show action
* Bug Fix: Prices entires for unchanged show(s) blank after duplicate price ref error (StageShow+)
* Bug Fix: Default Performance Expires time does not track changes in performance time
* Bug Fix: Incorrect value for Sample Sales total paid values
* Added Reservations (StageShow+)
* EMail Template File renamed "Sale EMail Template" in settings
* Fuctions in one or both of PayPal mode and Reservation mode (StageShow+)
* Implemented "Visibility" setting for prices (StageShow+)
* Performance Expiry time made editable (StageShow+)
* Sales use "local time" for sale time/date
* Leading and trailing spaces removed from text settings entries
* Settings tabs renamed
* Paid/Due column added to Sales List
* Added Checkout Notes

= 2.0.6 (30/06/2013) =
* Bug Fix: Currency codes in text emails changed to three letter currency code
* Bug Fix: Surplus /table tag in empty sales list

= 2.0.5 (06/06/2013) =
* Bug Fix: Undefined stockPrice in Sale Editor fixed
* Bug Fix: Inconsistant visibility of Merchant ID, and API ***** fields in PayPal Settings
* Bug Fix: Edit box for TxnID in Auto-Update Settings too small
* Bug Fix: Plugin version number check inconsistent
* Bug Fix: Daylight saving time handling inconsistent
* Bug Fix: Box Office shows inactive/expired shows
* Deleted Shows, Performances and Prices only removed from DB when not referenced by Sales
* Integrated Checkout syles rationised
* Added salePaid and saleFee to Sales Summary export (StageShow+)
* Flush Sales removed from Tools Menu

= 2.0.4 (21/05/2013) =
* Bug Fix: Fix for WP wp_mail() bug ... no HTML email content for Outlook/iPhone

= 2.0.3 (05/04/2013) =
* Bug Fix: PayPal Checkout failures - Cannot process transaction error

= 2.0.2 =
* Bug Fix: TDT Export MIME type changed to text/tab-separated-values
* Bug Fix: (StageShow+) Plugin Version check gave undefined error if Internet unavailable
* Added Logging of PayPal Transaction Fees
* Ticket Price logged with each sale
* Export File Field Names now defined by translatable table
* Added OFX format export (StageShow+)
* Checkout Timeout added to settings
* Box Office columns widths set by style sheet (stageshow.css)
* Implemented Checkout Complete and Checkout Cancelled URLs in settings
* IPN "Callback" URL changed to stageshow_ipn_callback.php (was stageshow_NotifyURL.php)

= 2.0.1 (04/03/2013) =
* Bug Fix: Integrated Checkout fails for performances with unlimited ticket quantities

= 2.0 (20/02/2013) =
* Bug Fix: Templates not always copied to uploads folder
* Implements Integrated Checkout
* Added Checkout Type and MerchantID to Settings
* Corrected spelling of "perfarmance" on prices admin page
* Blocks edit of PayPal settings once a Show has been defined
* Added Currency Formatting
* Admin Javascript moved to stageshow_admin.js
* Added "Sold Out" message on BoxOffice output when all tickets sold 
* Added missing &lt;div&gt; tag to Sales Admin page
* Added Users Guide (in PDF format)

= 1.2.1 (18/12/2012) =
* Bug Fix: Export Data gives 404 error - stageshow_export.php file has incorrect case
* New prices are added for a specified performance which cannot then be edited

= 1.2 (04/12/2012) =
* Bug Fix: StageShow_Validate capability not deleted on uninstall
* Admin Pages code optimised
* Separators added between tabs on settings page(s)
* Added support for translations
* Added stageshow.pot file to distribution
* Added PayPal login error code reports
* Moved "EMail Template File" option to StageShow settings
* Sample Sales are always for only one show
* New Shows are always initialised as ACTIVE
* Added Settings Page URL param to select tab

= 1.1.7 (23/10/2012) =
* Activate function explicitly called on version update
* Email and Images Templates moved to uploads/{pluginID}/****** folders
* Test Send Email added to Tools admin page
* Admin Pages - Redundant &lt;form&gt; tag action parameters removed
* Admin Pages CSS - stageshow-settings-**** classes changed to mjslib-settings-****

= 1.1.6 (07/09/2012) =
* Bug Fix: Custom templates and images are deleted on plugin update
* Bug Fix: Custom roles not deleted on plugin uninstall
* Emails templates moved to uploads/stageshow/emails folder
* PayPal Logo and Header Images moved to uploads/stageshow/images folder
* PayPal Logo and Header Images now selected using drop-down box on settings page
* Deletes uploads/stageshow folder when plugin is deleted
* Settings page default tab changes once PayPal settings are added
	
= 1.1.5 (23/08/2012) =
* Bug Fix: Version update code does not check database version
* Bug Fix: Ticket Types can be omitted from Sale Summary Export
* Ticket types for some samples changed to "All"
* Added stageshow-boxoffice-add class to Box Office Add buttons
* Renamed 'StageShow+ Auto Update Settings' as Auto Update Settings'
* Sales quantities on overview page are now links to show and performance sales pages 
* Sample performance dates altered to make shows visible on setup
* Settings sections displayed as tabs on admin page
* Add New Performance status messages improved

= 1.1.4 (18/07/2012) =
* Added "Sales Summary" option to Export Data on Tools admin page
* Added Offline Sales Verififier
* Bug Fix: Tools Admin page - Footer appears in middle of page
* Bug Fix: Settings Export permitted for users without 'StageShow_Admin' capability
* Bug Fix: Performances or Prices with associated sales can still be deleted
* Add New Performance uses local date/time as performance date/time

= 1.1.3 (05/07/2012) =
* Bug Fix: (Benign) Overview page generates "Undefined offset" error for Shows without any Performances
* Total Sales values added to Overview Page
* Effenciency improved on Overview page database queries 
* Settings page layout improved
* StageShow admin email defaults to WP admin email
* StageShow Organisation ID defaults to WP Site Name
* EMail template paths default to stageshow/templates folder

= 1.1.2 (30/06/2012) =
* Bug Fix: IPN Fails when sale has quotes in any field (including the Show name)
* ReadMe changelog changed to reverse chronological order

= 1.1.1 (24/06/2012) =
* Added Performance Sales Summaries to Overview Page
* Added "booking confirmed" message to email template
* Missing Configuration messages now include links to the relevant admin page
* Sales page accessible (non-edit) to users with StageShow_Validate capability
 
= 1.1.0 (15/06/2012) =
* Bug Fix: Activate/Deactivate Show not working ...
* Compatible with WP 3.4

= 1.0.7 (13/06/2012) =
* Bug Fix: Sale Ticket Type was always recorded as the same type
* Renamed "Presets" as "Price Plans"
* Coding of Admin Pages restructured
* Error notifications on Admin Pages improved
* StageShow specific styles now defined in stageshow-admin.css
* File Names rationised
* Bug Fix: "Validate" Capability implemented

= 1.0.6 (24/04/2012) =
* Implemented "Hidden Rows" for Details Fields in Sales Screen
* Bug Fix: Inventory Control not working in v1.0.5 - Fixed (includes PayPal buttons update on activation)
* Added check for zero ticket prices - new price entries initialised to 1.00

= 1.0.5 (20/04/2012) =
* Bug Fix: "Add New Show" not displayed on Shows Admin Page (Add New Price ahown instead)
	
= 1.0.4 (14/04/2012) =
* Added Currency Symbols to currency options
* Added option to output currency symbol in Box Office
* Cosmetic: Added separator line between admin screen entries
* Bug Fix: HTML Select element options on some admin screens not retrieved
* Bug Fix: Undefined variable error generated on Performances update error
* Class of BoxOffice output HTML elements changed from boxoffice-**** to stageshow-boxoffice-****

= 1.0.3 (02/04/2012) =
* Bug Fix: Input Edit box size value fixed
* Bug Fix: Box Office shortcode output was always at top of page.
* Max Ticket Count added to settings
* Items per page added to settings
* Negative/Non-Numeric max number of seats converted to unlimited (displayed as infinite)
* New Performance defaults to unlimited number of seats
* Number of seats available can be set to unlimited using negative value (default value)

= 1.0.1 (12/03/2012) =
* Bug Fix: include folder missing from archive in 1.0.0

= 1.0.0 =
* Bug Fix: Call to wp_enqueue_style() updated for compatibility with WP 3.3
* AutoComplete disabled on Settings page
* PayPal Account EMail address added to settings (PayPal may not report it correctly)
* Shortcodes Summary added to Overview page
* Added support of "User Roles" to admin pages
* Added Ticket Sale Reference Validation to "Tools" Admin page
* Added Pagination to all admin screen lists

= 0.9.5 =
* Dual PayPal Credentials merged - Live or Test (Sandbox) mode must be set before adding performances
* StageShow-Plus renamed StageShow+

= 0.9.4 =
* Added StageShow specific capabilities (StageShow_Sales, StageShow_Admin and StageShow_Settings) to WP roles 
* Added Facility to manually add a sale
* Added Facility to activate/deactivate selected performances
* Box Office page elements formatted by stageshow.css stylesheet
* Duplicate dates on BoxOffice output supressed (STAGESHOW_BOXOFFICE_ALLDATES overrides)

= 0.9.3.1 =
* Fixed "Function name must be a string" error when changing Admin EMail ( stageshow_manage_settings.php)

= 0.9.3 =
* Fixed Distribution Error in ver0.9.1
* Added Style Sheet (stageshow.css)
* Added styles to BoxOffice page and updated default style

= 0.9.2 =
* Bug Fix: Malformed &lt;form&gt; tag on BoxOffice page fixed
* BoxOffice time/date format now uses WordPress settings
* (Note: Private release)

= 0.9.1 =
* Added Pagination to Sales Summary
* Added Uninstall
* Added Activate/Deactivate options for shows and performances

= 0.9 =
* First public release

== Upgrade_Notice ==

= 2.2.4 =
* Performances with dates changed by StageShow versions 2.1.5 to 2.2.3 may have incorrect performance Expiry Date/Time  (StageShow+)

= 2.2 =
* Support for PayPal Checkout removed - MerchantID on PayPal Settings tab must be set

= 1.0.7 =
* Bug Fix: Sales were always recorded as the same Ticket Type

= 1.0.0 =
* Earlier versions not compatible with WP 3.3 - Style sheets may not load

= 0.9 =
* First public release

