#!/usr/bin/env bash

set -euo pipefail

PLUGIN_SLUG="welcart-simple-report-sales"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT_DIR}/build"
PACKAGE_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"
ZIP_FILE="${BUILD_DIR}/${PLUGIN_SLUG}.zip"

cd "${ROOT_DIR}"

if [[ ! -f "${ROOT_DIR}/welcart-simple-report-sales.php" ]]; then
	echo "Error: plugin main file not found."
	exit 1
fi

if [[ ! -f "${ROOT_DIR}/composer.json" ]]; then
	echo "Error: composer.json not found."
	exit 1
fi

command -v rsync >/dev/null 2>&1 || {
	echo "Error: rsync is required."
	exit 1
}

command -v composer >/dev/null 2>&1 || {
	echo "Error: composer is required."
	exit 1
}

command -v zip >/dev/null 2>&1 || {
	echo "Error: zip is required."
	exit 1
}

command -v unzip >/dev/null 2>&1 || {
	echo "Error: unzip is required."
	exit 1
}

rm -rf "${BUILD_DIR}"
mkdir -p "${PACKAGE_DIR}"

rsync -av \
	--exclude='.git' \
	--exclude='.github' \
	--exclude='.DS_Store' \
	--exclude='*/.DS_Store' \
	--exclude='.phpunit.cache' \
	--exclude='build' \
	--exclude='coverage' \
	--exclude='node_modules' \
	--exclude='tests' \
	--exclude='vendor' \
	--exclude='.vscode' \
	--exclude='.mise.toml' \
	--exclude='mise.toml' \
	--exclude='.editorconfig' \
	--exclude='.gitignore' \
	--exclude='cspell.json' \
	--exclude='phpcs.xml.dist' \
	--exclude='phpstan.neon.dist' \
	--exclude='phpstan-bootstrap.php' \
	--exclude='phpunit.xml.dist' \
	--exclude='*.bak' \
	--exclude='bin' \
	--exclude='.wordpress-org' \
	"${ROOT_DIR}/" "${PACKAGE_DIR}/"

cd "${PACKAGE_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction

cd "${BUILD_DIR}"
rm -f "${ZIP_FILE}"
zip -r "${ZIP_FILE}" "${PLUGIN_SLUG}"

echo "Built: ${ZIP_FILE}"

if unzip -l "${ZIP_FILE}" | grep -E 'DS_Store|debug|phpunit|phpstan|phpcs|tests|node_modules'; then
	echo "Error: unwanted files found in zip."
	exit 1
fi

unzip -l "${ZIP_FILE}" | grep "${PLUGIN_SLUG}/vendor/autoload.php" >/dev/null
unzip -l "${ZIP_FILE}" | grep "${PLUGIN_SLUG}/templates/default-sales-report@1.0.0.mustache" >/dev/null
unzip -l "${ZIP_FILE}" | grep "${PLUGIN_SLUG}/languages/welcart-simple-report-sales-ja.mo" >/dev/null
