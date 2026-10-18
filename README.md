![Mobile Detect](https://user-images.githubusercontent.com/10502887/161483098-d40a2d7d-0e78-4f38-a7ac-49390718746e.png)

MobileDetectBundle
==================

[![CI](https://img.shields.io/github/actions/workflow/status/tattali/MobileDetectBundle/ci.yml?branch=5.x&label=CI)](https://github.com/tattali/MobileDetectBundle/actions/workflows/ci.yml?query=branch%3A5.x)
[![Latest Version](https://img.shields.io/packagist/v/tattali/mobile-detect-bundle)](https://packagist.org/packages/tattali/mobile-detect-bundle)
[![Downloads](https://img.shields.io/packagist/dt/tattali/mobile-detect-bundle)](https://packagist.org/packages/tattali/mobile-detect-bundle/stats)
[![License](https://img.shields.io/packagist/l/tattali/mobile-detect-bundle)](src/Resources/meta/LICENSE)

Symfony 5.4 bundle to detect mobile devices, manage mobile view and redirect to the mobile and tablet version.

This is the 5.x branch: it supports Symfony 5.4 on PHP 7.2.5 or later and gets security fixes only, like Symfony 5.4. For newer Symfony versions, use the newer major versions of the bundle (see the `main` branch).

*This bundle is a fork of [suncat2000/MobileDetectBundle](https://github.com/suncat2000/MobileDetectBundle). As this project doesn't look maintained anymore, we decided to create & maintain a fork. For more information read our [manifest](https://github.com/tattali/MobileDetectBundle/issues/8).*

Introduction
------------

This Bundle use [Mobile_Detect](https://github.com/serbanghita/Mobile-Detect) class and provides the following features:

* Detect the various mobile devices by Name, OS, browser User-Agent
* Manages site views for the various mobile devices (`mobile`, `tablet`, `full`)
* Redirects to mobile and tablet sites

Documentation
-------------

### Installation
```sh
composer require tattali/mobile-detect-bundle
```
*Install with Symfony legacy versions: [here](src/Resources/doc/legacy-versions.md)*
### Usage

#### Checking device

```php
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;

public function someaction(MobileDetectorInterface $mobileDetector)
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

#### Switch device view

For switch device view, use `device_view` GET parameter:

```url
http://localhost:8000?device_view={full/mobile/tablet}
```

Or using the Symfony toolbar
![mbd-bundle-sf-toolbar](https://user-images.githubusercontent.com/10502887/161488224-aaedde1c-d3c3-4636-8761-a207fbd5d4ff.png)

#### Going further

- [Symfony legacy versions](src/Resources/doc/legacy-versions.md)
- [Redirection](src/Resources/doc/redirection.md)
- [Full reference](src/Resources/doc/reference.md)

Contribute and feedback
-----------------------

Any feedback and contribution will be very appreciated.

License and credits
-------

This bundle is under the MIT license. See the complete [license](src/Resources/meta/LICENSE) in the bundle

Original authors: [suncat2000](https://github.com/suncat2000), [HenriVesala](https://github.com/HenriVesala), [netmikey](https://github.com/netmikey) and [all contributors](https://github.com/suncat2000/MobileDetectBundle/graphs/contributors)
