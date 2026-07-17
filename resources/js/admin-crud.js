const option = (ar, en) => ({ ar, en });
const field = (name, ar, en, type = 'text', extra = {}) => ({ name, ar, en, type, ...extra });

const sharedStatus = [option('نشط', 'Active'), option('موقوف', 'Suspended')];
const schemas = {
    products: {
        ar: 'منتج', en: 'Product', description: option('أدخل بيانات المنتج والسعر والكمية المتاحة.', 'Enter the product, price and available quantity.'),
        fields: [field('sku','كود المنتج / SKU','Product code / SKU'), field('barcode','الباركود (اتركه فارغًا للتوليد التلقائي)','Barcode (leave blank to auto-generate)','text',{ required:false,inputmode:'numeric',minlength:'13',maxlength:'13',pattern:'[0-9]{13}' }), field('name','اسم المنتج','Product name'), field('brand','الماركة','Brand'), field('price','سعر البيع','Sale price','text',{ inputmode:'decimal' }), field('quantity','الكمية','Quantity','number',{ min:'0' }), field('image','صورة المنتج','Product image','file',{ required:false,table:false,wide:true,accept:'image/jpeg,image/png,image/webp' })]
    },
    sales: {
        ar: 'فاتورة بيع', en: 'Sales invoice', description: option('أنشئ فاتورة بيع واربطها بالعميل وحالة السداد.', 'Create a sales invoice and set its customer and payment status.'),
        fields: [field('number','رقم الفاتورة (اتركه فارغًا للتوليد)','Invoice number','text',{ required:false }), field('customer','اسم العميل','Customer name'), field('date','تاريخ البيع','Sale date','date'), field('total','إجمالي الفاتورة','Invoice total','text',{ inputmode:'decimal' }), field('status','حالة السداد','Payment status','select',{ options:[option('مكتملة','Paid'),option('معلقة','Pending')] }), field('customer_phone','هاتف العميل','Customer phone','tel',{table:false}), field('product_sku','كود المنتج SKU','Product SKU','text',{table:false}), field('quantity','الكمية','Quantity','number',{table:false,min:'0.001'}), field('unit_price','سعر الوحدة','Unit price','text',{table:false,inputmode:'decimal'}), field('paid_amount','المبلغ المدفوع','Paid amount','text',{table:false,inputmode:'decimal',required:false})]
    },
    purchases: {
        ar: 'فاتورة شراء', en: 'Purchase invoice', description: option('سجّل فاتورة المورد وحالة استلام البضاعة.', 'Record the supplier invoice and receiving status.'),
        fields: [field('number','رقم الفاتورة (اتركه فارغًا للتوليد)','Invoice number','text',{required:false}), field('supplier','اسم المورد','Supplier name'), field('date','تاريخ الشراء','Purchase date','date'), field('total','إجمالي الفاتورة','Invoice total','text',{ inputmode:'decimal' }), field('status','حالة الاستلام','Receiving status','select',{ options:[option('مستلمة','Received'),option('قيد الانتظار','Pending')] }), field('supplier_phone','هاتف المورد','Supplier phone','tel',{table:false,required:false}), field('product_sku','كود المنتج SKU','Product SKU','text',{table:false}), field('quantity','الكمية','Quantity','number',{table:false,min:'0.001'}), field('unit_cost','تكلفة الوحدة','Unit cost','text',{table:false,inputmode:'decimal'}), field('paid_amount','المدفوع','Paid amount','text',{table:false,inputmode:'decimal',required:false})]
    },
    inventory: {
        ar: 'حركة مخزون', en: 'Inventory movement', description: option('حدّد المخزن والمنتج والرصيد وحد إعادة الطلب.', 'Set the warehouse, product, stock and reorder level.'),
        fields: [field('warehouse','المخزن','Warehouse'), field('product','المنتج','Product'), field('stock','الرصيد الحالي','Current stock','number',{ min:'0' }), field('minimum','حد إعادة الطلب','Reorder level','number',{ min:'0' }), field('status','حالة المخزون','Stock status','select',{ options:[option('متوفر','Available'),option('منخفض','Low'),option('حرج','Critical')] })]
    },
    repairs: {
        ar: 'تذكرة صيانة', en: 'Repair ticket', description: option('سجّل بيانات العميل والجهاز والعطل وحالة الصيانة.', 'Record the customer, device, fault and repair status.'),
        fields: [field('number','رقم التذكرة','Ticket number','text',{required:false}), field('customer','اسم العميل','Customer name'), field('device','نوع الجهاز','Device'), field('fault','وصف العطل','Fault description'), field('status','حالة الصيانة','Repair status','select',{ options:[option('قيد الإصلاح','In repair'),option('بانتظار قطعة','Waiting for part'),option('جاهز للاستلام','Ready for pickup'),option('تم التسليم','Delivered')] }), field('customer_phone','رقم هاتف العميل','Customer phone','tel',{table:false})]
    },
    customers: {
        ar: 'عميل', en: 'Customer', description: option('أضف بيانات التواصل الأساسية للعميل.', 'Add the customer contact information.'),
        fields: [field('name','اسم العميل','Customer name'), field('phone','رقم الهاتف','Phone number','tel'), field('email','البريد الإلكتروني','Email address','email',{ required:false }), field('history','ملاحظات أو سجل العميل','Customer notes or history'), field('status','حالة العميل','Customer status','select',{ options:sharedStatus })]
    },
    suppliers: {
        ar: 'مورد', en: 'Supplier', description: option('أضف بيانات المورد ورصيده وحالة الحساب.', 'Add supplier details, balance and account status.'),
        fields: [field('name','اسم المورد','Supplier name'), field('phone','رقم الهاتف','Phone number','tel'), field('balance','الرصيد','Balance','text',{ inputmode:'decimal' }), field('status','حالة الحساب','Account status','select',{ options:[option('مسدد','Settled'),option('مستحق','Due'),option('معلق','On hold')] })]
    },
    expenses: {
        ar: 'حركة مالية', en: 'Cash transaction', description: option('سجّل المصروف أو الإيداع داخل الخزنة.', 'Record an expense or cash deposit.'),
        fields: [field('number','رقم الحركة','Transaction number'), field('description','البيان','Description'), field('date','التاريخ','Date','date'), field('amount','القيمة','Amount','text',{ inputmode:'decimal' }), field('type','نوع الحركة','Transaction type','select',{ options:[option('مصروف','Expense'),option('إيداع','Deposit'),option('سحب','Withdrawal')] })]
    },
    reports: {
        ar: 'تقرير', en: 'Report', description: option('حدّد اسم التقرير والنطاق وطريقة إخراجه.', 'Set the report name, scope and output.'),
        fields: [field('name','اسم التقرير','Report name'), field('scope','النطاق أو الفترة','Scope or period'), field('total','الإجمالي أو عدد النتائج','Total or result count'), field('action','طريقة الإخراج','Output','select',{ options:[option('عرض التقرير','View report'),option('تصدير Excel','Export Excel'),option('تصدير PDF','Export PDF')] }), field('status','الحالة','Status','select',{ options:[option('جاهز','Ready'),option('محدث','Updated'),option('قيد الإنشاء','Generating')] })]
    },
    team: {
        ar: 'موظف وصلاحياته', en: 'Employee and permissions', description: option('أدخل بيانات الموظف وحدّد دوره والأقسام المسموح له بالوصول إليها.', 'Enter employee details, role and allowed sections.'),
        fields: [field('name','اسم الموظف','Employee name'), field('email','البريد الإلكتروني','Email address','email'), field('role','الدور الوظيفي','Role','select',{ options:[option('Owner','Owner'),option('Manager','Manager'),option('Cashier','Cashier'),option('Technician','Technician')] }), field('permissions','صلاحيات الوصول','Access permissions','permissions',{ wide:true, options:[option('كل الصلاحيات','All permissions'),option('المبيعات','Sales'),option('المشتريات','Purchases'),option('المخزن','Inventory'),option('الصيانة','Repairs'),option('العملاء','Customers'),option('التقارير','Reports'),option('الإعدادات','Settings')] }), field('status','حالة الحساب','Account status','select',{ options:sharedStatus })]
    },
    settings: {
        ar: 'إعداد', en: 'Setting', description: option('أضف إعدادًا جديدًا وحدّد قيمته وحالته.', 'Add a setting and define its value and status.'),
        fields: [field('section','قسم الإعداد','Settings section'), field('name','اسم الإعداد','Setting name'), field('value','القيمة','Value'), field('action','الإجراء المتاح','Available action'), field('status','الحالة','Status','select',{ options:[option('فعال','Enabled'),option('سليم','Healthy'),option('موقوف','Disabled')] })]
    }
};

const isArabic = () => document.documentElement.lang !== 'en';
const text = value => isArabic() ? value.ar : value.en;
const notify = message => document.dispatchEvent(new CustomEvent('admin:toast', { detail: message }));

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('crud-modal');
    const form = document.getElementById('crud-form');
    const fieldsRoot = document.getElementById('crud-fields');
    if (!modal || !form || !fieldsRoot) return;

    let module = document.querySelector('[data-crud-add]')?.dataset.module || document.querySelector('.admin-table')?.dataset.module;
    let editingRow = null;
    const serverModules = new Set(['products', 'customers', 'suppliers', 'team', 'sales', 'purchases', 'inventory', 'repairs', 'expenses', 'settings']);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const dataCells = row => [...row.children].filter(cell => !cell.querySelector('.row-actions'));
    const tableBody = () => document.querySelector('.admin-table tbody');
    const storageKey = () => `bashamob-admin-records:${module}`;
    const setProductNameCell = (cell, value, imageUrl = '') => {
        const content = document.createElement('span');
        content.className = 'product-table-name';
        if (imageUrl) {
            const image = document.createElement('img');
            image.src = imageUrl;
            image.alt = '';
            content.append(image);
        } else {
            const placeholder = document.createElement('span');
            placeholder.className = 'product-image-placeholder';
            placeholder.textContent = '▣';
            content.append(placeholder);
        }
        const name = document.createElement('b');
        name.textContent = value;
        content.append(name);
        cell.replaceChildren(content);
    };
    const buildRow = (values, id = '', imageUrl = '') => {
        const row = document.createElement('tr');
        if (id) row.dataset.recordId = String(id);
        values.forEach((value, index) => {
            const cell = document.createElement('td');
            if (module === 'products' && index === 2) setProductNameCell(cell, value, imageUrl);
            else cell.textContent = value;
            if (index === values.length - 1) cell.className = 'status-cell';
            row.append(cell);
        });
        const actions = document.createElement('td');
        const canEdit = !['sales', 'purchases', 'inventory', 'reports'].includes(module);
        const canDelete = !['inventory', 'reports', 'repairs'].includes(module);
        const deleteTitle = ['sales', 'purchases'].includes(module) ? 'إلغاء' : 'حذف';
        actions.innerHTML = `<div class="row-actions">${module === 'sales' ? '<button type="button" data-row-print title="طباعة الفاتورة">▣</button>' : ''}${canEdit ? '<button type="button" data-row-edit title="تعديل">✎</button>' : ''}${canDelete ? `<button type="button" data-row-delete title="${deleteTitle}">×</button>` : ''}</div>`;
        row.append(actions);
        return row;
    };
    const persistRows = () => {};
    const fieldValue = (descriptor) => {
        if (descriptor.type === 'file') return form.elements[descriptor.name]?.files?.[0] || null;
        if (descriptor.type === 'permissions') {
            const checked = [...form.querySelectorAll(`[name="${descriptor.name}"]:checked`)];
            return checked.map(input => input.value).join(isArabic() ? '، ' : ', ');
        }
        return form.elements[descriptor.name]?.value?.trim() || '';
    };

    const makeField = (descriptor, value = '') => {
        const wrapper = document.createElement('label');
        wrapper.className = `crud-field${descriptor.wide ? ' crud-field--wide' : ''}${descriptor.type === 'permissions' ? ' crud-permissions' : ''}`;
        const caption = document.createElement('span');
        caption.textContent = isArabic() ? descriptor.ar : descriptor.en;
        wrapper.append(caption);

        if (descriptor.type === 'select') {
            const select = document.createElement('select');
            select.name = descriptor.name;
            select.required = descriptor.required !== false;
            descriptor.options.forEach(item => {
                const node = document.createElement('option');
                node.value = text(item);
                node.textContent = text(item);
                select.append(node);
            });
            const matching = [...select.options].find(item => item.value.toLowerCase() === value.toLowerCase());
            if (matching) select.value = matching.value;
            wrapper.append(select);
            return wrapper;
        }

        if (descriptor.type === 'permissions') {
            const grid = document.createElement('span');
            grid.className = 'permission-grid';
            descriptor.options.forEach((item, index) => {
                const choice = document.createElement('label');
                const input = document.createElement('input');
                const localized = text(item);
                input.type = 'checkbox'; input.name = descriptor.name; input.value = localized;
                input.checked = value.includes(localized) || (index > 0 && /كل الصلاحيات|All permissions/i.test(value));
                const labelText = document.createElement('span');
                labelText.textContent = localized;
                choice.append(input, labelText); grid.append(choice);
            });
            wrapper.append(grid);
            return wrapper;
        }

        const input = document.createElement('input');
        input.type = descriptor.type;
        input.name = descriptor.name;
        if (descriptor.type !== 'file') input.value = descriptor.type === 'date' ? normalizeDate(value) : value;
        input.required = descriptor.required !== false;
        input.placeholder = isArabic() ? `أدخل ${descriptor.ar}` : `Enter ${descriptor.en.toLowerCase()}`;
        ['inputmode','min','max','minlength','maxlength','pattern','accept'].forEach(attribute => { if (descriptor[attribute] !== undefined) input.setAttribute(attribute, descriptor[attribute]); });
        wrapper.append(input);
        if (descriptor.type === 'file') {
            const hint = document.createElement('small');
            hint.className = 'crud-file-hint';
            hint.textContent = isArabic() ? 'JPG أو PNG أو WebP — بحد أقصى 4 ميجابايت' : 'JPG, PNG or WebP — max 4 MB';
            wrapper.append(hint);
        }
        return wrapper;
    };

    const normalizeDate = value => {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
        const months = { 'يناير':'01','فبراير':'02','مارس':'03','أبريل':'04','مايو':'05','يونيو':'06','يوليو':'07','أغسطس':'08','سبتمبر':'09','أكتوبر':'10','نوفمبر':'11','ديسمبر':'12' };
        const match = value.match(/(\d{1,2})\s+([^\s]+)\s+(\d{4})/);
        return match && months[match[2]] ? `${match[3]}-${months[match[2]]}-${String(match[1]).padStart(2,'0')}` : '';
    };

    const renderFields = (schema, row) => {
        fieldsRoot.replaceChildren();
        const values = row ? dataCells(row).map(cell => cell.textContent.trim()) : [];
        let tableIndex = 0;
        schema.fields.forEach(descriptor => {
            const value = descriptor.table === false ? '' : (values[tableIndex++] || '');
            fieldsRoot.append(makeField(descriptor, value));
        });
        const allPermissions = form.querySelector('.permission-grid input');
        allPermissions?.addEventListener('change', () => {
            if (allPermissions.checked) form.querySelectorAll('.permission-grid input').forEach(input => { input.checked = true; });
        });
    };

    const open = (targetModule, row = null) => {
        module = targetModule || module;
        const schema = schemas[module];
        if (!schema) return;
        editingRow = row;
        form.reset();
        document.getElementById('crud-kicker').textContent = isArabic() ? `إدارة ${schema.ar}` : `${schema.en} management`;
        document.getElementById('crud-title').textContent = isArabic() ? `${row ? 'تعديل' : 'إضافة'} ${schema.ar}` : `${row ? 'Edit' : 'Add'} ${schema.en}`;
        document.getElementById('crud-description').textContent = text(schema.description);
        document.getElementById('crud-submit').textContent = isArabic() ? (row ? 'حفظ التعديلات' : `إضافة ${schema.ar}`) : (row ? 'Save changes' : `Add ${schema.en}`);
        renderFields(schema, row);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => fieldsRoot.querySelector('input,select')?.focus(), 100);
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        editingRow = null;
    };

    document.addEventListener('click', async event => {
        const addButton = event.target.closest('[data-crud-add]');
        const editButton = event.target.closest('[data-row-edit]');
        const deleteButton = event.target.closest('[data-row-delete]');
        if (addButton) open(addButton.dataset.module);
        if (editButton) open(document.querySelector('.admin-table')?.dataset.module, editButton.closest('tr'));
        if (deleteButton && window.confirm(isArabic() ? 'هل تريد حذف هذا السجل؟' : 'Delete this record?')) {
            const row = deleteButton.closest('tr');
            if (serverModules.has(module) && row?.dataset.recordId) {
                try {
                    const response = await fetch(`/admin/${module}/${row.dataset.recordId}`, { method: 'DELETE', headers: { 'Accept':'application/json', 'X-CSRF-TOKEN':csrfToken } });
                    const payload = await response.json().catch(() => ({ message:'تعذر حذف السجل.' }));
                    if (!response.ok) throw new Error(payload.message || 'تعذر حذف السجل.');
                    row.remove(); notify(payload.message);
                } catch (error) { notify(error.message); }
            } else {
                row?.remove();
                notify(isArabic() ? 'تم حذف السجل' : 'Record deleted');
            }
        }
    });
    document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', close));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const schema = schemas[module];
        const values = schema.fields.map(field => fieldValue(field));
        if (schema.fields.some((field, index) => field.required !== false && !values[index])) {
            notify(isArabic() ? 'أكمل كل البيانات المطلوبة' : 'Complete all required fields');
            return;
        }

        if (serverModules.has(module)) {
            const button = form.querySelector('button[type="submit"]');
            const recordId = editingRow?.dataset.recordId;
            const hasFile = schema.fields.some(field => field.type === 'file');
            let requestBody;
            const headers = { 'Accept':'application/json', 'X-CSRF-TOKEN':csrfToken };
            let requestMethod = recordId ? 'PATCH' : 'POST';
            if (hasFile) {
                requestBody = new FormData();
                schema.fields.forEach((field, index) => {
                    const value = values[index];
                    if (value instanceof File) requestBody.append(field.name, value);
                    else if (value !== null && value !== undefined) requestBody.append(field.name, value);
                });
                if (recordId) {
                    requestBody.append('_method', 'PATCH');
                    requestMethod = 'POST';
                }
            } else {
                headers['Content-Type'] = 'application/json';
                requestBody = JSON.stringify(Object.fromEntries(schema.fields.map((field, index) => [field.name, values[index]])));
            }
            button.disabled = true;
            try {
                const response = await fetch(`/admin/${module}${recordId ? `/${recordId}` : ''}`, {
                    method: requestMethod,
                    headers,
                    body: requestBody
                });
                const payload = await response.json().catch(() => ({ message:'تعذر حفظ البيانات.' }));
                if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'تعذر حفظ البيانات.');
                const saved = payload.row;
                if (editingRow) {
                    editingRow.dataset.recordId = String(saved.id);
                    dataCells(editingRow).forEach((cell, index) => {
                        const value = saved.cells[index] ?? '';
                        if (module === 'products' && index === 2) setProductNameCell(cell, value, saved.image_url);
                        else cell.replaceChildren(document.createTextNode(value));
                    });
                } else if (tableBody()) tableBody().prepend(buildRow(saved.cells, saved.id, saved.image_url));
                else window.setTimeout(() => window.location.reload(), 500);
                close(); notify(payload.message);
            } catch (error) { notify(error.message); }
            finally { button.disabled = false; }
            return;
        }

        if (editingRow) {
            const updatedRow = editingRow;
            dataCells(editingRow).forEach((cell, index) => {
                if (values[index] !== undefined) cell.replaceChildren(document.createTextNode(values[index]));
            });
            updatedRow.classList.add('search-hit');
            window.setTimeout(() => updatedRow.classList.remove('search-hit'), 1800);
            document.dispatchEvent(new CustomEvent('admin:content-updated', { detail: updatedRow }));
        } else {
            const body = tableBody();
            if (!body) return;
            body.prepend(buildRow(values));
        }
        const wasEditing = Boolean(editingRow);
        persistRows();
        close();
        notify(isArabic() ? (wasEditing ? 'تم حفظ التعديلات بنجاح' : `تمت إضافة ${schema.ar} بنجاح`) : (wasEditing ? 'Changes saved successfully' : `${schema.en} added successfully`));
    });

});
