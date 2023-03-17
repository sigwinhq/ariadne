.SILENT:
include vendor/sigwin/infra/resources/PHP/library.mk

vendor/sigwin/infra/resources/PHP/library.mk:
	mv composer.json composer.json~ && rm -f composer.lock
	docker run --rm --user '$(shell id -u):$(shell id -g)' --volume '$(shell pwd):/app' --workdir /app composer:2 require sigwin/infra
	mv composer.json~ composer.json && rm -f composer.lock

phar: | ${HOME}/.composer var/phpqa composer.lock ## Build PHAR file
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} box compile
	$(call block_end)
