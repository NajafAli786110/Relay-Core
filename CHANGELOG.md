# Changelog

## [0.1.0] - 2026-10-12

First release.

### Added

- Import a CSV or JSON file into the Listings post type. The file can have any columns in any order.
- Choose which column is the post title and which is the external ID. Every other column is saved as post meta.
- Importing the same file again updates the existing listings instead of creating duplicates.
- Large files are imported in batches of 500 rows, so imports no longer hit the server's time limit. Tested in development on the first 6,500 rows of a 50,000-row file.
- The import screen shows progress while it runs: rows done, total rows and a percentage.
- Rows with an empty title or an empty external ID are skipped instead of being imported with missing data.
- Every skipped or failed row is saved with its row number and the reason. The first 100 are listed when the import finishes.

### Security

- Only administrators can import, and every form and background request is checked with a nonce.
- Uploaded files are checked for extension, size (20 MB limit) and file type before they are read.
- All imported values are sanitized before they are saved, for both CSV and JSON files.
- The location of the uploaded file is kept on the server and is never sent to the browser.
