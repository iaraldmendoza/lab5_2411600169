(() => {
    'use strict';

    const form = document.getElementById('purchase-order-form');
    if (!form) return;

    const supplier = document.getElementById('supplier_id');
    const lines = document.getElementById('order-lines');
    const status = document.getElementById('catalog-status');
    const save = document.getElementById('save-order');
    const add = document.getElementById('add-line');
    const retry = document.getElementById('retry-catalog');
    const initial = JSON.parse(document.getElementById('initial-order-items').textContent);
    const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    let catalog = [];
    let sequence = 0;
    let request = null;

    function totals() {
        let subtotal = 0;
        for (const row of lines.children) {
            const total = Number(row.querySelector('.line-quantity').value) * Number(row.querySelector('.line-price').value);
            row.querySelector('.line-total').textContent = currency.format(total);
            subtotal += total;
        }
        const tax = Math.round(subtotal * Number(document.getElementById('tax_rate').value)) / 100;
        document.getElementById('po-subtotal').textContent = currency.format(subtotal);
        document.getElementById('po-tax').textContent = currency.format(tax);
        document.getElementById('po-total').textContent = currency.format(subtotal + tax);
        save.disabled = catalog.length === 0 || lines.children.length === 0;
    }

    function addLine(item = {}) {
        const index = sequence++;
        const row = document.createElement('tr');
        const productCell = document.createElement('td');
        const select = document.createElement('select');
        select.name = `items[${index}][product_id]`;
        select.required = true;
        select.setAttribute('aria-label', 'Product');
        select.append(new Option('Choose a product', ''));
        catalog.forEach(product => select.append(new Option(`${product.name} · ${product.sku}`, product.id)));
        select.value = item.product_id || '';
        const hint = document.createElement('small');
        hint.className = 'muted';
        productCell.append(select, hint);
        const quantityCell = document.createElement('td');
        const quantity = document.createElement('input');
        quantity.type = 'number';
        quantity.name = `items[${index}][quantity]`;
        quantity.className = 'line-quantity compact-input';
        quantity.required = true;
        quantity.max = '1000000';
        quantity.setAttribute('aria-label', 'Quantity');
        quantityCell.append(quantity);
        const priceCell = document.createElement('td');
        const price = document.createElement('input');
        price.type = 'number';
        price.name = `items[${index}][unit_price]`;
        price.className = 'line-price compact-input';
        price.min = '0.01'; price.max = '999999.99'; price.step = '0.01'; price.required = true;
        price.setAttribute('aria-label', 'Unit price');
        priceCell.append(price);
        const totalCell = document.createElement('td'); totalCell.className = 'line-total';
        const actionCell = document.createElement('td');
        const remove = document.createElement('button');
        remove.type = 'button'; remove.className = 'text-button'; remove.textContent = 'Remove line';
        remove.addEventListener('click', () => { row.remove(); totals(); });
        actionCell.append(remove);
        row.append(productCell, quantityCell, priceCell, totalCell, actionCell);
        lines.append(row);

        function applyProduct(preserve = false) {
            const product = catalog.find(product => String(product.id) === select.value);
            if (!product) { quantity.value = ''; price.value = ''; hint.textContent = ''; totals(); return; }
            const minimum = Math.ceil(product.minimum_order_quantity / product.package_size) * product.package_size;
            quantity.min = minimum; quantity.step = product.package_size;
            hint.textContent = `Minimum ${product.minimum_order_quantity} · Package ${product.package_size} · Lead ${product.lead_time_days} days`;
            quantity.value = preserve && item.quantity ? item.quantity : minimum;
            price.value = preserve && item.unit_price ? item.unit_price : product.unit_price;
            totals();
        }
        select.addEventListener('change', () => applyProduct(false));
        quantity.addEventListener('input', totals); price.addEventListener('input', totals);
        applyProduct(true);
    }

    async function loadCatalog(useInitial = false) {
        if (request) request.abort();
        request = new AbortController();
        catalog = []; lines.replaceChildren(); save.disabled = true; add.disabled = true; retry.hidden = true;
        if (!supplier.value) { status.textContent = 'Choose a supplier to load its catalog.'; totals(); return; }
        const selected = supplier.selectedOptions[0];
        document.getElementById('supplier-terms').textContent = `Lead time ${selected.dataset.lead} days · Minimum order ${currency.format(selected.dataset.minimum)}`;
        if (!useInitial) {
            const date = new Date(document.getElementById('order_date').value + 'T12:00:00');
            date.setDate(date.getDate() + Number(selected.dataset.lead));
            document.getElementById('expected_delivery_date').value = date.toISOString().slice(0, 10);
            document.getElementById('terms').value = selected.dataset.terms;
        }
        status.textContent = 'Loading supplier catalog…';
        try {
            const response = await fetch(`/api/suppliers/${supplier.value}/catalog`, { headers: { Accept: 'application/json' }, signal: request.signal });
            if (!response.ok) throw new Error('Catalog unavailable');
            catalog = (await response.json()).data;
            add.disabled = catalog.length === 0;
            status.textContent = catalog.length ? `${catalog.length} products available. Prices and package sizes come from the supplier agreement.` : 'No products in this supplier catalog. Add a supply agreement first.';
            if (useInitial && initial.length) initial.forEach(addLine); else if (catalog.length) addLine();
        } catch (error) {
            if (error.name === 'AbortError') return;
            status.textContent = 'The supplier catalog could not be loaded. Please retry before saving.';
            retry.hidden = false;
        }
        totals();
    }

    supplier.addEventListener('change', () => loadCatalog(false));
    add.addEventListener('click', () => addLine());
    retry.addEventListener('click', () => loadCatalog(true));
    document.getElementById('tax_rate').addEventListener('input', totals);
    form.addEventListener('submit', event => {
        const ids = [...lines.querySelectorAll('select')].map(select => select.value);
        if (ids.length !== new Set(ids).size) {
            event.preventDefault(); status.textContent = 'Each product may appear only once. Combine its quantities into one line.';
        }
    });
    loadCatalog(true);
})();
