

function stageshow_OnSettingsLoad()
{
	var selectedTabId = stageshow_GetURLParam('tab');
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
	
	stageshow_SelectTab(selectedTabId);
	
	var selectedItemId = stageshow_GetURLParam('focus');
	if (selectedItemId != '')
	{		
		var focusElem;
		
		// Get the header 'Tab' Element					
		focusElem = document.getElementById(selectedItemId);
		focusElem.focus();
	}
}

function stageshow_ClickHeader(obj)
{
	stageshow_SelectTab(obj.id);
}

function stageshow_GetURLParam(paramID)
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

function stageshow_SelectTab(selectedTabID)
{
	for (index = 0; index < tabIdsList.length-1; index++)
	{
		tabId = tabIdsList[index];
		stageshow_ShowOrHideTab(tabId, selectedTabID);
	}
}

function stageshow_ShowOrHideTab(tabID, selectedTabID)
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

function stageshow_OnClickSeatingID(obj)
{
	var selectId = obj.id;
	var selectedIndex = obj.selectedIndex;
	if (typeof(selectedIndex) == 'undefined')
	{
		selectedIndex = obj.value;
	}
	var elemId = selectId.replace('perfSeatingID', '');
	var showMaxSeats = (selectedIndex == 0);
	var seatsObjId = 'perfSeats' + elemId;
	var seatsObj = document.getElementById(seatsObjId);
	if (showMaxSeats)
	{
		seatsObj.style.display = '';
	}
	else
	{
		seatsObj.style.display = 'none';
	}
}

function stageshow_OnTicketButtonClick(showEMailURL)
{
	var saleSelectObj = document.getElementById('TestSaleID');
	saleId = saleSelectObj.value;
	stageshow_OpenTicketView(saleId, showEMailURL);
}

function stageshow_OpenTicketView(saleId, showEMailURL)
{
	var wpnonceObj = document.getElementById('ShowEMailNOnce');
	
	saleParam = 'id=' + saleId;
	wpnonceParam = '_wpnonce=' + wpnonceObj.value;
	url = showEMailURL + '?' + saleParam + '&' + wpnonceParam;
	
	window.open(url);
}

function stageshow_TestClickSeat(obj)
{
	var seatId, hiddenSeatsElem, hiddenZonesElem;
	
	seatIdParts = obj.id.split("-");
	seatId = seatIdParts[seatIdParts.length-1];
		
	seatsElem = document.getElementById("stageshow-boxoffice-layout-seatdef");

	var className = obj.className;
	var classPosn = className.search('stageshow-boxoffice-seat-requested');
	
	/* Remove existing class specifier */
	className  = className.replace('stageshow-boxoffice-seat-available', ' ');
	className  = className.replace('stageshow-boxoffice-seat-requested', ' ');
	className  = className.replace('stageshow-boxoffice-seat-unknown', ' ');
	className  = className.replace('  ', ' ');
	
	seatName = 'Row ' + seatId.replace('_', ' Seat ');
	
	if (classPosn < 0)
	{
		className = 'stageshow-boxoffice-seat-requested ' + className;
		seatsElem.innerHTML = seatName + ' Changed to Booked';		
	}
	else
	{
		className = 'stageshow-boxoffice-seat-available ' + className;
		seatsElem.innerHTML = seatName + ' Changed to Available';		
	}
	obj.className = className;
	
}

