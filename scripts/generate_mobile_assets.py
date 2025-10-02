#!/usr/bin/env python3
"""Gera ícones e telas de lançamento mobile a partir da marca vetorial.

O script desenha o ícone da Academia da Comunicação usando apenas rotinas da
biblioteca padrão e Pillow para rasterização, evitando o versionamento de
arquivos binários no repositório. As imagens produzidas alimentam os assets
do iOS (`AppIcon.appiconset` e `LaunchImage.imageset`) e os *mipmaps* do
Android (`android/app/src/main/res/mipmap-*`).
"""

from __future__ import annotations

import sys
from pathlib import Path

try:
    from PIL import Image, ImageChops, ImageDraw, ImageOps
except ImportError as exc:  # pragma: no cover - instrução amigável de dependência
    sys.stderr.write(
        "Pillow não está instalado. Execute `pip install pillow` e rode o script novamente.\n"
    )
    raise SystemExit(1) from exc

ROOT_DIR = Path(__file__).resolve().parent.parent
IOS_ASSETS = ROOT_DIR / "flutter" / "ios" / "Runner" / "Assets.xcassets"
APP_ICON_DIR = IOS_ASSETS / "AppIcon.appiconset"
LAUNCH_IMAGE_DIR = IOS_ASSETS / "LaunchImage.imageset"
ANDROID_RES = ROOT_DIR / "android" / "app" / "src" / "main" / "res"
ANDROID_MIPMAP_SPECS = {
    "mipmap-mdpi": 48,
    "mipmap-hdpi": 72,
    "mipmap-xhdpi": 96,
    "mipmap-xxhdpi": 144,
    "mipmap-xxxhdpi": 192,
}

PRIMARY = (0x66, 0x45, 0xF6)
SECONDARY = (0x1D, 0xD3, 0xC4)
TERTIARY = (0xE5, 0xBE, 0x49)
WHITE = (0xFF, 0xFF, 0xFF)


def _write_bytes(path: Path, data: bytes) -> bool:
    path.parent.mkdir(parents=True, exist_ok=True)
    if path.exists() and path.read_bytes() == data:
        return False
    path.write_bytes(data)
    return True


def _make_gradient(
    width: int,
    height: int,
    start: tuple[int, int, int],
    end: tuple[int, int, int],
    *,
    orientation: str = "diagonal",
    alpha: bool = True,
) -> Image.Image:
    if orientation not in {"diagonal", "horizontal", "vertical"}:
        raise ValueError(f"Orientação desconhecida: {orientation}")

    if orientation == "horizontal":
        base = Image.linear_gradient("L").resize((width, 1), Image.BILINEAR)
        gradient = base.resize((width, height))
    elif orientation == "vertical":
        base = Image.linear_gradient("L").rotate(90, expand=True).resize((1, height), Image.BILINEAR)
        gradient = base.resize((width, height))
    else:
        horizontal = Image.linear_gradient("L").resize((width, height), Image.BILINEAR)
        vertical = Image.linear_gradient("L").rotate(90, expand=True).resize((width, height), Image.BILINEAR)
        gradient = ImageChops.add(horizontal, vertical).point(lambda value: value // 2)

    colored = ImageOps.colorize(gradient, black=start, white=end)
    if alpha:
        rgba = colored.convert("RGBA")
        rgba.putalpha(255)
        return rgba
    return colored


def _draw_background_square(size: int, scale: float) -> Image.Image:
    square_size = max(1, int(round(176 * scale)))
    gradient = _make_gradient(square_size, square_size, PRIMARY, SECONDARY)

    radius = int(round(48 * scale))
    mask = Image.new("L", (square_size, square_size), 0)
    mask_draw = ImageDraw.Draw(mask)
    mask_draw.rounded_rectangle([(0, 0), (square_size, square_size)], radius=radius, fill=255)

    background = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    offset = (int(round(32 * scale)), int(round(32 * scale)))
    background.paste(gradient, offset, mask)
    return background


def _draw_polygon(draw: ImageDraw.ImageDraw, points: list[tuple[float, float]], scale: float) -> None:
    scaled = [(int(round(x * scale)), int(round(y * scale))) for x, y in points]
    draw.polygon(scaled, fill=WHITE + (242,))


def _draw_bar(image: Image.Image, scale: float) -> None:
    width = max(1, int(round(36 * scale)))
    height = max(1, int(round(16 * scale)))
    start_x = int(round(102 * scale))
    start_y = int(round(126 * scale))

    bar = _make_gradient(width, height, SECONDARY, TERTIARY, orientation="horizontal")

    radius = int(round(8 * scale))
    mask = Image.new("L", (width, height), 0)
    mask_draw = ImageDraw.Draw(mask)
    mask_draw.rounded_rectangle([(0, 0), (width, height)], radius=radius, fill=255)

    image.paste(bar, (start_x, start_y), mask)


def _draw_circle(draw: ImageDraw.ImageDraw, center: tuple[float, float], radius: float, scale: float) -> None:
    cx = int(round(center[0] * scale))
    cy = int(round(center[1] * scale))
    r = int(round(radius * scale))
    bbox = [(cx - r, cy - r), (cx + r, cy + r)]
    draw.ellipse(bbox, fill=TERTIARY + (255,))


def render_logo(size: int) -> Image.Image:
    """Renderiza o logotipo quadrado dimensionado para ``size`` pixels."""

    scale = size / 240.0
    image = _draw_background_square(size, scale)
    draw = ImageDraw.Draw(image, "RGBA")

    _draw_polygon(
        draw,
        [
            (120, 52),
            (184, 188),
            (160, 188),
            (140, 144),
            (100, 144),
            (80, 188),
            (56, 188),
        ],
        scale,
    )
    _draw_bar(image, scale)
    _draw_circle(draw, (172, 84), 12, scale)

    return image


def render_round_logo(size: int) -> Image.Image:
    """Renderiza o logotipo adaptado para ícones redondos do Android."""

    square = render_logo(size)
    mask = Image.new("L", (size, size), 0)
    ImageDraw.Draw(mask).ellipse([(0, 0), (size, size)], fill=255)

    rounded = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    rounded.paste(square, (0, 0), mask)
    return rounded


def render_launch(size: int) -> Image.Image:
    """Gera a imagem de splash centrando o ícone sobre um gradiente."""

    background = _make_gradient(size, size, PRIMARY, SECONDARY, alpha=False)

    logo_size = int(round(size * 0.45))
    logo = render_logo(logo_size)
    offset = ((size - logo_size) // 2, (size - logo_size) // 2)
    background.paste(logo, offset, logo)
    return background


def _parse_icon_size(filename: str) -> int:
    base = filename.split("@", 1)[0]
    dimension = base.rsplit("-", 1)[-1]
    width_part = dimension.split("x", 1)[0]
    scale_part = filename.split("@", 1)[-1].split("x", 1)[0]
    width = float(width_part)
    scale = float(scale_part)
    return int(round(width * scale))


def generate_icons() -> int:
    changed = 0
    for entry in APP_ICON_DIR.glob("*.png"):
        entry.unlink()

    contents = APP_ICON_DIR / "Contents.json"
    if not contents.exists():
        raise FileNotFoundError(
            "Contents.json não encontrado em AppIcon.appiconset. Rode o script no repositório correto."
        )

    for filename in sorted({
        "Icon-App-20x20@1x.png",
        "Icon-App-20x20@2x.png",
        "Icon-App-20x20@3x.png",
        "Icon-App-29x29@1x.png",
        "Icon-App-29x29@2x.png",
        "Icon-App-29x29@3x.png",
        "Icon-App-40x40@1x.png",
        "Icon-App-40x40@2x.png",
        "Icon-App-40x40@3x.png",
        "Icon-App-60x60@2x.png",
        "Icon-App-60x60@3x.png",
        "Icon-App-76x76@1x.png",
        "Icon-App-76x76@2x.png",
        "Icon-App-83.5x83.5@2x.png",
        "Icon-App-1024x1024@1x.png",
    }):
        size = _parse_icon_size(filename)
        icon = render_logo(size)
        target_path = APP_ICON_DIR / filename
        changed += int(_write_bytes(target_path, _image_bytes(icon)))
    return changed


def generate_launch_images() -> int:
    changed = 0
    for entry in LAUNCH_IMAGE_DIR.glob("*.png"):
        entry.unlink()

    specs = {
        "LaunchImage.png": 1024,
        "LaunchImage@2x.png": 2048,
        "LaunchImage@3x.png": 3072,
    }
    for filename, size in specs.items():
        image = render_launch(size)
        target_path = LAUNCH_IMAGE_DIR / filename
        changed += int(_write_bytes(target_path, _image_bytes(image)))
    return changed


def generate_android_mipmaps() -> int:
    changed = 0
    for folder, size in ANDROID_MIPMAP_SPECS.items():
        target_dir = ANDROID_RES / folder
        target_dir.mkdir(parents=True, exist_ok=True)

        square_icon = render_logo(size)
        changed += int(
            _write_bytes(target_dir / "ic_launcher.png", _image_bytes(square_icon))
        )

        round_icon = render_round_logo(size)
        changed += int(
            _write_bytes(
                target_dir / "ic_launcher_round.png", _image_bytes(round_icon)
            )
        )

    return changed


def _image_bytes(image: Image.Image) -> bytes:
    from io import BytesIO

    buffer = BytesIO()
    image.save(buffer, format="PNG")
    return buffer.getvalue()


def main() -> None:
    if not APP_ICON_DIR.exists():
        raise SystemExit(
            "Diretório de assets do iOS não encontrado. Execute a partir da raiz do repositório."
        )
    if not ANDROID_RES.exists():
        raise SystemExit(
            "Diretório de recursos do Android não encontrado. Execute a partir da raiz do repositório."
        )

    ios_icons_changed = generate_icons()
    ios_launch_changed = generate_launch_images()
    android_changed = generate_android_mipmaps()

    total = ios_icons_changed + ios_launch_changed + android_changed
    if total == 0:
        print("Nenhuma imagem atualizada – os assets já estavam em dia.")
    else:
        print(f"Assets gerados/atualizados: {total}")


if __name__ == "__main__":
    main()
