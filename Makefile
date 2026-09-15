.PHONY: up down build restart logs ps ssl seed shell-php shell-nginx mysql-shell clean

COMPOSE = docker compose

up: ssl
	@if [ ! -f .env ]; then cp .env.example .env; echo "Created .env from .env.example"; fi
	$(COMPOSE) up -d --build
	@echo "Waiting for MySQL to be healthy..."
	@until [ "$$($(COMPOSE) ps -q mysql | xargs docker inspect -f '{{.State.Health.Status}}' 2>/dev/null)" = "healthy" ]; do sleep 2; done
	$(MAKE) seed
	@echo ""
	@echo "JobHunt is up: https://localhost (self-signed cert)"

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build --no-cache

restart:
	$(COMPOSE) restart

logs:
	$(COMPOSE) logs -f

ps:
	$(COMPOSE) ps

ssl:
	@mkdir -p docker/nginx/ssl
	@if [ ! -f docker/nginx/ssl/cert.pem ]; then \
		echo "Generating self-signed TLS cert..."; \
		MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL="*" openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
			-keyout docker/nginx/ssl/key.pem \
			-out docker/nginx/ssl/cert.pem \
			-subj "/C=VN/ST=HCM/L=HCM/O=JobHunt Lab/CN=localhost" 2>/dev/null; \
	else \
		echo "TLS cert already exists, skipping."; \
	fi

seed:
	MSYS_NO_PATHCONV=1 $(COMPOSE) exec -T php php /scripts/seed.php

shell-php:
	$(COMPOSE) exec php sh

shell-nginx:
	$(COMPOSE) exec nginx sh

mysql-shell:
	$(COMPOSE) exec mysql sh -c 'mysql -u$$MYSQL_USER -p$$MYSQL_PASSWORD $$MYSQL_DATABASE'

clean: down
	rm -rf docker/nginx/ssl/*.pem
	$(COMPOSE) down -v
