
function addWindowsLoadHandler(newHandler)
{
	var oldonload = window.onload;
	if (typeof window.onload != "function") 
	{
		window.onload = newHandler;
	} 
	else 
	{
		window.onload = function() 
		{
          oldonload();
          newHandler();
        }
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

function clickSeat(obj, zoneID, zoneDef)
{
	var seatId, hiddenSeatsElem, hiddenZonesElem, hiddenDefsElem;
	
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
		seatsElem.value = seatName + ' Changed to Booked';		
	}
	else
	{
		className = 'stageshow-boxoffice-seat-available ' + className;
		seatsElem.value = seatName + ' Changed to Available';		
	}
	obj.className = className;
	
}

function ShowDateTimeCalendar(pSender, pMode)
{
	pFormat = 'yyyyMMdd';
	pScroller = 'arrow';
	pShowTime = true; 
	pTimeMode = '24';
	pShowSeconds = false; 
	pEnableDateMode = 'future';
	
	UseTimeDropdown = false;
	TimeDropdownIncrements = 5;
	WeekChar = 3;
		
	if (pMode == null) 
	{
		pMode = 'DateSeconds';
	}
	
	pMode = pMode.toLowerCase();	
	switch(pMode)
	{
		case 'date':
			break;
		
		case 'dateseconds':
			pShowSeconds = true; 
			UseTimeDropdown = true;
			TimeDropdownIncrements = 1;
			break;
		
		default:
		case 'datetime':
			UseTimeDropdown = true;
			break;
	}

	return NewCssCal(pSender, pFormat, pScroller, pShowTime, pTimeMode, pShowSeconds, pEnableDateMode);
}

