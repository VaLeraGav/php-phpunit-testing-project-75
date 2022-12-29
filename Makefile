start:
	./bin/page-loader https://habr.com/ru/post/137664/ -o /mnt/c/Users/Ucer/Desktop/Hexlet/php-testing-project-75/tmp

start-2:
	./bin/page-loader https://ru.hexlet.io/courses -o /mnt/c/Users/Ucer/Desktop/Hexlet/php-testing-project-75/tmp

install:
	composer install

test:
	composer exec --verbose phpunit tests

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src bin

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src tests

test-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml