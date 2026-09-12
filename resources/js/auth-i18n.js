// The login UI shares the user's language preference with administration.
// Only presentation changes; login and recovery endpoints retain their contracts.
const messages = {
    "اللغة": "Language",
    "مرحبًا بك في": "Welcome To",
    "سجّل الدخول إلى حسابك": "Sign in to your account",
    "تسجيل الدخول": "Sign In",
    "البريد الإلكتروني": "Email Address",
    "كلمة المرور": "Password",
    "إظهار كلمة المرور": "Show password",
    "إخفاء كلمة المرور": "Hide password",
    "تذكرني": "Remember me",
    "نسيت كلمة المرور؟": "Forgot password?",
    "استرداد كلمة المرور عبر": "Recover your password via",
    "هل أنت عميل؟": "Customer?",
    "أرسل طلب صيانة": "Send a maintenance request",
    "إغلاق": "Close",
    "استرداد كلمة المرور": "Password recovery",
    "سنرسل كودًا صالحًا لمدة 10 دقائق.": "We will send a code valid for 10 minutes.",
    "البريد الإلكتروني على Gmail": "Gmail email address",
    "رقم WhatsApp بكود الدولة": "WhatsApp number with country code",
    "سنرسل الكود إلى بريدك، وهو صالح لمدة 10 دقائق.": "We will email you a code valid for 10 minutes.",
    "سنرسل الكود إلى رقم WhatsApp المسجل، وهو صالح لمدة 10 دقائق.": "We will send a code to your registered WhatsApp number, valid for 10 minutes.",
    "إرسال كود التحقق": "Send verification code",
    "كود التحقق": "Verification code",
    "كلمة المرور الجديدة": "New password",
    "تأكيد كلمة المرور": "Confirm password",
    "حفظ كلمة المرور": "Save password",
    "جاري الاتصال بالخدمة...": "Connecting to the service...",
    "تعذر الاتصال بخدمة الاسترداد.": "Could not connect to the recovery service.",
    "تعذر تنفيذ الطلب.": "Could not complete the request.",
    "تعذر إنشاء رمز تغيير كلمة المرور.": "Could not create a password reset token.",
    "تعذر تغيير كلمة المرور.": "Could not reset the password.",
    "إذا كانت البيانات مسجلة فسيصلك كود التحقق خلال لحظات.": "If these details are registered, you will receive a verification code shortly.",
    "الكود غير صحيح أو انتهت صلاحيته.": "The code is invalid or has expired.",
    "تم تجاوز عدد المحاولات المسموح.": "The maximum number of attempts has been exceeded.",
    "تم التحقق من الكود.": "Code verified.",
    "انتهت صلاحية رابط تغيير كلمة المرور.": "The password reset link has expired.",
    "تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.": "Password changed successfully. You can now sign in.",
    "بيانات تسجيل الدخول غير صحيحة.": "The login credentials are incorrect.",
    "هذا الحساب غير مصرح له بدخول الإدارة.": "This account is not authorized to access administration.",
    "مساعد الذكاء الاصطناعي": "AI Assistant",
    "اسألني أي شيء": "Ask me anything",
    "محادثة جديدة": "New conversation",
    "أهلًا! أنا مساعد BashaMob. اسألني أي سؤال، أو أخبرني بما تريد إنجازه.": "Hello! I am the BashaMob assistant. Ask a question or tell me what you want to do.",
    "اشرح الصفحة": "Explain this page",
    "ابدأ مهمة": "Start a task",
    "سؤال عام": "General question",
    "اكتب رسالتك...": "Type your message...",
    "رسالتك إلى BashaMob AI": "Your message to BashaMob AI",
    "إرسال": "Send"
};
const reverseMessages = Object.fromEntries(Object.entries(messages).map(([ar, en]) => [en, ar]));

export const translateAuth = value => {
    if (!document.body?.classList.contains('auth-body') || typeof value !== 'string') return value;
    const trimmed = value.trim();
    const english = document.documentElement.lang === 'en';
    const throttle = trimmed.match(/^محاولات كثيرة\. حاول مرة أخرى بعد (\d+) ثانية\.$/) || trimmed.match(/^Too many attempts\. Try again in (\d+) seconds\.$/);
    if (throttle) return value.replace(trimmed, english ? `Too many attempts. Try again in ${throttle[1]} seconds.` : `محاولات كثيرة. حاول مرة أخرى بعد ${throttle[1]} ثانية.`);
    const arabic = reverseMessages[trimmed] || trimmed;
    const translated = document.documentElement.lang === 'en' ? (messages[arabic] || trimmed) : arabic;
    return value.replace(trimmed, translated);
};

export const refreshAuthTranslations = () => {
    if (!document.body?.classList.contains('auth-body')) return;
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) {
        if (node.parentElement?.closest('script,style,option,[data-ai-messages] .from-user')) continue;
        const translated = translateAuth(node.nodeValue);
        if (node.nodeValue !== translated) node.nodeValue = translated;
    }
    document.querySelectorAll('[placeholder],[aria-label],[title]').forEach(element => {
        for (const attribute of ['placeholder', 'aria-label', 'title']) {
            if (element.hasAttribute(attribute)) element.setAttribute(attribute, translateAuth(element.getAttribute(attribute)));
        }
    });
    document.title = `${translateAuth('تسجيل الدخول')} | BashaMob`;
    const toggle = document.querySelector('.reference-eye');
    if (toggle) toggle.setAttribute('aria-label', translateAuth(document.getElementById('login-password')?.type === 'text' ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'));
};

export const applyAuthLocale = language => {
    const locale = language === 'en' ? 'en' : 'ar';
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.dataset.locale = locale;
    try { localStorage.setItem('basha-admin-language', locale); } catch { /* Storage may be disabled. */ }
    const selector = document.getElementById('auth-language');
    if (selector) selector.value = locale;
    refreshAuthTranslations();
};

const initialize = () => {
    if (!document.getElementById('auth-language')) return;
    applyAuthLocale(document.documentElement.lang);
    document.getElementById('auth-language').addEventListener('change', event => applyAuthLocale(event.target.value));
};
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
