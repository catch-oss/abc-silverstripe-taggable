# Migration Plan: abc-silverstripe-taggable

## Summary

- **Package**: azt3k/abc-silverstripe-taggable
- **Type**: B (Silverstripe module)
- **Tier**: 5
- **Risk Level**: High
- **Estimated Scope**: 6 source files, 5 classes, 1 template, 1 config file

## Change Inventory

### Namespace Renames Required

| Old Namespace | New Namespace | Files Affected |
|---|---|---|
| `SilverStripe\ORM\DataExtension` | `SilverStripe\Core\Extension` | `code/Taggable.php` |
| `SilverStripe\ORM\ArrayList` | `SilverStripe\Model\List\ArrayList` | `code/Taggable.php` |
| `SilverStripe\Forms\FieldSet` (dead import) | Remove entirely | `code/Taggable.php` |

### Composer Dependency Changes

| Package | Current Version | Target Version |
|---|---|---|
| `php` | `^8.1` | `^8.5` |
| `silverstripe/framework` | `^5` | `^6.0` |
| `silverstripe/cms` | `^5` | `^6.0` |
| `silverstripe/crontask` | `^3.0` | Remove (unused — no cron code in this module) |
| `azt3k/abc-silverstripe` | `dev-feature/ss5-update` | `dev-release/6` |
| `composer/installers` | `^2.2` | `^2.2` (keep) |
| `silverstripe/vendor-plugin` | (missing) | `^3.0` (add) |
| `phpunit/phpunit` | (missing) | `^11.0` (add to require-dev) |
| `silverstripe/recipe-cms` | (missing) | `^6.0` (add to require-dev) |

### API Changes Required

| Pattern | Migration | Files Affected |
|---|---|---|
| `DataExtension` base class | Change to `Extension` | `code/Taggable.php` |
| `new ArrayList` | `ArrayList::create()` (or `new` with updated import) | `code/Taggable.php` |
| `DataObject::get_one('TagPage')` | `DataObject::get_one(TagPage::class)` | `code/Taggable.php` |
| `$set->unlimitedRowCount = ...` dynamic property on ArrayList | Declare property or use ArrayData wrapper | `code/Taggable.php` |
| `TagPage_Controller` SS3 naming | Rename to `TagPageController`, move to separate file, extend `PageController` | `code/TagPage.php` → `code/TagPage.php` + `code/TagPageController.php` |
| `$this->TagStr`, `$this->TagSet`, `$this->Paginator` dynamic properties on controller | Declare as class properties | `code/TagPageController.php` |
| `TAGGABLE_DIR` constant for assets | Replace with `ModuleResourceLoader` or `ModuleLoader` resource paths | `code/TagField.php`, `_config.php` |
| `SQL_CALC_FOUND_ROWS` (deprecated MySQL 8.0.17) | Replace with separate COUNT query or ORM pagination | `code/Taggable.php` |
| `AbcDB::getInstance()` raw DB access | Verify works with SS6 DB connection layer | `code/Taggable.php` |
| `DataObjectHelper::getExtendedClasses('Taggable')` | Verify updated in abc-silverstripe SS6 migration | `code/Taggable.php` |
| `DataObjectHelper::getTableForClass()` | Verify updated in abc-silverstripe SS6 migration | `code/Taggable.php` |
| `DataObjectHelper::getExtensionTableForClassWithProperty()` | Verify updated in abc-silverstripe SS6 migration | `code/Taggable.php` |
| `SilverStripe\Forms\FieldSet` dead import | Remove | `code/Taggable.php` |

### PHP 8.5 Compatibility Fixes

| Issue | Fix | Files Affected |
|---|---|---|
| Dynamic properties on controller (`$this->TagStr`, etc.) | Declare typed properties | `code/TagPage.php` → `code/TagPageController.php` |
| Dynamic property on ArrayList (`$set->unlimitedRowCount`) | Use separate variable or ArrayData wrapper | `code/Taggable.php` |
| Missing return type declarations | Add `: void`, `: array`, `: string`, etc. | All source files |
| `array()` syntax | Modernize to `[]` (optional but consistent) | All source files |
| Implicit nullable `$maxLength = null` in TagField constructor | Parent signature — verify TextField::__construct() in SS6 | `code/TagField.php` |

### PHPUnit Migration

| Issue | Fix | Files Affected |
|---|---|---|
| Tests are empty stubs | Write real tests from scratch | `tests/TaggableTest.php` |
| `@depends` annotation | Remove (tests need rewrite) | `tests/TaggableTest.php` |
| No namespace on test class | Add `Azt3k\SS\Taggable\Tests` namespace | `tests/TaggableTest.php` |
| No phpunit.xml.dist | Create with SS6 bootstrap | (new file) |

### Config Changes

| File | Change Required |
|---|---|
| `_config.php` | Replace `TAGGABLE_DIR`/`TAGGABLE_PATH` constants with SS6 module resource loading |
| `_config/config.yml` | Review — currently minimal, likely OK. Add named extension keys if needed by consuming modules |

### Structural Changes

| Change | Details |
|---|---|
| `code/` → `src/` directory rename | Update PSR-4 autoload in composer.json to point to `src/` |
| Split `TagPage.php` | Extract `TagPage_Controller` to `TagPageController.php`, rename class, extend `PageController` |
| Add `.gitignore` entries | `app/`, `public/`, `.htaccess`, `index.php`, `web.config`, `.phpunit.cache/` |
| Move `autoload` to `src/` | Update `"Azt3k\\SS\\Taggable\\": "code/"` → `"Azt3k\\SS\\Taggable\\": "src/"` |

## Risk Assessment

| Area | Risk | Notes |
|---|---|---|
| Namespace renames | Low | Only 2 SS namespace renames needed (DataExtension, ArrayList) |
| API changes | **High** | `getTaggedWith()` uses raw SQL via AbcDB, `SQL_CALC_FOUND_ROWS`, dynamic properties on ArrayList. TagPage_Controller is SS3-era pattern |
| PHP 8.5 compat | Medium | Dynamic properties on controller and ArrayList need fixing. Return types needed on all methods |
| Test migration | Medium | Tests are empty stubs — need to write real tests from scratch. Complex tagging logic needs good coverage |
| Config changes | Low | Only `_config.php` constant definitions need updating |
| Dependencies | Medium | Depends on abc-silverstripe (Tier 4) being SS6-ready. AbcDB, DataObjectHelper, AbcPaginator must work in SS6 |
| Template | Low | SS template syntax unchanged, but verify `$AssociatedImage.resizedCroppedAbsoluteURL()` still works |

## Migration Steps (Ordered)

### Phase 1: composer.json
- [ ] Rename `code/` directory to `src/`
- [ ] Update autoload PSR-4 mapping from `code/` to `src/`
- [ ] Update `php` requirement to `^8.5`
- [ ] Update `silverstripe/framework` to `^6.0`
- [ ] Update `silverstripe/cms` to `^6.0`
- [ ] Remove `silverstripe/crontask` (unused in this module)
- [ ] Update `azt3k/abc-silverstripe` to `dev-release/6`
- [ ] Add `silverstripe/vendor-plugin: ^3.0` to require
- [ ] Add `require-dev` section: `phpunit/phpunit: ^11.0`, `silverstripe/recipe-cms: ^6.0`
- [ ] Add `autoload-dev` with psr-4 for tests and classmap for Page/PageController
- [ ] Add `allow-plugins` config for `composer/installers`, `silverstripe/vendor-plugin`, `silverstripe/recipe-plugin`
- [ ] Run `composer validate`

### Phase 2: Namespace Renames
- [ ] `SilverStripe\ORM\DataExtension` → `SilverStripe\Core\Extension` in `src/Taggable.php`
- [ ] `SilverStripe\ORM\ArrayList` → `SilverStripe\Model\List\ArrayList` in `src/Taggable.php`
- [ ] Remove dead `SilverStripe\Forms\FieldSet` import from `src/Taggable.php`
- [ ] Rename base class `extends DataExtension` → `extends Extension` in `src/Taggable.php`

### Phase 3: API Changes
- [ ] Split `TagPage_Controller` out of `src/TagPage.php` into `src/TagPageController.php`
- [ ] Rename `TagPage_Controller` → `TagPageController`
- [ ] Change `TagPageController extends Controller` → `extends PageController`
- [ ] Declare typed properties for `$TagStr`, `$TagSet`, `$Paginator` on `TagPageController`
- [ ] Update `DataObject::get_one('TagPage')` → `DataObject::get_one(TagPage::class)`
- [ ] Fix dynamic property `$set->unlimitedRowCount` on ArrayList in `getTaggedWith()`
- [ ] Replace `SQL_CALC_FOUND_ROWS` + `FOUND_ROWS()` with separate COUNT query
- [ ] Replace `TAGGABLE_DIR` constant usage in `TagField.php` with `ModuleResourceLoader`
- [ ] Update `_config.php` to remove constant definitions (or replace with module resource approach)
- [ ] Verify `AbcDB`, `DataObjectHelper`, `AbcPaginator` compatibility with SS6 abc-silverstripe

### Phase 4: PHP 8.5 Compatibility
- [ ] Add return type declarations to all methods across all classes
- [ ] Modernize `array()` to `[]` syntax
- [ ] Verify no implicit nullable parameters (check `TagField::__construct()` against SS6 parent)
- [ ] Ensure all dynamic properties are declared explicitly

### Phase 5: Logging Integration
- [ ] Add `monolog/monolog: ^3.2` to require (if not inherited via abc-silverstripe)
- [ ] Add Catch-format logging config to `_config/config.yml` for `Azt3k.SS.Taggable` channel

### Phase 6: Config Updates
- [ ] Update `_config.php` — remove `TAGGABLE_DIR`/`TAGGABLE_PATH` constants
- [ ] Review `_config/config.yml` — add named extension keys if needed
- [ ] Verify SS6 YAML config compatibility

### Phase 7: Test Suite (Silverstripe Best Practices)
- [ ] Add `silverstripe/recipe-cms: ^6.0` to require-dev (provides Page/PageController)
- [ ] Add `silverstripe/recipe-plugin: true` to allow-plugins
- [ ] Create `phpunit.xml.dist` with bootstrap `vendor/silverstripe/framework/tests/bootstrap.php`
- [ ] Add recipe-generated files to `.gitignore`: `app/`, `public/`, `.htaccess`, `index.php`, `web.config`, `.phpunit.cache/`
- [ ] Add namespace `Azt3k\SS\Taggable\Tests` to test classes
- [ ] Rewrite `TaggableTest.php` extending `SapphireTest` with real tests:
  - Test `Tag` DataObject creation and field validation
  - Test `Taggable` extension applies to DataObjects
  - Test `str_to_tags()` static method
  - Test `explode_tags()` static method
  - Test `extract_hash_tags()` static method
  - Test `tags2Links()` output format
  - Test `onBeforeWrite()` tag generation logic
  - Test `getTagFields()` returns expected form fields
  - Test `updateCMSFields()` adds tag fields to FieldList
  - Test `tagged_with()` query builder
- [ ] Add `TagAdminTest.php` — verify ModelAdmin config
- [ ] Add `TagPageTest.php` — verify page type and getCMSFields
- [ ] Use GIVEN/WHEN/THEN comments in all test methods
- [ ] Use `$usesDatabase = false` for tests that don't need ORM
- [ ] Use `::create()` instead of `new` for SS classes in tests
- [ ] Migrate to PHPUnit 11 syntax (attributes, removed methods)
- [ ] Add `allow-plugins` config to composer.json
- [ ] Achieve 80% line coverage target

## Dependencies

- **Depends on**: abc-silverstripe (Tier 4) — uses `AbcDB`, `DataObjectHelper`, `AbcPaginator`
- **Blocks**: abc-silverstripe-social (Tier 6) — depends on this module
