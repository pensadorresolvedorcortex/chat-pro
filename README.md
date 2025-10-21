# Slider Revolution step-by-step template

This repository contains a three-step onboarding Slider Revolution template.
Because binary attachments are not accepted, the slider export is stored as
plain text. You now have three different ways to turn that source into the
`.zip` archive that Slider Revolution expects during import.

## Option 1: one-click decode on Windows (recommended)

1. Place `decode_slider_zip.cmd` and `slider_step_by_step.zip.base64` in the same
   folder (they already are when you clone or download this repo).
2. Double-click `decode_slider_zip.cmd`.
3. When the window reports success, import the newly created
   `slider_step_by_step.zip` into Slider Revolution.

This helper only relies on the built-in `certutil` tool, so it works even when
PowerShell execution policies block scripts.

## Option 2: Build with PowerShell (Windows)

1. Double-click `build_slider_zip.bat`.
2. If PowerShell can run, it will create `slider_step_by_step.zip` using the
   `.ps1` helper. If PowerShell is blocked, the batch file will fall back to
   Python 3 if it is available.
3. Import `slider_step_by_step.zip` into Slider Revolution once the window
   reports success.

## Option 3: Build with Python (macOS, Linux, or Windows)

1. Make sure Python 3.8+ is available (check with `python3 --version`).
2. Run the helper script from the repository root:
   ```bash
   python3 build_slider_zip.py
   ```
3. Import the generated `slider_step_by_step.zip` file into Slider Revolution.

The resulting archive contains the `slider_export.txt` manifest with embedded
inline SVG backgrounds, so there are no binary image files to manage.
