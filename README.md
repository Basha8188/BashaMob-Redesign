# BashaMob

واجهة عربية RTL لنظام إدارة محل موبايلات وكمبيوتر، مبنية باستخدام Laravel 12 وBlade وVite. يحتوي المشروع حاليًا على واجهة العملاء، تسجيل جهاز للصيانة، دخول الموظفين، وإدارة تجريبية لأقسام المبيعات والمشتريات والمخزون والصيانة والعملاء والموردين والتقارير.

> **حالة المشروع:** الواجهات والبيانات داخل وحدات الإدارة Demo تفاعلية. لم تُنفذ بعد جداول وعمليات CRUD الإنتاجية للمنتجات والمبيعات والمشتريات والصيانة والمخزون.

## المتطلبات

- PHP 8.2 أو أحدث.
- Composer 2.
- Node.js 20 أو أحدث وnpm.
- MySQL أو MariaDB.

## التثبيت

```bash
composer install
npm ci
copy .env.example .env
php artisan key:generate
```

أنشئ قاعدة بيانات باسم `basha_mob`، ثم راجع بيانات الاتصال داخل `.env` ونفّذ:

```bash
php artisan migrate
php artisan storage:link
npm run build
php artisan serve
```

## التشغيل أثناء التطوير

```bash
npm run dev
php artisan serve
```

## الروابط الأساسية

- واجهة العملاء: `/`
- تسجيل جهاز العميل: `/customer/device-request`
- دخول الموظفين: `/staff/login`
- الإدارة: `/admin`

بيانات الدخول الظاهرة حاليًا مخصصة للعرض فقط:

```text
admin@bashamob.local
password
```

## إعداد قاعدة البيانات

القيم الافتراضية في `.env.example`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basha_mob
DB_USERNAME=root
DB_PASSWORD=
```

## الاختبارات

```bash
php artisan test
```

الاختبارات الحالية اختبارات Laravel أساسية فقط، ويجب إضافة Feature Tests لوحدات الأعمال عند تنفيذ قاعدة بيانات المنتجات والمبيعات والمخزون والصيانة.

## تجهيز الإنتاج

في خادم الإنتاج اضبط على الأقل:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
```

ثم نفّذ:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

لا ترفع ملف `.env` أو مجلدات `vendor` و`node_modules` و`public/build` إلى Git؛ جميعها مستبعدة في `.gitignore`.

## مسار المشروع محليًا

```text
C:\xampp\htdocs\myproject\BashaMob
```

مشروع `BashaMob` مستودع Git مستقل، وجذر المستودع هو مجلد المشروع نفسه.
