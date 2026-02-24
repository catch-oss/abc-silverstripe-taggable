# abc-silverstripe-taggable

<!-- PROJECT SHIELDS -->
[![SonarCloud](https://github.com/catch-oss/abc-silverstripe-taggable/actions/workflows/sonar.yml/badge.svg)](https://github.com/catch-oss/abc-silverstripe-taggable/actions/workflows/sonar.yml)
[![Test](https://github.com/catch-oss/abc-silverstripe-taggable/actions/workflows/test.yml/badge.svg)](https://github.com/catch-oss/abc-silverstripe-taggable/actions/workflows/test.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=bugs)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=code_smells)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=coverage)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Duplicated Lines Density](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=duplicated_lines_density)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=ncloc)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=reliability_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=security_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=sqale_index)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=sqale_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-taggable&metric=vulnerabilities)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-taggable)

Add tags to SilverStripe DataObjects with automatic tag generation from content.

## Compatibility

| Version | Silverstripe | PHP |
|---------|-------------|-----|
| release/6 | ^6.0 | ^8.5 |
| release/5 | ^5.1 | ~8.4 |

## Setup

1. Add the following to your `composer.json`:

```json
"require": {
    "azt3k/abc-silverstripe-taggable": "dev-release/6"
}
```

2. Run `composer install`

3. Add the extension to the DataObjects you wish to tag via YAML config:

```yaml
Page:
  extensions:
    taggable: Azt3k\SS\Taggable\Taggable
```

4. Run `dev/build`

5. Start tagging!

## Features

- Automatic tag generation from page title and content
- Meta keywords generation
- Hashtag extraction from content
- Known tags restriction mode
- Blacklisted common words filtering
- Tag administration via ModelAdmin
- TagPage for browsing tagged content

## License

BSD-3-Clause-Clear. See LICENSE file for details.
