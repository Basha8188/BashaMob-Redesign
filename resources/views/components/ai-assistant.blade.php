<aside class="ai-assistant" data-ai-assistant data-endpoint="{{ route('assistant.ask') }}">
    <button class="ai-assistant-trigger" type="button" data-ai-toggle aria-expanded="false"><span class="ai-assistant-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 9h8a4 4 0 0 1 4 4v4a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-4a4 4 0 0 1 4-4Z"/><path d="M12 9V5m-2 0h4M8 14h.01M16 14h.01M9 18h6"/></svg></span><b>AI Assistant</b></button>
    <section class="ai-assistant-panel" aria-hidden="true">
        <header><span><b>مساعد BashaMob</b><small>مساعدة سريعة داخل الصفحة</small></span><button type="button" data-ai-toggle>×</button></header>
        <div class="ai-assistant-messages" data-ai-messages><p>أهلًا بك، اسألني عن استخدام الصفحة أو اختر مساعدة سريعة.</p></div>
        <div class="ai-assistant-suggestions"><button type="button" data-ai-question="ماذا أفعل في هذه الصفحة؟">شرح الصفحة</button><button type="button" data-ai-question="كيف أضيف سجلًا جديدًا؟">إضافة جديد</button><button type="button" data-ai-question="كيف أبحث؟">البحث</button></div>
        <form data-ai-form><input name="question" autocomplete="off" placeholder="اكتب سؤالك هنا..." required><button type="submit">إرسال</button></form>
    </section>
</aside>
