const normalizeSearch = value => value.toLocaleLowerCase().replace(/[\u064B-\u065F\u0670]/g, '').replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').trim();

document.addEventListener('DOMContentLoaded', () => {
    if (!document.body.classList.contains('admin-body')) return;

    const searchBoxes = [...document.querySelectorAll('.system-search')];
    const navItems = () => [...document.querySelectorAll('.admin-sidebar nav a')].map(link => ({
        type: 'link',
        title: link.querySelector('span:last-child')?.textContent.trim() || link.textContent.trim(),
        subtitle: document.documentElement.lang === 'ar' ? 'قسم في النظام' : 'System section',
        href: link.href,
        source: link
    }));

    const tableItems = () => [...document.querySelectorAll('.admin-table tbody tr')].map(row => {
        const cells = [...row.querySelectorAll('td')].slice(0, 4).map(cell => cell.textContent.trim()).filter(Boolean);
        return { type: 'row', title: cells[0], subtitle: cells.slice(0, 4).join(' — '), source: row };
    });

    const closeAll = except => searchBoxes.forEach(box => { if (box !== except) box.classList.remove('search-open'); });

    searchBoxes.forEach(box => {
        const input = box.querySelector('input[type="search"]');
        const trigger = box.querySelector('.search-orbit');
        const results = box.querySelector('.system-search-results');
        let selectedIndex = -1;

        const chooseResult = item => {
            if (item.type === 'link') window.location.href = item.href;
            if (item.type === 'row') {
                item.source.scrollIntoView({ behavior: 'smooth', block: 'center' });
                item.source.classList.add('search-hit');
                window.setTimeout(() => item.source.classList.remove('search-hit'), 2200);
                box.classList.remove('search-open');
            }
        };

        const render = () => {
            const query = normalizeSearch(input.value);
            const allItems = [...navItems(), ...tableItems()];
            const matches = allItems.filter(item => !query || normalizeSearch(`${item.title} ${item.subtitle}`).includes(query)).slice(0, 7);
            results.replaceChildren();
            selectedIndex = -1;

            if (!matches.length) {
                const empty = document.createElement('span');
                empty.className = 'search-empty';
                empty.textContent = document.documentElement.lang === 'ar' ? 'لا توجد نتائج مطابقة' : 'No matching results';
                results.append(empty);
            } else {
                matches.forEach((item, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.resultIndex = String(index);
                    button.innerHTML = `<i></i><span><strong></strong><small></small></span><b>${document.documentElement.dir === 'rtl' ? '←' : '→'}</b>`;
                    button.querySelector('strong').textContent = item.title;
                    button.querySelector('small').textContent = item.subtitle;
                    button.addEventListener('click', () => chooseResult(item));
                    results.append(button);
                });
            }

            results._items = matches;
            closeAll(box);
            box.classList.add('search-open');
        };

        const focusSearch = () => { input.focus(); render(); };
        trigger?.addEventListener('click', focusSearch);
        input?.addEventListener('focus', render);
        input?.addEventListener('input', render);
        input?.addEventListener('keydown', event => {
            const buttons = [...results.querySelectorAll('button')];
            if (event.key === 'Escape') { box.classList.remove('search-open'); input.blur(); return; }
            if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return;
            event.preventDefault();
            if (event.key === 'Enter' && selectedIndex >= 0) return buttons[selectedIndex]?.click();
            if (event.key === 'Enter' && results._items?.length) return chooseResult(results._items[0]);
            selectedIndex = event.key === 'ArrowDown'
                ? Math.min(selectedIndex + 1, buttons.length - 1)
                : Math.max(selectedIndex - 1, 0);
            buttons.forEach((button, index) => button.classList.toggle('selected', index === selectedIndex));
        });
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('.system-search')) closeAll();
    });
});
