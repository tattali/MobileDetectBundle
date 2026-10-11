Changelog
=========

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
