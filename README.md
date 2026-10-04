# Relay Core

> A modern, developer-friendly bulk CSV importer for WordPress — built with clean architecture, PSR-4 autoloading, and WordPress coding standards.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Coding Standards](https://img.shields.io/badge/Coding%20Standards-WordPress-blueviolet.svg)](https://developer.wordpress.org/coding-standards/)

Relay Core is a **bulk CSV importer** plugin for WordPress that imports data into **any custom post type** with full control over column mapping, external ID tracking, and duplicate-safe re-imports.

Built for developers, agencies, and site owners who need **reliable, repeatable imports** — not one-off scripts.

---

## Table of Contents

- [Why Relay Core?](#why-relay-core)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
  - [Basic Import](#basic-import)
  - [Column Mapping](#column-mapping)
  - [External ID & Duplicate-Safe Imports](#external-id--duplicate-safe-imports)
- [Architecture](#architecture)
- [Development](#development)
  - [Environment Setup](#environment-setup)
  - [Project Structure](#project-structure)
  - [Coding Standards](#coding-standards)
  - [Testing](#testing)
- [Roadmap](#roadmap)
- [FAQ](#faq)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)

---

## Why Relay Core?

Most CSV importers on the market fall into two categories:

1. **Rigid** — you must match their exact column structure
2. **Overly complex** — dozens of settings for a simple task

Relay Core takes a different approach: **clean separation of concerns**.

- The **Reader** reads CSV files
- The **Validator** checks row integrity
- The **Mapper** applies user-defined column mapping
- The **Importer** writes to WordPress

Each responsibility lives in its own class, testable in isolation, and easy to extend. Whether you're importing 10 rows or 50,000, Relay Core handles it predictably.

---

## Features

### Core

- ✅ **Import any CSV** — no rigid column structure required
- ✅ **Custom column mapping** — choose which column is the post title, which is the external ID, and which become post meta
- ✅ **External ID support** — every row gets a unique identifier for duplicate-safe re-imports
- ✅ **Create-or-Update logic** — re-import the same CSV without creating duplicates
- ✅ **Per-row validation** — invalid rows are skipped, logged, and reported
- ✅ **Detailed import logs** — see exactly which rows failed and why

### Security

- ✅ **Nonce verification** on all forms
- ✅ **Capability checks** (`manage_options`)
- ✅ **File validation** (extension, MIME type, size)
- ✅ **Sanitization** of all inputs
- ✅ **Escaping** of all outputs
- ✅ **Random file names** for temporary uploads

### Developer Experience

- ✅ **PSR-4 autoloading** via Composer
- ✅ **WordPress Coding Standards** compliant (PHPCS)
- ✅ **Separated architecture** — Reader, Validator, Mapper, Importer
- ✅ **Testable** with PHPUnit
- ✅ **CI-ready** — GitHub Actions workflows included
- ✅ **Extensible** — new sources (JSON, XML, API) can be added without touching the Importer

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.0+ |
| PHP | 8.1+ |
| Composer | 2.x |
| Node.js | 18+ (dev only) |
| Docker | Latest (dev only) |

---

## Installation

### From WordPress Admin

1. Download the latest release `.zip`
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate the plugin

### From Source (Developers)

```bash
git clone https://github.com/NajafAli786110/Relay-Core.git
cd Relay-Core
composer install
npm install
npx wp-env start
```

WordPress will be available at `http://localhost:8888` (admin: `admin` / `password`).

---

## Usage

### Basic Import

1. Navigate to **Listings → Bulk Import** in the WordPress admin
2. Upload your CSV file
3. Choose which columns map to post title and external ID
4. Click **Submit Mapping** to run the import

### Column Mapping

Relay Core lets you map **any CSV column** to:

- **Post Title** — the main identifier for the listing
- **External ID** — a unique identifier for duplicate-safe updates
- **Post Meta** — all remaining columns are stored as post meta with sanitized keys

Example:

| CSV Column | Mapped To |
|---|---|
| Business Name | Post Title |
| Listing ID | External ID |
| City | Meta: `city` |
| Phone | Meta: `phone` |
| Rating | Meta: `rating` |

### External ID & Duplicate-Safe Imports

Every row **must** have an External ID column. This ID is used to detect existing records:

- **First import** → creates new listings
- **Re-import with same ID** → updates existing listings (no duplicates)
- **Re-import with new ID** → creates new listings

This makes Relay Core safe for **scheduled syncs**, **CRM integrations**, and **recurring data updates**.

---

## Architecture

Relay Core follows a clean, layered architecture:

```
UploadPage (orchestrator)
    │
    ├── FileValidator    → validates uploaded files
    ├── CsvReader        → reads CSV rows
    ├── ColumnMapper     → applies user mapping
    ├── ListingValidator → validates each row
    └── ListingImporter  → writes to WordPress
```

Each class has **one responsibility**, **no hidden dependencies**, and is **fully testable**.

### Project Structure

```
relay-core/
├── src/
│   ├── Admin/
│   │   └── UploadPage.php
│   ├── Import/
│   │   ├── CsvReader.php
│   │   ├── FileValidator.php
│   │   ├── ColumnMapper.php
│   │   ├── ListingValidator.php
│   │   └── ListingImporter.php
│   ├── PostTypes/
│   │   └── RegisterPostType.php
│   ├── Debug.php
│   └── RelayCorePlugin.php
├── tests/
├── .wp-env.json
├── composer.json
├── package.json
├── phpcs.xml.dist
├── phpunit.xml.dist
└── relay-core.php
```

---

## Development

### Environment Setup

Relay Core uses [**wp-env**](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) for local development.

```bash
# Start WordPress + MySQL in Docker
npx wp-env start

# Stop
npx wp-env stop

# Destroy (wipes database)
npx wp-env destroy
```

### Coding Standards

This plugin follows the **WordPress Coding Standards** enforced by PHPCS.

```bash
# Check all files
vendor/bin/phpcs

# Auto-fix what can be fixed
vendor/bin/phpcbf

# Check specific file
vendor/bin/phpcs src/Import/CsvReader.php
```

### Testing

```bash
# Run PHPUnit tests
composer test

# Run with coverage
composer test:coverage
```

### Available Composer Scripts

| Script | Purpose |
|---|---|
| `composer test` | Run PHPUnit tests |
| `composer lint` | Run PHPCS |
| `composer lint:fix` | Auto-fix coding standards |
| `composer dump-autoload` | Regenerate autoloader |

---

## Roadmap

- [x] Phase 1 — Flexible CSV import with any column structure
- [x] Phase 2 — Column mapping, external ID, duplicate-safe imports
- [ ] Phase 3 — User-defined conditions per column
- [ ] Phase 4 — JSON & XML source support
- [ ] Phase 5 — Background processing for large files (Action Scheduler)
- [ ] Phase 6 — CSV generator UI
- [ ] Phase 7 — REST API endpoints
- [ ] Phase 8 — Gutenberg admin interface

---

## FAQ

### Does Relay Core require a specific CSV format?

No. Any CSV works, as long as it has:
- A header row
- An External ID column
- A Post Title column

You choose which columns those are during the mapping step.

### What happens if my CSV has duplicates?

The **last occurrence wins**. If the same External ID appears twice, the second row overwrites the first.

### Can I re-import the same CSV?

Yes — that's the point. External IDs make imports idempotent. Re-importing updates existing records instead of creating duplicates.

### Is Relay Core translation-ready?

Yes. All user-facing strings use the `relay-core` text domain.

### Can I extend Relay Core?

Yes. The architecture is intentionally modular. To add a new source (JSON, XML, API), implement a new Reader and pass its output to the existing Mapper, Validator, and Importer.

---

## Contributing

Contributions are welcome. Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow WordPress Coding Standards
4. Add tests for new features
5. Ensure `composer lint` and `composer test` pass
6. Submit a pull request

---

## License

Relay Core is licensed under the **GPL-2.0-or-later** license.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

---

## Credits

**Built by [Najaf Ali Balti](https://github.com/NajafAli786110)** under **EngineWP**.

### Tech Stack

- WordPress
- PHP 8.1+
- Composer + PSR-4
- wp-env (Docker)
- PHPCS (WordPress Coding Standards)
- PHPUnit
- GitHub Actions
