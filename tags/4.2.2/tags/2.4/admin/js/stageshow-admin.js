function onSalesInterfaceClick(obj)
{
	var selectedInterface = obj.id;
	
	SetSalesInterfaceControls(obj);
}

function InitialiseSalesInterfaceControls()
{
	trolleyTypeObj = document.getElementById('TrolleyType');
	SetSalesInterfaceControls(trolleyTypeObj);	
}

function SetSalesInterfaceControls(selectObj)
{	
	var isIntegratedCheckout = (selectObj.value == 'Integrated');
	
	/* Control visible for Integrated Checkout */
	ShowOrHideControl('PayPalMerchantID', isIntegratedCheckout);
	ShowOrHideControl('CheckoutTimeout',  isIntegratedCheckout);
	
	/* Control visible for PayPal Checkout */
	ShowOrHideControl('PayPalAPIUser',   !isIntegratedCheckout);
	ShowOrHideControl('PayPalAPIPwd',    !isIntegratedCheckout);
	ShowOrHideControl('PayPalAPISig',    !isIntegratedCheckout);
}

function ShowOrHideControl(elemID, elementVisible)
{
	rowElem = document.getElementById(elemID);
	if (rowElem == null) 
		return;
	
	rowElem = rowElem.parentNode;
	rowElem = rowElem.parentNode;
			
	if (elementVisible)
	{
		// Show the control
		rowElem.style.display = '';
	}
	else
	{
		// Hide the control
		rowElem.style.display = 'none';
	}	
}

function onSettingsLoad()
{
	var selectedTabId = GetURLParam('tab');
	if (selectedTabId != '')
	{		
		selectedTabId = selectedTabId.replace(/_/g,'-');
		selectedTabId = selectedTabId.toLowerCase()
		selectedTabId = selectedTabId + '-tab';
	}
	else
	{
		selectedTabId = tabIdsList[defaultTabIndex];
	}
	
	SelectTab(selectedTabId);
}

function clickHeader(obj)
{
	SelectTab(obj.id);
}

function clickCartInterface(obj)
{
}

function GetURLParam(paramID)
{
	var rtnVal = '';
	
	var Url = location.href;
	Url.match(/\?(.+)$/);
 	var Params = RegExp.$1;
 	
	Variables = Params.split ('&');
	for (i = 0; i < Variables.length; i++) 
	{
		Separ = Variables[i].split('=');
		if (Separ[0] == paramID)
		{
			rtnVal = Separ[1];
			break;
		}
	}
	
	return rtnVal;
}

function SelectTab(selectedTabID)
{
	for (index = 0; index < tabIdsList.length-1; index++)
	{
		tabId = tabIdsList[index];
		ShowOrHideTab(tabId, selectedTabID);
	}
	
	if (selectedTabID == 'paypal-settings-tab')
	{
		selectInterfaceElem = document.getElementById('TrolleyType');
		SetSalesInterfaceControls(selectInterfaceElem);
	}
}

function ShowOrHideTab(tabID, selectedTabID)
{
	var headerElem, tabElem, pageElem, tabWidth;
	
	// Get the header 'Tab' Element					
	tabElem = document.getElementById(tabID);
	
	// Get the Body Element					
	pageElem = document.getElementById('recordoptions');

	// Get all <tr> entries for this TabID and hide/show them as required
	for (i=1; i<100; i++)
	{
		// Get the Body Element	
		rowElemID = tabID +'-row' + i;				
		rowElem = document.getElementById(rowElemID);
		if (rowElem == null) 
			break;
			
		if (tabID == selectedTabID)
		{
			// Show the settings row
			rowElem.style.display = '';
		}
		else
		{
			// Hide the settings row
			rowElem.style.display = 'none';
		}
	}
	
	if (tabID == selectedTabID)
	{
		// Make the font weight normal and background Grey
		tabElem.style.fontWeight = 'bold';	
		tabElem.style.borderBottom = '0px red solid';
		//tabElem.style.backgroundColor = '#F9F9F9';
	}
	else
	{
		// Make the font weight normal and background Grey
		tabElem.style.fontWeight = 'normal';	
		tabElem.style.borderBottom = '1px black solid';		
		//tabElem.style.backgroundColor = '#F1F1F1';
	}	
}

function OnTicketButtonClick(showEMailURL)
{
	var saleSelectObj = document.getElementById('TestSaleID');
	saleId = saleSelectObj.value;
	OpenTicketView(saleId, showEMailURL);
}

function OpenTicketView(saleId, showEMailURL)
{
	var wpnonceObj = document.getElementById('ShowEMailNOnce');
	
	saleParam = 'id=' + saleId;
	wpnonceParam = '_wpnonce=' + wpnonceObj.value;
	url = showEMailURL + '?' + saleParam + '&' + wpnonceParam;
	
	window.open(url);
}

