# Contributing to PHP Cron DSL

Thank you for your interest in contributing to PHP Cron DSL! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Making Changes](#making-changes)
- [Testing](#testing)
- [Code Style](#code-style)
- [Submitting Changes](#submitting-changes)
- [Release Process](#release-process)

## Code of Conduct

This project follows the [Contributor Covenant](https://www.contributor-covenant.org/) code of conduct. By participating, you agree to uphold this code.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/php-cron-dsl.git
   cd php-cron-dsl
   ```

## Development Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run the test suite to ensure everything works:
   ```bash
   composer test
   ```

3. Run code quality checks:
   ```bash
   composer quality
   ```

## Making Changes

### Branch Strategy

- Create a feature branch from `main`
- Use descriptive branch names (e.g., `feature/cron-step-expressions`, `fix/timer-validation`)
- Keep branches focused on a single feature or bug fix

### Code Structure

The project follows PSR-4 autoloading:

```
src/
├── Compiler/          # Main compiler logic
├── Emitter/           # Output emitters (filesystem, stdout)
├── Parser/            # Cron expression parsing
└── Unit/              # Systemd unit classes
```

### Adding New Features

1. **Cron Expression Support**: Add new patterns to `CronParser`
2. **Systemd Features**: Extend `Timer` or `Service` classes
3. **CLI Options**: Add new options to the `CompileCommand`
4. **Emitters**: Create new emitter implementations

### Example: Adding a New Cron Feature

```php
// In CronParser.php
private function parseSpecialExpression(string $expression): string
{
    // Add support for new special expressions
    if ($expression === '@custom') {
        return '0 0 * * *'; // Convert to standard cron
    }
    // ...
}
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test suite
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Integration/
```

### Writing Tests

- **Unit Tests**: Test individual classes and methods
- **Integration Tests**: Test end-to-end workflows
- **Test Coverage**: Aim for high coverage, especially for core functionality

### Test Structure

```php
<?php

declare(strict_types=1);

namespace CronDSL\Tests\Unit;

use CronDSL\Parser\CronParser;
use PHPUnit\Framework\TestCase;

class CronParserTest extends TestCase
{
    private CronParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CronParser();
    }

    public function testNewFeature(): void
    {
        // Test implementation
        $result = $this->parser->parseToOnCalendar('*/5 * * * *');
        $this->assertEquals('expected', $result);
    }
}
```

## Code Style

### PHP CS Fixer

The project uses PHP CS Fixer with PSR-12 rules:

```bash
# Check code style
composer cs-check

# Fix code style issues
composer cs-fix
```

### PHPStan

Static analysis is performed with PHPStan:

```bash
composer phpstan
```

### Code Style Guidelines

- Use strict typing (`declare(strict_types=1)`)
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Add PHPDoc comments for public methods
- Keep methods focused and small
- Use dependency injection where appropriate

## Submitting Changes

### Pull Request Process

1. **Create a Pull Request** from your feature branch
2. **Provide a clear description** of changes
3. **Reference any related issues**
4. **Ensure all tests pass**
5. **Update documentation** if needed

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass
- [ ] New tests added
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
```

### Review Process

- Maintainers will review your PR
- Address any feedback promptly
- Keep PRs focused and reasonably sized
- Respond to review comments constructively

## Release Process

### Versioning

The project follows [Semantic Versioning](https://semver.org/):
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

- [ ] Update `CHANGELOG.md`
- [ ] Update version in `composer.json`
- [ ] Create release tag
- [ ] Update documentation
- [ ] Test release artifacts

## Documentation

### README Updates

When adding features, update the README with:
- New configuration options
- Example usage
- CLI options
- Breaking changes

### Code Documentation

- Add PHPDoc comments for public methods
- Include examples in complex methods
- Document any breaking changes

## Getting Help

- **Issues**: Use GitHub Issues for bug reports and feature requests
- **Discussions**: Use GitHub Discussions for questions and ideas
- **Email**: Contact maintainers for sensitive issues

## Recognition

Contributors will be recognized in:
- `CHANGELOG.md`
- Release notes
- Project documentation

Thank you for contributing to PHP Cron DSL!