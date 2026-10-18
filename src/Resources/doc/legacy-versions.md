Installation
============

Composer
--------

On Symfony 8, install the latest version:

```sh
composer require tattali/mobile-detect-bundle
```

On older Symfony versions, require the bundle version made for your Symfony version.
On Symfony 7 (7.4 included), require `^7.1`: a plain `composer require` on Symfony 7.4 installs 8.0.0,
which allows Symfony 7.4 but gets no more fixes.

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
    "tattali/mobile-detect-bundle": "^8.1"
}
```

and run command:
```sh
composer update tattali/mobile-detect-bundle
```
