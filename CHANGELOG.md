# Changelog

## [1.27.0](https://github.com/ymirapp/wordpress-plugin/compare/v1.26.5...v1.27.0) (2025-10-05)


### Features

* Add compatibility layer for divi ([6663368](https://github.com/ymirapp/wordpress-plugin/commit/66633684271a0904e7cf2d1cc6d0b3961ee1ea9b))
* Disable unnecessary `available_updates_disk_space` health check ([6c866d9](https://github.com/ymirapp/wordpress-plugin/commit/6c866d96ee22547c346c36449c05ad6d458a38ce))

## [1.26.5](https://github.com/ymirapp/wordpress-plugin/compare/v1.26.4...v1.26.5) (2025-09-09)


### Bug Fixes

* Change capability required to access lifterlms setup wizard ([5cc67e5](https://github.com/ymirapp/wordpress-plugin/commit/5cc67e5c5e77f555f54847ae1f27dd067f3b2b5c))

## [1.26.4](https://github.com/ymirapp/wordpress-plugin/compare/v1.26.3...v1.26.4) (2025-07-16)


### Bug Fixes

* Don't add `/wp` prefix to `build` directory with radicle projects ([3264105](https://github.com/ymirapp/wordpress-plugin/commit/3264105db96a727d78e10323bbd2aea6258e9ff5))

## [1.26.3](https://github.com/ymirapp/wordpress-plugin/compare/v1.26.2...v1.26.3) (2025-07-13)


### Bug Fixes

* Enforce that we always have `response.data` when passing response to `fileUploaded` ([39a852d](https://github.com/ymirapp/wordpress-plugin/commit/39a852dacc19885f77ec638fab6d210a1cc3441f))

## [1.26.2](https://github.com/ymirapp/wordpress-plugin/compare/v1.26.1...v1.26.2) (2025-07-04)


### Bug Fixes

* Disable `upload_path` and `upload_url_path` options ([d98b2fe](https://github.com/ymirapp/wordpress-plugin/commit/d98b2fe9cb621e8fa8ce26276a221d6e8e4ae74f))

## [1.26.1](https://github.com/ymirapp/wordpress-plugin/compare/v1.26.0...v1.26.1) (2025-06-24)


### Bug Fixes

* Add `clipboard` as dependency for the plupload handler ([79b74d2](https://github.com/ymirapp/wordpress-plugin/commit/79b74d2f06c62a6f1b736e7e62d1a08855c49e70))

## [1.26.0](https://github.com/ymirapp/wordpress-plugin/compare/v1.25.0...v1.26.0) (2025-03-22)


### Features

* Add support for radicle ([aade37b](https://github.com/ymirapp/wordpress-plugin/commit/aade37bb46f8c2eb4deaf868f190a5fa94f07a4c))

## [1.25.0](https://github.com/ymirapp/wordpress-plugin/compare/v1.24.0...v1.25.0) (2025-01-31)


### Features

* Disable some health checks when on ymir ([9e42fa9](https://github.com/ymirapp/wordpress-plugin/commit/9e42fa941ae21f91dc58b5e9c31f92e88b060109))

## [1.24.0](https://github.com/ymirapp/wordpress-plugin/compare/v1.23.0...v1.24.0) (2024-09-06)


### Features

* add `ymir_admin_notices` filter to handle admin notices ([514679b](https://github.com/ymirapp/wordpress-plugin/commit/514679bd8aced83e09085a1a666c299239db9afc))
* add an admin notice when production access isn't enabled in ses ([204327c](https://github.com/ymirapp/wordpress-plugin/commit/204327c3ef9b70b12fa9572be4927999c8dd28d1))
* add an admin notice when ses email is disabled because of the vanity domain ([55d7703](https://github.com/ymirapp/wordpress-plugin/commit/55d7703ef054460d951e85332431a51d5236271a))

## [1.23.0](https://github.com/ymirapp/wordpress-plugin/compare/v1.22.1...v1.23.0) (2024-08-27)


### Features

* allow `ymir_page_caching_urls_to_clear` filter to return an array or string ([0ae69cf](https://github.com/ymirapp/wordpress-plugin/commit/0ae69cf02228a13a520759c2c1337a0ac850cf13))

## [1.22.1](https://github.com/ymirapp/wordpress-plugin/compare/v1.22.0...v1.22.1) (2024-08-16)


### Bug Fixes

* don't allow wp all import to use php ([e4e7508](https://github.com/ymirapp/wordpress-plugin/commit/e4e75080442e6dd6265647f63764533ffe2ca082))

## [1.22.0](https://github.com/ymirapp/wordpress-plugin/compare/v1.21.1...v1.22.0) (2024-08-11)


### Features

* add support for `stream_metadata` operations with stream wrapper ([bacc240](https://github.com/ymirapp/wordpress-plugin/commit/bacc240a90b66adc54f3858a72baef0c20a14a60))
* move woocommerce logs directory to private cloud storage ([d9f3512](https://github.com/ymirapp/wordpress-plugin/commit/d9f3512c736d6fc15ec7521cfe661e8184277680))

## [1.21.1](https://github.com/ymirapp/wordpress-plugin/compare/v1.21.0...v1.21.1) (2024-07-12)


### Bug Fixes

* cast source value as int ([f7bf891](https://github.com/ymirapp/wordpress-plugin/commit/f7bf8910c16ba5ee459926bed3e2f68389ce6a8a))

## [1.21.0](https://github.com/ymirapp/wordpress-plugin/compare/v1.20.2...v1.21.0) (2023-11-17)


### Features

* add php 8.2 support ([a718c51](https://github.com/ymirapp/wordpress-plugin/commit/a718c5116093d3e724988074ae55c90d44c0bf5d))
* add the `ymir_run_wp_cli_command` helper function ([8f8df54](https://github.com/ymirapp/wordpress-plugin/commit/8f8df54d472532de981f2b11d8c8c682370d2571))
* disable automatic woocommerce image resizing when not needed ([c46d5ae](https://github.com/ymirapp/wordpress-plugin/commit/c46d5ae3fc13573ecf17c5cbe92ec5123fdfb6f3))
