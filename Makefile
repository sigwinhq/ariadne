.SILENT:
include vendor/sigwin/infra/resources/PHP/phar.mk

vendor/sigwin/infra/resources/PHP/phar.mk:
	mv composer.json composer.json~ && rm -f composer.lock
	docker run --rm --user '$(shell id -u):$(shell id -g)' --volume '$(shell pwd):/app' --workdir /app composer:2 require sigwin/infra
	mv composer.json~ composer.json && rm -f composer.lock
