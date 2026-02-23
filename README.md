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

Add tags to SilverStripe DataObjects.

## Setup

1. Add the following to your composer.json

```json
"require": {
    "azt3k/abc-silverstripe-taggable" : "dev-feature/ss"
}
```

2. Run `composer install`

3. Add the extension to the DataObjects you wish to tag

```php
Page::add_extension('Taggable')
```

4. Run dev/build

5. Start Tagging!

## SS 5 upgrade notes

Don't believe the TagPage.ss will work, as the includes do not exist, may need to find the correct namespaces.
Tag page controller -> Tag function needs testing

## License

Copyright (c) 2015, azt3k
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
