# Contributing to Laravel Package Template

Thank you for considering contributing to Laravel Package Template! We welcome contributions from the community.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue on GitHub with the following information:

- A clear and descriptive title
- Steps to reproduce the issue
- Expected behavior vs. actual behavior
- Your environment (PHP version, Laravel version, etc.)
- Any relevant code snippets or error messages

### Suggesting Enhancements

We welcome suggestions for new features or improvements! Please create an issue with:

- A clear description of the enhancement
- Why it would be useful
- Any potential implementation ideas

### Pull Requests

1. **Fork the repository** and create a new branch from `main`
2. **Make your changes** following our coding standards
3. **Write tests** for your changes when applicable
4. **Run the test suite** to ensure nothing is broken
5. **Run code quality tools** (Pint and PHPStan)
6. **Update documentation** if needed
7. **Submit a pull request** with a clear description of your changes

#### Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/laravel-package-template.git
cd laravel-package-template

# Install dependencies
composer install

# Run tests
composer test

# Run code styling
composer lint

# Run static analysis
composer analyse
```

#### Coding Standards

- Follow **PSR-12** coding standards
- Use **strict types** (`declare(strict_types=1);`)
- Write **clear, descriptive variable and method names**
- Add **DocBlocks** for classes and methods
- Ensure code passes **PHPStan level 10** analysis
- Format code with **Laravel Pint**

#### Testing Guidelines

- Write tests for new features
- Ensure all tests pass before submitting PR
- Aim for good test coverage
- Use descriptive test method names

#### Commit Messages

- Use clear and descriptive commit messages
- Start with a verb in present tense (e.g., "Add", "Fix", "Update")
- Reference issues when applicable (e.g., "Fix #123")

Example:

```text
Add support for custom configuration options

- Added new config options for flexibility
- Updated documentation with examples
- Added tests for new functionality

Fixes #123
```

## Code of Conduct

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

**Positive behavior includes:**

- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable behavior includes:**

- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without permission
- Other conduct which could reasonably be considered inappropriate

## Questions?

If you have questions about contributing, feel free to:

- Create an issue on GitHub
- Contact us at <support@convertain.com>

Thank you for contributing! 🎉
