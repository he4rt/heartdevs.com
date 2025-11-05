.DEFAULT_GOAL := help

.PHONY: help
help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: route-list
route-list: ## List all registered routes
	@php artisan route:list --ansi

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
	@$(CURDIR)/vendor/bin/phpstan analyse --ansi

.PHONY: p
p: phpstan ## Alias for phpstan

.PHONY: test-phpstan
test-phpstan: ## Run PHPStan in test mode
	@$(CURDIR)/vendor/bin/phpstan analyse --ansi

.PHONY: format
format: rector pint ## Run Pint and Rector and try to fixes the source code

.PHONY: f
f: format ## Alias for format

.PHONY: check
check: test-rector test-pint test-phpstan ## Run Pint, PHPStan with Rector in dry-run mode

.PHONY: test
test: ## Run all tests
	@$(CURDIR)/vendor/bin/pest --parallel --compact

.PHONY: t
t: test ## Alias for test

.PHONY: test-unit
test-unit: ## Run unit tests
	@$(CURDIR)/vendor/bin/pest --parallel --compact --group=unit

.PHONY: test-feature
test-feature: ## Run feature tests
	@$(CURDIR)/vendor/bin/pest --parallel --compact --group=feature

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
	@composer install
	@npm install
	@composer run-script post-root-package-install
	@composer run-script post-create-project-cmd
	@php artisan key:generate --ansi
	@php artisan storage:link --ansi
	@composer run-script ide-helper
