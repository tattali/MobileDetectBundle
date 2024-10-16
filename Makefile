all: test

phpunit:
	php vendor/bin/phpunit

lint:
	php vendor/bin/php-cs-fixer fix --diff
	php vendor/bin/phpcs --report=code
	php vendor/bin/phpstan analyse

refactor:
	php vendor/bin/php-cs-fixer fix
	php vendor/bin/rector

test: lint phpunit
