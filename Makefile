.DEFAULT_GOAL := help

.PHONY: help
help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: route-list
route-list: ## List all registered routes
	@php artisan route:list --ansi --except-vendor

.PHONY: rl
rl: route-list ## Alias for route-list

.PHONY: pint
pint: ## Run Pint code style fixer
	@export XDEBUG_MODE=off
	@$(CURDIR)/vendor/bin/pint --parallel
	@unset XDEBUG_MODE

.PHONY: test-pint
test-pint: ## Run Pint code style fixer in test mode
	@export XDEBUG_MODE=off
	@$(CURDIR)/vendor/bin/pint --test --parallel
	@unset XDEBUG_MODE=off

.PHONY: rector
rector: ## Run Rector
	@$(CURDIR)/vendor/bin/rector process

.PHONY: test-rector
test-rector: ## Run Rector in test mode
	@$(CURDIR)/vendor/bin/rector process --dry-run

.PHONY: phpstan
phpstan: ## Run PHPStan
	@$(CURDIR)/vendor/bin/phpstan analyse --ansi --memory-limit=1G

.PHONY: p
p: phpstan ## Alias for phpstan

.PHONY: test-phpstan
test-phpstan: ## Run PHPStan in test mode
	@$(CURDIR)/vendor/bin/phpstan analyse --ansi --memory-limit=1G

.PHONY: format
format: rector pint ## Run Pint and Rector and try to fixes the source code

.PHONY: f
f: format ## Alias for format

.PHONY: check
check: test-rector test-pint test-phpstan ## Run Pint, PHPStan with Rector in dry-run mode

.PHONY: c
c: check ## Alias for check

.PHONY: test
test: ## Run all tests
	@php artisan test --compact

.PHONY: t
t: test ## Alias for test

.PHONY: test-unit
test-unit: ## Run unit tests
	@php artisan test --compact --group=unit

.PHONY: test-feature
test-feature: ## Run feature tests
	@php artisan test --compact --group=feature

.PHONY: test-arch
test-arch: ## Run architecture tests
	@php artisan test --compact --group=arch

.PHONY: setup-test-db
setup-test-db: ## Create & migrate the testing database (honors .env.testing overrides)
	@php artisan migrate --env=testing --no-interaction --force

.PHONY: migrate-fresh
migrate-fresh: ## Run migrations and seed the database
	@php artisan migrate:fresh --seed

.PHONY: env-up
env-up: ## Start the development environment
	@docker compose --file docker-compose.yml up --detach

.PHONY: env-down
env-down: ## Start the development environment
	@docker compose --file docker-compose.yml down --rmi all --volumes

.PHONY: dev
dev: ## Start the server
	@composer run-script dev

.PHONY: setup
setup: ## Setup the project
	@composer run-script setup

-include Makefile.local
