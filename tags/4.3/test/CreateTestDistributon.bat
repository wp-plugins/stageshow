@setlocal EnableDelayedExpansion

@rem @ECHO off

@SET MJSLIBPrefixLib=StageShowLib
@SET MJSLIBPrefixFile=stageshowlib_
@SET MJSLIBPrefixDefine=STAGESHOWLIB

@ECHO.
@ECHO ------------------------------------------------------------------------------
@ECHO Change Library Names from MJSLib* to %MJSLIBPrefixFile%* 
@ECHO ------------------------------------------------------------------------------
@ECHO.

@call :reformatfolder test

@EXIT /b

@REM  ------------------------------------------------------------------------------
@REM  SUBROUTINE "REFORMATFOLDER"
@REM  ------------------------------------------------------------------------------
:reformatfolder

@IF NOT EXIST %1 GOTO FolderMissing
@ECHO Reformating %1 folder ...
@CD %1
@DOS2UNIX -k -q *.php 2>nul
@FOR /F "Delims=" %%f in ('Dir /B *.php') do @call :reformatfile %%f   ? subroutine called here
@CD ..

:FolderMissing

@PAUSE

@EXIT /b

@REM  ------------------------------------------------------------------------------
@REM  SUBROUTINE "REFORMATFILE"
@REM  ------------------------------------------------------------------------------
:reformatfile
@REM @ECHO Reformatting file %1 ...

@SED "s/MJSLib/%MJSLIBPrefixLib%/g" %1 >temp.php
@DEL %1
@RENAME temp.php %1

@SED "s/MJSLIB/%MJSLIBPrefixDefine%/g" %1 >temp.php
@DEL %1
@RENAME temp.php %1

@SED "s/mjslib_/%MJSLIBPrefixFile%/g" %1 >temp.php
@DEL %1
@RENAME temp.php %1

@ECHO %1 | sed "s/ //g" | sed "s/mjslib_/%MJSLIBPrefixFile%/" >tmpfile
@SET /p newname= < tmpFile
@REM @ECHO newname=%newname%
@DEL tmpFile 

@IF "%1"=="%newname%" GOTO NoRename
@RENAME %1 %newname%
:NoRename

@EXIT /b
