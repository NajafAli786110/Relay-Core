# Relay Core

> A bulk CSV and JSON importer for WordPress, built with single-responsibility classes, PSR-4 autoloading and WordPress Coding Standards.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Coding Standards](https://img.shields.io/badge/Coding%20Standards-WordPress-blueviolet.svg)](https://developer.wordpress.org/coding-standards/)

Relay Core imports rows from a CSV or JSON file into a **Listings** custom post type. You choose which column is the post title and which is the external ID; every other column is saved as post meta. Re-importing the same file updates the existing listings instead of creating duplicates.

**Status:** in active development (pre-release, version 0.1.0). See [Roadmap](#roadmap) for what works today and what is still being built.

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
- **Row validation** — rows with an empty title or external ID are skipped and reported with their row number
- **Streaming reads** — CSV rows are read one at a time with a generator, not loaded into memory

### Batch processing

- **`BatchProcessor`** imports a fixed number of rows per call and saves its position
- **`ImportState`** stores each import's file, mapping, offset, counters and status in a non-autoloaded option
- **AJAX endpoint** (`wp_ajax_relay_core_import_batch`) runs one batch per request and returns progress as JSON

The admin screen does not use the batch endpoint yet; it still imports in a single request. Wiring the screen to the endpoint is the current work in progress.

### Security

- Nonce verification and `manage_options` capability checks on every form and AJAX handler
- File validation: extension, size (20 MB limit) and MIME type
- Random file names for uploaded files
- The uploaded file path never leaves the server; the browser only receives a random 64-character import ID
- Input is sanitized before it is saved and output is escaped

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.1+ |
| WordPress | Developed and run against the latest release |
| Composer | 2.x |
| Node.js | 18+ (development only) |
| Docker | Required by wp-env (development only) |

---

## Installation

There is no packaged release yet. To run it from source:

```bash
git clone https://github.com/NajafAli786110/Relay-Core.git
cd Relay-Core
composer install
npm install
npx wp-env start
```

WordPress is then available at `http://localhost:8888` (user `admin`, password `password`).

---

## Usage

1. Go to **Listings → Bulk Import** in the WordPress admin.
2. Upload a `.csv` or `.json` file.
3. Choose which column is the **post title** and which is the **external ID**.
4. Click **Submit Mapping** to run the import.

When the import finishes you see how many listings were created and updated, and a list of skipped rows with the reason.

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
Admin\UploadPage      upload form, mapping form, single-request import
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
Import\BatchProcessor   imports one batch and updates the import state
```

`BatchProcessor` receives its collaborators through its constructor, which keeps it testable without WordPress admin or a real upload.

### Project structure

```
relay-core/
├── src/
│   ├── Admin/
│   │   ├── ImportAjax.php
│   │   └── UploadPage.php
│   ├── Import/
│   │   ├── BatchProcessor.php
│   │   ├── ColumnMapper.php
│   │   ├── CsvReader.php
│   │   ├── FileValidator.php
│   │   ├── ImportState.php
│   │   ├── JsonReader.php
│   │   ├── ListingImporter.php
│   │   ├── ListingValidator.php
│   │   ├── ReaderFactory.php
│   │   └── ReaderInterface.php
│   ├── RegisterPostType.php
│   └── RelayCorePlugin.php
├── .wp-env.json
├── composer.json
├── package.json
├── phpcs.xml.dist
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

---

## Roadmap

**Done**

- [x] Import a CSV with any column structure into Listings
- [x] Column mapping screen, external ID and create-or-update logic
- [x] JSON source through `ReaderInterface`
- [x] Streaming CSV reads with generators
- [x] Import state, batch processor and AJAX batch endpoint

**In progress**

- [ ] Run imports from the admin screen in batches, with a progress indicator
- [ ] Persistent per-row import logs

**Planned**

- [ ] PHPUnit test suite
- [ ] GitHub Actions CI (PHPCS and tests)
- [ ] Cleanup of abandoned imports and their uploaded files
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
