# Contributing to PasPapan

Thank you for your interest in contributing to PasPapan! 🎉

## How to Contribute

### 🐛 Reporting Bugs

1. Check the [Issues](https://github.com/RiprLutuk/PasPapan/issues) page to see if the bug has already been reported.
2. If not, create a new issue using the **Bug Report** template.
3. Include steps to reproduce, expected vs actual behavior, and screenshots if applicable.

### 💡 Suggesting Features

1. Open a new issue using the **Feature Request** template.
2. Describe the feature, why it's needed, and how it should work.
3. Discuss with the community in [Discussions](https://github.com/RiprLutuk/PasPapan/discussions).

### 🔧 Submitting Code

1. **Fork** the repository.
2. Create a new branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Make your changes following our conventions (see below).
4. Test your changes locally.
5. Commit with clear messages:
   ```bash
   git commit -m "feat: add new feature description"
   ```
6. Push and open a **Pull Request**.

## Development Setup

```bash
git clone https://github.com/YOUR_USERNAME/PasPapan.git
cd PasPapan
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# Run dev servers
npm run dev          # Terminal 1
php artisan serve    # Terminal 2
```

## Code Conventions

- **PHP**: Follow PSR-12 coding standards.
- **Blade**: Use Livewire components where possible.
- **Commits**: Use [Conventional Commits](https://www.conventionalcommits.org/):
  - `feat:` — New feature
  - `fix:` — Bug fix
  - `docs:` — Documentation
  - `style:` — Formatting (no logic change)
  - `refactor:` — Code restructuring
  - `chore:` — Maintenance tasks

## Code of Conduct

Please read our [Code of Conduct](CODE_OF_CONDUCT.md) before contributing.

## Questions?

Feel free to ask in [GitHub Discussions](https://github.com/RiprLutuk/PasPapan/discussions) or open an issue.

Thank you for helping make PasPapan better! 🙏
