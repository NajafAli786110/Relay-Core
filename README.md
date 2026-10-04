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
