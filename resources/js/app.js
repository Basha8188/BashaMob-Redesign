import './bootstrap';
import './background';
import './theme-manager';

document.addEventListener('DOMContentLoaded', () => {
    const lampIntro = document.querySelector('[data-lamp-intro]');
    if (lampIntro) {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let lampIntroTimer;
        const finishLampIntro = () => {
            window.clearTimeout(lampIntroTimer);
            lampIntro.classList.add('is-finished');
            document.body.classList.remove('lamp-intro-active');
            window.setTimeout(() => lampIntro.remove(), reducedMotion ? 0 : 650);
        };

        if (reducedMotion) {
            finishLampIntro();
        } else {
            document.body.classList.add('lamp-intro-active');
            window.requestAnimationFrame(() => lampIntro.classList.add('is-playing'));
            lampIntroTimer = window.setTimeout(finishLampIntro, 4100);
            lampIntro.querySelector('[data-lamp-intro-skip]')?.addEventListener('click', finishLampIntro);
        }
    }

    const assistant = document.querySelector('[data-ai-assistant]');
    if (assistant) {
        const panel = assistant.querySelector('.ai-assistant-panel');
        const messages = assistant.querySelector('[data-ai-messages]');
        const form = assistant.querySelector('[data-ai-form]');
        const submit = form?.querySelector('button[type="submit"]');
        const storageKey = 'basha-ai-conversation';
        let history = [];
        try { history = JSON.parse(sessionStorage.getItem(storageKey) || '[]').slice(-8); } catch { history = []; }
        const addBubble = (text, className = '') => {
            const bubble = document.createElement('p');
            bubble.className = className;
            bubble.textContent = text;
            messages?.append(bubble);
            messages?.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });
            return bubble;
        };
        const saveHistory = () => {
            if (history.length > 8) history.splice(0, history.length - 8);
            sessionStorage.setItem(storageKey, JSON.stringify(history));
        };
        const renderAction = action => {
            if (!messages || action?.type !== 'confirmation') return;
            const card = document.createElement('section');
            card.className = 'ai-action-card';

            const title = document.createElement('strong');
            title.textContent = action.title;
            const details = document.createElement('dl');
            (action.details || []).forEach(item => {
                const label = document.createElement('dt');
                const value = document.createElement('dd');
                label.textContent = item.label;
                value.textContent = item.value;
                details.append(label, value);
            });
            card.append(title, details);

            if (action.warning) {
                const warning = document.createElement('p');
                warning.className = 'ai-action-warning';
                warning.textContent = action.warning;
                card.append(warning);
            }

            const status = document.createElement('small');
            status.className = 'ai-action-status';
            const controls = document.createElement('div');
            const confirm = document.createElement('button');
            confirm.type = 'button';
            confirm.className = 'ai-action-confirm';
            confirm.textContent = action.confirm_label;
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'ai-action-cancel';
            cancel.textContent = action.cancel_label;
            controls.append(confirm, cancel);
            card.append(status, controls);
            messages.append(card);
            messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });

            cancel.addEventListener('click', () => {
                card.remove();
                const text = document.documentElement.lang === 'en'
                    ? 'Cancelled. No data was changed.'
                    : 'تم الإلغاء، ولم يتم تغيير أي بيانات.';
                addBubble(text);
                history.push({ role: 'assistant', content: text });
                saveHistory();
            });

            confirm.addEventListener('click', async () => {
                confirm.disabled = true;
                cancel.disabled = true;
                status.textContent = document.documentElement.lang === 'en' ? 'Executing securely…' : 'جاري التنفيذ بأمان…';
                try {
                    const response = await fetch(action.confirm_url, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ locale: document.documentElement.lang === 'en' ? 'en' : 'ar' }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || (document.documentElement.lang === 'en' ? 'The action could not be completed.' : 'تعذر تنفيذ الإجراء.'));
                    }
                    card.remove();
                    const result = addBubble(payload.answer);
                    if (payload.invoice?.url) {
                        const link = document.createElement('a');
                        link.className = 'ai-action-link';
                        link.href = payload.invoice.url;
                        link.textContent = document.documentElement.lang === 'en' ? 'Open sales invoices' : 'فتح فواتير البيع';
                        result.append(document.createElement('br'), link);
                    }
                    history.push({ role: 'assistant', content: payload.answer });
                    saveHistory();
                    document.dispatchEvent(new CustomEvent('admin:toast', { detail: { message: payload.answer } }));
                } catch (error) {
                    status.textContent = error.message;
                    status.classList.add('is-error');
                    confirm.disabled = false;
                    cancel.disabled = false;
                }
            });
        };
        if (history.length) {
            messages.innerHTML = '';
            history.forEach(message => addBubble(message.content, message.role === 'user' ? 'from-user' : ''));
        }
        const answer = async question => {
            const pending = addBubble(document.documentElement.lang === 'en' ? 'Thinking…' : 'جاري التفكير…', 'is-thinking');
            if (submit) submit.disabled = true;
            try {
                const response = await fetch(assistant.dataset.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        question,
                        page: document.querySelector('h1')?.textContent?.trim() || document.title,
                        locale: document.documentElement.lang === 'en' ? 'en' : 'ar',
                        history: history.slice(-8),
                    }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const safeMessage = [429, 502, 503].includes(response.status) && payload.message
                        ? payload.message
                        : (document.documentElement.lang === 'en' ? 'The assistant is unavailable right now. Please try again.' : 'المساعد غير متاح حاليًا. حاول مرة أخرى.');
                    throw new Error(safeMessage);
                }
                pending.textContent = payload.answer;
                pending.classList.remove('is-thinking');
                history.push({ role: 'user', content: question }, { role: 'assistant', content: payload.answer });
                saveHistory();
                renderAction(payload.action);
            } catch (error) {
                pending.textContent = error.message;
                pending.classList.remove('is-thinking');
            } finally {
                if (submit) submit.disabled = false;
            }
        };
        assistant.querySelectorAll('[data-ai-toggle]').forEach(button => button.addEventListener('click', () => { const open = !assistant.classList.contains('is-open'); assistant.classList.toggle('is-open', open); panel?.setAttribute('aria-hidden', String(!open)); assistant.querySelector('.ai-assistant-trigger')?.setAttribute('aria-expanded', String(open)); }));
        assistant.querySelector('[data-ai-reset]')?.addEventListener('click', () => {
            history = [];
            sessionStorage.removeItem(storageKey);
            messages.innerHTML = '';
            addBubble(document.documentElement.lang === 'en' ? 'New conversation started. How can I help?' : 'بدأنا محادثة جديدة. كيف أقدر أساعدك؟');
            form?.elements.question.focus();
        });
        assistant.querySelectorAll('[data-ai-question]').forEach(button => button.addEventListener('click', async () => {
            const question = button.dataset.aiQuestion;
            addBubble(question, 'from-user');
            await answer(question);
        }));
        form?.addEventListener('submit', async event => {
            event.preventDefault();
            const input = event.currentTarget.elements.question;
            const question = input.value.trim();
            if (!question || submit?.disabled) return;
            addBubble(question, 'from-user');
            input.value = '';
            await answer(question);
            input.focus();
        });
    }

    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-request-decision]');
        if (!button) return;
        button.disabled = true;
        try {
            const response = await fetch(`/admin/customer-requests/${button.dataset.requestType}/${button.dataset.requestId}`, { method: 'PATCH', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, body: JSON.stringify({ decision: button.dataset.requestDecision }) });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'تعذر تحديث الطلب.');
            const card = button.closest('[data-customer-request-card]'); card.querySelector('[data-request-status]').textContent = payload.status; card.querySelector('footer span')?.remove();
            document.dispatchEvent(new CustomEvent('admin:toast', { detail: { message: payload.message } }));
        } catch (error) { document.dispatchEvent(new CustomEvent('admin:toast', { detail: { message: error.message } })); button.disabled = false; }
    });
    const html = document.documentElement;
    const nav = document.querySelector('.liquid-nav');
    const glow = document.querySelector('.nav-glow');
    const links = [...document.querySelectorAll('.nav-link')];

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
    const printReceiptButton = document.getElementById('print-receipt');

    const showToast = (detail = 'تم حفظ البيانات بنجاح') => {
        if (!toast) return;
        const options = typeof detail === 'object' && detail !== null ? detail : { message: detail };
        const message = options.message || 'تم حفظ البيانات بنجاح';
        const toastMessage = toast.querySelector('span');
        if (toastMessage) toastMessage.textContent = message;
        if (printReceiptButton) printReceiptButton.hidden = !options.printable;
        if (options.printable) {
            if (receiptAction) receiptAction.textContent = options.action || message;
            if (receiptDate) receiptDate.textContent = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date());
            if (receiptNumber) receiptNumber.textContent = options.number || `RCP-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`;
            if (receiptAmount) receiptAmount.textContent = options.amount ? `${options.amount} ج.م` : '—';
        }
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
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarPinButtons = [...document.querySelectorAll('[data-sidebar-pin]')];
    const syncSidebarToggle = () => {
        const expanded = window.innerWidth <= 760 ? sidebar?.classList.contains('mobile-open') : html.classList.contains('sidebar-pinned');
        sidebarToggle?.setAttribute('aria-expanded', String(expanded));
        sidebarToggle?.setAttribute('title', expanded ? 'إلغاء تثبيت القائمة الجانبية' : 'تثبيت القائمة الجانبية');
        sidebarPinButtons.forEach(button => {
            button.setAttribute('aria-pressed', String(expanded));
            button.setAttribute('title', expanded ? 'إلغاء تثبيت القائمة' : 'تثبيت القائمة');
        });
    };
    const toggleSidebar = () => {
        if (window.innerWidth <= 760) sidebar?.classList.toggle('mobile-open');
        else {
            html.classList.toggle('sidebar-pinned');
            localStorage.setItem('basha-sidebar', html.classList.contains('sidebar-pinned') ? 'pinned' : 'hover');
        }
        syncSidebarToggle();
    };
    sidebarToggle?.addEventListener('click', toggleSidebar);
    sidebarPinButtons.forEach(button => button.addEventListener('click', toggleSidebar));
    syncSidebarToggle();

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

    const posWorkspace = document.querySelector('[data-pos-workspace]');
    if (posWorkspace) {
        const selection = posWorkspace.querySelector('[data-pos-cart-selection]');
        const empty = posWorkspace.querySelector('[data-pos-cart-empty]');
        const quantity = posWorkspace.querySelector('[data-pos-quantity]');
        const checkout = posWorkspace.querySelector('[data-pos-checkout]');
        let selectedProduct = null;
        const updatePosTotal = () => {
            const amount = Number(selectedProduct?.price || 0) * Math.max(1, Number(quantity?.value || 1));
            const total = posWorkspace.querySelector('[data-pos-total]');
            if (total) total.textContent = `${amount.toLocaleString('ar-EG', { minimumFractionDigits: 2 })} ج.م`;
        };
        posWorkspace.querySelectorAll('[data-pos-product]').forEach(button => button.addEventListener('click', () => {
            posWorkspace.querySelectorAll('[data-pos-product]').forEach(item => item.classList.toggle('is-selected', item === button));
            selectedProduct = { name: button.dataset.name, sku: button.dataset.sku, price: Number(button.dataset.price || 0) };
            empty.hidden = true; selection.hidden = false; checkout.disabled = false;
            selection.querySelector('[data-pos-name]').textContent = selectedProduct.name;
            selection.querySelector('[data-pos-sku]').textContent = selectedProduct.sku;
            quantity.value = 1; updatePosTotal();
        }));
        quantity?.addEventListener('input', updatePosTotal);
        checkout?.addEventListener('click', () => {
            if (!selectedProduct) return;
            document.querySelector('[data-crud-add][data-module="sales"]')?.click();
            window.setTimeout(() => {
                const form = document.getElementById('crud-form');
                if (!form) return;
                if (form.elements.product_sku) form.elements.product_sku.value = selectedProduct.sku;
                if (form.elements.quantity) form.elements.quantity.value = quantity.value;
                if (form.elements.unit_price) form.elements.unit_price.value = selectedProduct.price;
                if (form.elements.total) form.elements.total.value = selectedProduct.price * Number(quantity.value || 1);
            }, 80);
        });
    }

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

    const cartKey = 'basha-customer-cart';
    const readCart = () => {
        try { return JSON.parse(localStorage.getItem(cartKey) || '[]'); }
        catch { return []; }
    };
    const writeCart = cart => {
        localStorage.setItem(cartKey, JSON.stringify(cart));
        document.querySelectorAll('[data-cart-count]').forEach(badge => { badge.textContent = String(cart.length); });
    };
    const orderModal = document.querySelector('[data-order-modal]');
    const cartItems = document.querySelector('[data-cart-items]');
    const renderCart = () => {
        const cart = readCart();
        writeCart(cart);
        if (!cartItems) return;
        if (!cart.length) {
            cartItems.innerHTML = '<p class="customer-cart-empty">لم تضف منتجات إلى طلبك بعد.</p>';
            return;
        }
        cartItems.replaceChildren(...cart.map((item, index) => {
            const row = document.createElement('article');
            const details = document.createElement('span');
            const name = document.createElement('b'); name.textContent = item.name;
            const price = document.createElement('small'); price.textContent = `${Number(item.price || 0).toLocaleString('ar-EG')} ج.م`;
            const remove = document.createElement('button'); remove.type = 'button'; remove.dataset.cartRemove = String(index); remove.textContent = '×'; remove.setAttribute('aria-label', 'حذف المنتج');
            details.append(name, price); row.append(details, remove);
            return row;
        }));
    };
    const closeOrderModal = () => {
        orderModal?.classList.remove('is-open');
        orderModal?.setAttribute('aria-hidden', 'true');
    };
    document.querySelectorAll('[data-cart-open]').forEach(button => button.addEventListener('click', () => {
        renderCart(); orderModal?.classList.add('is-open'); orderModal?.setAttribute('aria-hidden', 'false');
    }));
    document.querySelectorAll('[data-cart-close]').forEach(button => button.addEventListener('click', closeOrderModal));
    renderCart();

    document.addEventListener('click', event => {
        const addButton = event.target.closest('[data-add-product]');
        const removeButton = event.target.closest('[data-cart-remove]');
        if (removeButton) {
            const cart = readCart(); cart.splice(Number(removeButton.dataset.cartRemove), 1); writeCart(cart); renderCart(); return;
        }
        if (!addButton) return;
        const card = addButton.closest('[data-product-card]');
        const name = card?.querySelector('h3')?.textContent.trim() || 'المنتج';
        const cart = readCart();
        cart.push({ name, price: Number(card?.dataset.productPrice || 0), addedAt: new Date().toISOString() });
        writeCart(cart);
        addButton.textContent = '✓';
        addButton.classList.add('is-added');
        showStorefrontToast(`تمت إضافة ${name} إلى طلبك`);
    });

    document.querySelector('[data-customer-order]')?.addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const result = form.querySelector('[data-order-result]');
        const button = form.querySelector('button[type="submit"]');
        const cart = readCart();
        if (!cart.length) { if (result) result.textContent = 'أضف منتجًا واحدًا على الأقل.'; return; }
        button.disabled = true;
        if (result) result.textContent = 'جاري تسجيل طلبك...';
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.elements._token.value },
                body: JSON.stringify({ name: form.elements.name.value, phone: form.elements.phone.value, email: form.elements.email.value, items: cart.map(({ name, price }) => ({ name, price })) })
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'تعذر تسجيل طلب الشراء.');
            writeCart([]); form.reset(); renderCart();
            localStorage.setItem(requestCountKey, String(readRequestCount() + 1));
            if (result) result.textContent = `تم تسجيل طلبك برقم ${payload.order_number}`;
            showStorefrontToast(`تم إرسال طلب الشراء ${payload.order_number} إلى BashaMob`);
            window.setTimeout(closeOrderModal, 2200);
        } catch (error) {
            if (result) result.textContent = error.message;
        } finally { button.disabled = false; }
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
            launch?.classList.remove('is-active');
            if (launch) void launch.offsetWidth;
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

    const trackingModal = document.querySelector('[data-tracking-modal]');
    const closeTrackingModal = () => {
        trackingModal?.classList.remove('is-open');
        trackingModal?.setAttribute('aria-hidden', 'true');
    };
    document.querySelectorAll('[data-close-tracking]').forEach(button => button.addEventListener('click', closeTrackingModal));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeTrackingModal(); });

    document.querySelector('[data-maintenance-track]')?.addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const button = form.querySelector('button[type="submit"]');
        const results = document.querySelector('[data-tracking-results]');
        button.disabled = true;
        trackingModal?.classList.add('is-open');
        trackingModal?.setAttribute('aria-hidden', 'false');
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
                if (request.review_message) { const note = document.createElement('p'); note.className = 'tracking-review-message'; note.textContent = request.review_message; card.append(note); }
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
