# Laravel NDK-CRUD Package Structure

```
ndk-crud/
├── composer.json
├── src/
│   ├── Commands/
│   │   └── MakeNdkCrudCommand.php
│   ├── NdkCrudServiceProvider.php
│   └── stubs/
│       ├── controller.stub
│       ├── migration.stub
│       ├── model.stub
│       └── routes.stub
```


# NDK-CRUD Package

A Laravel package to generate CRUD operations (migration, model, routes, controller) with a single artisan command.

## Installation

### 1. Add the repository to your composer.json:

```json
"repositories": [
    {
        "type": "path",
        "url": "../path/to/ndk-crud"
    }
]
```

### 2. Require the package:

```bash
composer require ndkumarawansha/ndk-crud
```

### 3. The package will be auto-discovered by Laravel.

## Usage

Run the artisan command:

```bash
php artisan make:ndk-crud Product
```

The command will:

1. Ask you for table columns and their data types
2. Create a migration file with the specified columns
3. Create a model file with the fillable properties
4. Create a controller with all CRUD operations
5. Add resource routes to your web.php file

## Example

```bash
php artisan make:ndk-crud Product
```

Sample Input:
- Column name: name, Data type: string, Nullable: No
- Column name: price, Data type: decimal, Nullable: No
- Column name: description, Data type: text, Nullable: Yes
- Column name: category_id, Data type: integer, Nullable: Yes

This will generate:
- A migration for the products table
- The Product model
- A ProductController with index, create, store, show, edit, update, and destroy methods
- Resource routes for products

## Support

This package supports Laravel 10, 11, and 12.

## License

MIT