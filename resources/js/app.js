import './bootstrap';
import './background';
import './theme-manager';
import { translateAuth, refreshAuthTranslations } from './auth-i18n';

document.addEventListener('DOMContentLoaded', () => {
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
    // One shared glass orb per existing navigation, including vertical sidebars.
    document.querySelectorAll('[data-liquid-nav]').forEach(liquidNav => {
        const indicator = liquidNav.querySelector('[data-liquid-indicator]');
        const items = [...liquidNav.querySelectorAll('[data-liquid-nav-item]')];
        if (!indicator || !items.length) return;
        const routeItem = items.find(item => item.getAttribute('aria-current') === 'page'
            || item.classList.contains('is-active') || item.classList.contains('active'));
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const vertical = liquidNav.dataset.liquidNav === 'vertical';
        let selected = routeItem;
        let motion;
        let pop;
        let navigationTimer;
        let resizeFrame;
        let ready = false;

        const measure = item => {
            const navRect = liquidNav.getBoundingClientRect();
            const icon = item.querySelector('svg') || item;
            const iconRect = icon.getBoundingClientRect();
            const itemRect = item.getBoundingClientRect();
            return {
                x: iconRect.left + iconRect.width / 2 - navRect.left + liquidNav.scrollLeft - liquidNav.clientLeft,
                y: iconRect.top + iconRect.height / 2 - navRect.top + liquidNav.scrollTop - liquidNav.clientTop,
                size: Math.min(itemRect.height - 4, itemRect.width - 4, Math.max(iconRect.width, iconRect.height) * 1.65),
            };
        };
        const transform = (x, y, size, sx = 1, sy = 1) =>
            'translate3d(' + (x - size / 2) + 'px,' + (y - size / 2) + 'px,0) scale(' + sx + ',' + sy + ')';

        const move = (item, animate = true) => {
            if (!item || !item.getClientRects().length) { indicator.style.opacity = '0'; return 0; }
            pop?.cancel();
            const target = measure(item);
            if (target.size <= 0) return 0;
            // Sample the visual position before cancelling: rapid clicks never jump back.
            const visible = indicator.getBoundingClientRect();
            const navRect = liquidNav.getBoundingClientRect();
            const from = ready ? {
                x: visible.left + visible.width / 2 - navRect.left + liquidNav.scrollLeft - liquidNav.clientLeft,
                y: visible.top + visible.height / 2 - navRect.top + liquidNav.scrollTop - liquidNav.clientTop,
                sx: visible.width / target.size,
                sy: visible.height / target.size,
            } : { x: target.x, y: target.y, sx: 1, sy: 1 };
            motion?.cancel();
            pop?.cancel();
            indicator.style.width = target.size + 'px';
            indicator.style.height = target.size + 'px';
            indicator.style.transform = transform(target.x, target.y, target.size);
            indicator.style.opacity = '1';
            liquidNav.classList.add('liquid-nav-ready');
            selected = item;
            ready = true;
            const dx = target.x - from.x;
            const dy = target.y - from.y;
            const distance = Math.hypot(dx, dy);
            items.forEach(link => {
                link.classList.toggle('liquid-nav-target', link === item);
                link.classList.toggle('liquid-nav-leaving', link === routeItem && link !== item);
            });
            if (!animate || reducedMotion.matches || !indicator.animate || distance < 1) {
                indicator.classList.remove('is-moving');
                return 0;
            }
            const duration = Math.min(600, 450 + distance * .25);
            const stretch = Math.min(1.9, 1.2 + distance / (target.size * 4));
            const direction = (vertical ? dy : dx) >= 0 ? 1 : -1;
            indicator.style.setProperty('--liquid-tail-angle', (vertical ? direction * 90 : direction > 0 ? 0 : 180) + 'deg');
            indicator.classList.add('is-moving');
            const frame = (progress, elongation, squash, offset) => ({
                transform: transform(
                    from.x + dx * progress, from.y + dy * progress, target.size,
                    vertical ? squash : elongation, vertical ? elongation : squash
                ), offset
            });
            motion = indicator.animate([
                { transform: transform(from.x, from.y, target.size, from.sx, from.sy), offset: 0 },
                frame(.25, stretch, .86, .24),
                frame(.67, stretch * .92, .92, .52),
                frame(.94, 1.15, .97, .80),
                frame(1, 1, 1, 1),
            ], { duration, easing: 'cubic-bezier(0.22,1,0.36,1)' });
            const thisMotion = motion;
            motion.finished.then(() => {
                if (motion !== thisMotion) return;
                indicator.classList.remove('is-moving');
                motion = null;
            }).catch(() => {});
            const icon = item.querySelector('svg');
            if (icon?.animate) pop = icon.animate([
                { transform: 'translateY(0) scale(1)' },
                { transform: 'translateY(-2px) scale(1.10)', offset: .4 },
                { transform: 'translateY(0) scale(1.03)', offset: .72 },
                { transform: 'translateY(0) scale(1)' },
            ], { duration: 180, delay: duration * .68, easing: 'cubic-bezier(0.22,1,0.36,1)' });
            return duration;
        };
        move(routeItem, false);
        const realign = () => {
            cancelAnimationFrame(resizeFrame);
            resizeFrame = requestAnimationFrame(() => move(selected, false));
        };
        window.addEventListener('resize', realign, { passive: true });
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(realign);
            observer.observe(liquidNav);
            items.forEach(item => observer.observe(item));
        }
        document.fonts?.ready.then(realign);
        reducedMotion.addEventListener?.('change', realign);
        window.addEventListener('pageshow', () => {
            clearTimeout(navigationTimer);
            move(routeItem, false);
        });
        liquidNav.addEventListener('click', event => {
            const item = event.target.closest('[data-liquid-nav-item]');
            if (!item || !liquidNav.contains(item) || event.defaultPrevented
                || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
                || item.hasAttribute('download') || (item.target && item.target !== '_self')) return;
            const destination = new URL(item.href, window.location.href);
            if (destination.origin !== window.location.origin || !/^https?:$/.test(destination.protocol)) return;
            clearTimeout(navigationTimer);
            const duration = move(item);
            // Preserve native navigation for reduced-motion and older browsers.
            if (!duration) return;
            event.preventDefault();
            navigationTimer = window.setTimeout(() => window.location.assign(destination.href), duration + 70);
        });
    });

    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            if (button.classList.contains('reference-eye')) {
                button.classList.toggle('is-visible', input.type === 'text');
                button.setAttribute('aria-label', translateAuth(input.type === 'password' ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور'));
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
        recoveryStatus.dataset.originalMessage = message;
        recoveryStatus.textContent = translateAuth(message);
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
        refreshAuthTranslations();
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
