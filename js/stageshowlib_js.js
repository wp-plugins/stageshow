var currencySymbol = '';

function StageShowLib_addWindowsLoadHandler(newHandler)
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

function StageShowLib_ParseCurrency(currencyText)
{
	currencySymbol = '';
	while (currencyText.length >= 1) 
	{
		nextChar = currencyText[0];
		if (!isNaN(parseInt(nextChar)))
			break;
			
		if (nextChar == '.')
			break;
			
		currencySymbol = currencySymbol + nextChar;
		currencyText = currencyText.substr(1, currencyText.length);
	}
	
	return parseFloat(currencyText);
}

function StageShowLib_OnChangeTrolleyTotal(obj)
{
	var pluginId = stageshowlib_pluginId;
	var donationObj = document.getElementById('saleDonation');
	var saleDonation = 0;
	if (donationObj != null)
	{
		var saleDonation = StageShowLib_ParseCurrency(donationObj.value);
		if (isNaN(saleDonation))
		{
			saleDonation = 0;
		}
		else
		{
			saleDonation = Math.abs(saleDonation);
		}		
	}

	var postValue = 0;
	var postTicketsObj = document.getElementById('salePostTickets');
	if (postTicketsObj != null)
	{
		var salePostageRowObj = document.getElementById(pluginId + '-trolley-postagerow');
		if (postTicketsObj.checked)
		{
			var salePostageObj = document.getElementById('salePostage');
			postValue = StageShowLib_ParseCurrency(salePostageObj.value);
			
			salePostageRowObj.style.display = '';
		}
		else
		{			
			salePostageRowObj.style.display = 'none';
		}
	}

	var subTotalObj = document.getElementById("saleTrolleyTotal");
	var finalTotalObj = document.getElementById(pluginId + '-trolley-totalval');
	var subTotal = subTotalObj.value;
	
	var newTotalVal = StageShowLib_ParseCurrency(subTotal);
	newTotalVal += saleDonation;
	newTotalVal += postValue;
	newTotalVal += 0.00001; /* To force rounding error ... then it is corrected below */
		 	
	var newTotal = newTotalVal.toString();

	var origDps = StageShowLib_NumberOfDps(subTotal);
	var newDps = StageShowLib_NumberOfDps(newTotal);
	while (newDps < origDps)
	{
		if (newDps == 0) newTotal += '.';
		newTotal += '0';
		newDps++;
	}

	if (newDps > origDps)
	{
		/* Limit the number of decimal points */
		newTotal = newTotal.substr(0, newTotal.length + origDps - newDps);
	}
	
	finalTotalObj.innerHTML = currencySymbol + newTotal;
}

function StageShowLib_NumberOfDps(price)
{
	var priceFormat, dpLen;
	var dpPosn = price.indexOf('.');
	if (dpPosn < 0)
	{
		dpLen = 0;
	}
	else
	{
		dpLen = price.length-dpPosn-1;
	}
	
	return dpLen;
}

function StageShowLib_HideElement(obj)
{
	// Get the header 'Tab' Element					
	tabElem = document.getElementById(obj.id);
	
	// Hide the settings row
	tabElem.style.display = 'none';
}

function StageShowLib_replaceAll(find, replace, str) 
{
	return str.replace(new RegExp(find, 'g'), replace);
}

function StageShowLib_SetBusy(newState, elemClassId) 
{
	if (newState)
	{
		jQuery("body").css("cursor", "progress");		
		StageShowLib_EnableControls(elemClassId, false);
	}
	else
	{
		StageShowLib_EnableControls(elemClassId, true);
		jQuery("body").css("cursor", "default");		
	}
}

function StageShowLib_EnableControls(classId, state)
{
	var classSpec = "."+classId;
	var buttonElemsList = jQuery(classSpec);
	jQuery.each(buttonElemsList,
		function(i, listObj) 
		{
			var uiElemSpec = "#" + listObj.name;
			var uiElem = jQuery(uiElemSpec);
			
			if (state)
			{
				uiElem.prop("disabled", false);			
				uiElem.css("cursor", "default");				
			}
			else
			{
				uiElem.prop("disabled", true);			
				uiElem.css("cursor", "progress");				
			}
				
	    	return true;
		}
	);
	
	return state;		
}

function StageShowLib_SubmitOnReturnKey(obj, event)
{
	if (event.keyCode!=13)
	{
		return true;
	}
	
	/* Find the objects parent form */
	parentForm = jQuery(obj).closest("form");
	parentForm.submit();
	return true;
}

function StageShowLib_CheckNumericOnly(obj, event, maxval, minval, dp)
{
	var newValueText = obj.value + event.key;
	var newValue;
	
	if (dp)
	{
		newValue = parseFloat(newValueText);
	}
	else
	{
		newValue = parseInt(newValueText);
	}

	if ((maxval != 'U') && (newValue > maxval))
	{
		obj.value = maxval;
		event.preventDefault();
		return false;
	}
	if ((minval != 'U') && (newValue < minval))
	{
		obj.value = minval;
		event.preventDefault();
		return false;
	}
	
	return true;
}

function StageShowLib_OnChangeNumericOnly(obj, event, maxval, minval, dp)
{
	if (obj.value == '') obj.value = minval;
	
	return StageShowLib_CheckNumericOnly(obj, event, maxval, minval, dp);
}

function StageShowLib_OnKeypressNumericOnly(obj, event, maxval, minval, dp)
{
	if (event.altKey || event.ctrlKey)
	{
		return true;
	}
	
	if (event.keyCode == 13)
	{
		event.preventDefault();
		return false;
	}
	
	if ((event.keyCode == 0) && (event.charCode > 32))
	{
		if (dp && (event.charCode == 46))
		{
			return StageShowLib_CheckNumericOnly(obj, event, maxval, minval, dp);
		}
		else if ((event.charCode < 48) || (event.charCode > 57))
		{
			event.preventDefault();
			return false;
		}
		else
		{
			/* return StageShowLib_CheckNumericOnly(obj, event, maxval, minval, dp); */
			return true;
		}
	}
	
	return true;
}

function StageShowLib_JQuery_ScrollToAnchor(inst)
{
	if ( (typeof stageshowlib_pageAnchor == 'undefined') 
	  || (typeof stageshowlib_pageAnchor[inst] != 'string') )
		return;
	
	var anchorId = "#"+stageshowlib_pageAnchor[inst];	
	if (jQuery(anchorId).length == 0)
		return;

    jQuery('html, body').animate({
        scrollTop: jQuery(anchorId).offset().top - anchorOffset
    }, anchorDuration);					
	
}

function StageShowLib_JQuery_OnClickTrolleyButton(obj, inst)
{
	var pluginId = stageshowlib_pluginId;
	var scIndex = inst;
	
	/* Set Cursor to Busy and Disable All UI Buttons */
	StageShowLib_SetBusy(true, pluginId + "-trolley-ui");
			
	var postvars = {
		jquery: "true"
	};
	postvars.count = inst;
	postvars.timeout = 30000;
	postvars.cache = false;
	postvars.path = window.location.pathname + window.location.search;
	
	postvars = StageShowLib_JQuery_PostVars(postvars);

	var buttonId = obj.id;	
	postvars[buttonId] = "submit";
	var qty = 0;
	var nameParts = buttonId.split("_");
	if (nameParts[0] == "AddItemButton")
	{
		var qtyId = "quantity_" + nameParts[1];
		qty = document.getElementById(qtyId).value;
		postvars[qtyId] = qty;
	}
	
	ourAtts = stageshowlib_attStrings[scIndex-1];
	ourAtts = ourAtts.split(",");
	for (var attId=0; attId<ourAtts.length; attId++) 
	{
		var thisAtt = ourAtts[attId].split("=");
		var key = thisAtt[0];
		var value = thisAtt[1];
		
		postvars[key] = value;
	}

	/* Get New HTML from Server */
    jQuery.post(jQueryURL, postvars,
	    function(data, status)
	    {
			trolleyTargetElem = jQuery("#" + pluginId + "-trolley-trolley-std");
			
	    	if ((status != 'success') || (data.length == 0))
	    	{
				StageShowLib_SetBusy(false, pluginId + "-trolley-ui");
				
	    		/* Fall back to HTML Post Method */
				return true;
			}

			/* Apply translations to any message */
			for (var index=0; index<tl8_srch.length; index++)
			{
				var srchFor = tl8_srch[index];
				var repWith = tl8_repl[index];
				data = StageShowLib_replaceAll(srchFor, repWith, data);
			}

			targetElemID = "#" + pluginId + "-trolley-container" + inst;
			divElem = jQuery(targetElemID);
			divElem.html(data);
	
			/* Copy New Trolley HTML */
			trolleyUpdateElem = jQuery("#" + pluginId + "-trolley-trolley-jquery");
			
			/* Get updated trolley (which is not visible) */
			trolleyHTML = trolleyUpdateElem[0].innerHTML;

			/* Copy New Trolley HTML */
			trolleyTargetElem.html(trolleyHTML);
			
			/* Now delete the downloaded HTML */
			trolleyUpdateElem.remove();
			
			if (qty > 0)
			{
				StageShowLib_JQuery_ScrollToAnchor(inst);				
			}
							
			/* Set Cursor to Normal and Enable All UI Buttons */
			StageShowLib_SetBusy(false, pluginId + "-trolley-ui");
	    }
    );
    
    return false;
}
				
