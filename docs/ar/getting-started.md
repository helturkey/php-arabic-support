# البدء السريع

## التثبيت

```bash
composer require helturkey/php-arabic-support
```

## استخدام PHP مباشر

```php
use ArabicSupport\Arabic;

Arabic::unicodeSlug('دليل المستخدم العربي');
Arabic::searchKey('إدارة المبيعات');
Arabic::sanitize('<b>أَحْمَدُ</b>');
```

## استخدام Laravel

يتم اكتشاف Service Provider تلقائيًا. ويمكن نشر ملف الإعدادات:

```bash
php artisan vendor:publish --tag=php-arabic-support-config
```

```php
use ArabicSupport\Laravel\Facades\Arabic;
use Illuminate\Support\Str;

Arabic::unicodeSlug('دليل المستخدم');
Str::arabicSearchKey('إدارة المبيعات');
```

## الامتدادات الموصى بها

يوصى بتفعيل `ext-mbstring` و`ext-intl` لتحسين التعامل مع Unicode والتطبيع والـ grapheme. توجد fallbacks عند عدم توفرها.
