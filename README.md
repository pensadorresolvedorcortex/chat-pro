# Slider Revolution step-by-step template

This repository contains a three-step onboarding Slider Revolution template.
Because binary attachments are not accepted, the slider export is stored as plain
text and a small helper script produces the `.zip` archive that Slider Revolution
expects during import.

## How to build the importable archive

1. Make sure you have Python 3.8+ available.
2. Run the helper script from the repository root:
   ```bash
   python build_slider_zip.py
   ```
3. Import the generated `slider_step_by_step.zip` file into Slider Revolution.

The resulting archive contains the `slider_export.txt` manifest with embedded
inline SVG backgrounds, so there are no binary image files to manage.
