(() => {
  const layout = document.querySelector('.pos-layout');
  if (!layout) return;
  const cards = [...document.querySelectorAll('.product-card')];
  const grid = document.getElementById('product-grid');
  const search = document.getElementById('product-search');
  const empty = document.getElementById('catalog-empty');
  const orderItems = document.getElementById('order-items');
  const amountInput = document.getElementById('amount-received');
  const checkoutButton = document.getElementById('checkout');
  const toast = document.getElementById('pos-toast');
  const cart = new Map();
  const csrf = layout.dataset.csrf;
  const money = value => `₱${Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  let toastTimer;
  let busy = false;

  document.getElementById('sale-date').textContent = new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
  function notify(message) {
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3200);
  }
  function total() {
    let cents = 0;
    cart.forEach(item => { cents += Math.round(item.price * 100) * item.quantity; });
    return cents / 100;
  }
  function render() {
    const items = [...cart.values()];
    const amount = total();
    document.getElementById('item-count').textContent = items.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('subtotal').textContent = money(amount);
    document.getElementById('grand-total').textContent = money(amount);
    document.getElementById('clear-order').disabled = !items.length || busy;
    amountInput.disabled = !items.length || busy;
    const received = Math.round((Number(amountInput.value) || 0) * 100) / 100;
    const canPay = items.length > 0 && received >= amount && !busy;
    document.getElementById('change-due').textContent = money(Math.max(0, received - amount));
    const hint = document.getElementById('payment-hint');
    hint.textContent = !items.length ? 'Enter the amount received to calculate change.' : received < amount ? `₱${(amount - received).toFixed(2)} more needed.` : 'Payment covers the total.';
    checkoutButton.disabled = !canPay;
    checkoutButton.innerHTML = busy ? '<span class="button-spinner"></span> Processing…' : 'Complete sale <span aria-hidden="true">→</span>';
    if (!items.length) {
      orderItems.innerHTML = '<div class="order-empty"><span class="empty-basket" aria-hidden="true">＋</span><strong>Your order is empty</strong><p>Choose products to add them to this sale.</p></div>';
      return;
    }
    orderItems.replaceChildren(...items.map(item => {
      const row = document.createElement('article');
      row.className = 'order-item';
      const info = document.createElement('div'); info.className = 'order-item-info';
      const name = document.createElement('strong'); name.textContent = item.name;
      const unit = document.createElement('span'); unit.textContent = `${money(item.price)} each · ${item.stock} in stock`;
      info.append(name, unit);
      const subtotal = document.createElement('strong'); subtotal.className = 'line-subtotal'; subtotal.textContent = money(item.price * item.quantity);
      const controls = document.createElement('div'); controls.className = 'item-controls';
      const minus = document.createElement('button'); minus.type = 'button'; minus.textContent = '−'; minus.setAttribute('aria-label', `Decrease ${item.name}`); minus.disabled = busy; minus.addEventListener('click', () => setQuantity(item.id, item.quantity - 1));
      const quantity = document.createElement('input'); quantity.type = 'number'; quantity.min = '1'; quantity.max = String(item.stock); quantity.step = '1'; quantity.value = String(item.quantity); quantity.setAttribute('aria-label', `Quantity of ${item.name}`); quantity.disabled = busy;
      quantity.addEventListener('change', () => { const val = Number(quantity.value); setQuantity(item.id, Number.isInteger(val) ? val : item.quantity); });
      const plus = document.createElement('button'); plus.type = 'button'; plus.textContent = '+'; plus.setAttribute('aria-label', `Increase ${item.name}`); plus.disabled = busy || item.quantity >= item.stock; plus.addEventListener('click', () => setQuantity(item.id, item.quantity + 1));
      const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'remove-item'; remove.textContent = 'Remove'; remove.addEventListener('click', () => { cart.delete(item.id); render(); }); remove.disabled = busy;
      controls.append(minus, quantity, plus, remove);
      row.append(info, subtotal, controls);
      return row;
    }));
  }
  function setQuantity(id, quantity) {
    const item = cart.get(id);
    if (!item) return;
    if (quantity < 1) cart.delete(id);
    else if (quantity > item.stock) { item.quantity = item.stock; notify(`Only ${item.stock} ${item.name} in stock.`); }
    else item.quantity = quantity;
    render();
  }
  function filterProducts() {
    const query = search.value.trim().toLowerCase();
    let visible = 0;
    cards.forEach(card => {
      const matches = `${card.dataset.name} ${card.dataset.category} ${card.dataset.barcode}`.toLowerCase().includes(query);
      card.hidden = !matches;
      if (matches) visible++;
    });
    document.getElementById('catalog-count').textContent = visible;
    empty.hidden = visible !== 0;
  }
  search.addEventListener('input', filterProducts);
  cards.forEach(card => card.addEventListener('click', () => {
    const id = Number(card.dataset.id);
    const stock = Number(card.dataset.stock);
    const item = cart.get(id);
    if (item && item.quantity >= stock) { notify(`Only ${stock} ${item.name} in stock.`); return; }
    cart.set(id, item ? { ...item, quantity: item.quantity + 1 } : { id, name: card.dataset.name, price: Number(card.dataset.price), stock, quantity: 1 });
    render();
  }));
  amountInput.addEventListener('input', render);
  document.getElementById('clear-order').addEventListener('click', () => { cart.clear(); amountInput.value = ''; render(); });

  checkoutButton.addEventListener('click', async () => {
    if (busy || checkoutButton.disabled) return;
    busy = true; render();
    try {
      const response = await fetch('checkout.php', {
        method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ items: [...cart.values()].map(({ id, quantity }) => ({ id, quantity })), amount_received: Number(amountInput.value) })
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.error || 'Sale could not be completed.');
      const sold = new Map(result.items.map(item => [Number(item.id), item.quantity]));
      cards.forEach(card => {
        const id = Number(card.dataset.id);
        if (!sold.has(id)) return;
        const remaining = Number(card.dataset.stock) - sold.get(id);
        card.dataset.stock = String(remaining);
        const stockLabel = card.querySelector('.product-card-bottom span');
        stockLabel.textContent = remaining < 1 ? 'Out of stock' : `${remaining} in stock`;
        stockLabel.classList.toggle('out-stock', remaining < 1);
        stockLabel.classList.toggle('low-stock', remaining > 0 && remaining <= 2);
        card.disabled = remaining < 1;
      });
      cart.clear(); amountInput.value = ''; render();
      notify(`Sale #${result.sale_id} complete · Change ${money(result.change)}`);
    } catch (error) {
      notify(error.message || 'Checkout failed. Please try again.');
    } finally { busy = false; render(); }
  });
  render();
})();
