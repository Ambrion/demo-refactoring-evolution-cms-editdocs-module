export PUID ?= $(shell id -u)
export PGID ?= $(shell id -g)

build:
	if [ ! -f .env ]; then cp .env.dist .env; fi
	docker compose build --build-arg PUID=$(PUID) --build-arg PGID=$(PGID)
install:
	docker compose exec --user dev php composer install
start:
	docker compose up -d
stop:
	docker compose down
enter:
	docker compose exec --user dev php sh
