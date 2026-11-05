const option = (ar, en) => ({ ar, en });
const field = (name, ar, en, type = 'text', extra = {}) => ({ name, ar, en, type, ...extra });

const sharedStatus = [option('نشط', 'Active'), option('موقوف', 'Suspended')];
const schemas = {
    products: {
        ar: 'منتج', en: 'Product', description: option('أدخل بيانات المنتج والسعر والكمية المتاحة.', 'Enter the product, price and available quantity.'),
        fields: [field('sku','كود المنتج / SKU','Product code / SKU'), field('barcode','الباركود (اتركه فارغًا للتوليد التلقائي)','Barcode (leave blank to auto-generate)','text',{ required:false,inputmode:'numeric',minlength:'13',maxlength:'13',pattern:'[0-9]{13}' }), field('name','اسم المنتج','Product name'), field('brand','الماركة','Brand'), field('purchase_cost','سعر الشراء','Purchase price','number',{ min:'0',step:'0.000001' }), field('price','سعر البيع','Sale price','number',{ min:'0',step:'0.01' }), field('quantity','الكمية','Quantity','number',{ min:'0',step:'0.001' }), field('tracking_type','نوع التتبع','Tracking type','select',{ required:false,table:false,options:[{ar:'كمية',en:'Quantity',value:'quantity'},{ar:'IMEI',en:'IMEI',value:'imei'},{ar:'Serial',en:'Serial',value:'serial'}] }), field('image','صورة المنتج','Product image','file',{ required:false,table:false,wide:true,accept:'image/jpeg,image/png,image/webp' })]
    },
    sales: {
        ar: 'فاتورة بيع', en: 'Sales invoice', description: option('أنشئ فاتورة بيع واربطها بالعميل وحالة السداد.', 'Create a sales invoice and set its customer and payment status.'),
        fields: [field('number','رقم الفاتورة (اتركه فارغًا للتوليد)','Invoice number','text',{ required:false }), field('customer','اسم العميل','Customer name'), field('date','تاريخ البيع','Sale date','date'), field('total','إجمالي الفاتورة','Invoice total','text',{ inputmode:'decimal' }), field('status','حالة السداد','Payment status','select',{ options:[option('مكتملة','Paid'),option('معلقة','Pending')] }), field('customer_phone','هاتف العميل','Customer phone','tel',{table:false}), field('product_sku','كود المنتج SKU','Product SKU','text',{table:false}), field('quantity','الكمية','Quantity','number',{table:false,min:'0.001'}), field('unit_price','سعر الوحدة','Unit price','text',{table:false,inputmode:'decimal'}), field('product_serial_id','IMEI / Serial المتاح','Available IMEI / Serial','select',{table:false,required:false,options:[]}), field('product_variant_id','الصنف الفرعي','Variant','hidden',{table:false,required:false}), field('paid_amount','المبلغ المدفوع','Paid amount','text',{table:false,inputmode:'decimal',required:false})]
    },
    purchases: {
        ar: 'فاتورة شراء', en: 'Purchase invoice', description: option('سجّل فاتورة المورد وحالة استلام البضاعة.', 'Record the supplier invoice and receiving status.'),
        fields: [field('number','رقم الفاتورة (اتركه فارغًا للتوليد)','Invoice number','text',{required:false}), field('supplier','اسم المورد','Supplier name'), field('date','تاريخ الشراء','Purchase date','date'), field('total','إجمالي الفاتورة','Invoice total','text',{ inputmode:'decimal' }), field('status','حالة الاستلام','Receiving status','select',{ options:[option('مستلمة','Received'),option('قيد الانتظار','Pending')] }), field('supplier_phone','هاتف المورد','Supplier phone','tel',{table:false,required:false}), field('product_sku','كود المنتج SKU','Product SKU','text',{table:false}), field('quantity','الكمية','Quantity','number',{table:false,min:'0.001',step:'0.001'}), field('unit_cost','تكلفة الوحدة','Unit cost','number',{table:false,min:'0',step:'0.000001'}), field('devices','الأجهزة: IMEI1,IMEI2,Serial,التكلفة لكل سطر','Devices: IMEI1,IMEI2,Serial,cost per line','textarea',{table:false,required:false,wide:true}), field('paid_amount','المدفوع','Paid amount','number',{table:false,min:'0',step:'0.01',required:false})]
    },
    inventory: {
        ar: 'حركة مخزون', en: 'Inventory movement', description: option('حدّد المخزن والمنتج والرصيد وحد إعادة الطلب.', 'Set the warehouse, product, stock and reorder level.'),
        fields: [field('warehouse','المخزن','Warehouse'), field('product','المنتج','Product'), field('stock','الرصيد الحالي','Current stock','number',{ min:'0',step:'0.001' }), field('unit_cost','تكلفة الوحدة للزيادة','Unit cost for stock increase','number',{ min:'0',step:'0.000001',required:false,table:false }), field('minimum','حد إعادة الطلب','Reorder level','number',{ min:'0' }), field('status','حالة المخزون','Stock status','select',{ options:[option('متوفر','Available'),option('منخفض','Low'),option('حرج','Critical')] })]
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
const notify = (message, options = {}) => document.dispatchEvent(new CustomEvent('admin:toast', { detail: { message, ...options } }));

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('crud-modal');
    const form = document.getElementById('crud-form');
    const fieldsRoot = document.getElementById('crud-fields');
    if (!modal || !form || !fieldsRoot) return;

    let module = document.querySelector('[data-crud-add]')?.dataset.module || document.querySelector('.admin-table')?.dataset.module;
    let editingRow = null;
    let receivingPurchase = null;
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
        actions.innerHTML = `<div class="row-actions">${module === 'products' ? '<a data-row-barcode title="عرض وطباعة الباركود">▥</a>' : ''}${module === 'sales' ? '<button type="button" data-row-print title="طباعة الفاتورة">▣</button>' : ''}${module === 'purchases' && !['received','cancelled'].includes(String(values.at(-1))) ? '<button type="button" data-row-receive title="استلام البضاعة">استلام</button>' : ''}${canEdit ? '<button type="button" data-row-edit title="تعديل">✎</button>' : ''}${canDelete ? `<button type="button" data-row-delete title="${deleteTitle}">×</button>` : ''}</div>`;
        const barcodeLink = actions.querySelector('[data-row-barcode]');
        if (barcodeLink && id) { barcodeLink.href = `/admin/products/${id}/barcode`; barcodeLink.target = '_blank'; }
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
        if (descriptor.type === 'hidden') wrapper.hidden = true;
        wrapper.className = `crud-field${descriptor.wide ? ' crud-field--wide' : ''}${descriptor.type === 'permissions' ? ' crud-permissions' : ''}`;
        const caption = document.createElement('span');
        caption.textContent = isArabic() ? descriptor.ar : descriptor.en;
        wrapper.append(caption);

        if (descriptor.type === 'select') {
            const select = document.createElement('select');
            select.name = descriptor.name;
            select.required = descriptor.required !== false;
            if (descriptor.required === false) select.append(new Option(isArabic() ? 'اختر عند الحاجة' : 'Select when needed', ''));
            descriptor.options.forEach(item => {
                const node = document.createElement('option');
                node.value = item.value ?? text(item);
                node.textContent = text(item);
                select.append(node);
            });
            const matching = [...select.options].find(item => item.value.toLowerCase() === value.toLowerCase());
            if (matching) select.value = matching.value;
            wrapper.append(select);
            return wrapper;
        }

        if (descriptor.type === 'textarea') {
            const input = document.createElement('textarea');
            input.name = descriptor.name;
            input.value = value;
            input.required = descriptor.required !== false;
            input.rows = 4;
            wrapper.append(input);
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
        ['inputmode','min','max','step','minlength','maxlength','pattern','accept'].forEach(attribute => { if (descriptor[attribute] !== undefined) input.setAttribute(attribute, descriptor[attribute]); });
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
        if (module === 'products' && row) form.elements.tracking_type.value = '';
        if (module === 'sales') {
            const skuInput = form.elements.product_sku;
            const serialSelect = form.elements.product_serial_id;
            const costPreview = document.createElement('output');
            costPreview.className = 'product-profit-preview';
            fieldsRoot.append(costPreview);
            const showEstimate = () => {
                const cost = serialSelect.selectedOptions[0]?.dataset.cost;
                const price = Number(form.elements.unit_price.value || 0);
                costPreview.textContent = cost === undefined ? '' : (isArabic() ? `تكلفة الجهاز: ${cost} ج.م — مجمل الربح المتوقع: ${(price - Number(cost)).toFixed(2)} ج.م` : `Device cost: ${cost} EGP — estimated gross profit: ${(price - Number(cost)).toFixed(2)} EGP`);
            };
            const loadDevices = async () => {
                serialSelect.replaceChildren(new Option(isArabic() ? 'اختر عند الحاجة' : 'Select when needed', ''));
                form.elements.product_variant_id.value = '';
                costPreview.textContent = '';
                if (!skuInput.value.trim()) return;
                const response = await fetch(`/admin/sales/available-devices?product_sku=${encodeURIComponent(skuInput.value.trim())}`, {headers:{Accept:'application/json'}});
                if (!response.ok) return;
                const payload = await response.json();
                for (const device of payload.devices || []) {
                    const option = new Option([device.imei_1,device.imei_2,device.serial_number].filter(Boolean).join(' / '), String(device.id));
                    if (device.purchase_cost !== undefined) option.dataset.cost = device.purchase_cost;
                    option.dataset.variant = device.product_variant_id ?? '';
                    serialSelect.append(option);
                }
                serialSelect.required = payload.tracking_type !== 'quantity';
            };
            skuInput.addEventListener('change', loadDevices);
            skuInput.addEventListener('blur', loadDevices);
            serialSelect.addEventListener('change', () => { form.elements.product_variant_id.value = serialSelect.selectedOptions[0]?.dataset.variant || ''; showEstimate(); });
            form.elements.unit_price.addEventListener('input', showEstimate);
        }
        if (module === 'products') {
            const profit = document.createElement('output');
            profit.className = 'product-profit-preview';
            fieldsRoot.append(profit);
            const updateProfit = () => {
                const purchase = Number(form.elements.purchase_cost?.value || 0);
                const sale = Number(form.elements.price?.value || 0);
                const quantity = Number(form.elements.quantity?.value || 0);
                const unit = sale - purchase;
                profit.textContent = isArabic() ? `ربح الوحدة: ${unit.toLocaleString()} ج.م — إجمالي الربح المتوقع: ${(unit * quantity).toLocaleString()} ج.م` : `Unit profit: ${unit.toLocaleString()} EGP — expected total: ${(unit * quantity).toLocaleString()} EGP`;
            };
            ['purchase_cost','price','quantity'].forEach(name => form.elements[name]?.addEventListener('input', updateProfit));
            updateProfit();
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => fieldsRoot.querySelector('input,select')?.focus(), 100);
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        editingRow = null;
        receivingPurchase = null;
    };

    const parseDeviceLines = (value, fallbackCost = '') => String(value || '').split(/\r?\n/).map(line => line.trim()).filter(Boolean).map(line => {
        const [imei_1, imei_2, serial_number, purchase_cost] = line.split(',').map(part => part.trim());
        return {imei_1:imei_1 || null, imei_2:imei_2 || null, serial_number:serial_number || null, purchase_cost:purchase_cost || fallbackCost || null};
    });

    const openPurchaseReceipt = row => {
        receivingPurchase = row;
        module = 'purchases';
        form.reset();
        fieldsRoot.replaceChildren(makeField(field('devices','الأجهزة: IMEI1,IMEI2,Serial,التكلفة لكل سطر','Devices: IMEI1,IMEI2,Serial,cost per line','textarea',{required:false,wide:true})));
        document.getElementById('crud-kicker').textContent = isArabic() ? 'المشتريات' : 'Purchases';
        document.getElementById('crud-title').textContent = isArabic() ? 'استلام البضاعة' : 'Receive purchase';
        document.getElementById('crud-description').textContent = isArabic() ? 'للأجهزة، اكتب سطرًا لكل جهاز. اتركه فارغًا للأصناف بالكمية.' : 'For devices, enter one line per device. Leave blank for quantity products.';
        document.getElementById('crud-submit').textContent = isArabic() ? 'تأكيد الاستلام' : 'Confirm receipt';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        fieldsRoot.querySelector('textarea')?.focus();
    };

    document.addEventListener('click', async event => {
        const addButton = event.target.closest('[data-crud-add]');
        const editButton = event.target.closest('[data-row-edit]');
        const deleteButton = event.target.closest('[data-row-delete]');
        const receiveButton = event.target.closest('[data-row-receive]');
        if (receiveButton) openPurchaseReceipt(receiveButton.closest('tr'));
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
        if (receivingPurchase) {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            try {
                const response = await fetch(`/admin/purchases/${receivingPurchase.dataset.recordId}/receive`, {
                    method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
                    body:JSON.stringify({devices:parseDeviceLines(form.elements.devices.value)})
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Could not receive purchase.');
                dataCells(receivingPurchase).at(-1).textContent = payload.purchase.status;
                receivingPurchase.querySelector('[data-row-receive]')?.remove();
                close();
                notify(payload.message);
            } catch (error) { notify(error.message); }
            finally { button.disabled = false; }
            return;
        }
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
                const payload = Object.fromEntries(schema.fields.map((field, index) => [field.name, values[index]]));
                if (module === 'purchases') {
                    payload.devices = parseDeviceLines(payload.devices, payload.unit_cost);
                }
                requestBody = JSON.stringify(payload);
            }
            button.disabled = true;
            try {
                const response = await fetch(`/admin/${module}${recordId ? `/${recordId}` : ''}`, {
                    method: requestMethod,
                    headers,
                    body: requestBody
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const validationMessage = Object.values(payload.errors || {}).flat()[0];
                    const statusMessage = response.status === 419
                        ? (isArabic() ? 'انتهت الجلسة. حدّث الصفحة ثم حاول مرة أخرى.' : 'Session expired. Refresh the page and try again.')
                        : response.status === 413
                            ? (isArabic() ? 'حجم الملف أكبر من الحد المسموح.' : 'The uploaded file is too large.')
                            : (isArabic() ? 'تعذر حفظ البيانات. حاول مرة أخرى.' : 'Could not save the data. Try again.');
                    throw new Error(validationMessage || payload.message || statusMessage);
                }
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
                const repairIsCompleted = module === 'repairs' && /ready_for_delivery|delivered|ready for pickup|تم التسليم|جاهز للاستلام/i.test(String(saved.cells.at(-1) || ''));
                const printable = module === 'sales' || repairIsCompleted;
                close();
                notify(payload.message, printable ? {
                    printable: true,
                    action: module === 'sales' ? (isArabic() ? 'فاتورة بيع' : 'Sales invoice') : (isArabic() ? 'إيصال صيانة' : 'Repair receipt'),
                    number: saved.cells[0],
                    amount: module === 'sales' ? saved.cells[3] : ''
                } : {});
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
