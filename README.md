# BashaMob

لتشغيل نسخة تجريبية مستقلة على Windows افتح `demo.cmd`. أغلق نافذة الديمو لحذف بيانات التجربة. تفاصيل الدخول ومتطلبات التسليم في [دليل الديمو والتسليم](docs/demo-and-release.md).

نظام إدارة عربي RTL لمحل موبايلات وكمبيوتر مبني بـ Laravel 12 وBlade وVite. يحافظ على واجهة الـ Glassmorphism الحالية ويستخدم قاعدة بيانات فعلية للموظفين والصلاحيات والمنتجات والمخزون والعملاء والموردين والمبيعات والمشتريات والصيانة والخزنة.

## المتطلبات

- PHP 8.2+ وComposer 2
- MySQL 8 أو MariaDB حديث
- Node.js 20+ وnpm
- Queue worker دائم في التشغيل الفعلي

## التثبيت المحلي

```bash
composer install
npm ci
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

شغّل الـ Queue في نافذة مستقلة:

```bash
php artisan queue:work --tries=3 --timeout=90
```

## قاعدة البيانات

أنشئ قاعدة `basha_mob` واضبط `DB_*` في `.env`. المخطط يشمل Catalog وIMEI/Serial والمخازن وحركات المخزون والمبيعات والمرتجعات والمشتريات والصيانة وقطع الغيار والخزنة والمصروفات والإعدادات وسجل النشاط.

كل مبالغ المال مخزنة كـ `decimal(14,2)`، والعملة الافتراضية `EGP`. يمنع `InventoryService` المخزون السالب، وتنفذ عمليات البيع والاستلام والصيانة داخل Database Transactions مع `lockForUpdate`.

## الدخول والأدوار

مسار الموظفين: `/staff/login`، والإدارة: `/admin`.

يضبط Seeder حساب Owner محليًا من:

```env
OWNER_EMAIL=admin@bashamob.local
OWNER_PASSWORD=BashaMob!2026Local
```

غيّر القيم قبل أي نشر. الأدوار: Owner وManager وCashier وTechnician وInventory Clerk وAccountant. حماية الصلاحيات مطبقة على السيرفر، وليس عبر إخفاء الأزرار فقط.

## البريد

لـ Gmail استخدم App Password:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=your-address@gmail.com
```

رسائل استرجاع الحساب وتأكيد طلب الصيانة تعمل عبر Queue.

## WhatsApp Business

```env
WHATSAPP_API_URL=https://graph.facebook.com/vXX.X/PHONE_NUMBER_ID/messages
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_RECOVERY_TEMPLATE=password_reset_code
WHATSAPP_TEMPLATE_LANGUAGE=ar
```

لا تُخزن Tokens داخل Git. يجب اعتماد الـ template من Meta قبل التشغيل.

## الاختبارات

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

تغطي الاختبارات دخول الموظفين والصلاحيات وأمان OTP والـ Reset Token والحفظ الفعلي للمنتجات والموظفين ومنع المخزون السالب وبيع IMEI مرتين وعدم تكرار خصم البيع وعكس الإلغاء وحد المرتجع واستلام المشتريات وقطع غيار الصيانة.

## النشر

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
```

اضبط `APP_ENV=production` و`APP_DEBUG=false`، وشغّل `queue:work` عبر Supervisor أو خدمة Windows، واستخدم HTTPS ونسخًا احتياطية دورية لقاعدة البيانات والملفات.
