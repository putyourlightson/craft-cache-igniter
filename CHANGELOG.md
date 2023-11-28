# Release Notes for Cache Igniter

## 1.1.1 - 2023-11-28

### Changed

- All site URIs are now fetched on the `EVENT_AFTER_REFRESH_ALL_CACHE` event if not generating on refresh.

## 1.1.0 - 2023-11-17

### Added

- Added the ability to configure a custom queue component via `config/app.php` to use when running queue jobs.

## 1.0.1 - 2023-11-09

### Fixed

- Fixed a bug caused by listening for the wrong refresh event.

## 1.0.0 - 2023-11-07
