<!doctype html>
<html lang="ar" dir="rtl">
<body style="margin:0;background:#eef5f9;color:#17314d;font-family:Tahoma,Arial,sans-serif">
<div style="max-width:620px;margin:30px auto;padding:28px;border:1px solid #d4e5ef;border-radius:20px;background:#fff">
    <h1 style="margin:0 0 12px;color:#176f91">BashaMob</h1>
    <h2 style="margin:0 0 16px">أهلًا {{ $maintenanceRequest->name }}</h2>
    <p style="line-height:1.9">تم تسجيل بيانات جهازك بنجاح، وطلب الصيانة الآن <strong>قيد المراجعة</strong>. سيتواصل معك فريقنا بعد مراجعة التفاصيل.</p>
    <div style="margin:22px 0;padding:16px;border-radius:14px;background:#edf8fc">
        <strong>رقم المتابعة: {{ $maintenanceRequest->request_number }}</strong><br>
        الجهاز: {{ $maintenanceRequest->device }} — {{ $maintenanceRequest->brand }}
    </div>
    <p style="line-height:1.8;color:#637a8c">احتفظ برقم المتابعة، ولا ترد على هذه الرسالة ببيانات حساسة.</p>
</div>
</body>
</html>
