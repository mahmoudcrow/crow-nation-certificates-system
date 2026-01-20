# إصلاح مشكلة تحديث الصور في الشهادات

## المشكلة
عند تعديل صورة شهادة موجودة بالفعل:
- ✗ يتم رفع الصورة الجديدة (Upload يحدث)
- ✗ لكن الصورة المعروضة في الواجهة لا تتحدث

## الأسباب التي تم إصلاحها

### 1. **خطأ في تحديث قاعدة البيانات** (الملف: `admin/admin-page.php`)
**المشكلة الأساسية:**
```php
// قبل الإصلاح - خطأ
$wpdb->update($table, $data, ['id' => $cert_id], null, ['%d']);
```

عند عدم مرور `$formats` (المعامل الثالث)، MySQL قد لا يحفظ البيانات بشكل صحيح.

**بعد الإصلاح:**
```php
// تحديد أنماط البيانات بشكل صريح
$formats = [];
foreach ($data as $key => $value) {
    $formats[$key] = '%s';
}
$wpdb->update($table, $data, ['id' => $cert_id], $formats, ['%d']);
```

### 2. **مشكلة ذاكرة التخزين المؤقت (Browser Cache)**
المتصفح يخزن الصور القديمة في الـ Cache ولا يحملها من جديد.

**الحل: إضافة Cache-Busting Parameter**
- تم إضافة `?v={timestamp}` لـ URL الصورة
- يجعل المتصفح يعتقد أنها صورة جديدة في كل مرة

### 3. **دالة مساعدة جديدة**
تم إضافة دالة `crow_get_certificate_image_url()` في `includes/certificate-functions.php`:
```php
function crow_get_certificate_image_url($image_url)
{
    // تحصل على timestamp آخر تعديل للملف
    // وتضيفه لـ URL الصورة
}
```

## الملفات المعدّلة

| الملف | التغيير |
|------|--------|
| `admin/admin-page.php` | إضافة `$formats` array عند تحديث البيانات |
| `public/shortcode-display-new.php` | إضافة cache-busting للصور |
| `public/shortcode-display.php` | إضافة cache-busting للصور |
| `includes/certificate-functions.php` | دالة مساعدة `crow_get_certificate_image_url()` |

## كيفية اختبار الإصلاح

1. أنشئ شهادة جديدة مع صورة
2. عدّل الشهادة واختر صورة جديدة
3. احفظ التعديلات
4. تحقق من أن الصورة الجديدة تظهر فوراً (بدون تحديث الصفحة يدويّاً)

## ملاحظات تقنية

- Cache-Busting يستخدم `filemtime()` من WordPress Media Library
- إذا كانت الصورة خارج WordPress Library، يتم استخدام `time()` الحالي
- يتم حذف الصورة القديمة تلقائياً عند رفع صورة جديدة
- كل التحديثات تحافظ على أمان البيانات (Sanitization و Escaping)

## تجنب المشاكل المستقبلية

- تأكد من أن مجلد التحميلات (`wp-content/uploads/`) له صلاحيات الكتابة
- تأكد من أن WordPress Media Library يعمل بشكل صحيح
- تفريغ cache المتصفح إذا استمرت المشكلة
