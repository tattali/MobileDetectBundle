![Mobile Detect](https://user-images.githubusercontent.com/10502887/161483098-d40a2d7d-0e78-4f38-a7ac-49390718746e.png)

MobileDetectBundle
==================

[![CI](https://img.shields.io/github/actions/workflow/status/tattali/MobileDetectBundle/ci.yml?branch=7.x&label=CI)](https://github.com/tattali/MobileDetectBundle/actions/workflows/ci.yml?query=branch%3A7.x)
[![Latest Version](https://img.shields.io/packagist/v/tattali/mobile-detect-bundle)](https://packagist.org/packages/tattali/mobile-detect-bundle)
[![Downloads](https://img.shields.io/packagist/dt/tattali/mobile-detect-bundle)](https://packagist.org/packages/tattali/mobile-detect-bundle/stats)
[![License](https://img.shields.io/packagist/l/tattali/mobile-detect-bundle)](LICENSE)

Symfony 7 bundle to detect mobile devices, manage mobile view and redirect to the mobile and tablet version.

This is the 7.x branch: it supports Symfony 7.0 to 7.4 on PHP 8.2 or later and gets bug fixes only. For Symfony 8, use the newer major version of the bundle (see the `main` branch).

*This bundle is a fork of [suncat2000/MobileDetectBundle](https://github.com/suncat2000/MobileDetectBundle). As this project doesn't look maintained anymore, we decided to create & maintain a fork. For more information read our [manifest](https://github.com/tattali/MobileDetectBundle/issues/8).*

Introduction
------------

This Bundle use [MobileDetect](https://github.com/serbanghita/Mobile-Detect) class and provides the following features:

* Detect the various mobile devices by Name, OS, browser User-Agent
* Manages site views for the various mobile devices (`mobile`, `tablet`, `desktop`)
* Redirects to mobile and tablet sites

Documentation
-------------

### Installation
```sh
composer require 'tattali/mobile-detect-bundle:^7.1'
```
Keep the `^7.1` constraint: on Symfony 7.4, a plain `composer require` installs 8.0.0, which gets no more fixes.

*Install with Symfony legacy versions: [here](src/Resources/doc/legacy-versions.md)*
### Usage

#### Checking device

```php
use Detection\MobileDetect;

public function someaction(MobileDetect $mobileDetector)
{
    $mobileDetector->isMobile();
    $mobileDetector->isTablet();
    $mobileDetector->is('iPhone');
}
```

With Twig
```twig
{% if is_mobile() %}
{% if is_tablet() %}
{% if is_device('iPhone') %} # magic methods is[...]
```
Available User-Agents (uaMatch) with the php `is()` and twig `is_device()` [here](https://github.com/serbanghita/Mobile-Detect/blob/4.8.x/MobileDetect.json)

#### Switch device view

For switch device view, use `device_view` GET parameter:

```url
https://localhost:8000?device_view={desktop/mobile/tablet}
```

Or using the Symfony toolbar
![mbd-bundle-sf-toolbar](src/Resources/doc/sf-toolbar.png)

#### Going further

- [Symfony legacy versions](src/Resources/doc/legacy-versions.md)
- [Redirection](src/Resources/doc/redirection.md)
- [Full reference](src/Resources/doc/reference.md)

Contribute and feedback
-----------------------

Any feedback and contribution will be very appreciated.

License and credits
-------

This bundle is under the MIT license. See the complete [license](LICENSE) in the bundle

Original authors: [suncat2000](https://github.com/suncat2000), [HenriVesala](https://github.com/HenriVesala), [netmikey](https://github.com/netmikey) and [all contributors](https://github.com/suncat2000/MobileDetectBundle/graphs/contributors)
