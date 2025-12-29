# phpIP

**Intellectual Property Management System**

A modern, open-source docketing system for managing patents, trademarks, designs, and other IP rights. Built for IP law firms and corporate IP departments.

---

## Overview

phpIP provides a complete solution for intellectual property portfolio management:

- **Matter Management** - Track patents, trademarks, designs, and other IP rights through their entire lifecycle
- **Task & Deadline Tracking** - Automated task generation based on configurable business rules
- **Renewal Management** - Complete renewal workflow with fee calculation, invoicing, and payment tracking
- **Document Generation** - Merge templates with matter data for automated document creation
- **Multi-language Support** - Full UI localization (English, French, German, Chinese)
- **Audit Trail** - Complete change history for compliance and accountability

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Database | PostgreSQL |
| Frontend | Tailwind CSS 4, DaisyUI 5, Alpine.js |
| Build | Vite, Sass |
| Testing | PHPUnit 11 |

## Requirements

- PHP 8.2 or higher
- PostgreSQL 14 or higher
- Composer 2.x
- Node.js 18+ and npm

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/jjdejong/phpip.git
cd phpip
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database connection:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=phpip
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Set up the database

```bash
php artisan migrate --seed
```

### 5. Build assets

```bash
npm run build
```

### 6. Start the development server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## Features

### Matter Lifecycle Management

Track IP matters from filing through grant, maintenance, and expiry. Support for:

- Patents and patent applications
- Trademarks
- Designs
- Utility models
- Plant variety rights

### Automated Task Generation

Configure business rules that automatically generate tasks based on events:

- Filing triggers examination request deadlines
- Publication triggers opposition periods
- Grant triggers first renewal payment
- Customizable rules per country and category

### Renewal Management

Complete renewal workflow:

- Automatic fee calculation based on country and year
- Client call generation
- Reminder scheduling
- Invoice generation
- Payment tracking
- Integration with external renewal services

### Document Templates

Create DOCX templates with merge fields:

- Drag-and-drop merge with matter data
- Email template support
- Customizable field mappings

### Multi-language Support

- UI available in English, French, German, and Chinese
- Per-user language preferences
- Translatable database fields (event names, roles, categories)

### Role-Based Access Control

- **Admin** - Full system access
- **Read-Write** - Data entry and modification
- **Read-Only** - View-only access
- **Client** - External client portal with limited access

## Development

### Running Tests

```bash
# Set up test database
php artisan migrate:fresh --env=testing --seed

# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Code Quality

```bash
# Format PHP code
npm run lint:php

# Format Blade templates
npm run format:blade
```

### Development Server

```bash
# Start Laravel dev server
php artisan serve

# Start Vite dev server (in separate terminal)
npm run dev
```

## Project Structure

```
phpip/
├── app/
│   ├── Enums/          # PHP enums (UserRole, EventCode, etc.)
│   ├── Http/Controllers/
│   ├── Models/         # Eloquent models
│   ├── Policies/       # Authorization policies
│   ├── Services/       # Business logic
│   └── Traits/         # Reusable traits
├── database/
│   ├── migrations/     # Database migrations
│   ├── factories/      # Model factories
│   └── seeders/        # Database seeders
├── resources/
│   ├── css/           # Tailwind CSS
│   ├── js/            # Alpine.js components
│   └── views/         # Blade templates
├── routes/
│   └── web.php        # Web routes
└── tests/
    ├── Feature/       # Integration tests
    └── Unit/          # Unit tests
```

## Documentation

- [Wiki](https://github.com/jjdejong/phpip/wiki) - Comprehensive user documentation
- [Localization Guide](LOCALIZATION.md) - Multi-language setup
- [Renewal Management](https://github.com/jjdejong/phpip/wiki/Renewal-Management) - Renewal workflow documentation
- [Document Templates](https://github.com/jjdejong/phpip/wiki/Templates-(email-and-documents)) - Template creation guide

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the GPL License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

phpIP was originally created by patent attorneys who needed a flexible, user-centric IP management tool. It has evolved through community contributions into a comprehensive open-source solution for IP portfolio management.
