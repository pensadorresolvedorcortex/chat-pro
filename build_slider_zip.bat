@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "PS_HELPER=%SCRIPT_DIR%build_slider_zip.ps1"

powershell -NoProfile -ExecutionPolicy Bypass -File "%PS_HELPER%"
if errorlevel 1 goto try_python

goto success

:try_python
echo.
echo PowerShell helper was unable to run. Trying Python fallback...

echo.
set "PYTHON_CMD=py -3"
%PYTHON_CMD% --version >nul 2>&1
if errorlevel 1 set "PYTHON_CMD=python"
%PYTHON_CMD% --version >nul 2>&1
if errorlevel 1 goto fail_no_python

%PYTHON_CMD% "%SCRIPT_DIR%build_slider_zip.py"
if errorlevel 1 goto fail

goto success

:fail_no_python
echo Could not find a working Python 3 installation. Install it from https://www.python.org/downloads/ and rerun this script.
goto fail

:fail
echo.
echo Failed to build slider_step_by_step.zip. See messages above for details.
pause
exit /b 1

:success
echo.
echo slider_step_by_step.zip generated next to this script. You can now import it into Slider Revolution.
pause
exit /b 0
