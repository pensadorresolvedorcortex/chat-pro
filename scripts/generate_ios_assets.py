#!/usr/bin/env python3
"""Generate deterministic placeholder assets for the iOS runner.

The script recreates the app icon set and launch images that would normally be
tracked as binary files. It keeps the repository export-friendly while still
allowing iOS builds to produce the required assets locally.
"""

from __future__ import annotations

from pathlib import Path
from typing import Iterable, Tuple

try:
    from PIL import Image, ImageDraw
except ImportError as exc:  # pragma: no cover - import guard for helpful error
    raise SystemExit(
        "Missing Pillow dependency. Install with `pip install Pillow` or run "
        "`pip install -r requirements-dev.txt`."
    ) from exc


APP_ICON_DIR = Path("flutter/ios/Runner/Assets.xcassets/AppIcon.appiconset")
LAUNCH_IMAGE_DIR = Path("flutter/ios/Runner/Assets.xcassets/LaunchImage.imageset")


APP_ICON_SPECS: Tuple[Tuple[str, int], ...] = (
    ("Icon-App-20x20@1x.png", 20),
    ("Icon-App-20x20@2x.png", 40),
    ("Icon-App-20x20@3x.png", 60),
    ("Icon-App-29x29@1x.png", 29),
    ("Icon-App-29x29@2x.png", 58),
    ("Icon-App-29x29@3x.png", 87),
    ("Icon-App-40x40@1x.png", 40),
    ("Icon-App-40x40@2x.png", 80),
    ("Icon-App-40x40@3x.png", 120),
    ("Icon-App-60x60@2x.png", 120),
    ("Icon-App-60x60@3x.png", 180),
    ("Icon-App-76x76@1x.png", 76),
    ("Icon-App-76x76@2x.png", 152),
    ("Icon-App-83.5x83.5@2x.png", 167),
    ("Icon-App-1024x1024@1x.png", 1024),
)

LAUNCH_IMAGE_SPECS: Tuple[Tuple[str, int], ...] = (
    ("LaunchImage.png", 1024),
    ("LaunchImage@2x.png", 2048),
    ("LaunchImage@3x.png", 3072),
)


PRIMARY = "#6645f6"
SECONDARY = "#1dd3c4"
TERTIARY = "#e5be49"
BACKGROUND = "#0c3c64"


def ensure_directory(path: Path) -> None:
    path.mkdir(parents=True, exist_ok=True)


def create_app_icon(path: Path, size: int) -> None:
    img = Image.new("RGBA", (size, size), PRIMARY)
    draw = ImageDraw.Draw(img)

    inset = max(int(size * 0.08), 2)
    outer_radius = max(int(size * 0.22), 8)
    middle_radius = max(int(size * 0.18), 6)
    draw.rounded_rectangle(
        (inset, inset, size - inset, size - inset),
        radius=outer_radius,
        fill=SECONDARY,
    )

    draw.rounded_rectangle(
        (
            inset * 2,
            inset * 2,
            size - inset * 2,
            size - inset * 2,
        ),
        radius=middle_radius,
        outline=BACKGROUND,
        width=max(int(size * 0.05), 2),
    )

    draw.ellipse(
        (
            size * 0.3,
            size * 0.3,
            size * 0.7,
            size * 0.7,
        ),
        fill=TERTIARY,
    )

    draw.ellipse(
        (
            size * 0.42,
            size * 0.42,
            size * 0.65,
            size * 0.65,
        ),
        fill=PRIMARY,
    )

    img.save(path, format="PNG")


def create_launch_image(path: Path, size: int) -> None:
    img = Image.new("RGB", (size, size), BACKGROUND)
    draw = ImageDraw.Draw(img)

    band_height = size // 6
    for index, color in enumerate((PRIMARY, SECONDARY, TERTIARY)):
        top = index * band_height
        draw.rectangle((0, top, size, top + band_height * 2), fill=color, width=0)

    circle_radius = size * 0.18
    center = size / 2
    draw.ellipse(
        (
            center - circle_radius,
            center - circle_radius,
            center + circle_radius,
            center + circle_radius,
        ),
        fill=BACKGROUND,
    )

    draw.ellipse(
        (
            center - circle_radius * 0.55,
            center - circle_radius * 0.55,
            center + circle_radius * 0.55,
            center + circle_radius * 0.55,
        ),
        fill=SECONDARY,
    )

    img.save(path, format="PNG")


def generate_assets(specs: Iterable[Tuple[str, int]], directory: Path, factory) -> None:
    ensure_directory(directory)
    for filename, size in specs:
        target = directory / filename
        factory(target, size)


def main() -> None:
    generate_assets(APP_ICON_SPECS, APP_ICON_DIR, create_app_icon)
    generate_assets(LAUNCH_IMAGE_SPECS, LAUNCH_IMAGE_DIR, create_launch_image)


if __name__ == "__main__":
    main()
