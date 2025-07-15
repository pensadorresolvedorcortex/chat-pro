# Chat Pro Plugin

This repository contains the source code for the **Ultimate Dashboard PRO** plugin in an unzipped form. Binary translation files and the distributable zip archive are not kept under version control to avoid large binary diffs.

## Building

Run `./build.sh` to compile translation files and create `ultimate-dashboard-pro.zip` for installation in WordPress.

```bash
./build.sh
```

The script uses `msgfmt` (from `gettext`) to generate `.mo` files from the `.po` sources and then packages everything into `ultimate-dashboard-pro.zip`.
