@echo off
setlocal

rem Prefer the "py" launcher when available (standard on Windows installations of Python).
set "PYTHON_CMD=py -3"
%PYTHON_CMD% --version >nul 2>&1
if errorlevel 1 (
    set "PYTHON_CMD=python"
)

%PYTHON_CMD% "%~dp0build_slider_zip.py"
if errorlevel 1 (
    echo.
    echo Failed to build slider_step_by_step.zip. Make sure Python 3 is installed and
    echo available in your PATH (https://www.python.org/downloads/).
    exit /b 1
)

echo.
echo slider_step_by_step.zip generated next to this script. You can now import it into Slider Revolution.
pause
