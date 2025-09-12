# Flowlight Blueprint Extension

[![Tests](https://github.com/omnitech-solutions/flowlight-blueprint-extension/actions/workflows/tests.yml/badge.svg)](https://github.com/omnitech-solutions/flowlight-blueprint-extension/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/omnitech-solutions/flowlight-blueprint-extension.svg?style=flat-square)](https://packagist.org/packages/omnitech-solutions/flowlight-blueprint-extension)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Blueprint extension for generating DTOs, Organizers, and API-first components in Laravel.**

---

## ✨ Features

- **DTO Generation**
  Automatically scaffolds data transfer objects with attributes, validation rules, and messages.

- **Organizer Generation**
  Generates organizers for common CRUD workflows (`create`, `read`, `update`, `delete`, `list`).

- **API-First Approach**
  Blueprint-driven design ensures your API schema drives your application scaffolding.

- **Extensible Configuration**
  Override default field types, rules, and DTO settings via `config/flowlight.php`.

---

## 📦 Installation

```bash
composer require omnitech-solutions/flowlight-blueprint-extension --dev
```

The package will auto-register via Laravel’s package discovery.
If disabled, you can manually register:

```php
// config/app.php
'providers' => [
    Flowlight\Generator\Providers\FlowlightServiceProvider::class,
];
```

---

## ⚙️ Configuration

Publish config and stubs:

```bash
php artisan vendor:publish --tag=flowlight-config
php artisan vendor:publish --tag=flowlight-stubs
```

This creates:

- `config/flowlight.php` → field type rules, defaults
- `stubs/flowlight/` → stub templates (e.g., `dto.stub`)

---

## 🚀 Usage

### 1. Generate API Components

```bash
php artisan flowlight:generate Metric \
    --fields="value:decimal{18,6} unit:string{32}? description:text? recorded_at:datetime" \
    --dto --organizers
```

This command:

- Creates a `MetricData` DTO in `app/Domain/Metrics/Data/`
- Generates organizers (create/read/update/delete/list) for `Metric`

---

### 2. DTO Example

Generated DTO (simplified):

```php
namespace App\Domain\Metrics\Data;

use Flowlight\BaseData;
use Illuminate\Support\Collection;

final class MetricData extends BaseData
{
    public static function attributes(): array
    {
        return [
            'value' => 'Value',
            'unit' => 'Unit',
            'description' => 'Description',
            'recorded_at' => 'Recorded At',
        ];
    }

    public static function messages(): array
    {
        return [
            'value.required' => 'Value is required.',
            'unit.string' => 'Unit must be text.',
        ];
    }

    protected static function rules(): Collection
    {
        return collect([
            'value' => ['required', 'numeric'],
            'unit' => ['sometimes', 'string', 'max:32'],
            'description' => ['sometimes', 'string'],
            'recorded_at' => ['required', 'date'],
        ]);
    }
}
```

---

## 🧪 Testing

```bash
make test
make coverage
make static
```

Or directly:

```bash
composer test
composer analyse
composer lint
```

---

## 📂 Project Structure

```
src/
  Config/         # Config wrappers (FieldConfig, DtoConfig, OrganizerConfig, ModelConfigWrapper)
  Console/        # Artisan commands (GenerateApiCommand)
  Generators/     # DTO + Organizer generators
  Providers/      # FlowlightServiceProvider
stubs/            # DTO stub templates
config/           # Default field types config
tests/            # Unit and feature tests (Pest)
```

---

## 📝 Roadmap

- [ ] Add full organizer stub generation
- [ ] Support nested DTOs and value objects
- [ ] Add mapper/gateway scaffolding
- [ ] CLI generators for GraphQL schema

---

## 🤝 Contributing

PRs are welcome! Run tests + static analysis before submitting:

```bash
make ci
```

---

## 📜 License

The MIT License (MIT).
See [LICENSE](LICENSE) for details.
