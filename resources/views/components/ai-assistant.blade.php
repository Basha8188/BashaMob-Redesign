<aside class="ai-assistant" data-ai-assistant data-endpoint="{{ route('assistant.ask') }}">
    <button class="ai-assistant-trigger" type="button" data-ai-toggle aria-expanded="false"><span class="ai-assistant-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 9h8a4 4 0 0 1 4 4v4a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-4a4 4 0 0 1 4-4Z"/><path d="M12 9V5m-2 0h4M8 14h.01M16 14h.01M9 18h6"/></svg></span><b>AI Assistant</b></button>
    <section class="ai-assistant-panel" aria-hidden="true">
        <header><span><b><i class="ai-online-dot"></i>BashaMob AI</b><small>اسألني أي شيء</small></span><div><button type="button" data-ai-reset title="محادثة جديدة" aria-label="محادثة جديدة">↻</button><button type="button" data-ai-toggle aria-label="إغلاق">×</button></div></header>
        <div class="ai-assistant-messages" data-ai-messages><p>أهلًا! أنا مساعد BashaMob. اسألني أي سؤال، أو أخبرني بما تريد إنجازه.</p></div>
        <div class="ai-assistant-suggestions"><button type="button" data-ai-question="اشرح لي هذه الصفحة باختصار">اشرح الصفحة</button><button type="button" data-ai-question="ساعدني في تنفيذ مهمة جديدة">ابدأ مهمة</button><button type="button" data-ai-question="لدي سؤال عام">سؤال عام</button></div>
        <form data-ai-form><input name="question" autocomplete="off" placeholder="اكتب رسالتك..." aria-label="رسالتك إلى BashaMob AI" required><button type="submit">إرسال</button></form>
    </section>
</aside>
