import './bootstrap';

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
            button.textContent = input.type === 'password' ? '◉' : '⊘';
        });
    });

    const upload = document.querySelector('.device-upload input');
    upload?.addEventListener('change', () => {
        const file = upload.files?.[0];
        const title = document.querySelector('.device-upload strong');
        if (file && title) title.textContent = file.name;
    });

    const modal = document.getElementById('crud-modal');
    const crudForm = document.getElementById('crud-form');
    const tableBody = document.querySelector('.admin-table tbody');
    const toast = document.getElementById('admin-toast');
    const receiptAction = document.getElementById('receipt-action');
    const receiptDate = document.getElementById('receipt-date');
    const receiptNumber = document.getElementById('receipt-number');
    const receiptAmount = document.getElementById('receipt-amount');
    let editingRow = null;

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

    const openCrud = (row = null) => {
        if (!modal || !crudForm) return;
        editingRow = row;
        crudForm.reset();
        document.getElementById('crud-title').textContent = row ? 'تعديل البيانات' : 'إضافة سجل جديد';
        if (row) {
            const cells = row.querySelectorAll('td');
            crudForm.elements.primary.value = cells[1]?.textContent.trim() || '';
            crudForm.elements.details.value = cells[2]?.textContent.trim() || '';
            crudForm.elements.value.value = cells[3]?.textContent.trim() || '';
            crudForm.elements.status.value = [...crudForm.elements.status.options].some(option => option.value === cells[4]?.textContent.trim()) ? cells[4].textContent.trim() : 'نشط';
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeCrud = () => {
        modal?.classList.remove('is-open');
        modal?.setAttribute('aria-hidden', 'true');
    };

    document.querySelector('[data-crud-add]')?.addEventListener('click', () => openCrud());
    document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', closeCrud));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeCrud(); });

    document.addEventListener('click', event => {
        const editButton = event.target.closest('[data-row-edit]');
        const deleteButton = event.target.closest('[data-row-delete]');
        if (editButton) openCrud(editButton.closest('tr'));
        if (deleteButton && window.confirm('هل تريد حذف هذا السجل التجريبي؟')) {
            deleteButton.closest('tr')?.remove();
            showToast('تم حذف السجل');
        }
    });

    crudForm?.addEventListener('submit', event => {
        event.preventDefault();
        const values = new FormData(crudForm);
        if (editingRow) {
            const cells = editingRow.querySelectorAll('td');
            cells[1].textContent = values.get('primary');
            cells[2].textContent = values.get('details');
            cells[3].textContent = values.get('value');
            cells[4].textContent = values.get('status');
        } else if (tableBody) {
            const row = document.createElement('tr');
            row.dataset.demoRow = '';
            row.innerHTML = `<td>NEW-${String(Date.now()).slice(-5)}</td><td></td><td></td><td></td><td class="status-cell"></td><td><div class="row-actions"><button type="button" data-row-edit title="تعديل">✎</button><button type="button" data-row-delete title="حذف">×</button></div></td>`;
            const cells = row.querySelectorAll('td');
            cells[1].textContent = values.get('primary');
            cells[2].textContent = values.get('details');
            cells[3].textContent = values.get('value');
            cells[4].textContent = values.get('status');
            tableBody.prepend(row);
        }
        closeCrud();
        showToast();
    });

    document.querySelector('[data-demo-form]')?.addEventListener('submit', event => {
        event.preventDefault();
        showToast('تم حفظ بيانات المحل في نسخة العرض');
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

    document.querySelector('.notification-button')?.addEventListener('click', () => showToast('لا توجد إشعارات جديدة'));
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
});
