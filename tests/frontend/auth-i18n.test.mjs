import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

test('login language switches both ways, preserves input, and works without storage', () => {
    const nodes = [
        { nodeValue: '  مرحبًا بك في ', parentElement: { closest: () => null } },
        { nodeValue: 'بيانات تسجيل الدخول غير صحيحة.', parentElement: { closest: () => null } },
    ];
    const input = {
        value: 'user@example.com',
        attributes: { placeholder: 'البريد الإلكتروني' },
        hasAttribute(key) { return key in this.attributes; },
        getAttribute(key) { return this.attributes[key]; },
        setAttribute(key, value) { this.attributes[key] = value; },
    };
    const selector = { value: 'ar' };
    const root = { lang: 'ar', dir: 'rtl', dataset: {} };
    let stored;
    const document = {
        body: { classList: { contains: () => true } },
        documentElement: root,
        readyState: 'loading',
        addEventListener() {},
        createTreeWalker() { let i = 0; return { nextNode: () => nodes[i++] || null }; },
        querySelectorAll: () => [input],
        querySelector: () => null,
        getElementById: id => id === 'auth-language' ? selector : null,
    };
    const storage = { setItem(key, value) { stored = [key, value]; } };
    const context = vm.createContext({ document, localStorage: storage, NodeFilter: { SHOW_TEXT: 4 } });
    const source = fs.readFileSync(new URL('../../resources/js/auth-i18n.js', import.meta.url), 'utf8').replaceAll('export const ', 'var ');
    vm.runInContext(source, context);
    context.applyAuthLocale('en');
    assert.equal(root.dir, 'ltr');
    assert.equal(nodes[0].nodeValue, '  Welcome To ');
    assert.equal(nodes[1].nodeValue, 'The login credentials are incorrect.');
    assert.equal(input.attributes.placeholder, 'Email Address');
    assert.equal(document.title, 'Sign In | BashaMob');
    assert.deepEqual(stored, ['basha-admin-language', 'en']);
    assert.equal(context.translateAuth('الكود غير صحيح أو انتهت صلاحيته.'), 'The code is invalid or has expired.');
    nodes[1].nodeValue = 'سنرسل الكود إلى رقم WhatsApp المسجل، وهو صالح لمدة 10 دقائق.';
    context.refreshAuthTranslations();
    assert.match(nodes[1].nodeValue, /registered WhatsApp/);
    context.applyAuthLocale('ar');
    assert.equal(root.dir, 'rtl');
    assert.equal(selector.value, 'ar');
    assert.equal(nodes[0].nodeValue, '  مرحبًا بك في ');
    assert.equal(input.attributes.placeholder, 'البريد الإلكتروني');
    assert.equal(input.value, 'user@example.com');
    storage.setItem = () => { throw new Error('Storage denied'); };
    assert.doesNotThrow(() => context.applyAuthLocale('en'));
    context.applyAuthLocale('invalid');
    assert.equal(root.lang, 'ar');
});
