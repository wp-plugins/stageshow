
function stageshowlib_OnSettingsLoad()
{
	/* Get Disabled GatewaysList */
	
	var selectedTabId = jQuery("#lastTabId").val();
	if (selectedTabId == '')
	{
		selectedTabId = stageshowlib_GetURLParam('tab');
		if (selectedTabId != '')
		{		
			selectedTabId = selectedTabId.replace(/_/g,'-');
			selectedTabId = selectedTabId.toLowerCase()
			selectedTabId = selectedTabId + '-tab';
		}
	}
	
	if (selectedTabId == '')
	{
		selectedTabId = tabIdsList[defaultTabIndex];
	}
	
	stageshowlib_SelectTab(selectedTabId);
	
	var selectedItemId = stageshowlib_GetURLParam('focus');
	if (selectedItemId != '')
	{		
		var focusElem;
		
		// Get the header 'Tab' Element					
		focusElem = document.getElementById(selectedItemId);
		focusElem.focus();
	}
}

function stageshowlib_ClickGateway(obj)
{
	stageshowlib_SelectTab('gateway-settings-tab');
}

function stageshowlib_ClickHeader(obj)
{
	stageshowlib_SelectTab(obj.id);
}

function stageshowlib_GetURLParam(paramID)
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

function stageshowlib_SelectTab(selectedTabID)
{
	for (index = 0; index < tabIdsList.length-1; index++)
	{
		tabId = tabIdsList[index];
		stageshowlib_ShowOrHideTab(tabId, selectedTabID);
	}
	
	lastTabElem = document.getElementById('lastTabId');
	if (lastTabElem)
	{
		lastTabElem.value = selectedTabID;
	}
	
}

function stageshowlib_HideElement(elemID)
{
	thisElem = document.getElementById(elemID);
	thisElem.style.display = 'none';	
}

function stageshowlib_ShowOrHideTab(tabID, selectedTabID)
{
	var headerElem, tabElem, pageElem, tabWidth, rowstyle;
	
	selectedGatewayTag = '';
	if (tabID == selectedTabID)
	{
		// Show the matching settings rows
		rowstyle = '';
		
		gatewayElem = document.getElementById('GatewaySelected');
		if (gatewayElem)
		{
			var gatewayId = gatewayElem.value;
			gatewayParts = gatewayId.split('_');
			gatewayBase = gatewayParts[0];
			selectedGatewayTag = '-tab-'+gatewayBase+'-row';
		}
	}
	else
	{
		// Hide the matching settings rows
		rowstyle = 'none';
	}
	
	
	// Get the header 'Tab' Element					
	tabElem = document.getElementById(tabID);
	
	// Get the Body Element					
	pageElem = document.getElementById('recordoptions');

	// Get all <tr> entries for this TabID and hide/show them as required
	var tabElements = pageElem.getElementsByTagName("tr");
	for(var i = 0; i < tabElements.length; i++) 
	{
		rowElem = tabElements[i];
		id = rowElem.id;
		
   		if (id.indexOf('-settings-tab') > 0) 
    	{
		    if (id.indexOf(tabID) == 0) 
		    {
		    	if ( (id.indexOf('-tab-') > 0) && (id.indexOf('-tab-row') < 0) )
		    	{
		    		if (selectedGatewayTag != '')
			    	{
			    		/* Must be a Gateway specific entry */
				    	if (id.indexOf(selectedGatewayTag) < 0)
				    	{
							rowElem.style.display = 'none';		
							continue;		
						}		
					}			
				}
				
				// Show or Hide the settings row
				rowElem.style.display = rowstyle;				
			}
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

function stageshowlib_OnTicketButtonClick(showEMailURL)
{
	var saleSelectObj = document.getElementById('TestSaleID');
	saleId = saleSelectObj.value;
	stageshowlib_OpenTicketView(saleId, showEMailURL);
}

function stageshowlib_OpenTicketView(saleId, showEMailURL)
{
	var wpnonceObj = document.getElementById('ShowEMailNOnce');
	
	saleParam = 'id=' + saleId;
	wpnonceParam = '_wpnonce=' + wpnonceObj.value;
	url = showEMailURL + '?' + saleParam + '&' + wpnonceParam;
	
	window.open(url);
}

function stageshowlib_serialiseText(text)	
{
	text = encodeURIComponent(text);
	var serialiseText = 's:'+text.length+':"'+text+'";';
	return serialiseText;
}
	
function stageshowlib_serialiseArrayElem(key, value)	
{
	var serialiseText = stageshowlib_serialiseText(key) + stageshowlib_serialiseText(value);
	return serialiseText;
}
	
function stageshowlib_serialisePost(obj, classId)	
{	
	var formElem = obj.form;
	
	var elemsList = jQuery(formElem).find("." + classId);
	var serializedString = "a:"+elemsList.length+":{";
	for (i=0; i<elemsList.length; i++)
	{
		elemId = elemsList[i].id;
		elemVal = elemsList[i].value;
		
		serializedString += stageshowlib_serialiseArrayElem(elemId, elemVal);
	}
	
	serializedString += "}";
		
	var input = jQuery("<input>")
		.attr("type", "hidden")
		.attr("name", "stageshowlib_PostVars").val(serializedString);
               
	jQuery(formElem).append(jQuery(input));	
	
	return true;
}
