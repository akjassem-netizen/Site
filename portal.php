<?php
// بوابة ديوان مكتب المحامي علي كاظم الهاشمي - AKH Law Office Portal (Secured Vault & Bilingual)
// مسار الحفظ في الجذر الرئيسي: /portal.php

$statusFile = 'office_status.json';
$customHolidaysFile = 'custom_holidays.json';
$message = '';

// معالجة تحديث حالة المكتب الحية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $newStatus = [
        'status' => $_POST['office_status'],
        'note' => htmlspecialchars($_POST['custom_note'] ?? ''),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($statusFile, json_encode($newStatus, JSON_UNESCAPED_UNICODE));
    $message = 'تم تحديث حالة المكتب بنجاح | Office status updated successfully!';
}

// معالجة إضافة عطلة استثنائية أو طارئة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_custom_holiday') {
    $holidayName = trim($_POST['holiday_name']);
    $holidayDate = $_POST['holiday_date'];
    
    if (!empty($holidayName) && !empty($holidayDate)) {
        $currentCustomHolidays = [];
        if (file_exists($customHolidaysFile)) {
            $currentCustomHolidays = json_decode(file_get_contents($customHolidaysFile), true) ?: [];
        }
        
        $currentCustomHolidays[] = [
            'name' => htmlspecialchars($holidayName),
            'date' => $holidayDate,
            'added_at' => date('Y-m-d')
        ];
        
        file_put_contents($customHolidaysFile, json_encode($currentCustomHolidays, JSON_UNESCAPED_UNICODE));
        $message = 'تمت إضافة العطلة الاستثنائية بنجاح | Emergency holiday added successfully!';
    }
}

// قراءة حالة المكتب الحالية
$currentStatus = ['status' => 'active', 'note' => ''];
if (file_exists($statusFile)) {
    $currentStatus = json_decode(file_get_contents($statusFile), true);
}

// قراءة العطل الاستثنائية
$customHolidays = [];
if (file_exists($customHolidaysFile)) {
    $customHolidays = json_decode(file_get_contents($customHolidaysFile), true) ?: [];
}

// جدول العطل الرسمية الأساسية في العراق (قانون رقم 12 لسنة 2024)
$officialHolidays = [
    ["ar" => "رأس السنة الميلادية (1 كانون الثاني)", "en" => "New Year's Day (Jan 1)", "date" => "2026-01-01"],
    ["ar" => "عيد الجيش العراقي (6 كانون الثاني)", "en" => "Iraqi Army Day (Jan 6)", "date" => "2026-01-06"],
    ["ar" => "ذكرى جرائم النظام البائد (حلبجة والأنفال)", "en" => "Halabja & Anfal Memorial Day", "date" => "2026-03-16"],
    ["ar" => "عيد نوروز (21 آذار)", "en" => "Newroz / Kurdish New Year (Mar 21)", "date" => "2026-03-21"],
    ["ar" => "عيد العمال العالمي (1 أيار)", "en" => "International Workers' Day (May 1)", "date" => "2026-05-01"],
    ["ar" => "رأس السنة الهجرية (1 محرم)", "en" => "Islamic New Year (1 Muharram)", "date" => "متغير / Variable"],
    ["ar" => "يوم عاشوراء (10 محرم)", "en" => "Ashura Day (10 Muharram)", "date" => "متغير / Variable"],
    ["ar" => "المولد النبوي الشريف (12 ربيع الأول)", "en" => "The Prophet's Birthday (12 Rabi al-Awwal)", "date" => "متغير / Variable"],
    ["ar" => "عيد الفطر المبارك (1-3 شوال)", "en" => "Eid al-Fitr (1-3 Shawwal)", "date" => "متغير / Variable"],
    ["ar" => "عيد الأضحى المبارك (10-13 ذي الحجة)", "en" => "Eid al-Adha (10-13 Dhu al-Hijjah)", "date" => "متغير / Variable"],
    ["ar" => "يوم الغدير (18 ذي الحجة)", "en" => "Al-Ghadir Day (18 Dhu al-Hijjah)", "date" => "متغير / Variable"],
    ["ar" => "ذكرى تأسيس الجمهورية (14 تموز)", "en" => "Republic Foundation Day (July 14)", "date" => "2026-07-14"],
    ["ar" => "العيد الوطني لجمهورية العراق", "en" => "National Day of the Republic of Iraq", "date" => "2026-10-03"]
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة ديوان مكتب المحامي علي كاظم الهاشمي | AKH Law Office Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', system-ui, sans-serif;
            background-color: #f7f9fc;
            color: #1a1a1a;
            padding: 30px 16px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            align-items: flex-start;
        }
        .panel {
            max-width: 950px;
            width: 100%;
            background: #ffffff;
            border-radius: 14px;
            padding: 30px 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        /* الشعار الموحد */
        .brand-section {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 1.5px solid #edf2f7;
            padding-bottom: 20px;
        }
        .logo-text {
            font-size: 46px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #0b1a30;
            line-height: 1;
        }
        .logo-text span { color: #d32f2f; }
        .sub-brand {
            font-size: 13px;
            font-weight: 700;
            color: #111;
            letter-spacing: 3px;
            margin-top: 6px;
            direction: ltr;
        }
        .sub-brand span { color: #d32f2f; margin-right: 4px; font-weight: 800; }
        .est {
            font-size: 10.5px;
            color: #777;
            letter-spacing: 2px;
            margin-top: 3px;
            direction: ltr;
        }
        .portal-title {
            font-size: 19px;
            font-weight: 800;
            color: #0b1a30;
            margin-top: 12px;
        }
        .portal-subtitle-en {
            font-size: 13px;
            color: #4a5568;
            font-weight: 600;
            margin-top: 3px;
            direction: ltr;
        }
        /* شاشات القفل والإعداد */
        .screen-box { text-align: center; padding: 25px 10px; }
        .lock-desc { font-size: 14.5px; color: #4a5568; margin-bottom: 20px; line-height: 1.6; }
        .pin-input { letter-spacing: 8px; font-size: 24px; font-weight: 800; text-align: center; max-width: 260px; margin: 0 auto 16px; }
        .btn-unlock { width: 100%; max-width: 260px; background-color: #0b1a30; color: #ffffff; padding: 13px; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; font-family: inherit; margin: 0 auto; display: block; }
        .alert-box { padding: 12px 15px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; display: none; }
        .alert-error { background-color: #fde8e8; color: #9b1c1c; }

        /* شريط التنقل العلوي السريع */
        .top-nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .top-nav-bar a, .btn-lock-nav {
            background-color: #0b1a30;
            color: #ffffff;
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: 0.3s;
        }
        .top-nav-bar a:hover, .btn-lock-nav:hover { opacity: 0.9; }
        .btn-lock-nav { background-color: #e53e3e; }

        /* شبكة الوحدات والأقسام */
        .grid-modules {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .module-card {
            background: #fafafa;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            text-align: right;
        }
        .module-card h3 {
            font-size: 15px;
            font-weight: 800;
            color: #0b1a30;
            margin-bottom: 6px;
        }
        .module-card p {
            font-size: 12.5px;
            color: #4a5568;
            margin-bottom: 12px;
            line-height: 1.6;
        }
        .module-link {
            display: inline-block;
            background-color: #3182ce;
            color: #ffffff;
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }
        .module-link.disabled {
            background-color: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
        }
        /* البطاقات والأقسام الداخلية */
        .card-section {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 25px;
        }
        .card-section h2 {
            font-size: 16.5px;
            font-weight: 800;
            color: #0b1a30;
            margin-bottom: 16px;
            border-bottom: 1px dashed #cbd5e0;
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .card-section h2 span.en-title {
            font-size: 12.5px;
            color: #718096;
            font-weight: 600;
            direction: ltr;
        }
        .alert-success {
            background-color: #def7ec;
            color: #03543f;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 700;
        }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-weight: 700; font-size: 13.5px; margin-bottom: 6px; color: #2d3748; }
        label span.en-label { font-size: 11.5px; color: #718096; font-weight: 600; float: left; direction: ltr; }
        select, input[type="text"], input[type="date"], input[type="password"] {
            width: 100%;
            padding: 11px 13px;
            border: 1.5px solid #cbd5e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            background-color: #fff;
        }
        select:focus, input:focus { outline: none; border-color: #0b1a30; box-shadow: 0 0 0 3px rgba(11,26,48,0.1); }
        .btn-submit {
            background-color: #0b1a30;
            color: #ffffff;
            border: none;
            padding: 12px 22px;
            font-size: 14.5px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-submit:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13.5px; }
        th, td { padding: 11px; text-align: right; border-bottom: 1px solid #edf2f7; }
        th { background-color: #0b1a30; color: #ffffff; font-weight: 700; }
        tr:hover { background-color: #fafafa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700; }
        .badge-off { background: #fefcbf; color: #744210; }
        .badge-holiday { background: #fed7d7; color: #9b1c1c; }
    </style>
</head>
<body>

<div class="panel">

    <!-- الشعار الموحد للموقع -->
    <div class="brand-section">
        <div class="logo-text">A<span>K</span>H</div>
        <div class="sub-brand"><span>/</span> LAW OFFICE</div>
        <div class="est">EST. 2014</div>
        <div class="portal-title">بوابة ديوان مكتب المحامي علي كاظم الهاشمي</div>
        <div class="portal-subtitle-en">AKH Law Office Chambers Portal</div>
    </div>

    <div id="portalAlertBox" class="alert-box alert-error"></div>

    <!-- 1. شاشة إعداد الحماية لأول مرة (رمز طويل + PIN) -->
    <div id="portalSetupScreen" class="screen-box" style="display: none;">
        <p class="lock-desc"><strong>إعداد حماية البوابة لأول مرة:</strong><br>أدخل الرمز الطويل (المفتاح الرئيسي / التوكن) مع رمز PIN سري (6 أرقام):</p>
        <div class="form-group" style="max-width: 420px; margin: 0 auto 12px;">
            <input type="password" id="portalSetupTokenInput" placeholder="أدخل الرمز الطويل (مثال: ghp_... أو مفتاح المكتب)" dir="ltr">
        </div>
        <div class="form-group" style="max-width: 260px; margin: 0 auto 16px;">
            <input type="password" id="portalSetupPinInput" class="pin-input" maxlength="6" placeholder="******">
        </div>
        <button type="button" class="btn-unlock" onclick="performPortalSetup()">تشفير وحفظ الحماية 🔒</button>
    </div>

    <!-- 2. شاشة القفل (يطلب فقط رمز الـ PIN للدخول) -->
    <div id="portalLockScreen" class="screen-box" style="display: none;">
        <p class="lock-desc">البوابة محمية بخزنة مشفرة.<br>أدخل رمز الـ PIN السري (6 أرقام) لفك القفل والدخول:</p>
        <div class="form-group" style="max-width: 260px; margin: 0 auto 16px;">
            <input type="password" id="portalUnlockPinInput" class="pin-input" maxlength="6" placeholder="******" autofocus>
        </div>
        <button type="button" class="btn-unlock" onclick="attemptPortalPinUnlock()">دخول آمن ←</button>
    </div>

    <!-- 3. محتوى البوابة الرئيسي (يظهر بعد فك القفل بنجاح) -->
    <div id="portalMainContent" style="display: none;">

        <!-- شريط التنقل السريع -->
        <div class="top-nav-bar">
            <span style="font-size: 13px; font-weight: 700; color: #2d3748;">🟢 النظام محمي وخزنة الأمان مفعلة | Vault Secured</span>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-lock-nav" onclick="lockPortalSession()">قفل البوابة 🔒</button>
                <a href="portal.php">🔄 تحديث</a>
                <a href="index.html" target="_blank">معاينة 🌐</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- شبكة الأدوات والأقسام -->
        <div class="grid-modules">
            <div class="module-card">
                <h3>📚 إدارة التحليلات والآراء <span style="font-size:11px; color:#718096; display:block;">Legal Insights Manager</span></h3>
                <p>منصة نشر، تعديل، إخفاء ومتابعة مشاهدات المقالات والإضاءات القانونية.</p>
                <a href="admin.html" class="module-link">فتح المنصة | Open ←</a>
            </div>
            <div class="module-card">
                <h3>👥 إدارة الموكلين والوكالات <span style="font-size:11px; color:#718096; display:block;">Client & POA Management</span></h3>
                <p>أرشيف الموكلين، حفظ الوكالات القضائية ومتابعة القضايا الجارية.</p>
                <span class="module-link disabled">قريباً | Coming Soon 🔒</span>
            </div>
            <div class="module-card">
                <h3>⚖️ أرشيف المبادئ التمييزية <span style="font-size:11px; color:#718096; display:block;">Judicial Principles Archive</span></h3>
                <p>مكتبة القرارات والمطابقة الذكية لوقائع الموكلين مع السوابق القضائية.</p>
                <span class="module-link disabled">قريباً | Coming Soon 🔒</span>
            </div>
        </div>

        <!-- أ. التحكم بحالة المكتب الحية -->
        <div class="card-section">
            <h2>
                <span>🟢 التحكم الفوري بحالة المكتب</span>
                <span class="en-title">Live Office Status Control</span>
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status">
                <div class="form-group">
                    <label>حالة المكتب الظاهرة للزوار: <span class="en-label">Office Status Display</span></label>
                    <select name="office_status">
                        <option value="active" <?php if($currentStatus['status'] == 'active') echo 'selected'; ?>>🟢 متاح الآن لاستقبال الطلبات (أوقات العمل الرسمية) | Active</option>
                        <option value="off_hours" <?php if($currentStatus['status'] == 'off_hours') echo 'selected'; ?>>🟡 خارج ساعات العمل الرسمية (عبر الاستمارة فقط) | Off Hours</option>
                        <option value="holiday" <?php if($currentStatus['status'] == 'holiday') echo 'selected'; ?>>🔴 إجازة رسمية / عطلة (مفعل) | Holiday / Leave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>سبب التعطيل أو ملاحظة مخصصة: <span class="en-label">Custom Note (Bilingual)</span></label>
                    <input type="text" name="custom_note" value="<?php echo htmlspecialchars($currentStatus['note']); ?>" placeholder="مثلاً: عطلة رسمية بقرار مجلس الوزراء / Official Holiday...">
                </div>
                <button type="submit" class="btn-submit">حفظ وتحديث الحالة فوراً 💾 | Save & Update</button>
            </form>
        </div>

        <!-- ب. إضافة العطل الاستثنائية والطارئة -->
        <div class="card-section">
            <h2>
                <span>⚡ إضافة عطلة استثنائية أو طارئة</span>
                <span class="en-title">Add Emergency / Custom Holiday</span>
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_custom_holiday">
                <div class="form-group">
                    <label>اسم المناسبة أو العطلة الطارئة: <span class="en-label">Holiday Name</span></label>
                    <input type="text" name="holiday_name" placeholder="مثلاً: عطلة رسمية بقرار مجلس الوزراء / طوارئ أمطار..." required>
                </div>
                <div class="form-group">
                    <label>التاريخ: <span class="en-label">Date</span></label>
                    <input type="date" name="holiday_date" required>
                </div>
                <button type="submit" class="btn-submit" style="background-color: #3182ce;">إضافة العطلة للجدول ➕ | Add Holiday</button>
            </form>

            <?php if (!empty($customHolidays)): ?>
            <h3 style="font-size: 14.5px; margin-top: 22px; color: #0b1a30; margin-bottom: 10px;">العطل الاستثنائية المضافة حالياً | Current Custom Holidays:</h3>
            <table>
                <thead>
                    <tr>
                        <th>اسم المناسبة الطارئة | Holiday Name</th>
                        <th>التاريخ | Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customHolidays as $ch): ?>
                    <tr>
                        <td><strong><?php echo $ch['name']; ?></strong></td>
                        <td><span class="badge badge-holiday"><?php echo $ch['date']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ج. جدول العطل الرسمية الأساسية في العراق -->
        <div class="card-section">
            <h2>
                <span>📅 جدول العطل الرسمية الأساسية</span>
                <span class="en-title">Official Iraqi Holidays (Law No. 12 of 2024)</span>
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>اسم العطلة محلياً (العراق) | Local Name</th>
                        <th>التسمية المعيارية العالمية | Global Standard Name</th>
                        <th>التاريخ المقابل | Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($officialHolidays as $holiday): ?>
                    <tr>
                        <td><strong><?php echo $holiday['ar']; ?></strong></td>
                        <td style="color: #4a5568;"><?php echo $holiday['en']; ?></td>
                        <td><span class="badge badge-off"><?php echo $holiday['date']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<script>
// دوال التشفير وفك الخزنة (مطابقة لمنصة الآراء تماماً)
async function deriveKey(pin, salt) {
    const enc = new TextEncoder();
    const keyMaterial = await crypto.subtle.importKey('raw', enc.encode(pin), { name: 'PBKDF2' }, false, ['deriveKey']);
    return crypto.subtle.deriveKey({ name: 'PBKDF2', salt: salt, iterations: 100000, hash: 'SHA-256' }, keyMaterial, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
}

async function encryptToken(token, pin) {
    const enc = new TextEncoder();
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const key = await deriveKey(pin, salt);
    const encrypted = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, key, enc.encode(token));
    return { salt: Array.from(salt), iv: Array.from(iv), data: Array.from(new Uint8Array(encrypted)) };
}

async function decryptToken(encryptedObj, pin) {
    const salt = new Uint8Array(encryptedObj.salt);
    const iv = new Uint8Array(encryptedObj.iv);
    const data = new Uint8Array(encryptedObj.data);
    const key = await deriveKey(pin, salt);
    const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: iv }, key, data);
    return new TextDecoder().decode(decrypted);
}

window.onload = function() {
    const savedVault = localStorage.getItem('akh_portal_vault');
    const isUnlocked = sessionStorage.getItem('akh_portal_unlocked');

    if (!savedVault) {
        document.getElementById('portalSetupScreen').style.display = 'block';
    } else if (isUnlocked === 'true') {
        document.getElementById('portalMainContent').style.display = 'block';
    } else {
        document.getElementById('portalLockScreen').style.display = 'block';
    }
};

async function performPortalSetup() {
    const token = document.getElementById('portalSetupTokenInput').value.trim();
    const pin = document.getElementById('portalSetupPinInput').value.trim();

    if (!token) {
        showPortalAlert('يرجى إدخال الرمز الطويل أو المفتاح الرئيسي.');
        return;
    }
    if (pin.length !== 6 || isNaN(pin)) {
        showPortalAlert('رمز الـ PIN يجب أن يكون مكوناً من 6 أرقام بالضبط.');
        return;
    }

    try {
        const vaultObj = await encryptToken(token, pin);
        localStorage.setItem('akh_portal_vault', JSON.stringify(vaultObj));
        sessionStorage.setItem('akh_portal_unlocked', 'true');

        document.getElementById('portalSetupScreen').style.display = 'none';
        document.getElementById('portalMainContent').style.display = 'block';
    } catch(e) {
        showPortalAlert('حدث خطأ أثناء عملية التشفير.');
    }
}

async function attemptPortalPinUnlock() {
    const pin = document.getElementById('portalUnlockPinInput').value.trim();
    try {
        const vaultObj = JSON.parse(localStorage.getItem('akh_portal_vault'));
        // محاولة فك التشفير باستخدام رمز الـ PIN المدخل
        await decryptToken(vaultObj, pin);

        sessionStorage.setItem('akh_portal_unlocked', 'true');
        document.getElementById('portalLockScreen').style.display = 'none';
        document.getElementById('portalMainContent').style.display = 'block';
    } catch(e) {
        showPortalAlert('رمز الـ PIN غير صحيح! تعذر فتح الخزنة المشفرة.');
        document.getElementById('portalUnlockPinInput').value = '';
    }
}

function lockPortalSession() {
    sessionStorage.removeItem('akh_portal_unlocked');
    location.reload();
}

function showPortalAlert(msg) {
    const box = document.getElementById('portalAlertBox');
    box.innerText = msg;
    box.style.display = 'block';
    setTimeout(() => { box.style.display = 'none'; }, 4000);
}
</script>
</body>
</html>
