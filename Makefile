all: test

phpunit:
	XDEBUG_MODE=coverage | php vendor/bin/phpunit --display-phpunit-deprecations

lint:
	php vendor/bin/php-cs-fixer fix --diff
	php vendor/bin/phpstan analyse --memory-limit=-1

test: lint phpunit
