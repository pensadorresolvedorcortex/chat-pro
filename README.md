# Slider Revolution step-by-step template

This repository contains a three-step onboarding Slider Revolution template.
Because binary attachments are not accepted, the slider export is stored as plain
text and a small helper script produces the `.zip` archive that Slider Revolution
expects during import.

## How to build the importable archive

Choose the option that matches your operating system:

### Windows

1. Double-click `build_slider_zip.bat`. It uses PowerShell to build
   `slider_step_by_step.zip` without requiring you to install anything extra.
2. If PowerShell is unavailable on your machine, the helper falls back to Python.
   Install [Python 3.8 or newer](https://www.python.org/downloads/) and run the
   script again if that happens.
3. When the window reports that `slider_step_by_step.zip` was generated, import
   that file into Slider Revolution.

### macOS / Linux

1. Make sure Python 3.8+ is available (check with `python3 --version`).
2. Run the helper script from the repository root:
   ```bash
   python3 build_slider_zip.py
   ```
3. Import the generated `slider_step_by_step.zip` file into Slider Revolution.

The resulting archive contains the `slider_export.txt` manifest with embedded
inline SVG backgrounds, so there are no binary image files to manage.
