USER_ID=$(shell id -u)

DC = @USER_ID=$(USER_ID) docker compose

init: build install

build:
	${DC} build $(c)

up:
	${DC} up -d $(c)

down:
	${DC} down -v $(c)

install:
	${DC} run --no-deps --rm php composer install

console:
	${DC} exec php /bin/bash
