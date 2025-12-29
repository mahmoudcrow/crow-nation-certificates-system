<?php
/**
 * QR Code Generator - PHP QR Code Library
 * مكتبة توليد رموز QR Code
 */

class QRCode
{
    // QR Code من Google Chart API (الطريقة الحالية - بسيطة وسريعة)
    public static function generateURL($text, $size = 200)
    {
        return 'https://chart.googleapis.com/chart?cht=qr&chs=' . $size . 'x' . $size . '&chl=' . urlencode($text);
    }

    // طريقة بديلة باستخدام qr-server.com (مجاني وموثوق)
    public static function generateURLAlternative($text, $size = 200)
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($text);
    }

    // طريقة مجمعة تجرب الخيارات
    public static function generateWithFallback($text, $size = 200)
    {
        // حاول Google أولاً
        $url = self::generateURL($text, $size);

        // إذا فشل (نادر جداً)، استخدم البديل
        if (!self::isImageValid($url)) {
            return self::generateURLAlternative($text, $size);
        }

        return $url;
    }

    // تحقق من أن الصورة صحيحة
    private static function isImageValid($url)
    {
        if (function_exists('wp_remote_get')) {
            $response = wp_remote_get($url, ['timeout' => 2]);
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        }
        return true; // افترض أنها صحيحة إذا لم نستطع التحقق
    }
}

// دالة مساعدة للاستخدام السريع
function crow_generate_qr_code($text, $size = 200)
{
    return QRCode::generateURL($text, $size);
}

// دالة مساعدة مع Fallback
function crow_generate_qr_code_safe($text, $size = 200)
{
    return QRCode::generateWithFallback($text, $size);
}
