# Release Notes for Cache Igniter

## 2.2.0 - Unreleased

> [!NOTE]
> All pending Cache Igniter queue jobs must complete before updating.
> Custom integrations that manage warming jobs or pending URLs may need updating.

- Improved deduplication of URLs waiting to be warmed.
- Improved reliability when warming requests overlap or queue jobs fail.
- Improved coordination between queued and console warming.
- Increased the default maximum URL length to 2048 characters.
- Fixed a bug in which URLs differing only by case could be treated as the same URL.
- Fixed a bug in which URLs could be skipped when warming multiple batches.

## 2.1.0 - 2026-01-31

- Added an API access token setting to the Globalping warmer that makes it possible to increase rate limits ([#6](https://github.com/putyourlightson/craft-cache-igniter/issues/6)).

## 2.0.0 - 2024-04-08

- Added compatibility with Craft 5.
