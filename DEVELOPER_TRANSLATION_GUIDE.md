# 👨‍💻 دليل المطور - Translation Developer Guide

**للمطورين الذين يضيفون ميزات جديدة أو يعدلون الكود**

---

## 🎯 الهدف

ضمان أن جميع النصوص الجديدة قابلة للترجمة والنظام يدعم لغات متعددة تلقائياً.

---

## 🚀 الخطوات السريعة (Quick Start)

### عند إضافة نص جديد:

#### ❌ **ما لا تفعله:**
```php
// خطأ 1: نص بدون ترجمة (Hardcoded)
echo "Welcome to the system";

// خطأ 2: ترجمة بـ text domain خاطئ
_e("Welcome", "wrong-domain");

// خطأ 3: دالة خاطئة
echo __("This is welcome", "crow-certificates"); // يجب أن تكون _e()
```

#### ✅ **ما تفعله:**
```php
// صحيح 1: للعرض المباشر
<?php _e('Welcome to the system', 'crow-certificates'); ?>

// صحيح 2: للتخزين ثم العرض
<?php
$message = __('Welcome to the system', 'crow-certificates');
echo $message;
?>

// صحيح 3: مع متغيرات
<?php
printf(
    __('Welcome %s to the system', 'crow-certificates'),
    'Mahmoud'
);
?>

// صحيح 4: مع جمع (Plural)
<?php
printf(
    _n('You have %d certificate', 'You have %d certificates', $count, 'crow-certificates'),
    $count
);
?>
```

---

## 📚 جدول مرجعي للدوال

| الدالة | الاستخدام | المثال |
|--------|---------|---------|
| `_e()` | عرض نص مباشرة | `<?php _e('Text', 'crow-certificates'); ?>` |
| `__()` | الحصول على النص المترجم | `$text = __('Text', 'crow-certificates');` |
| `_n()` | نص جمع (Plural) | `_n('item', 'items', $count, 'crow-certificates')` |
| `_x()` | نص مع سياق (Context) | `_x('Post', 'post type', 'crow-certificates')` |
| `_ex()` | عرض نص مع سياق | `<?php _ex('Post', 'post type', 'crow-certificates'); ?>` |
| `_nx()` | جمع مع سياق | `_nx('item', 'items', $count, 'context', 'crow-certificates')` |
| `esc_html__()` | نص آمن HTML | `$text = esc_html__('Text', 'crow-certificates');` |
| `esc_html_e()` | عرض نص آمن | `<?php esc_html_e('Text', 'crow-certificates'); ?>` |
| `esc_attr__()` | نص آمن للخصائص | `placeholder="<?php echo esc_attr__('...', 'crow-certificates'); ?>"` |

---

## 🎨 أمثلة عملية

### مثال 1: حقل بسيط
```php
// ❌ خطأ
<input placeholder="Enter your name">

// ✅ صحيح
<input placeholder="<?php _e('Enter your name', 'crow-certificates'); ?>">

// ✅ أفضل (مع escape للخصائص)
<input placeholder="<?php esc_attr_e('Enter your name', 'crow-certificates'); ?>">
```

### مثال 2: رسالة خطأ
```php
// ❌ خطأ
if (!$certificate) {
    echo "Certificate not found!";
}

// ✅ صحيح
if (!$certificate) {
    printf(
        '<div class="error">%s</div>',
        __('Certificate not found!', 'crow-certificates')
    );
}
```

### مثال 3: جمع (Plural)
```php
// ❌ خطأ
echo "You have " . count($certs) . " certificates";

// ✅ صحيح
printf(
    _n('You have %d certificate', 'You have %d certificates', count($certs), 'crow-certificates'),
    count($certs)
);
```

### مثال 4: مع HTML
```php
// ❌ خطأ
echo "<strong>Status: Active</strong>";

// ✅ صحيح
printf(
    '<strong>%s %s</strong>',
    __('Status:', 'crow-certificates'),
    __('Active', 'crow-certificates')
);

// ✅ أفضل (استخدام escape)
printf(
    '<strong>%s %s</strong>',
    esc_html__('Status:', 'crow-certificates'),
    esc_html__('Active', 'crow-certificates')
);
```

### مثال 5: في HTML attributes
```php
// ❌ خطأ
<input title="This is a tooltip">

// ✅ صحيح
<input title="<?php esc_attr_e('This is a tooltip', 'crow-certificates'); ?>">
```

---

## 📂 أين تضع الترجمات؟

### في ملفات PHP:
```
✅ public/shortcode-display-new.php
✅ public/shortcode-display.php
✅ admin/admin-page.php
✅ admin/settings-page.php
✅ admin/analytics-page.php
✅ admin/certificates-list.php
✅ includes/certificate-functions.php
✅ includes/api.php
✅ crow-nation-certificates.php
```

### في ملفات الترجمة:
```
languages/
├── crow-certificates.pot (النموذج الأساسي)
├── crow-certificates-ar.po (العربية)
├── crow-certificates-ar.mo (العربية مُترجمة)
└── crow-certificates-en.po (الإنجليزية - اختياري)
```

---

## 🔄 سير العمل الكامل

### الخطوة 1: أضف النص في الكود
```php
// في ملف PHP
<?php _e('My New Feature', 'crow-certificates'); ?>
```

### الخطوة 2: حدّث ملف POT
```bash
cd /path/to/plugin
xgettext -d crow-certificates -o languages/crow-certificates.pot \
  --from-code=UTF-8 --keyword=__ --keyword=_e --keyword=_n:1,2 \
  public/*.php admin/*.php includes/*.php crow-nation-certificates.php
```

### الخطوة 3: دمج مع الترجمة الموجودة
```bash
cd languages/
msgmerge -U crow-certificates-ar.po crow-certificates.pot
```

### الخطوة 4: ترجم النص الجديد
- افتح `crow-certificates-ar.po` بـ Poedit
- ابحث عن النص الجديد بدون ترجمة
- أضف الترجمة العربية
- احفظ الملف

### الخطوة 5: أنتج ملف MO
- Poedit: File → Save (ينتج MO تلقائياً)
- أو السطر: `msgfmt -o crow-certificates-ar.mo crow-certificates-ar.po`

### الخطوة 6: اختبر
- اذهب إلى WordPress: Settings → General → Site Language
- اختر اللغة العربية
- تحقق من الصفحات

---

## 🧪 اختبار الترجمات

### 1️⃣ تفعيل Debug Mode (اختياري)
```php
// في wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 2️⃣ فحص يدوي
```php
// في قالب الويب أو ملف اختبار
<?php
echo __('My text', 'crow-certificates');
echo '<br>';
// يجب أن يظهر النص المترجم إذا كنت في WordPress بلغة مختلفة
?>
```

### 3️⃣ استخدام WP CLI (إن كان مثبتاً)
```bash
# فحص ملفات الترجمة
wp i18n make-pot . languages/crow-certificates.pot --domain=crow-certificates

# التحقق من صيغة الملف
msgfmt -c -v -o /dev/null languages/crow-certificates-ar.mo
```

---

## 📋 قائمة التحقق قبل الكود

قبل الـ commit/push:

- [ ] استخدمت `_e()` أو `__()` لكل النصوص
- [ ] استخدمت Text Domain الصحيح: `crow-certificates`
- [ ] استخدمت الدالة الصحيحة (للعرض vs التخزين)
- [ ] استخدمت escape عند الحاجة (`esc_html__`, `esc_attr_e`, إلخ)
- [ ] اختبرت الصفحة بلغات مختلفة
- [ ] لا توجد نصوص hardcoded
- [ ] حدثت ملف POT
- [ ] دمجت مع ملفات PO
- [ ] أضفت الترجمات العربية
- [ ] أنتجت ملفات MO

---

## 🐛 استكشاف الأخطاء

### المشكلة: النص لا يظهر مترجماً

**الحل**:
1. تأكد من اسم ملف MO: `crow-certificates-ar.mo`
2. تأكد من موقع ملف MO: `/languages/`
3. تأكد أن WordPress مضبوط على اللغة الصحيحة
4. امسح الـ cache وأعد التحميل

### المشكلة: ملف POT فارغ

**الحل**:
1. تأكد من استخدام الدوال الصحيحة `__` و `_e`
2. تأكد من صيغة الأمر:
```bash
xgettext -d crow-certificates -o languages/crow-certificates.pot \
  --from-code=UTF-8 --keyword=__ --keyword=_e --keyword=_n:1,2 \
  **/*.php
```

### المشكلة: Poedit لا يفتح الملف

**الحل**:
1. تأكد من الترميز: UTF-8
2. تأكد من صيغة الملف: POT أو PO
3. أعد فتح البرنامج

---

## 🎯 أفضل الممارسات

### 1️⃣ استخدم نصوص واضحة وموجزة
```php
// ❌ غير واضح
_e('Cert', 'crow-certificates');

// ✅ واضح
_e('Certificate', 'crow-certificates');
```

### 2️⃣ لا تترجم الأسماء العلمية
```php
// ❌
_e('QR Code', 'crow-certificates'); // "كود كيو آر" يبدو غريب

// ✅ اتركه كما هو أو استخدم سياق
_x('QR Code', 'technology name', 'crow-certificates');
```

### 3️⃣ استخدم سياق للنصوص المبهمة
```php
// ❌ قد يكون محيراً
_e('Posts', 'crow-certificates');

// ✅ واضح
_x('Posts', 'post type', 'crow-certificates');
```

### 4️⃣ لا تضع علامات ترقيم في النهاية
```php
// ❌
_e('Welcome!', 'crow-certificates');

// ✅ أفضل (الترجمة قد تحتاج تنسيق مختلف)
_e('Welcome', 'crow-certificates');
echo '!';
```

### 5️⃣ استخدم sprintf للمتغيرات
```php
// ❌
echo "Hello " . $name;

// ✅
printf(__('Hello %s', 'crow-certificates'), $name);
```

---

## 📞 مراجع إضافية

- [WordPress i18n Documentation](https://developer.wordpress.org/plugins/internationalization/)
- [Gettext Manual](https://www.gnu.org/software/gettext/manual/)
- [Poedit Tutorial](https://poedit.net/features)
- [WP CLI i18n](https://developer.wordpress.org/cli/commands/i18n/)

---

## 📝 نموذج Pull Request

عند إرسال تعديلات:

```markdown
## الوصف
تم إضافة ميزة جديدة لـ [اسم الميزة]

## التغييرات
- [ ] تم استخدام دوال الترجمة
- [ ] تم تحديث ملف POT
- [ ] تم تحديث ملفات PO
- [ ] تم إنشاء ملفات MO
- [ ] تم اختبار الترجمة

## ملفات الترجمة المُحدثة
- languages/crow-certificates.pot
- languages/crow-certificates-ar.po
- languages/crow-certificates-ar.mo

## الاختبار
- [ ] اختبرت باللغة العربية ✅
- [ ] اختبرت باللغة الإنجليزية ✅
```

---

**آخر تحديث**: January 20, 2026  
**الإصدار**: 1.0.7  
**للمزيد من المعلومات**: اقرأ [TRANSLATION_SYSTEM.md](TRANSLATION_SYSTEM.md)
