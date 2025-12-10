# Contributing to Laravel AI Metering

Thank you for considering contributing to the Laravel AI Metering package! This guide will help you understand how you can contribute effectively.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Commit Message Guidelines](#commit-message-guidelines)

## Code of Conduct

This project adheres to a Code of Conduct that all contributors are expected to follow. Please read [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) before contributing.

## How Can I Contribute?

### Reporting Bugs

If you discover a bug in the package, please open an issue on [GitHub](https://github.com/ajooda/laravel-ai-metering/issues). Please include as much information as possible:

- **Steps to reproduce** the bug
- **Expected behavior** - what should happen
- **Actual behavior** - what actually happens
- **Environment details**:
  - PHP version
  - Laravel version
  - Package version
  - Database type and version
- **Error messages** or stack traces (if applicable)
- **Code examples** that demonstrate the issue

### Suggesting Features

Feature requests are welcome! Please open an issue on GitHub with:

- A clear description of the feature
- Use cases and examples
- Why this feature would be useful
- Any potential implementation ideas (optional)

### Pull Requests

We welcome pull requests! Please follow the process outlined below.

## Development Setup

1. **Fork the repository** on GitHub

2. **Clone your fork**:
   ```bash
   git clone https://github.com/ajooda/laravel-ai-metering.git
   cd laravel-ai-metering
   ```

3. **Install dependencies**:
   ```bash
   composer install
   ```

4. **Set up testing environment**:
   The package uses Orchestra Testbench for testing. No additional setup is required.

5. **Run tests** to verify everything works:
   ```bash
   composer test
   ```

## Coding Standards

We follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard and the [Laravel coding style](https://laravel.com/docs/contributions#coding-style).

### Code Formatting

We use [Laravel Pint](https://laravel.com/docs/pint) for code formatting. Before committing, run:

```bash
vendor/bin/pint
```

Or if you have it installed globally:

```bash
pint
```

This will automatically format your code according to our standards.

### Code Style Checklist

- ✅ Follow PSR-12 coding standards
- ✅ Use type hints for parameters and return types
- ✅ Add PHPDoc blocks for public methods
- ✅ Use meaningful variable and method names
- ✅ Keep methods focused and small
- ✅ Add comments for complex logic

## Testing

### Running Tests

Run all tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

View coverage report in `coverage/index.html`

### Writing Tests

- Write tests for all new features
- Write tests for bug fixes
- Aim for high test coverage
- Tests should be clear and well-documented
- Use descriptive test method names

### Test Structure

- **Unit tests**: Test individual classes and methods in isolation
- **Feature tests**: Test complete workflows and integrations

Place tests in the appropriate directory:
- `tests/Unit/` - Unit tests
- `tests/Feature/` - Feature tests

### Laravel Version Compatibility

When adding features, ensure compatibility with:
- Laravel 10.x
- Laravel 11.x
- Laravel 12.x

Test your changes against multiple Laravel versions if possible.

## Pull Request Process

1. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/your-bug-fix
   ```

2. **Make your changes**:
   - Write your code
   - Add tests
   - Update documentation if needed
   - Run Pint to format code
   - Run tests to ensure everything passes

3. **Commit your changes**:
   - Follow [commit message guidelines](#commit-message-guidelines)
   - Make small, focused commits
   - Each commit should represent a logical change

4. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

5. **Open a Pull Request**:
   - Use a clear, descriptive title
   - Fill out the PR template (if available)
   - Reference any related issues
   - Describe what your PR does and why
   - Include screenshots if UI changes are involved

### PR Checklist

Before submitting your PR, ensure:

- [ ] Code follows PSR-12 standards
- [ ] Code has been formatted with Pint
- [ ] All tests pass (`composer test`)
- [ ] New tests have been added for new features
- [ ] Documentation has been updated (README, code comments)
- [ ] CHANGELOG.md has been updated (for user-facing changes)
- [ ] No linter errors
- [ ] Commit messages follow the guidelines

### What Makes a Good PR?

- **Clear description**: Explain what and why
- **Small scope**: One feature or fix per PR
- **Well-tested**: Includes tests and all tests pass
- **Documented**: Code comments and updated docs
- **Backward compatible**: Doesn't break existing functionality (unless intentional)

## Commit Message Guidelines

We follow [Conventional Commits](https://www.conventionalcommits.org/) format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```
feat(billing): add support for credit-based billing

Add credit wallet functionality with transaction history
and overdraft protection.

Closes #123
```

```
fix(limiter): correct period calculation for rolling periods

The rolling period calculation was incorrectly using
calendar boundaries instead of subscription start date.
```

```
docs(readme): add multi-tenancy setup guide

Add comprehensive guide for setting up tenant resolvers
and using the package with multi-tenant applications.
```

## Documentation

When adding features, please update:

- **README.md**: Add usage examples and documentation
- **CHANGELOG.md**: Add entry under "Unreleased" section
- **Code comments**: Add PHPDoc blocks for new methods/classes
- **Inline comments**: Explain complex logic

## Questions?

If you have questions about contributing:

- Open an issue on GitHub
- Check existing issues and discussions
- Review the codebase and existing tests

Thank you for contributing! 🎉
