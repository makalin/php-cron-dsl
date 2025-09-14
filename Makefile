.PHONY: install test test-coverage cs-fix cs-check phpstan quality clean build

# Install dependencies
install:
	composer install

# Run tests
test:
	composer test

# Run tests with coverage
test-coverage:
	composer test-coverage

# Fix code style
cs-fix:
	composer cs-fix

# Check code style
cs-check:
	composer cs-check

# Run PHPStan
phpstan:
	composer phpstan

# Run all quality checks
quality:
	composer quality

# Clean build artifacts
clean:
	rm -rf build/
	rm -rf coverage/
	rm -rf vendor/

# Build PHAR (if needed)
build: clean install test
	@echo "Build complete"

# Example: Compile config to systemd units
compile-example:
	vendor/bin/cron-dsl compile config/cron.php --out build/systemd --prefix app- --force

# Example: Dry run compilation
dry-run:
	vendor/bin/cron-dsl compile config/cron.php --dry-run

# Validate configuration
validate:
	vendor/bin/cron-dsl compile config/cron.php --validate