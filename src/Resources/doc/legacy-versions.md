Installation
============

Composer
--------

Composer installs the newest bundle version that supports your Symfony version:

```sh
composer require tattali/mobile-detect-bundle
```

To pin a version explicitly:

| Symfony | Command |
|---------|---------|
| 8 | `composer require 'tattali/mobile-detect-bundle:^8.1'` |
| 7 | `composer require 'tattali/mobile-detect-bundle:^7.1'` |
| 6.4 | `composer require 'tattali/mobile-detect-bundle:^6.0'` |
| 5.4 | `composer require 'tattali/mobile-detect-bundle:^5.0'` (security fixes only) |
| 5.0 to 6.3 | `composer require 'tattali/mobile-detect-bundle:2.2.*'` (no longer maintained) |
| 3.4 to 4.4 | `composer require 'tattali/mobile-detect-bundle:2.1.*'` (no longer maintained) |

Or add the version to the `require` section of your project's `composer.json`:
```json
{
    "tattali/mobile-detect-bundle": "2.1.*"
}
```

and run command:
```sh
composer update tattali/mobile-detect-bundle
```
