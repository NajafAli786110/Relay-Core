# Relay Core

> A bulk CSV and JSON importer for WordPress, built with single-responsibility classes, PSR-4 autoloading and WordPress Coding Standards.

[![CI](https://github.com/NajafAli786110/Relay-Core/actions/workflows/ci.yml/badge.svg)](https://github.com/NajafAli786110/Relay-Core/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Coding Standards](https://img.shields.io/badge/Coding%20Standards-WordPress-blueviolet.svg)](https://developer.wordpress.org/coding-standards/)

Relay Core imports rows from a CSV or JSON file into a **Listings** custom post type. You choose which column is the post title and which is the external ID; every other column is saved as post meta. Re-importing the same file updates the existing listings instead of creating duplicates.

Imports run in the background with Action Scheduler, 500 rows per job, so they never hit the request time limit and keep going if you close the page. An **Import History** dashboard built in React lists every import and opens its failed rows in a popup.

**Status:** version 0.1.0 is released; the background imports, REST API and History dashboard are in `main` and not tagged yet. See [Roadmap](#roadmap).

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

- **Background jobs** — each batch of 500 rows runs as an Action Scheduler job, and every job queues the next one until the file is done
- **Saved progress** — each import's file, mapping, position and counters are stored on the server, so every job continues where the last one stopped
- **Stuck import recovery** — a check every 5 minutes restarts a running import that has not moved for 4 minutes, and marks it failed after 3 retries
- **Progress display** — a progress bar with rows done, the total and a percentage; you can leave the page while it runs
- **Error log** — every failed row is saved to a custom database table with its row number, field and reason

### Admin screens

- **Three-step import** — upload, map columns, import, with a step indicator on every screen
- **Import History** — a React dashboard listing every import with its status, date, total and failed count
- **Failed rows popup** — opens from an import's row, 20 rows per page, closes with the close button, Escape or a click outside
- **Follows the admin colour scheme** — the screens use the user's WordPress admin colour as their primary colour

### REST API

All routes need the `manage_options` capability. From the admin, `apiFetch` sends the REST nonce automatically.

| Method | Route | Returns |
|---|---|---|
| GET | `/wp-json/relay-core/v1/imports` | Every import with its status, counters and created time |
| GET | `/wp-json/relay-core/v1/imports/{id}` | One import |
| GET | `/wp-json/relay-core/v1/imports/{id}/logs?page=1&per_page=20` | One page of that import's failed rows, with `total`, `page` and `per_page` |

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
npm run build
npx wp-env start
```

WordPress is then available at `http://localhost:8888` (user `admin`, password `password`).

`npm run build` compiles the History dashboard and the stylesheets into `build/`, which is not committed. Without it, the History page shows a notice asking you to run it.

The plugin creates its log table when it is activated. It also stores a database version and updates the table on the next page load whenever that version changes, so updating the code does not need a reactivation.

---

## Usage

1. Go to **Listings → Bulk Import** in the WordPress admin.
2. Upload a `.csv` or `.json` file.
3. Choose which column is the **post title** and which is the **external ID**.
4. Click **Start import**. The import starts in the background and the screen shows its progress, for example `1500 / 50000 rows (3%)`.

You can leave the page; the import keeps running. If you stay, you see how many listings were created, updated and failed when it finishes, followed by the failed rows:

```
Row 14: Required field is empty. (post_title)
Row 203: Required field is empty. (external_id)
```

### Import History

**Listings → History** lists every import, newest first. Click **View details** on an import with failures to open its failed rows in a popup, 20 per page.

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
Admin\ImportAjax      AJAX handler: reports an import's status to the progress screen
Admin\LogsPage        History page; loads the React dashboard on that screen only
Rest\ImportsController  REST routes for imports and their failed rows

Import\BackgroundImport   queues and runs one batch job with Action Scheduler
Import\StuckImportChecker restarts imports whose job chain broke
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

assets/js/import.js     polls the import status every 3 seconds and updates the progress bar
src/LogDashboard/       React History dashboard (ImportsTable, ImportRow, ImportLogs popup)
src/Upload/             stylesheet entry for the import screens
src/styles/             shared SCSS tokens and components
```

`BatchProcessor`, `BackgroundImport` and the REST controller receive their collaborators through their constructors, which keeps them testable without the WordPress admin or a real upload.

### How one import runs

1. The upload request validates the file, saves it and creates an import state with status `pending`.
2. The mapping request stores the chosen columns, sets the status to `running` and queues the first background job. Only the 64-character import ID is passed to the job.
3. Each job reads the state, imports the next 500 rows, logs the rows that failed, saves the new position and queues the next job.
4. When a job finds no more rows, the status becomes `done`.
5. Meanwhile the progress screen asks for the status every 3 seconds, and the History dashboard reads the same data through the REST API.

### Project structure

```
relay-core/
├── .github/
│   └── workflows/
│       └── ci.yml
├── assets/
│   └── js/
│       └── import.js
├── build/                      (generated by npm run build, not committed)
├── src/
│   ├── Admin/
│   │   ├── ImportAjax.php
│   │   ├── LogsPage.php
│   │   └── UploadPage.php
│   ├── Import/
│   │   ├── BackgroundImport.php
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
│   │   ├── ReaderInterface.php
│   │   └── StuckImportChecker.php
│   ├── LogDashboard/
│   │   ├── app.js
│   │   ├── app.scss
│   │   ├── ImportLogs.js
│   │   └── ImportRow.js
│   ├── Rest/
│   │   └── ImportsController.php
│   ├── styles/
│   │   ├── _components.scss
│   │   └── _tokens.scss
│   ├── Upload/
│   │   ├── upload.js
│   │   └── upload.scss
│   └── RegisterPostType.php
├── tests/
│   ├── integration/
│   └── unit/
├── .wp-env.json
├── composer.json
├── package.json
├── phpcs.xml.dist
├── phpunit.xml.dist
├── phpunit-integration.xml.dist
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

### Dashboard and styles

The History dashboard and both stylesheets are built with [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/). React comes from WordPress itself (`wp.element`), so it is not bundled.

```bash
npm run build   # production build into build/
npm run start   # rebuild on every change while developing
```

Each entry also writes an `.asset.php` file with its script dependencies and a content-hash version, which the PHP side reads when it enqueues the files.

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

Unit tests run with PHPUnit and do not need WordPress or a database. They cover `CsvReader`, `JsonReader` and `ListingValidator`.

```bash
vendor/bin/phpunit
```

Integration tests for the REST routes run inside WordPress with `phpunit-integration.xml.dist`.

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

**Done since 0.1.0 (in `main`)**

- [x] Background processing with Action Scheduler
- [x] Restart of stuck imports, failed after 3 retries
- [x] Database version check that updates the log table without reactivation
- [x] REST API endpoints for imports and their failed rows
- [x] Import History dashboard in React, with a paginated failed rows popup
- [x] Redesigned import screens with a step indicator and progress bar
- [x] Tests for `JsonReader`, `ListingValidator` and the REST routes

**Planned**

- [ ] Tests for the mapper, import state and batch processor
- [ ] Cleanup of abandoned imports, their uploaded files and old log rows
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

Nothing. The import runs in the background, not in the browser. You can check its result later under **Listings → History**.

### What if an import gets stuck?

If a job dies before queuing the next one, the stuck import check restarts it within a few minutes. After 3 restarts without progress the import is marked `failed`.

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
