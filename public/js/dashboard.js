(() => {
 let busy = false;
 const byId = id => document.getElementById(id);
 const money = value => new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(value);
 async function get(path) {
  const response = await fetch(path, {headers: {Accept: 'application/json'}, credentials: 'same-origin', cache: 'no-store'});
  if (!response.ok) throw new Error(response.status === 401 ? 'Please log in again.' : 'Update delayed. Retrying automatically.');
  return response.json();
 }
 function cell(row, text, url) {
  const td = document.createElement('td');
  if (url) {const a=document.createElement('a'); a.href=url; a.textContent=text; td.append(a);}
  else td.textContent=text;
  row.append(td);
 }
 async function refresh() {
  if (busy) return;
  busy=true; byId('refresh-inventory').disabled=true;
  try {
   const [stats, low, recent] = await Promise.all([get('/api/dashboard/stats'),get('/api/products/low-stock'),get('/api/transactions/recent')]);
   const values={'total-products':stats.data.total_products,'low-stock-count':stats.data.low_stock,'out-of-stock-count':stats.data.out_of_stock,'inventory-value':money(stats.data.inventory_value)};
   Object.entries(values).forEach(([id,value])=>{const el=byId(id); if(el.textContent!==String(value)){el.textContent=value;el.classList.remove('stat-updated');void el.offsetWidth;el.classList.add('stat-updated');}});
   byId('notification-count').textContent=stats.data.unread_notifications;
   const rows=document.createDocumentFragment();
   recent.data.forEach(item=>{const tr=document.createElement('tr');cell(tr,item.product,item.url);cell(tr,item.type);cell(tr,(item.delta>0?'+':'')+item.delta);cell(tr,item.balance_after);cell(tr,item.user);rows.append(tr);});
   if(!recent.data.length){const tr=document.createElement('tr');const td=document.createElement('td');td.colSpan=5;td.textContent='No movements recorded.';tr.append(td);rows.append(tr);}
   byId('recent-movements').replaceChildren(rows);
   const alerts=document.createDocumentFragment();
   low.data.forEach(item=>{const a=document.createElement('a');a.className='stock-alert';a.href=item.url;const title=document.createElement('strong');title.textContent=item.name;const span=document.createElement('span');span.textContent=item.quantity+' left · Reorder at '+item.reorder_level;a.append(title,span);alerts.append(a);});
   if(!low.data.length){const p=document.createElement('p');p.textContent='No products need replenishment.';alerts.append(p);}
   byId('low-stock-list').replaceChildren(alerts);
   byId('last-updated').textContent=new Date(stats.meta.updated_at).toLocaleTimeString();
   byId('poll-status').textContent='Connected';
   document.dispatchEvent(new CustomEvent('inventory:updated',{detail:stats.data}));
  } catch(error) {byId('poll-status').textContent=error.message;}
  finally {busy=false;byId('refresh-inventory').disabled=false;}
 }
 byId('refresh-inventory').addEventListener('click',refresh);
 refresh();window.setInterval(refresh,30000);
})();
