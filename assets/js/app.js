/* Zoghdan | زغدان — lightweight storefront interactions. */
document.querySelectorAll('[data-modal-open]').forEach((button) => button.addEventListener('click', () => {
  const dialog = document.getElementById(button.dataset.modalOpen);
  if (!dialog) return;
  if (button.dataset.modalOpen === 'product-modal') {
    const form = dialog.querySelector('form');
    form?.reset();
    if (form?.elements.id) form.elements.id.value = '';
    const title = dialog.querySelector('.modal-head h2');
    if (title) title.textContent = 'إضافة منتج';
  }
  dialog.showModal();
}));
document.querySelectorAll('[data-product-edit]').forEach((button) => button.addEventListener('click', () => {
  const dialog = document.getElementById('product-modal');
  const form = dialog?.querySelector('form');
  if (!dialog || !form) return;
  try {
    const product = JSON.parse(button.dataset.productEdit || '{}');
    for (const [key, value] of Object.entries(product)) {
      if (form.elements[key]) form.elements[key].value = value ?? '';
    }
    const title = dialog.querySelector('.modal-head h2');
    if (title) title.textContent = 'تعديل المنتج';
    dialog.showModal();
  } catch (error) {
    console.error('Could not load product data for editing.', error);
  }
}));
document.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); }));
const checkoutSummary = document.querySelector('.checkout-summary');
const deliveryArea = document.querySelector('.delivery-area');
if (checkoutSummary && deliveryArea) {
  const deliveryValue = checkoutSummary.querySelector('.delivery-value');
  const totalValue = checkoutSummary.querySelector('.checkout-total');
  const formatMoney = (amount) => `${new Intl.NumberFormat('ar', { maximumFractionDigits: 2 }).format(amount)} ${checkoutSummary.dataset.currency || ''}`;
  const updateCheckoutTotal = () => {
    const delivery = Number(deliveryArea.value === 'outside_city' ? checkoutSummary.dataset.deliveryOutside : checkoutSummary.dataset.deliveryInside) || 0;
    const subtotal = Number(checkoutSummary.dataset.subtotal) || 0;
    if (deliveryValue) deliveryValue.textContent = formatMoney(delivery);
    if (totalValue) totalValue.textContent = formatMoney(subtotal + delivery);
  };
  deliveryArea.addEventListener('change', updateCheckoutTotal);
}
