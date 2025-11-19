.PHONY: help install up down shell test test-watch lint fix stan insights rector rector-fix ide-helper fresh clear d

##@ General

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

##@ Docker/Sail

up: ## Start Sail
	./vendor/bin/sail up -d

down: ## Stop Sail
	./vendor/bin/sail down

d: ## Rebuild containers (down -v + up)
	./vendor/bin/sail down -v
	./vendor/bin/sail up -d

shell: ## Access Sail shell
	./vendor/bin/sail shell

##@ Testing

test: ## Run Pest tests
	./vendor/bin/sail artisan test

test-watch: ## Run Pest tests in watch mode
	./vendor/bin/sail artisan test --watch

##@ Code Quality

stan: ## Run all linters
	./vendor/bin/phpstan analyse

fix: ## Fix code style with Pint
	./vendor/bin/sail php vendor/bin/pint

insights: ## Run PHP Insights
	./vendor/bin/sail artisan insights

rector-fix: ## Run Rector and apply fixes
	./vendor/bin/sail php vendor/bin/rector process

##@ Development

ide-helper: ## Generate IDE helper files
	./vendor/bin/sail artisan ide-helper:generate
	./vendor/bin/sail artisan ide-helper:models --nowrite
	./vendor/bin/sail artisan ide-helper:meta

fresh: ## Fresh database with seeding
	./vendor/bin/sail artisan migrate:fresh --seed

clear: ## Clear all caches and refresh database
	./vendor/bin/sail artisan clear-compiled
	./vendor/bin/sail artisan cache:clear
	./vendor/bin/sail artisan route:clear
	./vendor/bin/sail artisan view:clear
	./vendor/bin/sail artisan config:clear
	./vendor/bin/sail artisan optimize:clear
	./vendor/bin/sail artisan migrate:fresh --seed
	./vendor/bin/sail artisan jetstream:verify --show-recommendations
#	./vendor/bin/sail artisan scout:import "App\Models\User"
#	./vendor/bin/sail artisan scout:import "App\Models\Team"
	./vendor/bin/sail npm run build
