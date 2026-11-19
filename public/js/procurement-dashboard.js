(() => {
    'use strict';
    const state = document.getElementById('procurement-poll-status');
    if (!state) return;
    const widgets = ['pending-orders', 'recent-orders', 'supplier-performance', 'reorder-alerts'];
    const money = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    let busy = false;

    function article(url, title, detail, extra = '') {
        const result = document.createElement('article');
        const link = document.createElement('a'); link.href = url; link.textContent = title;
        const paragraph = document.createElement('p'); paragraph.textContent = detail;
        result.append(link, paragraph);
        if (extra) { const small = document.createElement('small'); small.textContent = extra; result.append(small); }
        return result;
    }

    function update(name, data) {
        const root = document.querySelector(`[data-widget="${name}"]`);
        if (name === 'pending-orders') {
            root.querySelector('[data-widget-value]').textContent = data.count;
            root.querySelector('[data-widget-amount]').textContent = Number(data.value).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            root.querySelector('[data-widget-admin]').textContent = data.awaiting_admin;
            document.getElementById('open-orders').textContent = data.open_orders;
            document.getElementById('active-suppliers').textContent = data.active_suppliers;
            document.getElementById('product-count').textContent = data.product_count;
            return;
        }
        const list = root.querySelector('[data-widget-list]'); list.replaceChildren();
        let rows = [];
        if (name === 'recent-orders') rows = data.orders.map(row => article(`/purchase-orders/${row.id}`, row.reference, `${row.supplier} · ${row.status.replaceAll('_', ' ')}`, money.format(row.total)));
        if (name === 'supplier-performance') rows = data.suppliers.map(row => { const node = article(`/suppliers/${row.supplier_id}`, row.supplier_name, `${row.on_time_percentage}% delivered on time · ${row.completed_orders} completed orders`); const track = document.createElement('div'); track.className = 'bar-track'; const bar = document.createElement('div'); bar.className = 'bar'; bar.style.width = `${Math.max(0, Math.min(100, Number(row.on_time_percentage)))}%`; track.append(bar); node.append(track); return node; });
        if (name === 'reorder-alerts') {
            root.querySelector('[data-widget-value]').textContent = data.count;
            rows = data.products.map(row => article(`/products/${row.id}`, row.name, `Position ${row.position} · Reorder point ${row.reorder_point} · Suggested ${row.suggested_quantity}`, row.supplier));
        }
        if (rows.length) list.append(...rows); else { const p = document.createElement('p'); p.textContent = name === 'reorder-alerts' ? 'Current stock and outstanding orders cover the reorder points.' : 'No records available yet.'; list.append(p); }
    }

    async function refresh() {
        if (busy) return;
        busy = true;
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 12000);
        try {
            const results = await Promise.all(widgets.map(async name => {
                const response = await fetch(`/api/dashboard/${name}`, { headers: { Accept: 'application/json' }, signal: controller.signal });
                if (!response.ok) throw new Error('Dashboard unavailable');
                return [name, (await response.json()).data];
            }));
            results.forEach(([name, data]) => update(name, data));
            state.textContent = `Connected · ${new Date().toLocaleTimeString()}`;
        } catch (error) {
            state.textContent = 'Connection lost · retrying';
        } finally { clearTimeout(timer); busy = false; }
    }
    refresh();
    setInterval(refresh, 30000);
})();
