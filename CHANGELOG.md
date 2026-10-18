Changelog
=========

8.1.0
-----

- Require Symfony 8 and PHP 8.4; drop Symfony 7.4 and PHP 8.2 and 8.3. Each major version of the bundle now supports one Symfony major version: Symfony 7 projects use the 7.x versions (require `^7.1`: on Symfony 7.4 a plain `composer require` installs 8.0.0, which gets no more fixes), Symfony 6.4 projects the 6.x versions and Symfony 5.4 projects the 5.x versions (security fixes only)
- CI: test PHP 8.4 and 8.5 against Symfony 8

8.0.0
-----

- Add Symfony 8 support (thanks @gulaandrij for #41 and @phiamo for #40, requested in #38)
- Require Symfony 7.4 LTS or 8.x; drop Symfony 7.2 and 7.3
- Declare `symfony/http-foundation` as a direct dependency
- Read the current route from the request attributes instead of `Request::get()`, removed in Symfony 8
- Fix redirection being disabled when the redirect host uses an env var such as `%env(MOBILE_HOST)%` (#36); invalid hosts are now skipped at runtime
- CI: test PHP 8.2 to 8.5 against Symfony 7.4 and 8.x

See [Migrate to 8x](src/Resources/doc/migration-8x.md).

7.0.0
-----

- Symfony 7.2+ and PHP 8.2+ support. See [Migrate to 7x](src/Resources/doc/migration-7x.md).
