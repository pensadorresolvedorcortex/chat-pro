#!/usr/bin/env bash

set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_file="${root_dir}/blog-privilege-ai.zip"

command -v zip >/dev/null 2>&1 || {
	echo "Erro: o comando 'zip' não está instalado." >&2
	exit 1
}

rm -f "${output_file}"
(
	cd "${root_dir}"
	zip -qr "${output_file}" blog-privilege-ai
)

echo "Pacote criado em ${output_file}"
