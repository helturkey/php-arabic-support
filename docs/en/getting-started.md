# Getting Started

## Installation

```bash
composer require helturkey/php-arabic-support
```

## Plain PHP usage

```php
use ArabicSupport\Arabic;

Arabic::unicodeSlug('دليل المستخدم العربي');
Arabic::searchKey('إدارة المبيعات');
Arabic::sanitize('<b>أَحْمَدُ</b>');
```

## Laravel usage

Laravel integration is auto-discovered. You may publish the config file:

```bash
php artisan vendor:publish --tag=php-arabic-support-config
```

Use the facade or `Str` macros:

```php
use ArabicSupport\Laravel\Facades\Arabic;
use Illuminate\Support\Str;

Arabic::unicodeSlug('دليل المستخدم');
Str::arabicSearchKey('إدارة المبيعات');
```

## Recommended extensions

`ext-mbstring` and `ext-intl` are recommended for best Unicode behavior. The package includes fallbacks when they are unavailable.
