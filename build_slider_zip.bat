@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "BASE64_FILE=%SCRIPT_DIR%slider_step_by_step.zip.base64"
set "ZIP_FILE=%SCRIPT_DIR%slider_step_by_step.zip"
set "PS_HELPER=%SCRIPT_DIR%build_slider_zip.ps1"

if not exist "%BASE64_FILE%" goto try_powershell

certutil >nul 2>&1
if errorlevel 1 goto try_powershell

certutil -f -decode "%BASE64_FILE%" "%ZIP_FILE%" >nul 2>&1
if errorlevel 1 goto try_powershell

echo.
echo slider_step_by_step.zip generated next to this script. You can now import it into Slider Revolution.
pause
exit /b 0

:try_powershell
powershell -NoProfile -ExecutionPolicy Bypass -File "%PS_HELPER%"
if errorlevel 1 goto try_python

echo.
echo slider_step_by_step.zip generated next to this script. You can now import it into Slider Revolution.
pause
exit /b 0

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

echo.
echo slider_step_by_step.zip generated next to this script. You can now import it into Slider Revolution.
pause
exit /b 0

:fail_no_python
echo Could not find a working Python 3 installation. Install it from https://www.python.org/downloads/ and rerun this script.

:fail
echo.
echo Failed to build slider_step_by_step.zip. See messages above for details.
pause
exit /b 1
