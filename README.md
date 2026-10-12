# Relay Core

> A bulk CSV and JSON importer for WordPress, built with single-responsibility classes, PSR-4 autoloading and WordPress Coding Standards.

[![CI](https://github.com/NajafAli786110/Relay-Core/actions/workflows/ci.yml/badge.svg)](https://github.com/NajafAli786110/Relay-Core/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Coding Standards](https://img.shields.io/badge/Coding%20Standards-WordPress-blueviolet.svg)](https://developer.wordpress.org/coding-standards/)

Relay Core imports rows from a CSV or JSON file into a **Listings** custom post type. You choose which column is the post title and which is the external ID; every other column is saved as post meta. Re-importing the same file updates the existing listings instead of creating duplicates.

Large files are imported in batches, so the import does not hit the server's request time limit. The screen shows progress while it runs and lists the rows that failed when it finishes.

**Status:** version 0.1.0, an early release. See [Roadmap](#roadmap) for what works today and what is planned.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Architecture](#architecture)
- [Development](#development)
- [Roadmap](#roadmap)
- [FAQ](#faq)
- [License](#license)
- [Credits](#credits)

---

## Features

### Import

- **Any column structure** — no fixed CSV format; the header row defines the columns
- **CSV and JSON sources** — both implement one `ReaderInterface`, so a new source does not touch the importer
- **Column mapping** — pick the post title column and the external ID column on a mapping screen
- **Create or update** — rows are matched on the external ID, so re-imports update instead of duplicating
- **Row validation** — rows with an empty title or external ID are skipped and logged
- **Streaming reads** — CSV rows are read one at a time with a generator, not loaded into memory

### Large files

- **Batch processing** — the import runs 500 rows per request, one request after another, until the file is done
- **Saved progress** — each import's file, mapping, position and counters are stored on the server, so every request continues where the last one stopped
- **Progress display** — the screen shows rows done out of the total and a percentage
- **Error log** — every failed row is saved to a custom database table with its row number, field and reason; the first 100 are listed when the import finishes

### Security

- Nonce verification and `manage_options` capability checks on every form and AJAX handler
- File validation: extension, size (20 MB limit) and MIME type
- Random file names for uploaded files
- The uploaded file path never leaves the server; the browser only receives a random 64-character import ID
- Input is sanitized before it is saved and output is escaped
- Database queries on the log table use `$wpdb->insert()` and `$wpdb->prepare()`

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.1+ |
| WordPress | 6.2+ (developed and run against the latest release) |
| Composer | 2.x |
| Node.js | 18+ (development only) |
| Docker | Required by wp-env (development only) |

---

## Installation

To run it from source:

```bash
git clone https://github.com/NajafAli786110/Relay-Core.git
cd Relay-Core
composer install
npm install
npx wp-env start
```

WordPress is then available at `http://localhost:8888` (user `admin`, password `password`).

The plugin creates its log table when it is activated. If you update the code and the table is missing, deactivate and activate the plugin once.

---

## Usage

1. Go to **Listings → Bulk Import** in the WordPress admin.
2. Upload a `.csv` or `.json` file.
3. Choose which column is the **post title** and which is the **external ID**.
4. Click **Submit Mapping**. The import starts and the screen shows its progress, for example `1500 / 50000 rows (3%)`.

Keep the page open until the import finishes. You then see how many listings were created, updated and failed, followed by the failed rows:

```
Row 14: Required field is empty. (post_title)
Row 203: Required field is empty. (external_id)
```

### Column mapping

| Column in the file | Saved as |
|---|---|
| The column you pick as post title | Post title |
| The column you pick as external ID | Post meta `external_id`, used to find the listing on re-import |
| Every other column | Post meta, with the column name converted to a key (`Business City` → `business-city`) |

### External ID

Every row needs a value in the external ID column.

- First import of an ID creates a listing.
- Importing the same ID again updates that listing.
- If an ID appears twice in one file, the later row overwrites the earlier one.

### JSON files

JSON files must be a flat array of objects. Nested arrays or objects are not supported.

```json
[
    {
        "External ID": "A-001",
        "Business Name": "Karachi Biryani",
        "City": "Karachi",
        "Rating": "4.5"
    }
]
```

---

## Architecture

Each class has one job. Classes in `Import/` know nothing about `$_POST`, `$_FILES` or the admin screen, so they can be reused from an AJAX handler, WP-CLI or a background job.

```
Admin\UploadPage      upload form, mapping form, starts the import
Admin\ImportAjax      AJAX handler: runs one batch per request

Import\FileValidator    checks the uploaded file (extension, size, MIME type)
Import\ReaderFactory    returns the reader that matches the file extension
Import\ReaderInterface  contract for all readers
  ├── Import\CsvReader  streams CSV rows
  └── Import\JsonReader reads flat JSON rows
Import\ColumnMapper     turns a raw row into title, external ID and meta
Import\ListingValidator checks that a mapped row has a title and an external ID
Import\ListingImporter  creates or updates the listing
Import\ImportState      saves and loads the progress of an import
Import\ImportLog        saves and reads failed rows in a custom table
Import\BatchProcessor   imports one batch, logs failures, updates the import state

assets/js/import.js     calls the AJAX handler until the import is done
```

`BatchProcessor` and `ImportAjax` receive their collaborators through their constructors, which keeps them testable without the WordPress admin or a real upload.

### How one import runs

1. The upload request validates the file, saves it and creates an import state.
2. The mapping request stores the chosen columns in that state and prints the progress screen.
3. The script on that screen sends one AJAX request per batch. Each request reads the state, imports the next 500 rows, logs the rows that failed and saves the new position.
4. When a batch finds no more rows, the status becomes `done` and the response includes the failed rows.

### Project structure

```
relay-core/
├── .github/
│   └── workflows/
│       └── ci.yml
├── assets/
│   └── js/
│       └── import.js
├── src/
│   ├── Admin/
│   │   ├── ImportAjax.php
│   │   └── UploadPage.php
│   ├── Import/
│   │   ├── BatchProcessor.php
│   │   ├── ColumnMapper.php
│   │   ├── CsvReader.php
│   │   ├── FileValidator.php
│   │   ├── ImportLog.php
│   │   ├── ImportState.php
│   │   ├── JsonReader.php
│   │   ├── ListingImporter.php
│   │   ├── ListingValidator.php
│   │   ├── ReaderFactory.php
│   │   └── ReaderInterface.php
│   ├── RegisterPostType.php
│   └── RelayCorePlugin.php
├── tests/
│   ├── fixtures/
│   └── CsvReaderTest.php
├── .wp-env.json
├── composer.json
├── package.json
├── phpcs.xml.dist
├── phpunit.xml.dist
└── relay-core.php
```

---

## Development

### Local environment

Relay Core uses [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), which runs WordPress and MariaDB in Docker.

```bash
npx wp-env start      # start the site
npx wp-env stop       # stop it, keep the data
npx wp-env destroy    # remove the containers and the data
```

Run WP-CLI inside the environment:

```bash
npx wp-env run cli wp post list --post_type=listing --format=count
```

### Coding standards

The code follows the WordPress Coding Standards, checked with PHPCS.

```bash
vendor/bin/phpcbf     # fix what can be fixed automatically
vendor/bin/phpcs -s   # report the rest, with sniff codes
```

### Tests

Unit tests run with PHPUnit and do not need WordPress or a database.

```bash
vendor/bin/phpunit
```

The suite currently covers `CsvReader`: a normal file, a header-only file and a row with the wrong number of columns. Tests for the other classes are planned.

### Continuous integration

GitHub Actions runs PHPCS and PHPUnit on every push to `main` and on every pull request. The workflow is in `.github/workflows/ci.yml`.

---

## Roadmap

**Done in 0.1.0**

- [x] Import a CSV with any column structure into Listings
- [x] Column mapping screen, external ID and create-or-update logic
- [x] JSON source through `ReaderInterface`
- [x] Streaming CSV reads with generators
- [x] Batch imports from the admin screen, with saved state and a progress display
- [x] Failed rows saved to a custom table and listed after the import
- [x] PHPUnit set up, with the first tests for `CsvReader`
- [x] GitHub Actions CI (PHPCS and PHPUnit)

**Planned**

- [ ] Tests for the validator, mapper, import state and batch processor
- [ ] Cleanup of abandoned imports, their uploaded files and old log rows
- [ ] A page to browse the logs of past imports
- [ ] Resume an import that was interrupted
- [ ] Background processing with Action Scheduler
- [ ] REST API endpoints
- [ ] User-defined validation rules per column
- [ ] Translation support

---

## FAQ

### Does my file need specific column names?

No. It needs a header row, one column to use as the post title and one column with a unique ID per row. You choose both on the mapping screen.

### Can I import the same file twice?

Yes. Rows are matched on the external ID, so the second import updates the listings created by the first.

### How large a file can it import?

Uploads are limited to 20 MB. Because the import runs in batches, the number of rows is not limited by the request time limit. In development the batch import was run on a 50,000-row file for its first 6,500 rows, with no timeouts and no duplicates; a full run of that size has not been timed yet.

### What happens if I close the page during an import?

The import stops after the batch that is running. The rows already imported stay, and importing the same file again updates them instead of duplicating them. Resuming from where it stopped is planned.

### Can it import into other post types?

Not yet. It imports into the `listing` post type that the plugin registers.

### How do I add another file format?

Write a class that implements `ReaderInterface` and return it from `ReaderFactory`. The mapper, validator and importer do not change.

---

## License

Relay Core is licensed under **GPL-2.0-or-later**.

---

## Credits

Built by [Najaf Ali Balti](https://github.com/NajafAli786110) under **EngineWP**.
