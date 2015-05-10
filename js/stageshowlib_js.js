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
		var salePostageRowObj = document.getElementById('stageshow-trolley-postagerow');
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
	var finalTotalObj = document.getElementById("stageshow-trolley-totalval");
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