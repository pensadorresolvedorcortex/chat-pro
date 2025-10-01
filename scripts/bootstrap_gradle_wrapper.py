#!/usr/bin/env python3
"""Download the Gradle wrapper JAR on demand.

The repository keeps binary artifacts out of version control, so this helper
retrieves the official wrapper from the configured distribution before running
Android builds. If the JAR already exists, the script exits without changes.
"""

from __future__ import annotations

import hashlib
import pathlib
import sys
import tempfile
import urllib.request
import zipfile


REPO_ROOT = pathlib.Path(__file__).resolve().parents[1]
WRAPPER_JAR = REPO_ROOT / "android" / "gradle" / "wrapper" / "gradle-wrapper.jar"
WRAPPER_PROPERTIES = REPO_ROOT / "android" / "gradle" / "wrapper" / "gradle-wrapper.properties"


def _read_distribution_url() -> str:
    """Parse the distribution URL from gradle-wrapper.properties."""
    with WRAPPER_PROPERTIES.open("r", encoding="utf-8") as handle:
        for line in handle:
            line = line.strip()
            if line.startswith("distributionUrl="):
                return line.split("=", 1)[1].replace("\\", "")
    raise RuntimeError("distributionUrl not found in gradle-wrapper.properties")


def _download_file(url: str, destination: pathlib.Path) -> None:
    """Download ``url`` to ``destination`` streaming the contents to disk."""
    with urllib.request.urlopen(url) as response, destination.open("wb") as target:
        chunk = response.read(8192)
        while chunk:
            target.write(chunk)
            chunk = response.read(8192)


def _extract_wrapper(jar_destination: pathlib.Path, distribution_zip: pathlib.Path) -> None:
    """Extract the wrapper JAR from the Gradle distribution archive."""
    with zipfile.ZipFile(distribution_zip) as archive:
        candidates = [name for name in archive.namelist() if name.endswith("gradle-wrapper.jar")]
        if not candidates:
            raise RuntimeError("gradle-wrapper.jar not found inside distribution archive")
        if len(candidates) > 1:
            raise RuntimeError("multiple gradle-wrapper.jar entries found; aborting")
        member = candidates[0]
        with archive.open(member) as source, jar_destination.open("wb") as target:
            target.write(source.read())


def _sha256(path: pathlib.Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for block in iter(lambda: handle.read(8192), b""):
            digest.update(block)
    return digest.hexdigest()


def main() -> int:
    if WRAPPER_JAR.exists():
        print("Gradle wrapper JAR already present:", WRAPPER_JAR)
        return 0

    distribution_url = _read_distribution_url()
    print("Downloading Gradle distribution from", distribution_url)

    with tempfile.TemporaryDirectory() as tmp_dir:
        tmp_path = pathlib.Path(tmp_dir) / "gradle-distribution.zip"
        _download_file(distribution_url, tmp_path)
        _extract_wrapper(WRAPPER_JAR, tmp_path)

    print("Gradle wrapper JAR written to", WRAPPER_JAR)
    print("SHA-256:", _sha256(WRAPPER_JAR))
    return 0


if __name__ == "__main__":
    sys.exit(main())
