# Release Notes for Cache Igniter

## 1.2.0 - Unreleased

### Added

- Added batched warm jobs.
- Added a new `warmJobBatchSize` config setting that sets the batch size to use for warm jobs.

## 1.1.2 - 2023-11-29

### Changed

- The default URL column size is now set to `500` by default to accommodate `utf8mb4` character sets, and configurable using the `maxUrlLength` config setting ([#1](https://github.com/putyourlightson/craft-cache-igniter/issues/1)).

## 1.1.1 - 2023-11-28

### Changed

- All site URIs are now fetched on the `EVENT_AFTER_REFRESH_ALL_CACHE` event if not generating on refresh.
- Set the Guzzle client connection timeout to 10 seconds.

## 1.1.0 - 2023-11-17

### Added

- Added the ability to configure a custom queue component via `config/app.php` to use when running queue jobs.

## 1.0.1 - 2023-11-09

### Fixed

- Fixed a bug caused by listening for the wrong refresh event.

## 1.0.0 - 2023-11-07
