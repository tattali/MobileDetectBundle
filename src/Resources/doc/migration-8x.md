Migrate to 8x
=============

Changes from version `^7` to `^8`

- Symfony 8 is supported. The minimum Symfony version is now 7.4 (LTS): Symfony 7.2 and 7.3 are no longer maintained upstream. Stay on `tattali/mobile-detect-bundle:7.0.*` if you cannot upgrade Symfony yet.
- PHP 8.2 is still the minimum. Composer installs Symfony 7.4 on PHP 8.2/8.3 and can install Symfony 8 on PHP 8.4+.
- `RequestResponseListener` now reads the current route from the request attributes (`$request->attributes->get('_route')`) instead of `Request::get()`, which Symfony 8 removed. If you extend the listener and override `mustRedirect()` or `getRedirectUrl()`, do the same.
- Redirect hosts can now be set with env vars (`host: '%env(MOBILE_HOST)%'`). The URL check that used to run when the container was compiled, and silently disabled the redirection for such hosts, now runs on the resolved value: a redirection whose host is not a valid URL is skipped at runtime.
- `MobileDetectExtension` gained a protected `usesEnvVar()` helper.
