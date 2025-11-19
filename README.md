***
# Laravel Team Management System

A robust Laravel Jetstream application enhanced with a strict Service Layer architecture, Data Transfer Objects (DTOs), and granular Role-Based Access Control (RBAC). This project demonstrates advanced team management workflows, including ownership transfer and custom dashboard widgets.

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
    git clone https://github.com/yourusername/project-name.git
    cd project-name
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    npm install
    ```

3.  **Environment Setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Database Setup**
    Configure your database credentials in `.env`, then run migrations and seeders. The seeder creates demo users and teams with various roles.
    ```bash
    php artisan migrate --seed
    ```

5.  **Build Assets**
    ```bash
    npm run build
    ```

6.  **Run Local Server**
    ```bash
    php artisan serve
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
php artisan test

# Run code style fixer
./vendor/bin/pint
```

## 👤 Demo Accounts

If you ran the seeder, you can log in with:

*   **Admin**: `admin@example.com` / `password`
*   **User**: `john@example.com` / `password`

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
