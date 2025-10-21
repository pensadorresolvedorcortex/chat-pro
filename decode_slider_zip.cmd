@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
set "BASE64_FILE=%SCRIPT_DIR%slider_step_by_step.zip.base64"
set "ZIP_FILE=%SCRIPT_DIR%slider_step_by_step.zip"

if not exist "%BASE64_FILE%" (
    echo Could not find %%BASE64_FILE%% next to this script.
    echo Make sure slider_step_by_step.zip.base64 is in the same folder.
    pause
    exit /b 1
)

echo Creating slider_step_by_step.zip from the base64 source...
certutil -f -decode "%BASE64_FILE%" "%ZIP_FILE%" >nul
if errorlevel 1 (
    echo Failed to decode the archive. CertUtil returned an error.
    echo Try running this script from a Command Prompt that has access to certutil.exe.
    pause
    exit /b 1
)

echo Done! slider_step_by_step.zip is ready for import into Slider Revolution.
pause
exit /b 0
