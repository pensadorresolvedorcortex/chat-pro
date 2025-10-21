@echo off
setlocal EnableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
set "BASE64_FILE=%SCRIPT_DIR%slider_step_by_step.zip.base64"
set "ZIP_FILE=%SCRIPT_DIR%slider_step_by_step.zip"
set "PS_HELPER=%SCRIPT_DIR%build_slider_zip.ps1"

echo ==============================================
echo   Slider Revolution ZIP builder (Windows)
echo ==============================================
echo This helper will try three approaches:
echo   1. CertUtil (built into Windows)
echo   2. PowerShell
echo   3. Python 3
echo.

if exist "%ZIP_FILE%" (
    echo Removing existing slider_step_by_step.zip so we can create a fresh copy...
    del "%ZIP_FILE%"
)

if exist "%BASE64_FILE%" goto try_certutil
echo Base64 source not found, skipping CertUtil attempt.
goto try_powershell

:try_certutil
echo [1/3] Trying built-in CertUtil...
certutil >nul 2>&1
if errorlevel 1 (
    echo   CertUtil is not available in this environment.
    goto try_powershell
)

certutil -f -decode "%BASE64_FILE%" "%ZIP_FILE%" >nul 2>&1
set "ERR=%ERRORLEVEL%"
if not "%ERR%"=="0" (
    echo   CertUtil returned error code !ERR!.
    goto try_powershell
)

if not exist "%ZIP_FILE%" (
    echo   CertUtil reported success but the ZIP was not found.
    goto try_powershell
)

goto success

:try_powershell
echo [2/3] Trying PowerShell helper...
powershell -NoProfile -ExecutionPolicy Bypass -File "%PS_HELPER%"
set "ERR=%ERRORLEVEL%"
if "%ERR%"=="0" goto success
echo   PowerShell returned error code !ERR!.
echo   (This can happen if PowerShell scripts are blocked.)

:try_python
echo [3/3] Trying Python fallback...
set "PYTHON_CMD=py -3"
%PYTHON_CMD% --version >nul 2>&1
if errorlevel 1 set "PYTHON_CMD=python"
%PYTHON_CMD% --version >nul 2>&1
if errorlevel 1 goto fail_no_python

%PYTHON_CMD% "%SCRIPT_DIR%build_slider_zip.py"
set "ERR=%ERRORLEVEL%"
if not "%ERR%"=="0" goto fail_python

goto success

:success
echo.
echo Done! slider_step_by_step.zip was generated next to this script.
echo You can now import it into Slider Revolution.
pause
exit /b 0

:fail_no_python
echo.
echo Python 3 was not found. Install it from https://www.python.org/downloads/
echo and rerun this script, or follow the manual CertUtil steps in the README.
goto fail

:fail_python
echo.
echo Python reported an error while creating the ZIP (exit code !ERR!).
echo Scroll up for the exact message, fix the issue, and try again.

:fail
echo.
echo Failed to build slider_step_by_step.zip. See messages above for details.
pause
exit /b 1
