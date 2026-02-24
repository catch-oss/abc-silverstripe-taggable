# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- Upgraded to Silverstripe 6 compatibility
- Updated PHP requirement to ^8.5
- Migrated test suite to PHPUnit 11
- Renamed `DataExtension` base class to `Extension` (SS6 rename)
- Renamed `ORM\ArrayList` import to `Model\List\ArrayList`
- Moved source files from `code/` to `src/` (PSR-4 convention)
- Extracted `TagPage_Controller` to standalone `TagPageController` class extending `PageController`
- Replaced `SQL_CALC_FOUND_ROWS` with separate COUNT subquery
- Replaced `TAGGABLE_DIR` constant with `azt3k/abc-silverstripe-taggable:` vendor path syntax
- Updated `DataObjectHelper::getExtendedClasses()` to use FQCN lookup
- Updated `updateCMSFields()` to use `instanceof` checks (PHP 8.5 null safety)
- Updated `addFieldsToTab()` calls to pass arrays instead of FieldList (SS6 API)

### Added
- MIGRATION-PLAN.md documenting all changes
- Test suite: 76 tests, 132 assertions (81% line coverage)
- `TagPageController.php` as standalone controller file
- Empty-SQL guard in `getTaggedWith()` for robustness
- Auto-scaffolded field removal before custom CMS field layout

### Fixed
- PHP 8.5 compatibility (return types, typed properties, union types, `str_starts_with()`)
- Removed `parent::onBeforeWrite()` call (SS6 Extension has no lifecycle hooks)
- Fixed duplicate CMS fields caused by SS6 auto-scaffolding
- Fixed `preg_replace()` null parameter deprecation in `safe_args()`
- Removed dead `FieldSet` import
