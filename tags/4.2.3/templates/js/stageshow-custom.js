
function stageshow_AddEntryToCheckout(customHTMLObjId)
{	
	/* Append custom HTML element value to a Standard Checkout Value */
	/* Note:
		Custom HTML can be added to the checkout by entering it as the "Checkout Note"
		setting on the "Advanced" tab of the StageShow Settings admin page.
		
		For example HTML to add a drop down selection box is as follows:
		
			<select id=custom_checkout_element>
			<option>Collect Tickets from Office</option>
			<option>Collect Tickets at Door</option>
			</select>
	*/
	var saleCustomValuesObj = document.getElementById('saleCustomValues');	
	var customHTMLObj = document.getElementById(customHTMLObjId);	
	if ( (saleCustomValuesObj == null) || (customHTMLObj == null) )
	{
		return;
	}
	
	var saleCustomValue = saleCustomValuesObj.value;
	if (saleCustomValue.length > 0)
	{
		/* Add a line separator for each custom HTML Element */
		saleCustomValue += "\n";
	}
	else
	{
		var saleNoteToSellerObj = document.getElementById('saleNoteToSeller');
		if (saleNoteToSellerObj != null)
		{
			/* "Note to Seller" Element is present */
			if (saleNoteToSellerObj.length > 0)
			{
				/* User has entered a value for the "Note to Seller" ; add a line separator after it! */
				saleCustomValue += "\n";
			}
		}		
	}
	saleCustomValue += customHTMLObj.value;
	
	/* Store this as the value of the hidden form element */
	saleCustomValuesObj.value = saleCustomValue;
}

function stageshow_AddCustomHTMLValues()
{
	/* 
		Add values from custom HTML Elements here 
		Note: For multiple custom HTML Elements call stageshow_AddEntryToCheckout for each element
	*/
	stageshow_AddEntryToCheckout('custom_checkout_element');
}

function stageshow_OnClickAdd(obj)
{
	return true;
}

function stageshow_OnClickSelectseats(obj)
{
	return true;
}

function stageshowCustom_OnClickSeatsselected(obj)
{	
	return true;
}

function stageshowCustom_OnClickReserve(obj)
{
	stageshow_AddCustomHTMLValues();
	return true;
}

function stageshowCustom_OnClickCheckout(obj)
{
	stageshow_AddCustomHTMLValues();
	return true;
}
