# Contributing to Laravel Package Template

Thank you for considering contributing to this Laravel package template! We welcome contributions from the community.

## Understanding the Repository Structure

This is a **package template repository**. The source code contains placeholder variables (like `:vendor_name`, `:package_slug`) that get replaced when users run `install.php` to scaffold their own package.

**Important:** The `composer.json` in this repository is a template file and cannot be used directly. You cannot run `composer install` on this repository without first running the installer.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue on GitHub with the following information:

- A clear and descriptive title
- Steps to reproduce the issue
- Expected behavior vs. actual behavior
- Your environment (PHP version, OS, etc.)
- Any relevant error messages

### Suggesting Enhancements

We welcome suggestions for new features or improvements! Please create an issue with:

- A clear description of the enhancement
- Why it would be useful for package developers
- Any potential implementation ideas

### Pull Requests

1. **Fork the repository** and create a new branch from `main`
2. **Make your changes** to template files or the installer
3. **Test the installer** by running it and verifying the generated package works
4. **Update documentation** (README.md) if needed
5. **Submit a pull request** with a clear description of your changes

#### Development & Testing Workflow

Since this is a template repository, testing requires running the installer:

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/laravel-package-template.git
cd laravel-package-template

# Run the installer to generate a package
php install.php

# After installation, test the generated package
composer test
composer lint
composer analyse
```

To test changes without losing the template:

```bash
# Copy the template to a test directory
cp -r laravel-package-template test-package
cd test-package

# Run the installer
php install.php

# Verify everything works
composer test && composer lint && composer analyse
```

#### Key Files to Understand

| File | Purpose |
|------|---------|
| `install.php` | Interactive installer script |
| `composer.json` | Template for the generated package's composer.json |
| `data/` | Template files copied during installation |
| `data/composer.json` | Dependencies for the installer (Laravel Prompts) |
| `data/Provider.php.txt` | Template for the service provider |
| `data/licenses/` | License file templates |
| `data/github/` | GitHub workflow and issue templates |

#### Coding Standards

- Use **strict types** (`declare(strict_types=1);`)
- Write **clear, descriptive variable and method names**
- Keep the installer user-friendly with helpful prompts
- Test all installer paths (different feature combinations)

#### Commit Messages

- Use clear and descriptive commit messages
- Start with a verb in present tense (e.g., "Add", "Fix", "Update")
- Reference issues when applicable (e.g., "Fix #123")

Example:

```text
Add configurable PHPStan level selection

- Added prompt for PHPStan level (0-10)
- Updated phpstan.neon.dist template
- Updated README with new feature

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

If you have questions about contributing, feel free to create an issue on GitHub.

Thank you for contributing! 🎉
