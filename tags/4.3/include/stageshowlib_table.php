<?php
/* 
Description: Code for Table Management Class
 
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

if (!class_exists('StageShowLibTableClass')) 
{
	if (!defined('STAGESHOWLIB_EVENTS_PER_PAGE'))
		define('STAGESHOWLIB_EVENTS_PER_PAGE', 20);
	
	class StageShowLibTableClass // Define class
	{
		const HEADERPOSN_TOP = 1;
		const HEADERPOSN_BOTTOM = 2;
		const HEADERPOSN_BOTH = 3;

		const TABLETYPE_HTML = 'html';
		const TABLETYPE_RTF = 'RTF';
		const TABLETYPE_TEXT = 'text';

		const TABLEPARAM_LABEL = 'Label';
		const TABLEPARAM_TAB = 'Tab';
		const TABLEPARAM_ID = 'Id';
		const TABLEPARAM_TYPE = 'Type';
		const TABLEPARAM_ITEMS = 'Items';
		const TABLEPARAM_TEXT = 'Text';
		const TABLEPARAM_LEN = 'Len';
		const TABLEPARAM_SIZE = 'Size';
		const TABLEPARAM_LINK = 'Link';
		const TABLEPARAM_DEFAULT = 'Default';
		const TABLEPARAM_NEXTINLINE = 'Next-Inline';
		const TABLEPARAM_ONCHANGE = 'OnChange';
		const TABLEPARAM_READONLY = 'ReadOnly';
		const TABLEPARAM_NOTFORDEMO = 'NotForDemo';
		
		const TABLEPARAM_NAME = 'Name';

		const TABLEPARAM_DIR = 'Dir';
		const TABLEPARAM_EXTN = 'Extn';
		const TABLEPARAM_FUNC = 'Func';
		const TABLEPARAM_ROWS = 'Rows';
		const TABLEPARAM_COLS = 'Cols';
		const TABLEPARAM_DECODE = 'Decode';
		const TABLEPARAM_CANEDIT = 'CanEdit';	
		const TABLEPARAM_ADDEMPTY = 'AddEmpty';
		const TABLEPARAM_BLOCKBLANK = 'BlockBlank';
		const TABLEPARAM_BEFORE = 'Before';
		const TABLEPARAM_AFTER = 'After';
		
		const TABLEENTRY_ARRAY = 'array';
		const TABLEENTRY_BUTTON = 'button';
		const TABLEENTRY_CHECKBOX = 'checkbox';
		const TABLEENTRY_FUNCTION = 'function';
		const TABLEENTRY_SELECT = 'select';
		const TABLEENTRY_TEXT = 'text';
		const TABLEENTRY_TEXTBOX = 'textbox';
		const TABLEENTRY_VIEW = 'view';
		const TABLEENTRY_READONLY = 'readonly';
		const TABLEENTRY_VALUE = 'value';
		const TABLEENTRY_COOKIE = 'cookie';
		
		const STAGESHOWLIB_EVENTS_UNPAGED = -1;
		
		var $tableContents = array();
		var $rowAttr = array();
		var $tableName = '';
		var $tableTags;
		var $divClass;
		var $colId;
		var $rowsPerPage;
		var $columnHeadersId = '';
		var $HeadersPosn;
		
		var $colWidth = array();
		var $colAlign = array();
		var $colClass = array();
		var $columns;
		var $bulkActions;
		var $hideEmptyRows;
		var $spanEmptyCells;
		var $useTHTags;
		var $noAutoComplete;
		var $ignoreEmptyCells;
		
		var $detailsRowsDef;
		var $moreText;
		var $lessText;
		var $hiddenRowsButtonId;
		var $showOptionsID = 0;
		var $hiddenRowStyle  = 'style="display: none;"';
		var $visibleRowStyle = 'style="display: "';
		
		var $currRow;
		var $currCol;
		var $maxCol;
		var $rowActive = array();
		var $currentPage;
		var $totalRows;
		var $firstRowShown;
		var $maxRowsShown;
		
		var $rowCount = 0;
		
		var $scriptsOutput;
		var $moreScriptsOutput;
		
		var $tableType;
		
		var $dateTimeMode = 'dateseconds';
			
		function __construct($newTableType = self::TABLETYPE_HTML) //constructor
		{
			if (!isset($this->myDomain) || ($this->myDomain == ''))
				$this->myDomain = basename(dirname(dirname(__FILE__)));
			
			if (!isset($this->tabHeadClass))
				$this->tabHeadClass = "mjstab-tab-inactive";
				
			$this->tableType = $newTableType;
			switch ($this->tableType)
			{
				case self::TABLETYPE_HTML:
				case self::TABLETYPE_RTF:
				case self::TABLETYPE_TEXT:
					break;
					
				default:
					StageShowLibUtilsClass::ShowCallStack();
					echo "<strong><br>Invalid table type ($newTableType) ".get_class($this)." class<br></strong>\n";
					die;
					break;
			}
			
			$this->currRow = 1;
			$this->currCol = 0;
			$this->maxCol = 0;
			$this->HeaderCols = 0;
			$this->isTabbedOutput = false;
			$this->rowActive[$this->currRow] = false;
			$this->hideEmptyRows = true;
			$this->spanEmptyCells = false;
			$this->divClass = '';
			$this->colId = '';
			$this->divClass = '';
			$this->tableTags = '';
			$this->colId = '';
			$this->totalRows = 0;
			$this->rowsPerPage = 0;
			$this->useTHTags = false;
			$this->noAutoComplete = true;
			$this->ignoreEmptyCells = true;
			$this->scriptsOutput = false;
			$this->moreScriptsOutput = false;
			
			$this->detailsRowsDef = array_merge($this->GetDetailsRowsDefinition(), $this->GetDetailsRowsFooter());
				
			$this->moreText = __('Show', $this->myDomain);
			$this->lessText = __('Hide', $this->myDomain);
			
		}
		
		function SetRowsPerPage($rowsPerPage)
		{
			$this->rowsPerPage = $rowsPerPage;
			
			$this->currentPage = StageShowLibUtilsClass::GetArrayElement($_REQUEST, 'paged', 1);
			$this->currentPage = StageShowLibUtilsClass::GetArrayElement($_GET, 'paged', $this->currentPage);
			
			$this->firstRowShown = 1 + (($this->currentPage - 1) * $this->rowsPerPage);
		}

		function AddHiddenRows($result, $hiddenRowsID, $hiddenRows, $style)
		{
			$this->NewRow($result, 'id="'.$hiddenRowsID.'" '.$style.' class="hiddenRow"');
			$this->AddToTable($result, $hiddenRows);

			$this->maxCol = max($this->maxCol, $this->HeaderCols);
		}

		function GetMainRowsDefinition()
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetMainRowsDefinition');
		}
		
		function CanShowDetailsRow($result, $fieldName)
		{
			return true;
		}
		
		function GetDetailsRowsDefinition()
		{
			return array();
		}
		
		function GetDetailsRowsFooter()
		{
			return array();
		}
		
		function HasHiddenRows()
		{
			// No extended settings
			return (count($this->detailsRowsDef) > 0);
		}
		
		function ExtendedSettingsDBOpts()
		{
			return array();
		}
		
		function NewRow($result, $rowAttr = '')
		{
			// Increment Row ... but only if the current row has data
			if ($this->rowActive[$this->currRow]) 
				$this->currRow++;
				
			$this->currCol = 0;
			$this->rowActive[$this->currRow] = false;
			$this->rowAttr[$this->currRow] = $rowAttr;
		}

		function SetColWidths($newColWidths)
		{
			$this->colWidth = explode(',', ','.$newColWidths);
		}

		function SetColAlign($newColAlign)
		{			
			$this->colAlign = explode(',', ','.$newColAlign);
		}

		function SetColClass($newColClass)
		{			
			$this->colClass = explode(',', ','.$newColClass);
		}

		function SetListHeaders($headerId, $columns = null, $headerPosn = self::HEADERPOSN_BOTH)
		{
			// Save the settings, the headers are actually set by the EnableListHeaders function			
			$this->columnHeadersId = $headerId;

			if ($columns != null)
				$this->columns = $columns;	// Save for possible next call
				
			$this->HeadersPosn = $headerPosn;
			$this->HeaderCols = count($columns);
		}

		function EnableListHeaders()
		{
			if ($this->columnHeadersId === '') return;
			if ($this->columns === null) return;
			
			$columns = $this->columns;	// Use columns from last call
				
			if ($this->showDBIds)
			{
				// Add the ID column
				$columns = array_merge(array('eventID' => 'ID'), $columns); 
			}
				
			if (isset($this->bulkActions))
			{
				// Add the Checkbox column
				$columns = array_merge(array('eventCb' => '<input name="checkall" id="checkall" type="checkbox"  onClick="StageShowLib_updateCheckboxes(this)" />'), $columns); 
			}
			
			if ($this->HasHiddenRows() && ($this->hiddenRowsButtonId !== ''))
			{
				$columns = array_merge($columns, array('eventOptions' => $this->hiddenRowsButtonId)); 
			}
				
			// 
			$this->mergedColumns = $columns;
			
			//register_column_headers($this->columnHeadersId, $columns);	
		}
		
		function AddCheckBoxToTable($result, $inputName, $checked=false, $col=0, $checkedValue='checked', $label='', $newRow = false)
		{
			if (substr($inputName, -2) != '[]')
			{
				$inputName .= $this->GetRecordID($result).$this->GetDetailID($result);				
			}

			$checkedTag = $checked ? ' checked="yes"' : '';
			
		    $content = "$label<input name=\"$inputName\" id=\"$inputName\" type=\"checkbox\" value=\"$checkedValue\" $checkedTag/>";
			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddInputToTable($result, $inputName, $maxlength, $value, $col=0, $newRow = false, $extraParams = '')
		{
			$inputName .= $this->GetRecordID($result).$this->GetDetailID($result);				

			$params  = " name=$inputName";
			$params .= " id=$inputName";
			$params .= " maxlength=\"$maxlength\"";
			$params .= " value=\"$value\"";
			
			if ($extraParams != '')
			{
				$params .= ' '.$extraParams;
			}			
			
			if ($this->noAutoComplete)
				$params .= " autocomplete=\"off\""; 
			
			$content = "<input type=\"text\" $params />";
			
			$inputName = 'curr'.$inputName;
			
			$params  = " name=$inputName";
			$params .= " id=$inputName";
			$params .= " value=\"$value\"";
			
			$content .= "<input type=\"hidden\" $params />";
			
			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddDivToTable($result, $inputName, $value, $col=0, $newRow = false, $extraParams = '')
		{
			$inputName .= $this->GetRecordID($result).$this->GetDetailID($result);				

			$params  = " name=$inputName";
			$params .= " id=$inputName";
			
			if ($extraParams != '')
			{
				$params .= ' '.$extraParams;
			}			
			
			$content = "<div $params />$value</div>";
			
			$inputName = 'curr'.$inputName;
			
			$params  = " name=$inputName";
			$params .= " id=$inputName";
			$params .= " value=\"$value\"";
			
			$content .= "<input type=\"hidden\" $params />";
			
			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddSelectToTable($result, $columnDef, $options, $value='', $col=0, $newRow = false)
		{
			$inputName = $columnDef[self::TABLEPARAM_ID];
			$inputName .= $this->GetRecordID($result).$this->GetDetailID($result);
			
			$onChange = isset($columnDef[self::TABLEPARAM_ONCHANGE]) ? ' onchange="'.$columnDef[self::TABLEPARAM_ONCHANGE].'(this)" ' : '';
		
			$content = "<select name=$inputName id=$inputName $onChange>"."\n";
			foreach ($options as $index => $option)
			{
				$selected = ($index == $value) ? ' selected=""' : '';
				$content .= '<option value="'.$index.'"'.$selected.'>'.$option.'&nbsp;&nbsp;</option>'."\n";
			}
			$content .= "</select>"."\n";

			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddLinkToTable($result, $content, $link, $col=0, $newRow = false)
		{
			$content = '<a href="'.$link.'">'.$content.'</a>';
			$this->AddToTable($result, $content, $col, $newRow);
		}
	
		function AddShowOrHideButtonToTable($result, $tableId, $rowId, $content, $col=0, $newRow = false)
		{
			$this->OutputMoreButtonScript();
			
			$recordID = $this->GetRecordID($result);
			$moreName = 'more'.$recordID;
			
			$content = '<a id="'.$moreName.'" class="button-secondary" onClick="StageShowLib_HideOrShowRows(\''.$moreName.'\', \''.$rowId.'\')">'.$content.'</a>';
			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddToTable($result, $content, $col=0, $newRow = false)
		{
			if ($this->ignoreEmptyCells)
			{
			if (!isset($content) || (strlen($content) == 0)) return;
			}
			
			// Increment Row ... but only if the current row has data
			if ($newRow) 
			{				
				$this->NewRow($result);
			}
			
			if ($col <= 0) 
			{
				$col = ++$this->currCol;
			}
			else
			{
				$this->currCol = $col;
			}
				
			$this->tableContents[$this->currRow][$col] = $content;
			$this->rowActive[$this->currRow] = true;
			$this->maxCol = max($col, $this->maxCol);
		}
		
		function GetOnClickHandler()
		{
			return '';
		}
		
		function Output_ColHeader()
		{
			$addSeparator = false;
			$tabParam = ' class="'.$this->tabHeadClass.'"';
			
			$width = 100/count($this->mergedColumns);
						
			if ($this->isTabbedOutput)
			{
				$separatorWidth = 1;
				$width -= $separatorWidth;
				$tabParam .= " onclick=".$this->GetOnClickHandler();
				$tabParam .= ' width="'.$width.'%"';
				$tabParam .= ' style="border: 1px solid black;"';
				$separatorParam = ' class=mjstab-tab-gap width="'.$separatorWidth.'%"';
				$separatorParam .= ' style="border-bottom: 1px solid black; background: #f9f9f9;"';				
			}
			
			
			foreach ($this->mergedColumns as $id => $text)
			{
				if ($addSeparator)
				{
					echo "<th $separatorParam></th>\n";					
				}
					
				echo "<th id=$id $tabParam >$text</th>\n";
				
				$addSeparator = $this->isTabbedOutput;
			}
		}
		
		function ColumnHeaders($atTop = true)
		{
			if (!isset($this->columnHeadersId)) 
				return;

			if ($this->columnHeadersId === '') 
				return;

			if ($atTop)
			{
				if ($this->HeadersPosn === self::HEADERPOSN_BOTTOM) 
					return;
					
				echo "<thead>\n";
				echo "<tr>\n";
				$this->Output_ColHeader();
				echo "</tr>\n";
				echo "</thead>\n";
			}
			else
			{
				if ($this->HeadersPosn === self::HEADERPOSN_TOP) 
					return;
					
				echo "<tfoot>\n";
				echo "<tr>\n";
				$this->Output_ColHeader();
				echo "</tr>\n";
				echo "</tfoot>\n";
				echo "<tbody>\n";
			}
		}
		
		function Header()
		{
			switch ($this->tableType)
			{
				case self::TABLETYPE_HTML:
					if ($this->divClass)
						echo "<div class=$this->divClass>\n";
						
					echo "<table ";
					if ($this->tableName !== '')
						echo 'id="'.$this->tableName.'" ';
					echo "$this->tableTags>\n";
					
					echo "<tbody>\n";
					break;
				case self::TABLETYPE_RTF:
				case self::TABLETYPE_TEXT:
				default:
					break;
			}
			$this->ColumnHeaders();
			$this->ColumnHeaders(false);
		}
		
		function Footer()
		{
			switch ($this->tableType)
			{
				case self::TABLETYPE_HTML:
					echo "</tbody></table>\n";		
					if ($this->divClass)
						echo "</div>\n";		
					break;
				case self::TABLETYPE_RTF:
				case self::TABLETYPE_TEXT:
				default:
					break;
			}
		}

		function ShowBulkActions( $which = 'top' ) 
		{	
			if (!isset($this->bulkActions)) return '';
			
			$this->OutputCheckboxScript();
			
			$this->OutputBulkActionsScript($this->bulkActions);
			
			$ctrlPosn = $which === 'top' ? '_t' : '_b';
			$buttonId = 'action_'.$this->tableName.$ctrlPosn;
			
			$bulkActions = __('Bulk Actions', $this->myDomain);
			
			$onclickParam = "onclick=\"return StageShowLib_confirmBulkAction(this, '$buttonId')\"";
			
			$output  = "<div class='alignleft actions'>\n";
			$output .= "<select id='$buttonId' name='action$ctrlPosn'>\n"; 
			$output .= "<option value='-1' selected='selected'>$bulkActions &nbsp;&nbsp;</option>\n"; 
			foreach ($this->bulkActions as $action => $actionID)
				$output .= "<option value='$action'>$actionID</option>\n"; 
			$output .= "</select>\n"; 
			$output .= "<input type='submit' name='doaction$ctrlPosn' id='doaction$ctrlPosn' $onclickParam class='button-secondary action' value=".__('Apply', $this->myDomain)."  />\n"; 
			$output .= "</div>\n"; 
			
			return $output;
		}
		
		function OutputMoreButtonScript()
		{
			if (isset($this->myPluginObj->moreScriptsOutput)) return;
			$this->myPluginObj->moreScriptsOutput = true;
			
			$moreText = $this->moreText;
			$lessText = $this->lessText;
			
			echo "
<script>

function StageShowLib_HideOrShowRows(buttonId, rowId)
{
	var rowObj = document.getElementById(rowId);
	var buttonObj = document.getElementById(buttonId);

	// Toggle display state
	if (rowObj.style.display === '')
	{
		rowObj.style.display = 'none';	
		buttonObj.innerHTML = '$moreText';
		rowsVisible = false;
	}
	else
	{
		rowObj.style.display = '';
		buttonObj.innerHTML = '$lessText';
		rowsVisible = true;	
	}
	
}

</script>
			";
		}
		
		function OutputCheckboxScript()
		{
			if (isset($this->myPluginObj->scriptsOutput)) return;
			$this->myPluginObj->scriptsOutput = true;
			
			
			echo "
<script>

function StageShowLib_getParentNode(obj, nodeName)
{
	var pobj = obj;
	while (pobj !== null)
	{
		pobj = pobj.parentNode;
		if (pobj === null)
			break;
		pName = pobj.nodeName;
		if (pName === nodeName)
			break;
	}
	
	return pobj;
}

function StageShowLib_updateCheckboxes(obj)
{
	var boxid = 'rowSelect[]';
	
	var elem = StageShowLib_getParentNode(obj, 'FORM');
	elem = elem.elements;
	
	var newState = obj.checked;				
	for(var i = 0; i < elem.length; i++)
	{
		if (elem[i].name == boxid) 
			elem[i].checked = newState;
		
		if (elem[i].name == obj.name)
			elem[i].checked = newState;
	} 
		
	//var eventtype = event.type;
}


</script>
			";
		}
		
		function OutputBulkActionsScript($bulkActions)
		{
			if (isset($this->myPluginObj->bulkActionScriptsOutput)) return;
			$this->myPluginObj->bulkActionScriptsOutput = true;
						
			echo "
<script>

var confirmActionsArray = new Array(
			";
			
			foreach ($bulkActions as $action => $actionID)
			{
				if ($this->NeedsConfirmation($action))
				{
					echo "\"$actionID\", // $action => $actionID - Confirm Required\n"; 					
				}
			}
			
			echo "
\"\");

function StageShowLib_confirmBulkAction(obj, ctrlId)
{
	var elem = StageShowLib_getParentNode(obj, 'FORM');
	var count = StageShowLib_getCheckboxesCount(elem);
	if (count == 0)
	{
		return false;
	}
	
	var actionObj = document.getElementById(ctrlId);	
	var actionIndex = actionObj.selectedIndex;
	if (actionIndex == 0)
	{
		return false;
	}
	
	var actionText = actionObj.options[actionIndex].text;
	
	var mustConfirm = false;
	for (i=0; i<confirmActionsArray.length; i++)
	{
		if (confirmActionsArray[i] == actionText)
		{
			mustConfirm = true;
			break;
		}
	}
	
	if (!mustConfirm)
	{
		return true;
	}
		
	var confirmMsg = 'Do ' + actionText + ' on ' + count + ' entries?';
	var agree = confirm(confirmMsg);
	if (!agree)
	{
		return false;
	}

	return true;	
}
	
function StageShowLib_getCheckboxesCount(elem)
{
	var boxid = 'rowSelect[]';
	
	elem = elem.elements;
	
	var checkedCount = 0;				
	for(var i = 0; i < elem.length; i++)
	{
		if (elem[i].name == boxid) 
		{
			if (elem[i].checked)
			{
				checkedCount++;
			}
		}
	} 
		
	return checkedCount;
}

</script>
			";
			
		}
		
		function GetCurrentURL() 
		{			
			$currentURL = StageShowLibUtilsClass::GetPageURL();
			$currentURL = $this->myDBaseObj->AddParamAdminReferer($this->caller, $currentURL);
			return $currentURL;
		}
		
		function ShowPageNavigation( $which = 'top' ) 
		{			
			if ($this->rowsPerPage <= 0) 
				return;
			
			// $which is 'top' or 'bottom'
			$output = '';
			
			if ( $this->totalRows <= $this->rowsPerPage ) 
				return;
				
			$totalPages = (int)(($this->totalRows-1)/$this->rowsPerPage) + 1;
			
			$output .= '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $this->totalRows ), number_format_i18n( $this->totalRows ) ) . '</span>';

			$current_url = $this->GetCurrentURL();

			$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first', 'paged' ), $current_url );

			$page_links = array();

			$disable_first = $disable_last = '';
			if ( $this->currentPage == 1 )
				$disable_first = ' disabled';
			if ( $this->currentPage == $totalPages )
				$disable_last = ' disabled';

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'first-page' . $disable_first,
				$disable_first === '' ? esc_attr__('Go to the first page', $this->myDomain) : '',
				$disable_first === '' ? 'href='.esc_url( remove_query_arg( 'paged', $current_url ) ) : '',
				'&laquo;'
			);

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'prev-page' . $disable_first,
				$disable_first === '' ? esc_attr__('Go to the previous page', $this->myDomain) : '',
				$disable_first === '' ? 'href='.esc_url( add_query_arg( 'paged', max( 1, $this->currentPage-1 ), $current_url ) ) : '',
				'&lsaquo;'
			);

			if ( 'bottom' == $which )
				$html_current_page = $this->currentPage;
			else
				$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
					esc_attr__( 'Current page', $this->myDomain),
					esc_attr( 'paged' ),
					$this->currentPage,
					strlen( $totalPages )
				);

			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $totalPages ) );
			$page_links[] = '<span class="paging-input">' . sprintf('%1$s '.__('of', $this->myDomain).' %2$s', $html_current_page, $html_total_pages ) . '</span>';

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'next-page' . $disable_last,
				$disable_last === '' ? esc_attr__('Go to the next page', $this->myDomain) : '',
				$disable_last === '' ? 'href='.esc_url( add_query_arg( 'paged', min( $totalPages, $this->currentPage+1 ), $current_url ) ) : '',
				'&rsaquo;'
			);

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'last-page' . $disable_last,
				$disable_last === '' ? esc_attr__('Go to the last page', $this->myDomain) : '',
				$disable_last === '' ? 'href='.esc_url( add_query_arg( 'paged', $totalPages, $current_url ) ) : '',
				'&raquo;'
			);

			$output .= "\n" . join( "\n", $page_links );

			$page_class = $totalPages < 2 ? ' one-page' : '';

			return "<div class='tablenav-pages{$page_class}'>$output</div>";
		}

		function ShowPageControls($which = 'top')
		{
			switch ($this->tableType)
			{
				case self::TABLETYPE_HTML:
					break;
				case self::TABLETYPE_RTF:
				case self::TABLETYPE_TEXT:
				default:
					return;
			}
						
			$pageControls  = $this->ShowBulkActions($which);
			$pageControls .= $this->ShowPageNavigation($which);
			if ($pageControls != '') 
			{
				echo "<!-- ShowPageControls - START -->\n";
				echo "<div class='tablenav $which actions'>\n$pageControls</div>\n";
				echo "<!-- ShowPageControls - END -->\n";
			}
		}
		
		function Display()
		{
			$colTag = $this->useTHTags ? 'th' : 'td';
			
			$this->ShowPageControls();
			$this->Header();

			for ($row = 1; $row <= $this->currRow; $row++)
			{
				if ($this->hideEmptyRows && !$this->rowActive[$row]) continue;
				switch ($this->tableType)
				{
					case self::TABLETYPE_HTML:
						if (isset($this->rowAttr[$row]) && ($this->rowAttr[$row] != ''))
							echo "<tr ".$this->rowAttr[$row].">\n";
						else
							echo "<tr>\n";
						break;
					case self::TABLETYPE_RTF:
						break;
					case self::TABLETYPE_TEXT:
					default:
						break;
				}
								
				for ($col = 1; $col <= $this->maxCol; $col++)
				{
					$setWidth = '';
					$setAlign = '';
					$setId = '';
					
					if ($row == 1)
					{
						$setWidth = isset($this->colWidth[$col]) ? ' width="'.$this->colWidth[$col].'"' : '';
						$setAlign = isset($this->colAlign[$col]) ? ' align="'.$this->colAlign[$col].'"' : '';
						$setId = ($this->colId !== '') ? ' id="'.$this->colId.$col.'"' : '';
					}
					
					$setClass = (isset($this->colClass[$col]) && $this->colClass[$col] != '') ? ' class="'.$this->colClass[$col].'"' : '';
					
					$colSpan = '';
					$colSpanCount = 1;
					if ($this->spanEmptyCells)
					{
						for ($nextCol = $col+1; $nextCol <= $this->maxCol; $nextCol++, $colSpanCount++)
						{
							if (isset($this->tableContents[$row][$nextCol])) break;
						}
					}		
										
					if ($colSpanCount > 1)
					{
						$colSpanCount = $this->isTabbedOutput ? (2*($colSpanCount-1))+1 : $colSpanCount;
						$colSpan = ' colspan="'.$colSpanCount.'"';						
					} 
						
					switch ($this->tableType)
					{
						case self::TABLETYPE_HTML:
							echo '<'.$colTag.$colSpan.$setWidth.$setAlign.$setId.$setClass.'>';
							break;
						case self::TABLETYPE_RTF:
							if ($col > 1) echo '\tab ';
						case self::TABLETYPE_TEXT:
						default:
							break;
					}
					$tableContents = isset($this->tableContents[$row][$col]) ? $this->tableContents[$row][$col] : "";
					$tableContents = trim($tableContents);
					echo (strlen($tableContents) > 0) ? $tableContents : "&nbsp;";
					
					switch ($this->tableType)
					{
						case self::TABLETYPE_HTML:
							echo "</$colTag>\n";
							break;
						case self::TABLETYPE_RTF:
							break;
						case self::TABLETYPE_TEXT:
						default:
							echo "\t";
							break;
					}
					
					// Skp "Spanned" cells
					$col += ($colSpanCount - 1);
				}			
					
				switch ($this->tableType)
				{
					case self::TABLETYPE_HTML:
						echo "</tr>\n";
						break;
					case self::TABLETYPE_RTF:
						echo '\par '."\n";
						break;
					case self::TABLETYPE_TEXT:
					default:
						echo "\n";
						break;
				}
			}
			
			$this->Footer();
			$this->ShowPageControls('bottom');
		}
	}
}

if (!class_exists('StageShowLibAdminListClass')) 
{
	class StageShowLibAdminListClass extends StageShowLibTableClass // Define class
	{		
		const VIEWMODE = false;
		const EDITMODE = true;
		
		const BULKACTION_TOGGLE = 'toggleactive';
		const BULKACTION_DELETE = 'delete';
		
		const TABLEENTRY_DATETIME = 'datetime';
		
		var $env;
		var $caller;
		var $results;
		var $myPluginObj;
		var $myDBaseObj;
		var $rowNo;
		var $rowCount;
		var $filterRowCounts;
		var $defaultFilterIndex;
		var $showDBIds;
		var $lastCBId;
		var $currResult;
		var $enableFilter;
		
		var $editMode;
		
		var $updateFailed;
		
		var $hasDateTimeEntry = false;
		
		function __construct($env, $editMode /* = false */, $newTableType = self::TABLETYPE_HTML) //constructor
		{
			$this->editMode = $editMode;
			
			$this->env = $env;
			
			$this->caller = $env['caller'];
			$this->myPluginObj = $env['PluginObj'];
			$this->myDBaseObj = $env['DBaseObj'];
			$this->myDomain = $env['Domain'];
				
			// Call base constructor
			parent::__construct($newTableType);
			
			$this->ignoreEmptyCells = false;
			
			$this->enableFilter = true;
			
			$this->pluginName = basename(dirname($this->caller));

			$tableClass = $this->myDBaseObj->get_domain().'-widefat';			
			$this->tableTags = 'class="'.$tableClass.' widefat" cellspacing="0"';
			
			if (isset($this->myDBaseObj->adminOptions['PageLength']))
				$this->SetRowsPerPage($this->myDBaseObj->adminOptions['PageLength']);
			else
				$this->SetRowsPerPage(STAGESHOWLIB_EVENTS_PER_PAGE);
				
			$this->useTHTags = true;
			$this->showDBIds = $this->myDBaseObj->isDbgOptionSet('Dev_ShowDBIds');					
			$this->lastCBId = '';
			
			$this->defaultFilterIndex = 0;	
			$this->updateFailed = false;
			
			$this->columnDefs = $this->GetMainRowsDefinition();			
			
			if (!isset($this->HeadersPosn)) $this->HeadersPosn = self::HEADERPOSN_BOTH;
			if (!isset($this->hiddenRowsButtonId)) 
			{
				if (!$this->editMode)
					$this->hiddenRowsButtonId = __('Details', $env['Domain']);		
				else
				{
					$this->hiddenRowStyle = '';
					$this->hiddenRowsButtonId = '';
					$this->moreText = '';
				}
			}
		}
		
		function NeedsConfirmation($bulkAction)
		{
			switch ($bulkAction)
			{
				case self::BULKACTION_DELETE:
					return true;
					
				default:
					return false;
			}
		}
		
		function NewRow($result, $rowAttr = '')
		{
			StageShowLibTableClass::NewRow($result, $rowAttr);
			
			$col=1;
			
			$recordID = $this->GetRecordID($result);
			$isFirstLine = ($this->lastCBId !== $recordID);
			$this->lastCBId = $recordID;
			
			if (isset($this->bulkActions))
			{
				//echo "Adding Checkbox - Col = $col<br>";				
				if ($isFirstLine)
					$this->AddCheckBoxToTable($result, 'rowSelect[]', false, $col++, $recordID);
				else	
					$this->AddToTable($result, ' ', $col++);
			}
			
			if ($this->showDBIds)
			{
				if ($isFirstLine)
					$this->AddToTable($result, $recordID, $col++);
				else	
					$this->AddToTable($result, ' ', $col++);
			}
		}
		
		function GetTableID($result)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetTableID');
		}
		
		function GetRecordID($result)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetRecordID');
		}
		
		function GetDetailID($result)
		{
			return '';
		}
		
		function GetRowClass($result)
		{
			return '';
		}
		
		function IsRowInView($result, $rowFilter)
		{
			return true;
		}
		
		function ShowRow($result, $rowFilter)
		{
			$rtnVal = true;

			if (!$this->enableFilter) 
				return $rtnVal;
			
			if ($this->rowNo < $this->firstRowShown) 
			{				
				$rtnVal = false;
			}	
			else if (($this->rowCount >= $this->rowsPerPage) && ($this->rowsPerPage > 0))
			{
				$rtnVal = false;
			}
				
			return $rtnVal;
		}

		function OutputFilterLinks($results, $rowFilter = '')
		{
			$current_url = StageShowLibUtilsClass::GetPageURL();
			$current_url = remove_query_arg( 'filter', $current_url);
			$current_url = remove_query_arg( 'paged', $current_url);
				
			// Loop through all entries to get row counts for each filter
			$filter_links = '';
			foreach ($this->filterRowCounts as $filterId => $filterCount)
			{
				$rowCount = 0;
					
				foreach($results as $result)
				{
					if ($this->IsRowInView($result, $filterId))
						$rowCount++;
				}						
				$this->filterRowCounts[$filterId] = $rowCount;
								
				if ($filter_links != '')
					$filter_links .= ', ';

				$filterClass = strtolower($filterId);
				
				$filterClass = ($rowFilter == $filterId) ? 'selected' : strtolower($filterId);
				$filterURL = esc_url( add_query_arg( 'filter', $filterId, $current_url ) );
						
				$filter_links .= sprintf( "<a class='%s' title='%s' %s>%s</a>",
					$filterClass,
					$rowCount > 0 ? esc_attr__('Show all Events', $this->myDomain) : '',
					$rowCount > 0 ? 'href='.$filterURL : '',
					"$filterId ($rowCount)"
				);
			}
				
			echo "<div class=filter-links>\n";
			echo $filter_links;					
			echo "</div>\n";
		}
		
		function GetSelectOptsArray($settingOption, $result=null)
		{
			if (isset($settingOption[StageShowLibTableClass::TABLEPARAM_DIR]))
			{
				if (isset($settingOption[StageShowLibTableClass::TABLEPARAM_EXTN]))
					$fileExtns = $settingOption[StageShowLibTableClass::TABLEPARAM_EXTN];
				else
					$fileExtns = '*';
				
				$selectOpts = array();
				
				$fileExtnsArray = explode(',', $fileExtns);
				foreach($fileExtnsArray as $fileExtn)
				{
					// Folder is defined ... create the search path
					$dir = $settingOption[StageShowLibTableClass::TABLEPARAM_DIR];
					if (substr($dir, strlen($dir)-1, 1) != '/')
						$dir .= '/';
						
					$dir .= '*.'.$fileExtn;					

					// Now get the files list and convert paths to file names
					$filesList = glob($dir);
					foreach ($filesList as $key => $path)
						$selectOpts[] = basename($path);
				}
			}
			else if (isset($settingOption[StageShowLibTableClass::TABLEPARAM_FUNC]))
			{
				$functionId = $settingOption[StageShowLibTableClass::TABLEPARAM_FUNC];
				$selectOpts = $this->$functionId($result);
			}
			else if (isset($settingOption[StageShowLibTableClass::TABLEPARAM_ITEMS]))
			{
				$selectOpts = $settingOption[StageShowLibTableClass::TABLEPARAM_ITEMS];
			}
			else
				return array();
									
			$selectOptsArray = array();
			
			if (isset($settingOption[StageShowLibTableClass::TABLEPARAM_ADDEMPTY]))
			{
				$selectOptsArray[''] = '';
			}
						
			foreach ($selectOpts as $selectOpt)
			{
				$selectAttrs = explode('|', $selectOpt);
				if (count($selectAttrs) == 1)
				{
					$selectOptValue = $selectOptText = $selectAttrs[0];
				}
				else
				{
					$selectOptValue = $selectAttrs[0];
					$selectOptText = __($selectAttrs[1], $this->myDomain);
				}
				
				$selectOptsArray[$selectOptValue] = $selectOptText;
			}
			
			return $selectOptsArray;
		}
		
		function GetSelectOptsText($settingOption, $controlValue)
		{
			$selectOptsArray = self::GetSelectOptsArray($settingOption);
			foreach ($selectOptsArray as $selectOptValue => $selectOptText)
			{
				if ($controlValue == $selectOptValue)
				{
					$controlValue = $selectOptText;
					break;
				}
			}
			
			return $controlValue;
		}
		
		function OutputButton($buttonId, $buttonText, $buttonClass = "button-secondary")
		{
			$buttonText = __($buttonText, $this->myDomain);
			
			return "<input class=\"$buttonClass\" type=\"submit\" name=\"$buttonId\" value=\"$buttonText\" />\n";
		}
		
		function GetHTMLTag($settingOption, $controlValue, $editMode = true)
		{
			$autocompleteTag = ' autocomplete="off"';
			$controlIdDef = 'id="'.$settingOption[self::TABLEPARAM_ID].'" name="'.$settingOption[self::TABLEPARAM_ID].'" ';
			
			$editControl = '';
			
			$settingType = $settingOption[self::TABLEPARAM_TYPE];
			$onChange = isset($settingOption[self::TABLEPARAM_ONCHANGE]) ? ' onchange="'.$settingOption[self::TABLEPARAM_ONCHANGE].'(this)" ' : '';

			if (isset($settingOption[self::TABLEPARAM_READONLY]))
				$editMode = false;
				
			if ($editMode && isset($settingOption[self::TABLEPARAM_CANEDIT]))
			{
				$funcName = $columnDef[StageShowLibTableClass::TABLEPARAM_CANEDIT];
				$editMode = $this->$funcName($result);
			}
			
			if (!$editMode)
			{
				echo '<input type="hidden" '.$controlIdDef.' value="'.$controlValue.'"/>'."\n";

				switch ($settingType)
				{
					case self::TABLEENTRY_SELECT:
						$controlValue = $this->GetSelectOptsText($settingOption, $controlValue);
						$settingType = self::TABLEENTRY_VIEW;
						break;						
					
					case self::TABLEENTRY_CHECKBOX:
						$controlValue = $controlValue ? __('Yes', $this->myDomain) : __('No', $this->myDomain);
						$settingType = self::TABLEENTRY_VIEW;
						break;		
										
					case self::TABLEENTRY_TEXT:
					case self::TABLEENTRY_DATETIME:
					case self::TABLEENTRY_TEXTBOX:
					case self::TABLEENTRY_COOKIE:
						$settingType = self::TABLEENTRY_VIEW;
						break;						
				}				
			}
				
			switch ($settingType)
			{
				case self::TABLEENTRY_DATETIME:
					$editSize = 28;
					$inputClass = $this->myDBaseObj->get_domain().'-dateinput';
					$eventHandler = " class=\"".$inputClass."\" readonly=true onclick=\"javascript:StageShowLib_CalendarSelector(this, '".$this->dateTimeMode."')\" ";
					$editControl  = '<input type="text"'.$eventHandler.' size="'.$editSize.'" '.$controlIdDef.' value="'.$controlValue.'" />'."\n";
					$editControl .= '<input type="hidden" '.str_replace('="', '="curr', $controlIdDef).' value="'.$controlValue.'" />'."\n";					
					$this->hasDateTimeEntry = true;
					break;

				
				case self::TABLEENTRY_TEXT:
				case self::TABLEENTRY_COOKIE:
					$editLen = $settingOption[self::TABLEPARAM_LEN];
					$editSize = isset($settingOption[self::TABLEPARAM_SIZE]) ? $settingOption[self::TABLEPARAM_SIZE] : $editLen+1;
					$editControl  = '<input type="text"'.$autocompleteTag.' maxlength="'.$editLen.'" size="'.$editSize.'" '.$controlIdDef.' value="'.$controlValue.'" />'."\n";
					$editControl .= '<input type="hidden" '.str_replace('="', '="curr', $controlIdDef).' value="'.$controlValue.'" />'."\n";					
					break;

				case self::TABLEENTRY_TEXTBOX:
					$editRows = $settingOption[self::TABLEPARAM_ROWS];
					$editCols = $settingOption[self::TABLEPARAM_COLS];
					$editControl = '<textarea rows="'.$editRows.'" cols="'.$editCols.'" '.$controlIdDef.$onChange.' >'.$controlValue."</textarea>\n";
					break;

				case self::TABLEENTRY_SELECT:
					$selectOptsArray = self::GetSelectOptsArray($settingOption);
					if (count($selectOptsArray)>1)
					{
						$editControl  = '<select '.$controlIdDef.$onChange.'>'."\n";
						foreach ($selectOptsArray as $selectOptValue => $selectOptText)
						{
							$selected = ($controlValue == $selectOptValue) ? ' selected=""' : '';
							$editControl .= '<option value="'.$selectOptValue.'"'.$selected.' >'.$selectOptText."&nbsp;</option>\n";
						}
						$editControl .= '</select>'."\n";	
					}
					else
					{
						$editControl  = $this->GetSelectOptsText($settingOption, $controlValue);
						$editControl .=  '<input type="hidden" '.$controlIdDef.' value="'.$controlValue.'"/>';					
					}
					break;

				case self::TABLEENTRY_CHECKBOX:
					$checked = ($controlValue === true) ? ' checked="yes"' : '';
					$cbText = __($settingOption[StageShowLibTableClass::TABLEPARAM_TEXT], $this->myDomain);
					$editControl = '<input type="checkbox" '.$controlIdDef.' value="1"'.$onChange.$checked.' />&nbsp;'.$cbText."\n";
					break;

				case self::TABLEENTRY_READONLY:
					$editControl = $controlValue;
					if (isset($settingOption[self::TABLEPARAM_ITEMS]))
					{
						// This was a drop down edit - Get User Prompt for this value
						$editControl = $this->GetSelectOptsText($settingOption, $controlValue);
					}
					$editControl .= '<input type="hidden" '.$controlIdDef.' value="'.$controlValue.'">'."\n";
					break;
					
				case self::TABLEENTRY_VIEW:
					$editControl = $controlValue.'&nbsp;';
					break;

				case self::TABLEENTRY_VALUE:
					$editControl = $settingOption['Value'];
					break;

				default:
					//echo "<string>Unrecognised Table Entry Type - $settingType </string><br>\n";
					//StageShowLibUtilsClass::ShowCallStack();
					break;
			}

			return $editControl;
		}
		
		function AddResultFromTable($result)
		{		
			$canDisplayTable = true;
			
			// Check if this row CAN be output using data from the columnDefs table
			foreach ($this->columnDefs as $key => $columnDef)
			{
				if (!isset($columnDef[self::TABLEPARAM_TYPE]))
					return true;
				
				switch ($columnDef[self::TABLEPARAM_TYPE])
				{
					case self::TABLEENTRY_CHECKBOX:
					case self::TABLEENTRY_TEXT:
					case self::TABLEENTRY_DATETIME:
					//case self::TABLEENTRY_TEXTBOX:
					case self::TABLEENTRY_SELECT:
					case self::TABLEENTRY_VALUE:
					case self::TABLEENTRY_VIEW:
					case self::TABLEENTRY_READONLY:
					case self::TABLEENTRY_COOKIE:
					case self::TABLEENTRY_FUNCTION:
						break;
												
					default:
						$canDisplayTable = false;
						echo "Can't display this table - Label:".$columnDef[self::TABLEPARAM_LABEL]." Id:".$columnDef[self::TABLEPARAM_ID]." Column Type:".$columnDef[self::TABLEPARAM_TYPE]."<br>\n";						
						break 2;
				}
			}
			
			if ($canDisplayTable)
			{
				$rowClass = $this->GetRowClass($result);
				$rowAttr = ($rowClass != '') ? 'class="'.$rowClass.'"' : '';
				$this->NewRow($result, $rowAttr);
				
				foreach ($this->columnDefs as $columnDef)
				{
					if (isset($columnDef[self::TABLEPARAM_ID]))
					{
						$columnId = $columnDef[self::TABLEPARAM_ID];
						$recId = $this->GetRecordID($result).$this->GetDetailID($result);
						
						if ($this->updateFailed && isset($_POST[$columnId.$recId]))
						{
							// Error updating values - Get value(s) from form controls
							$currVal = stripslashes($_POST[$columnId.$recId]);	// TODO: Check for SQLi
						}
						else
						{
							// Get value(s) from database
							$currVal = $result->$columnId;
						}						
					}
					else
						$currVal = '';
				
					if (isset($columnDef[StageShowLibTableClass::TABLEPARAM_DECODE]))
					{
						$optionId = $columnDef[StageShowLibTableClass::TABLEPARAM_ID];
						$funcName = $columnDef[StageShowLibTableClass::TABLEPARAM_DECODE];
						$currVal = $this->$funcName($result->$optionId, $result);
					}
					$hiddenVal = $currVal;
					
					$columnType = $columnDef[self::TABLEPARAM_TYPE];
					if ((!$this->editMode) && ($columnType != self::TABLEENTRY_FUNCTION))
					{
						if ($columnType == self::TABLEENTRY_CHECKBOX)
						{
							$currVal = ($currVal == 1) ? __('Yes', $this->myDomain) : __('No', $this->myDomain);
						}
						$columnType = self::TABLEENTRY_VIEW;
					}

					if ($this->editMode)	
					{
						if (isset($columnDef[self::TABLEPARAM_CANEDIT]))
						{
							$funcName = $columnDef[self::TABLEPARAM_CANEDIT];
							$editMode = $this->$funcName($result);
							if (!$editMode)
							{
								if ($columnType == self::TABLEENTRY_SELECT)
								{
									// Get Value from Items List
									$srchText = $currVal.'|';
									$srchLen = strlen($srchText);
									foreach ($columnDef[StageShowLibTableClass::TABLEPARAM_ITEMS] as $item)
									{
										if (substr($item, 0, $srchLen) === $srchText)
										{
											$currVal = substr($item, $srchLen);
											break;
										}
									}
								}
								$columnType = self::TABLEENTRY_VIEW;
							}
						}						
					}
						
					switch ($columnType)
					{
						case self::TABLEENTRY_CHECKBOX:
							$checked = ($currVal==1);
							$this->AddCheckBoxToTable($result, $columnId, $checked, 0, "1");
							break;
							
						//case self::TABLEENTRY_TEXTBOX:
						
						case self::TABLEENTRY_SELECT:
							$options = self::GetSelectOptsArray($columnDef, $result);							
							$this->AddSelectToTable($result, $columnDef, $options, $currVal);
							break;
						
						case self::TABLEENTRY_COOKIE:
							$cookieID = $columnDef[self::TABLEPARAM_ID];
							if (isset($_COOKIE[$cookieID]))
								$currVal = $_COOKIE[$cookieID];
							else
								$currVal = '';
							// Fall into next case ...
							
						case self::TABLEENTRY_TEXT:
							if (!isset($columnDef[self::TABLEPARAM_LEN]))
							{
								echo "No Len entry in Column Definition<br>\n";
								StageShowLibUtilsClass::print_r($columnDef, 'columnDef');
							}
							
							$size = isset($columnDef[self::TABLEPARAM_SIZE]) ? $columnDef[self::TABLEPARAM_SIZE] : $columnDef[self::TABLEPARAM_LEN]+1;
							$extraParams = 'size="'.$size.'"';
							$this->AddInputToTable($result, $columnId, $columnDef[self::TABLEPARAM_LEN], $currVal, 0, false, $extraParams);
							break;

						case self::TABLEENTRY_DATETIME:
							$size = 28;
							$inputClass = $this->myDBaseObj->get_domain().'-dateinput';
							$extraParams = "class=\"".$inputClass."\" readonly=true onclick=\"javascript:StageShowLib_CalendarSelector(this, '".$this->dateTimeMode."')\" ";
							$this->AddInputToTable($result, $columnId, $size, $currVal, 0, false, $extraParams);
							$this->hasDateTimeEntry = true;
							break;

						case self::TABLEENTRY_VALUE:
						case self::TABLEENTRY_VIEW:
						case self::TABLEENTRY_READONLY:
							$recId = $this->GetRecordID($result).$this->GetDetailID($result);
							$hiddenTag = '<input type="hidden" name="'.$columnId.$recId.'" id="'.$columnId.$recId.'" value="'.$hiddenVal.'"/>';
							if (isset($columnDef[StageShowLibTableClass::TABLEPARAM_LINK]))
							{
								$currValLink = $columnDef[StageShowLibTableClass::TABLEPARAM_LINK];
								if (isset($columnDef['LinkTo']))
								{
									$currValLink .= "http://";		// Make link absolute
									$currValLink .= $result->$columnDef['LinkTo'];
									$target = 'target="_blank"';
								}
								else
								{
									$currValLink .= $recId;
									$currValLink = $this->myDBaseObj->AddParamAdminReferer($this->caller, $currValLink);
									$target = '';
								}
								$currVal = '<a href="'.$currValLink.'" '.$target.'>'.$currVal.'</a>';
							}
							$this->AddToTable($result, $currVal.$hiddenTag.'&nbsp;');
							break;
							
						case self::TABLEENTRY_FUNCTION:
							$functionId = $columnDef[self::TABLEPARAM_FUNC];
							$content = $this->$functionId($result);
							$this->AddToTable($result, $content);
							break;
							
						default:
							break;
					}
				}
			}
						
			return $canDisplayTable;
		}
		
		function AddOptions($result, $optionDetails = array())
		{
			$optionsRecordID = $this->GetRecordID($result);
			$showOptions = ($this->showOptionsID == $optionsRecordID);
			
			$hiddenRowsID = 'record'.$optionsRecordID.'options';
			
			if (count($this->detailsRowsDef) > 0)
			{
				$colClassList = '';
				for ($c=1; $c<$this->maxCol; $c++)
					$colClassList .= ',';

				if ($this->moreText != '')
				{
					$buttonText = $showOptions ? $this->lessText : $this->moreText;				
					$this->AddShowOrHideButtonToTable($result, $this->tableName, $hiddenRowsID, $buttonText);
					$colClassList .= 'optionsCol';					
				}
				else if ($this->maxCol > 0)
				{
					$this->AddToTable($result, '');
				}
				
				$this->SetColClass($colClassList);
												
				$tableId = $this->GetTableID($result);
				$hiddenRowsColId = $tableId.'-hiddenCol';
		
				$tabbedRowCounts = array();
				
				$nextInline = false;
				$hiddenRows = "<table class=$tableId-table width=\"100%\">\n";
				foreach ($this->detailsRowsDef as $option)
				{
					if (isset($option[self::TABLEPARAM_ID]) && !$this->CanShowDetailsRow($result, $option[self::TABLEPARAM_ID]))
						continue;
						
					if (isset($option[self::TABLEPARAM_LABEL]))
						$optionLabel = __($option[self::TABLEPARAM_LABEL], $this->myDomain);						
						
					if (isset($option[self::TABLEPARAM_BLOCKBLANK]) && ($result->$option[self::TABLEPARAM_ID] == ''))
					{
						// Hide Row if the value is blank
						$tabRowId = 'style="display: none;"';
					}
					else if (!$nextInline && isset($option[self::TABLEPARAM_TAB]))
					{
						$tabId = $option[self::TABLEPARAM_TAB];
						$rowNumber = isset($tabbedRowCounts[$tabId]) ? $tabbedRowCounts[$tabId] + 1 : 1;
						$tabbedRowCounts[$tabId] = $rowNumber;
						
						$tabRowId = 'id='.$tabId.'-row'.$rowNumber;
					}
					else
						$tabRowId = '';
					 					
					$tableRowTag = '<tr '.$tabRowId.' >';
					switch ($option[self::TABLEPARAM_TYPE])
					{
						case self::TABLEENTRY_FUNCTION:
							$functionId = $option[self::TABLEPARAM_FUNC];
							$content = $this->$functionId($result, $optionDetails);
							$hiddenRows .= $tableRowTag."\n";
							$colSpan = ' class='.$hiddenRowsColId.'2';
							if (isset($option[self::TABLEPARAM_LABEL]))
								$hiddenRows .= '<td class='.$hiddenRowsColId.'1>'.$optionLabel."</td>\n";
							else
								$colSpan = " colspan=2";
								
							$hiddenRows .= '<td'.$colSpan.'>'.$content."</td>\n";
							$hiddenRows .= "</tr>\n";
							break;
							
						case self::TABLEENTRY_ARRAY:
							if (isset($option[self::TABLEPARAM_LABEL]))
							{
								$hiddenRows .= $tableRowTag."\n";
								$hiddenRows .= '<td colspan=2>'.$optionLabel."</td>\n";
								$hiddenRows .= "</tr>\n";
							}
							$arrayId = $option[self::TABLEPARAM_ID];
							foreach ($result->$arrayId as $elemId => $elemValue)
							{
								$hiddenRows .= $tableRowTag."\n";
								$hiddenRows .= '<td class='.$hiddenRowsColId.'1>'.$elemId."</td>\n";
								$hiddenRows .= '<td class='.$hiddenRowsColId.'2>'.$elemValue."</td>\n";
								$hiddenRows .= "</tr>\n";
							}
							break;
							
						default:
							$optionId = $option[self::TABLEPARAM_ID];
							$option[self::TABLEPARAM_ID] = $option[self::TABLEPARAM_ID].$this->GetRecordID($result);
											
							if (!$nextInline)
								$hiddenRows .= $tableRowTag."\n";
							if (strlen($option[self::TABLEPARAM_LABEL]) > 0)
							{
								if (!$nextInline)
									$hiddenRows .= '<td class='.$hiddenRowsColId.'1>';
								$hiddenRows .= $optionLabel."</td>\n";
								$nextInline = false;
							}
							if (!$nextInline)
								$hiddenRows .= '<td class='.$hiddenRowsColId.'2>';
							
							if (isset($option[self::TABLEPARAM_TYPE]) && ($option[self::TABLEPARAM_TYPE] != self::TABLEENTRY_COOKIE))
							{
								if (isset($result->$optionId))
									$currVal = $result->$optionId;
								else if (isset($option[self::TABLEPARAM_DEFAULT]))
									$currVal = $option[self::TABLEPARAM_DEFAULT];
								else
									$currVal = '';
									
								if (isset($option[StageShowLibTableClass::TABLEPARAM_DECODE]))
								{
									$funcName = $option[StageShowLibTableClass::TABLEPARAM_DECODE];
									$currVal = $this->$funcName($currVal, $result);
								}
							}
							else if (isset($_COOKIE[$optionId]))
								$currVal = $_COOKIE[$optionId];
							else
								$currVal = '';
								
							$hiddenRows .= self::GetHTMLTag($option, $currVal, $this->editMode);
							
							$nextInline = isset($option[self::TABLEPARAM_NEXTINLINE]);
							if (!$nextInline) 
								$hiddenRows .= "</td>\n</tr>\n";
							break;
					}
				}
				$hiddenRows .= "</table>\n";
				
				$style = $showOptions ? $this->visibleRowStyle : $this->hiddenRowStyle;				
				$this->spanEmptyCells = true;
				$this->AddHiddenRows($result, $hiddenRowsID, $hiddenRows, $style);					
			}			
		}
				
		static function GetSettingsRowIndex($arr1, $id)
		{			
			for ($index=0; $index<count($arr1); $index++)
			{
				if ($arr1[$index][self::TABLEPARAM_ID] === $id)
					return $index;
			}
			
			return -1;
		}
		
		static function MergeSettings($arr1, $arr2)
		{
			// Merge Arrays ... keeping all duplicate entries
			$vals1 = $arr1;
			foreach ($arr2 as $val2)
			{
				$index = -1;
				if (isset($val2[self::TABLEPARAM_BEFORE]))
				{
					// This entry must be positioned within earlier entries
					$index = self::GetSettingsRowIndex($vals1, $val2[self::TABLEPARAM_BEFORE]);
				}
				if (isset($val2[self::TABLEPARAM_AFTER]))
				{
					// This entry must be positioned within earlier entries
					$index = self::GetSettingsRowIndex($vals1, $val2[self::TABLEPARAM_AFTER]);
					if ($index >= 0) $index++;
				}
				
				if ($index >= 0)
					array_splice($vals1, $index, 0, array($val2));
				else
					$vals1 = array_merge($vals1, array($val2));
			}
			return $vals1;
		}
		
		function GetListDetails($result)
		{
			return array();
		}
		
		function OutputJSDateConstants()
		{
			if (!$this->hasDateTimeEntry)
				return;
			
			$scriptOutput  = "<script>\n";
			
			// Use a date for a Monday (23/12/2013)			
			$scriptOutput .= "var WeekDayName2 = [";
			for ($dayNo = 23; $dayNo <= 29; $dayNo++)
			{
				$day = date("D",strtotime('2013-12-'.$dayNo));
				if ($dayNo > 23) $scriptOutput .= ', ';
				$scriptOutput .= '"'.$day.'"';
			}
			$scriptOutput .= "];\n";
			
			$scriptOutput .= "var MonthName = [";
			for ($monthNo = 1; $monthNo <= 12; $monthNo++)
			{
				$month = date("F",strtotime('2000-'.$monthNo.'-20'));
				if ($monthNo > 1) $scriptOutput .= ', ';
				$scriptOutput .= '"'.$month.'"';
			}
			$scriptOutput .= "];\n";
						
			$scriptOutput .= "</script>\n";
			
			echo $scriptOutput;
		}
		
		function OutputJavascript($selectedTabIndex = 0)
		{
			if (!$this->isTabbedOutput)
				return;
					
			if (count($this->columnDefs) <= 1)
				return;
						
			$javascript = $this->JS_Top();
			foreach ($this->columnDefs as $column)
			{
				$setingsPageID = $column[self::TABLEPARAM_ID];
				$javascript .= $this->JS_Tab($setingsPageID);					
			}
				
			$javascript .= $this->JS_Bottom($selectedTabIndex);
			echo $javascript;
		}

		function OutputList($results, $updateFailed = false)
		{
			if (count($results) == 0) 
			{
				if (!isset($this->blankTableClass))
					return;
				$tableId = $this->GetTableID(null);
				$this->tableTags = str_replace('class="', 'class="'.$this->blankTableClass.' ', $this->tableTags);
			}
			else
			{
				$tableId = $this->GetTableID($results[0]);
				
				$this->OutputJavascript();		
			}
			
			$headerColumns = array();
			foreach ($this->columnDefs as $column)
			{
				$columnLabel = __($column[self::TABLEPARAM_LABEL], $this->myDomain);
				$headerColumns = array_merge($headerColumns, array($column[self::TABLEPARAM_ID] => $columnLabel));
			}
			$this->SetListHeaders($tableId, $headerColumns, $this->HeadersPosn);
			
			$this->results = $results;
			
			$this->EnableListHeaders();
			
			if (isset($this->filterRowCounts))
			{
				$filterKeys = array_keys($this->filterRowCounts);
				$defaultFilter = $filterKeys[$this->defaultFilterIndex];
				
				// Get the filter requested in the HTTP request 
				$rowFilter = isset($_GET['filter']) ? $_GET['filter'] : $defaultFilter;
				
				// Check that the selected filter is defined ... or use default
				$rowFilter = isset($this->filterRowCounts[$rowFilter]) ? $rowFilter : $defaultFilter;
						
				// Calculate and output filter links
				$this->OutputFilterLinks($results, $rowFilter);
				
				// Get the row count for the selected filter
				$this->totalRows = isset($this->filterRowCounts[$rowFilter]) ? $this->filterRowCounts[$rowFilter] : 0;
			}
			else
			{
				$this->totalRows = count($results);
				$rowFilter = '';
			}
										
			$this->rowNo = 0;
			$this->rowCount = 0;
			
			if (count($results) > 0)
				$this->tableName = $this->GetTableID($results[0]);			
	
			foreach($results as $result)
			{
				if (!$this->IsRowInView($result, $rowFilter))
					continue;
				
				$this->rowNo++;
				
				if (!$this->ShowRow($result, $rowFilter))
					continue;
				
				if (!$this->AddResultFromTable($result))
				{
					if (!isset($this->usedAddResult))
					{
						$this->usedAddResult = true;
						echo "<br>Error returned by AddResultFromTable function in ".get_class($this)." class<br>\n";
						StageShowLibUtilsClass::ShowCallStack();
					}
				}
				$resultDetails = $this->GetListDetails($result);
				$this->AddOptions($result, $resultDetails);
				$this->rowCount++;
			}
			
			$this->Display();
			
			$this->OutputJSDateConstants();			
		}
		
		function JS_Top()
		{
			return "
<script language='JavaScript'>
<!-- Hide script from old browsers
// End of Hide script from old browsers -->

var tabIdsList  = [";
	
		}
		
		function JS_Tab($tabID)
		{
			return "'$tabID',";	
		}
		
		function JS_Bottom($defaultTab)
		{
			$jsCode  = "''];\n";		
			$jsCode .= "var defaultTabIndex = ".$defaultTab.";\n";
			
			return $jsCode;
		}
	}
}
		 

if (!class_exists('Template_For_ClassDerivedFrom_StageShowLibAdminListClass')) 
{
	class Template_For_ClassDerivedFrom_StageShowLibAdminListClass extends StageShowLibAdminListClass // Define class
	{		
		function __construct($env) //constructor
		{
		}
		
		function GetRecordID($result)
		{
		}
		
		function IsRowInView($result, $rowFilter)
		{
		}

		function ShowRow($result, $rowFilter)
		{
		}
		
	}
}

?>