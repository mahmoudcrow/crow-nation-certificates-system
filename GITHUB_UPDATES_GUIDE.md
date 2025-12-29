# 🔄 دليل إعداد التحديثات التلقائية من GitHub

## 📋 المتطلبات

1. **حساب GitHub** - لديك بالفعل ✅
2. **Releases على GitHub** - يجب أن تنشئ releases
3. **WordPress 5.0+** - متوفر ✅
4. **PHP 7.4+** - متوفر ✅

---

## 🚀 خطوات الإعداد

### الخطوة 1️⃣: تعديل البيانات في الملف الرئيسي

**الملف**: `crow-nation-certificates.php`

ابحث عن هذا الجزء:

```php
new Crow_GitHub_Updater(
    __FILE__,
    'mahmoudcrow',                    // ✏️ عدّل هنا
    'crow-nation-certificates'         // ✏️ عدّل هنا
);
```

**عدّل البيانات:**
- السطر الأول: `mahmoudcrow` → ضع اسم حسابك على GitHub
- السطر الثاني: `crow-nation-certificates` → ضع اسم الريبو بالضبط

**مثال:**
```php
new Crow_GitHub_Updater(
    __FILE__,
    'starzuae',                       // اسمك على GitHub
    'crow-certificates'                // اسم الريبو
);
```

---

### الخطوة 2️⃣: إنشاء Release على GitHub

1. اذهب إلى ريبو Crow Nation على GitHub
2. اضغط على **Releases** (على الجانب الأيمن)
3. اضغط **Create a new release**
4. املأ البيانات:

```
Tag version:          v1.1  (أو أي نسخة جديدة)
Release title:        Version 1.1 (أو وصف أفضل)
Description:          - Fixed bugs
                      - Added features
                      - Improved performance
Attach binaries:      (لا تحتاج - يتم تحميل الـ zip تلقائياً)

✅ Publish release
```

**ملاحظات مهمة:**
- استخدم رقم نسخة أعلى من الحالي (مثلاً: من 1.0 إلى 1.1)
- يجب أن يكون رقم الإصدار في `crow-nation-certificates.php` أقل من الـ Release
- استخدم صيغة: `vX.X` أو `X.X` (مثل v1.1 أو 1.1)

---

### الخطوة 3️⃣: تحديث رقم الإصدار في الملف الرئيسي

**الملف**: `crow-nation-certificates.php`

غيّر رقم الإصدار:

```php
/**
 * Plugin Name: Crow Nation Certificates System
 * Description: Certificate verification system by Mahmoud Moustafa.
 * Version: 1.1  // ✏️ حدّث هذا الرقم (أقل من Release)
 * Author: Mahmoud Moustafa
 * Text Domain: crow-certificates
 * Domain Path: /languages
 */
```

---

## 🔍 كيف تتحقق من أن التحديثات تعمل؟

### 1. في لوحة التحكم:

```
WordPress Admin
  → Plugins
    → تحديث Crow Nation Certificates System
      → سترى "New Version X.X Available"
```

### 2. اختبر يدوياً:

```php
// أضف هذا الكود مؤقتاً في functions.php:
add_action('init', function() {
    if (is_user_logged_in() && current_user_can('manage_options')) {
        wp_cache_delete('update_plugins');
        delete_transient('crow_github_update_check');
        wp_remote_get(admin_url('admin-ajax.php?action=check-plugins-updates'));
        echo '<div class="notice notice-info"><p>✅ تم فحص التحديثات</p></div>';
    }
});
```

---

## 🛠️ خطوات إضافة تحديث جديد

### كل مرة تريد إضافة تحديث:

1. **عدّل الملفات وأضف الميزات**
2. **اختبر كل شيء على الكمبيوتر**
3. **أرسل إلى GitHub (commit + push)**
4. **أنشئ Release جديد على GitHub** (مع رقم نسخة أعلى)
5. **حدّث رقم الإصدار في `crow-nation-certificates.php`**
6. **WordPress سيكتشف التحديث تلقائياً**

---

## 📝 مثال عملي كامل

### الحالة الحالية:
- **في WordPress**: Version 1.0
- **في GitHub**: لا توجد releases

### لإضافة تحديث v1.1:

**الخطوة 1**: عدّل الملفات وأضف الميزات

**الخطوة 2**: اختبر على جهازك

**الخطوة 3**: أرسل إلى GitHub:
```bash
git add .
git commit -m "feat: Update to v1.1 - Better UI and bug fixes"
git push origin main
```

**الخطوة 4**: أنشئ Release على GitHub:
- Tag: `v1.1`
- Title: `Version 1.1 - UI Improvements`
- Description: `...`
- Publish

**الخطوة 5**: حدّث الملف الرئيسي:
```php
Version: 1.1
```

**الخطوة 6**: أرسل التحديث:
```bash
git add crow-nation-certificates.php
git commit -m "docs: Update version to 1.1"
git push origin main
```

**✅ النتيجة**: WordPress سيكتشف التحديث الجديد تلقائياً!

---

## ⚙️ إعدادات متقدمة

### استخدام GitHub Token (للريبوز الخاصة):

إذا كان ريبوك خاص على GitHub:

1. أنشئ Personal Access Token:
   - اذهب إلى GitHub Settings
   - اختر Developer Settings → Personal access tokens
   - اضغط Generate new token
   - اختر `public_repo` (أو `repo` للخاصة)
   - انسخ الـ token

2. أضف في `wp-config.php`:
```php
define('GITHUB_UPDATER_TOKEN', 'ghp_your_token_here');
```

### تعديل الفترة الزمنية للفحص:

في `includes/github-updater.php`:
```php
private $cache_time = 12 * HOUR_IN_SECONDS; // غيّر 12 إلى أي عدد ساعات
```

---

## 🐛 استكشاف الأخطاء

### المشكلة: "لا يظهر تحديث جديد"

**الحل:**
1. تحقق أن رقم الإصدار الجديد أعلى من الحالي
2. تأكد من اسم الريبو صحيح في الكود
3. امسح كاش WordPress:
   ```php
   delete_transient('crow_github_update_check');
   wp_cache_delete('update_plugins');
   ```
4. حاول الفحص اليدوي (تحديث الصفحة وانتظر)

### المشكلة: "خطأ في التحميل"

**الحل:**
1. تأكد من حجم الـ zip أقل من 100 MB
2. تأكد من اتصال الإنترنت
3. تحقق أن اسم الريبو والحساب صحيحان
4. جرّب Release مختلف

---

## 📚 موارد إضافية

- [GitHub Releases Documentation](https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases)
- [WordPress Plugin Update API](https://developer.wordpress.org/plugins/wordpress-org/how-wordpress-org-plugin-updates-work/)
- [Version Comparison in PHP](https://www.php.net/manual/en/function.version-compare.php)

---

**النسخة**: 1.0+
**آخر تحديث**: 29 ديسمبر 2025
**الحالة**: ✅ جاهز للاستخدام
