(() => {
 const product=document.getElementById('product_id'), type=document.getElementById('type'), quantity=document.getElementById('quantity'), physical=document.getElementById('physical_count'), output=document.getElementById('current-stock'), feedback=document.getElementById('stock-feedback'), save=document.getElementById('save-movement');
 let stock=null;
 function limits() {
  if(stock===null)return;
  if(quantity){if(type.value==='out'){quantity.max=stock;feedback.textContent='You may issue up to '+stock+' units.';}else{quantity.removeAttribute('max');feedback.textContent='Received units will be added to current stock.';}}
  if(physical&&physical.value!==''){const delta=Number(physical.value)-stock;feedback.textContent='Adjustment to record: '+(delta>0?'+':'')+delta+' units.';}
 }
 async function load() {
  stock=null;save.disabled=true;output.textContent='Loading…';
  const id=product.value;
  if(!id){output.textContent='Choose a product';feedback.textContent='Select a product to continue.';return;}
  try{const response=await fetch('/api/products/'+encodeURIComponent(id),{headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)throw Error();const result=await response.json();if(product.value!==id)return;stock=result.data.quantity;output.textContent=stock+' units';save.disabled=false;limits();}
  catch{output.textContent='Unavailable';feedback.textContent='Could not load stock. Select the product again to retry.';}
 }
 product.addEventListener('change',load);type?.addEventListener('change',limits);physical?.addEventListener('input',limits);load();
})();
