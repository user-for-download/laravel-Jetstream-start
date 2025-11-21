***
# Laravel Team Management System

A robust Laravel Jetstream application enhanced with a strict Service Layer architecture, Data Transfer Objects (DTOs), and granular Role-Based Access Control (RBAC). This project demonstrates advanced team management workflows, including ownership transfer and custom dashboard widgets.

# Tests:    
*   3 risky, 9 skipped, 234 passed (497 assertions)

## 🚀 Key Features

*   **Advanced Team Management**: Create teams, invite members, remove members, and **transfer team ownership**.
*   **Custom RBAC**: Enum-based role system (`Admin`, `Editor`, `Viewer`) with specific permission mapping.
*   **Service Layer Architecture**: Logic is decoupled from Controllers using `TeamService` and `UserService`.
*   **Data Transfer Objects (DTOs)**: Strictly typed data flow between layers.
*   **Interactive Dashboard**: Livewire components for Team Statistics, Recent Activity, Quick Actions, and Permission Visualization.
*   **System Health**: Built-in health check endpoint for monitoring database, cache, and storage status.

## 🛠 Tech Stack

*   **Framework**: Laravel 11
*   **Stack**: Jetstream (Livewire + Blade)
*   **Frontend**: Tailwind CSS
*   **Database**: MySQL / SQLite (Configurable)
*   **Code Quality**: PHPStan, Laravel Pint, Rector

## ⚙️ Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/user-for-download/laravel-Jetstream-start.git
    cd laravel-Jetstream-start
    ```

2.  **Install Dependencies**
    ```bash
    composer require laravel/sail --dev
    php artisan sail:install
    ./vendor/bin/sail up
    ```

3.  **Environment Setup**
    ```bash
    cp .env.example .env
    sail artisan key:generate
    ```

4.  **Setup**
    Configure your database credentials in `.env`, then run migrations and seeders. The seeder creates demo users and teams with various roles.
    ```bash
	./vendor/bin/sail artisan clear-compiled
	./vendor/bin/sail artisan cache:clear
	./vendor/bin/sail artisan route:clear
	./vendor/bin/sail artisan view:clear
	./vendor/bin/sail artisan config:clear
	./vendor/bin/sail artisan optimize:clear
	./vendor/bin/sail artisan migrate:fresh --seed
	./vendor/bin/sail artisan jetstream:verify --show-recommendations
	./vendor/bin/sail npm run build
    ```
## 🏗 Architecture Overview

This project moves away from "Fat Controllers" by utilizing a Service Layer pattern:

*   **Controllers** (`App\Http\Controllers`): Handle HTTP requests, authorization, and response formatting.
*   **Services** (`App\Services`): Contain business logic (e.g., `TeamService::transferOwnership`).
*   **DTOs** (`App\DataTransferObjects`): Immutable objects used to pass data into Services, ensuring type safety.
*   **Policies** (`App\Policies`): Handle authorization logic, including a global `before` check for Team Owners.

## 👥 Roles & Permissions

The system uses `App\Enums\RoleEnum` to define access levels:

| Role | Description | Permissions |
| :--- | :--- | :--- |
| **Owner** | Creator of the team | Full access (Implicit) |
| **Admin** | Team Administrator | Create, Read, Update, Delete, Manage Members |
| **Editor** | Content Creator | Create, Read, Update |
| **Viewer** | Read-only user | Read Only |

## 🧪 Testing

The project includes a suite of tests to ensure stability.

```bash
  # Run all tests
  sail artisan test

  Tests:    3 risky, 9 skipped, 234 passed (497 assertions)
  Duration: 12.38s

```

## 👤 Demo Accounts

If you ran the seeder, you can log in with:

*   **Admin**: `admin@example.com` / `password`
*   **User**: `john@example.com` / `password`

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
