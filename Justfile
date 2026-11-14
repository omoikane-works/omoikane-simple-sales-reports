# List available recipes.
default:
    @just --list

# Install frontend dependencies.
install:
    @echo ">>> Installing OSSR development dependencies..."
    npm ci
    composer install
    composer install --working-dir=tools/wp-cli

# Build Vue admin assets.
build:
    @echo ">>> Running build Vue admin assets..."
    npm run build

# Run frontend type checking.
typecheck:
    @echo ">>> Running frontend type check..."
    npm run typecheck

# Build distribution ZIP.
package:
    @echo ">>> Building distribution zip file..."
    composer build

# Check PHP syntax.
lint-php:
    @echo ">>> Checking PHP syntax..."
    composer lint

# Check coding standards.
phpcs:
    @echo ">>> Checking coding standards..."
    composer phpcs

# Run phpcbf
format:
    @echo ">>> Running format"
    composer phpcbf

# Run static analysis.
phpstan:
    @echo ">>> Running static analysis..."
    composer phpstan

# Run tests.
test:
    @echo ">>> Running unittest..."
    composer test

# Run unittest coverage
coverage:
    @echo ">>> Calculating unittest coverage..."
    composer coverage

# Run unittest coverage-html
coverage-html:
    @echo ">>> Calculating unittest coverage(html)..."
    composer coverage-html

# Run make pot
make-pot:
    @echo ">>> Making pot file"
    composer make-pot

# Run update po
update-po:
    @echo ">>> Updating po files"
    composer update-po

# Run make mo file
make-mo:
    @echo ">>> Making mo files"
    composer make-mo

# Run あl checks
check: lint-php phpcs phpstan test typecheck
