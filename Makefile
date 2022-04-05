all: test

phpunit:
	php vendor/bin/phpunit

lint:
	PHP_CS_FIXER_IGNORE_ENV=1 php vendor/bin/php-cs-fixer fix --diff
	php vendor/bin/phpcs --report=code
	php vendor/bin/phpstan analyse

test: lint phpunit
