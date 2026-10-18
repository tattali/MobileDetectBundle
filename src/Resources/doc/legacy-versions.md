Installation
============

Composer
--------

This is the 7.x branch, for Symfony 7 (7.4 included):

```sh
composer require 'tattali/mobile-detect-bundle:^7.1'
```

Keep the `^7.1` constraint: on Symfony 7.4, a plain `composer require` installs 8.0.0,
which allows Symfony 7.4 but gets no more fixes.

Or add to `composer.json` in your project to `require` section:
```json
{
    "tattali/mobile-detect-bundle": "^7.1"
}
```

and run command:
```sh
composer update tattali/mobile-detect-bundle
```

For other Symfony versions, see the
[legacy versions page on the `main` branch](https://github.com/tattali/MobileDetectBundle/blob/main/src/Resources/doc/legacy-versions.md).
