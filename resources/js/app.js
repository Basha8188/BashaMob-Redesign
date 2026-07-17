import './bootstrap';
import './admin-i18n';
import './admin-search';
import './admin-crud';

document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.ambient-leaves')) {
        const leaves = document.createElement('div');
        leaves.className = 'ambient-leaves';
        leaves.setAttribute('aria-hidden', 'true');
        leaves.innerHTML = Array.from({ length: 12 }, (_, index) => `<i style="--leaf:${index}"></i>`).join('');
        document.body.prepend(leaves);
    }

    const html = document.documentElement;
    const toggle = document.querySelector('.theme-toggle');
    const nav = document.querySelector('.liquid-nav');
    const glow = document.querySelector('.nav-glow');
    const links = [...document.querySelectorAll('.nav-link')];

    document.querySelectorAll('.theme-picker').forEach(picker => {
        const button = picker.querySelector('.theme-picker-toggle');
        button?.addEventListener('click', event => {
            event.stopPropagation();
            const open = picker.classList.toggle('is-open');
            button.setAttribute('aria-expanded', String(open));
        });
        picker.querySelectorAll('[data-palette]').forEach(option => option.addEventListener('click', () => {
            html.dataset.palette = option.dataset.palette;
            localStorage.setItem('basha-palette', option.dataset.palette);
            picker.classList.remove('is-open');
        }));
    });
    document.addEventListener('click', () => document.querySelectorAll('.theme-picker.is-open').forEach(picker => picker.classList.remove('is-open')));

    const moveGlow = (link) => {
        if (!link || !glow || !nav) return;
        glow.style.opacity = '1';
        glow.style.width = `${link.offsetWidth}px`;
        glow.style.transform = `translateX(${link.offsetLeft - 7}px)`;
    };

    const active = document.querySelector('.nav-link.is-active');
    requestAnimationFrame(() => moveGlow(active));
    links.forEach(link => link.addEventListener('mouseenter', () => moveGlow(link)));
    nav?.addEventListener('mouseleave', () => moveGlow(active));
    window.addEventListener('resize', () => moveGlow(active));

    toggle?.addEventListener('click', () => {
        const theme = html.dataset.theme === 'dark' ? 'light' : 'dark';
        html.dataset.theme = theme;
        localStorage.setItem('basha-theme', theme);
    });

    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            if (button.classList.contains('reference-eye')) {
                button.classList.toggle('is-visible', input.type === 'text');
                button.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
            } else {
                button.textContent = input.type === 'password' ? '◉' : '⊘';
            }
        });
    });

    const upload = document.querySelector('.device-upload input');
    upload?.addEventListener('change', () => {
        const file = upload.files?.[0];
        const title = document.querySelector('.device-upload strong');
        if (file && title) title.textContent = file.name;
    });

    const toast = document.getElementById('admin-toast');
    const receiptAction = document.getElementById('receipt-action');
    const receiptDate = document.getElementById('receipt-date');
    const receiptNumber = document.getElementById('receipt-number');
    const receiptAmount = document.getElementById('receipt-amount');

    const showToast = (message = 'تم حفظ البيانات بنجاح') => {
        if (!toast) return;
        const toastMessage = toast.querySelector('span');
        if (toastMessage) toastMessage.textContent = message;
        if (receiptAction) receiptAction.textContent = message;
        if (receiptDate) receiptDate.textContent = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date());
        if (receiptNumber) receiptNumber.textContent = `RCP-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`;
        if (receiptAmount) receiptAmount.textContent = '—';
        toast.classList.add('show');
        window.setTimeout(() => toast.classList.remove('show'), 7000);
    };

    document.addEventListener('admin:toast', event => showToast(event.detail));

    document.querySelector('[data-store-focus]')?.addEventListener('click', () => {
        const firstField = document.querySelector('[data-demo-form] input');
        document.querySelector('[data-demo-form]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(() => firstField?.focus(), 350);
    });

    document.querySelector('[data-demo-form]')?.addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value }, body: new FormData(form) });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'تعذر حفظ البيانات.');
            showToast(payload.message);
        } catch (error) {
            showToast(error.message);
        } finally {
            button.disabled = false;
        }
    });

    document.querySelector('[data-report-summary]')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        button.disabled = true;
        try {
            const response = await fetch('/admin/reports/summary', { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'تعذر إنشاء التقرير.');
            showToast(`التقرير جاهز — المبيعات: ${payload.sales ?? 0}، المشتريات: ${payload.purchases ?? 0}، قيمة المخزون: ${payload.stock_value ?? 0}`);
        } catch (error) {
            showToast(error.message);
        } finally {
            button.disabled = false;
        }
    });

    const sidebar = document.querySelector('.admin-sidebar');
    document.querySelector('.sidebar-toggle')?.addEventListener('click', () => sidebar?.classList.toggle('mobile-open'));

    const topbarSearch = document.querySelector('.topbar-search input');
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            topbarSearch?.focus();
        }
    });

    const notificationButton = document.querySelector('.notification-button');
    const notificationPopover = document.getElementById('admin-notifications');
    notificationButton?.addEventListener('click', event => {
        event.stopPropagation();
        const open = notificationPopover?.classList.toggle('is-open') ?? false;
        notificationPopover?.setAttribute('aria-hidden', String(!open));
        notificationButton.classList.toggle('is-open', open);
    });
    notificationPopover?.addEventListener('click', event => event.stopPropagation());
    document.querySelector('[data-mark-notifications]')?.addEventListener('click', () => {
        notificationPopover?.classList.add('all-read');
        notificationButton?.classList.add('all-read');
        const count = notificationButton?.querySelector('.notification-count');
        if (count) count.textContent = '0';
    });
    document.addEventListener('click', () => {
        notificationPopover?.classList.remove('is-open');
        notificationPopover?.setAttribute('aria-hidden', 'true');
        notificationButton?.classList.remove('is-open');
    });
    document.getElementById('print-receipt')?.addEventListener('click', () => window.print());

    const printInvoice = (row = null) => {
        const cells = row?.querySelectorAll('td');
        if (receiptAction) receiptAction.textContent = cells ? `فاتورة بيع — ${cells[1]?.textContent.trim()}` : 'فاتورة بيع';
        if (receiptDate) receiptDate.textContent = cells?.[2]?.textContent.trim() || new Intl.DateTimeFormat('ar-EG', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date());
        if (receiptNumber) receiptNumber.textContent = cells?.[0]?.textContent.trim() || `SAL-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`;
        if (receiptAmount) receiptAmount.textContent = cells?.[3]?.textContent.trim() ? `${cells[3].textContent.trim()} ج.م` : '—';
        window.print();
    };
    document.querySelector('[data-print-invoice]')?.addEventListener('click', () => printInvoice(document.querySelector('.admin-table tbody tr')));
    document.addEventListener('click', event => {
        const printButton = event.target.closest('[data-row-print]');
        if (printButton) printInvoice(printButton.closest('tr'));
    });

    const welcome = document.getElementById('customer-welcome');
    const customerPage = document.getElementById('customer-request-page');
    const dismissWelcome = () => {
        welcome?.classList.add('is-leaving');
        window.setTimeout(() => {
            welcome?.remove();
            customerPage?.classList.remove('is-waiting');
            customerPage?.classList.add('is-ready');
        }, 650);
    };
    if (welcome) {
        document.getElementById('skip-welcome')?.addEventListener('click', dismissWelcome);
        window.setTimeout(dismissWelcome, 2800);
    }

    const requestCountKey = 'basha-customer-request-count';
    const serverBadge = document.querySelector('[data-customer-request-badge][data-server-count]');
    if (serverBadge) localStorage.setItem(requestCountKey, serverBadge.dataset.serverCount || '0');
    const readRequestCount = () => Number.parseInt(localStorage.getItem(requestCountKey) || '0', 10) || 0;
    const updateRequestBadges = () => document.querySelectorAll('[data-customer-request-badge]').forEach(badge => {
        badge.textContent = String(readRequestCount());
        badge.hidden = readRequestCount() < 1;
    });
    updateRequestBadges();
    window.addEventListener('storage', event => { if (event.key === requestCountKey) updateRequestBadges(); });

    const storefrontToast = document.getElementById('storefront-toast');
    let storefrontToastTimer;
    const showStorefrontToast = message => {
        if (!storefrontToast) return;
        storefrontToast.textContent = message;
        storefrontToast.classList.add('show');
        window.clearTimeout(storefrontToastTimer);
        storefrontToastTimer = window.setTimeout(() => storefrontToast.classList.remove('show'), 3200);
    };

    const productCards = [...document.querySelectorAll('[data-product-card]')];
    const productSearch = document.querySelector('[data-product-search]');
    let activeProductFilter = 'all';
    const normalizeProductText = value => value.toLocaleLowerCase().replace(/[\u064B-\u065F\u0670]/g, '').replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').trim();
    const filterProducts = () => {
        const query = normalizeProductText(productSearch?.value || '');
        productCards.forEach(card => {
            const matchesType = activeProductFilter === 'all'
                || card.dataset.kind === activeProductFilter
                || (activeProductFilter === 'offers' && card.dataset.offer === '1');
            const matchesText = !query || normalizeProductText(card.dataset.productName || card.textContent).includes(query);
            card.hidden = !(matchesType && matchesText);
        });
    };
    document.querySelectorAll('[data-product-filter]').forEach(button => button.addEventListener('click', () => {
        activeProductFilter = button.dataset.productFilter;
        document.querySelectorAll('[data-product-filter]').forEach(item => item.classList.toggle('active', item === button));
        filterProducts();
    }));
    productSearch?.addEventListener('input', filterProducts);

    document.addEventListener('click', event => {
        const addButton = event.target.closest('[data-add-product]');
        if (!addButton) return;
        const card = addButton.closest('[data-product-card]');
        const name = card?.querySelector('h3')?.textContent.trim() || 'المنتج';
        const cart = JSON.parse(localStorage.getItem('basha-customer-cart') || '[]');
        cart.push({ name, addedAt: new Date().toISOString() });
        localStorage.setItem('basha-customer-cart', JSON.stringify(cart));
        addButton.textContent = '✓';
        addButton.classList.add('is-added');
        showStorefrontToast(`تمت إضافة ${name} إلى اختياراتك`);
    });

    document.querySelector('[data-quick-maintenance]')?.addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const result = form.querySelector('.quick-maintenance-result');
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        if (result) result.textContent = 'جاري تسجيل البيانات وإرسال رسالة التأكيد...';
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.elements._token.value },
                body: new FormData(form)
            });
            const payload = await response.json().catch(() => ({ message: 'تعذر تسجيل الطلب الآن.' }));
            if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'تعذر تسجيل الطلب الآن.');
            localStorage.setItem(requestCountKey, String(payload.pending_count || readRequestCount() + 1));
            updateRequestBadges();
            const launch = document.querySelector('[data-request-launch]');
            const launchNumber = launch?.querySelector('[data-launch-number]');
            if (launchNumber) launchNumber.textContent = payload.request_number;
            launch?.classList.add('is-active');
            launch?.setAttribute('aria-hidden', 'false');
            const deliveryStatus = launch?.querySelector('[data-delivery-status]');
            const deliveryStages = [
                [0, 'بنفتح خط الاستقبال لطلبك...'],
                [1100, 'موظف الاستقبال استلم بيانات الطلب'],
                [2500, 'طلبك اتحمّل في عربية BashaMob'],
                [4100, 'العربية في الطريق لمركز الصيانة'],
                [5500, 'وصل طلبك عندنا بأمان ✓']
            ];
            deliveryStages.forEach(([delay, message], stage) => window.setTimeout(() => {
                if (deliveryStatus) deliveryStatus.textContent = message;
                if (launch) launch.dataset.deliveryStage = String(stage + 1);
            }, delay));
            if (result) result.textContent = `تم استلام الطلب. رقم المتابعة: ${payload.request_number}`;
            window.setTimeout(() => {
                launch?.classList.remove('is-active');
                launch?.setAttribute('aria-hidden', 'true');
                launch?.removeAttribute('data-delivery-stage');
                showStorefrontToast(`تم تسجيل الطلب ${payload.request_number} وهو قيد المراجعة`);
                button.disabled = false;
            }, 6200);
            form.reset();
        } catch (error) {
            if (result) result.textContent = error.message;
            button.disabled = false;
        }
    });

    document.querySelector('[data-maintenance-track]')?.addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const button = form.querySelector('button[type="submit"]');
        const results = document.querySelector('[data-tracking-results]');
        button.disabled = true;
        if (results) results.innerHTML = '<p class="tracking-loading">جاري البحث عن طلبك...</p>';
        try {
            const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value }, body: new FormData(form) });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'تعذر الاستعلام الآن.');
            if (!results) return;
            if (!payload.requests.length) {
                results.innerHTML = `<p class="tracking-empty">${payload.message}</p>`;
                return;
            }
            results.replaceChildren(...payload.requests.map(request => {
                const card = document.createElement('article');
                card.className = `tracking-card status-${request.status_key}`;
                const heading = document.createElement('div');
                const number = document.createElement('strong'); number.textContent = request.request_number;
                const status = document.createElement('b'); status.textContent = request.status;
                heading.append(number, status);
                const details = document.createElement('p'); details.textContent = `${request.name} — ${request.brand} ${request.device}`;
                const date = document.createElement('small'); date.textContent = `تاريخ التسجيل: ${request.submitted_at}`;
                card.append(heading, details, date);
                return card;
            }));
        } catch (error) {
            if (results) results.innerHTML = `<p class="tracking-empty">${error.message}</p>`;
        } finally {
            button.disabled = false;
        }
    });

    const recoveryModal = document.getElementById('recovery-modal');
    const recoveryRequestForm = document.getElementById('recovery-request-form');
    const recoveryVerifyForm = document.getElementById('recovery-verify-form');
    const recoveryStatus = document.getElementById('recovery-status');
    const csrfToken = document.querySelector('.reference-login-form input[name="_token"]')?.value;

    const setRecoveryStatus = (message, error = false) => {
        if (!recoveryStatus) return;
        recoveryStatus.textContent = message;
        recoveryStatus.classList.toggle('is-error', error);
    };
    const openRecovery = method => {
        if (!recoveryModal || !recoveryRequestForm) return;
        const destination = recoveryRequestForm.elements.destination;
        recoveryRequestForm.elements.method.value = method;
        destination.type = method === 'email' ? 'email' : 'tel';
        destination.name = 'destination';
        destination.placeholder = method === 'email' ? 'admin@bashamob.local' : '2010xxxxxxxx';
        destination.value = method === 'email' ? (document.querySelector('.reference-login-form [name="email"]')?.value || '') : '';
        document.getElementById('recovery-destination-label').textContent = method === 'email' ? 'البريد الإلكتروني على Gmail' : 'رقم WhatsApp بكود الدولة';
        document.getElementById('recovery-copy').textContent = method === 'email' ? 'سنرسل الكود إلى بريدك، وهو صالح لمدة 10 دقائق.' : 'سنرسل الكود إلى رقم WhatsApp المسجل، وهو صالح لمدة 10 دقائق.';
        recoveryRequestForm.hidden = false;
        recoveryVerifyForm.hidden = true;
        recoveryRequestForm.reset();
        recoveryRequestForm.elements.method.value = method;
        destination.value = method === 'email' ? (document.querySelector('.reference-login-form [name="email"]')?.value || '') : '';
        setRecoveryStatus('');
        recoveryModal.classList.add('is-open');
        recoveryModal.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => destination.focus(), 100);
    };
    const closeRecovery = () => {
        recoveryModal?.classList.remove('is-open');
        recoveryModal?.setAttribute('aria-hidden', 'true');
    };
    document.querySelectorAll('[data-recovery-open]').forEach(button => button.addEventListener('click', () => openRecovery(button.dataset.recoveryMethod || 'email')));
    document.querySelectorAll('[data-recovery-close]').forEach(button => button.addEventListener('click', closeRecovery));

    const submitRecovery = async (form, onSuccess) => {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        setRecoveryStatus('جاري الاتصال بالخدمة...');
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(new FormData(form)))
            });
            const payload = await response.json().catch(() => ({ message: 'تعذر الاتصال بخدمة الاسترداد.' }));
            if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'تعذر تنفيذ الطلب.');
            setRecoveryStatus(payload.message);
            await onSuccess(payload);
        } catch (error) {
            setRecoveryStatus(error.message, true);
        } finally {
            button.disabled = false;
        }
    };
    recoveryRequestForm?.addEventListener('submit', event => {
        event.preventDefault();
        if (!recoveryRequestForm.reportValidity()) return;
        submitRecovery(recoveryRequestForm, payload => {
            if (!payload.challenge) return;
            recoveryVerifyForm.elements.challenge.value = payload.challenge;
            recoveryRequestForm.hidden = true;
            recoveryVerifyForm.hidden = false;
            recoveryVerifyForm.elements.code.focus();
        });
    });
    recoveryVerifyForm?.addEventListener('submit', event => {
        event.preventDefault();
        if (!recoveryVerifyForm.reportValidity()) return;
        submitRecovery(recoveryVerifyForm, async payload => {
            if (!payload.reset_token) throw new Error('تعذر إنشاء رمز تغيير كلمة المرور.');
            const resetResponse = await fetch(recoveryVerifyForm.dataset.resetAction, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reset_token: payload.reset_token,
                    password: recoveryVerifyForm.elements.password.value,
                    password_confirmation: recoveryVerifyForm.elements.password_confirmation.value
                })
            });
            const resetPayload = await resetResponse.json().catch(() => ({ message: 'تعذر تغيير كلمة المرور.' }));
            if (!resetResponse.ok) throw new Error(resetPayload.message || Object.values(resetPayload.errors || {})[0]?.[0] || 'تعذر تغيير كلمة المرور.');
            setRecoveryStatus(resetPayload.message);
            recoveryVerifyForm.reset();
            window.setTimeout(closeRecovery, 1800);
        });
    });
});
