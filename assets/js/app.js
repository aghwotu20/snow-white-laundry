/**
 * Snow White Laundry — Interactive Frontend Helpers
 */
document.addEventListener('DOMContentLoaded', () => {
  // Mobile Public Menu Toggle
  const navToggle = document.getElementById('swlNavToggle');
  const navMenu = document.getElementById('swlNavMenu');
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
      navMenu.classList.toggle('open');
    });
  }

  // Mobile Dashboard Sidebar Drawer
  const dashToggle = document.getElementById('swlDashToggle');
  const dashSidebar = document.getElementById('swlDashSidebar');
  if (dashToggle && dashSidebar) {
    dashToggle.addEventListener('click', () => {
      dashSidebar.classList.toggle('open');
    });
  }

  // FAQ Accordions
  document.querySelectorAll('.faq-question').forEach((btn) => {
    btn.addEventListener('click', () => {
      const parent = btn.closest('.faq-item');
      if (parent) parent.classList.toggle('open');
    });
  });

  // Live Image Preview before Upload (Passport Photographs, Website Logo, Favicon)
  document.querySelectorAll('input[type="file"][data-preview-target]').forEach((input) => {
    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      const targetId = input.getAttribute('data-preview-target');
      const img = targetId ? document.getElementById(targetId) : null;
      if (!file || !img) return;
      const reader = new FileReader();
      reader.onload = (ev) => {
        img.src = ev.target.result;
        img.style.display = 'block';
        const placeholder = img.parentElement.querySelector('[data-preview-placeholder]');
        if (placeholder) placeholder.style.display = 'none';
      };
      reader.readAsDataURL(file);
    });
  });

  // Live Laundry Order Calculator (on Pricing & Visitor Booking Pages)
  const calcForm = document.getElementById('swlOrderBuilder');
  if (calcForm) {
    const qtyInputs = calcForm.querySelectorAll('.js-qty-input');
    const deliveryRadios = calcForm.querySelectorAll('input[name="delivery_option"]');
    const speedRadios = calcForm.querySelectorAll('input[name="service_speed"]');
    const pickupFee = parseFloat(calcForm.dataset.pickupFee || '1500');
    const expressPct = parseFloat(calcForm.dataset.expressPct || '40');

    const recalc = () => {
      let subtotal = 0;
      let count = 0;
      qtyInputs.forEach((inp) => {
        const qty = Math.max(0, parseInt(inp.value || '0', 10));
        const unit = parseFloat(inp.dataset.price || '0');
        subtotal += qty * unit;
        count += qty;
      });

      const delivOpt = (calcForm.querySelector('input[name="delivery_option"]:checked') || {}).value || 'pickup_delivery';
      const speedOpt = (calcForm.querySelector('input[name="service_speed"]:checked') || {}).value || 'standard';

      const delivFee = subtotal > 0 && delivOpt === 'pickup_delivery' ? pickupFee : 0;
      const expressFee = subtotal > 0 && speedOpt === 'express' ? Math.round((subtotal * expressPct) / 100) : 0;
      const total = subtotal + delivFee + expressFee;

      const fmt = (n) => '₦' + Number(n).toLocaleString('en-NG');
      const setEl = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
      };
      setEl('sumItems', count + (count === 1 ? ' item/bag' : ' items/bags'));
      setEl('sumSubtotal', fmt(subtotal));
      setEl('sumDelivery', fmt(delivFee));
      setEl('sumExpress', fmt(expressFee));
      setEl('sumTotal', fmt(total));
    };

    qtyInputs.forEach((i) => i.addEventListener('input', recalc));
    deliveryRadios.forEach((r) => r.addEventListener('change', recalc));
    speedRadios.forEach((r) => r.addEventListener('change', recalc));

    document.querySelectorAll('.js-qty-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        if (!target) return;
        const delta = parseInt(btn.dataset.delta || '0', 10);
        target.value = Math.max(0, parseInt(target.value || '0', 10) + delta);
        recalc();
      });
    });

    recalc();
  }

  // Copy to clipboard buttons
  document.querySelectorAll('[data-copy-value]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const text = btn.getAttribute('data-copy-value') || '';
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
      }
      const orig = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(() => { btn.textContent = orig; }, 1600);
    });
  });
});

// Toggle password / secret key input visibility
function swlToggleSecret(id, btn) {
  const el = document.getElementById(id);
  if (!el) return;
  if (el.type === 'password') {
    el.type = 'text';
    if (btn) btn.textContent = 'Hide';
  } else {
    el.type = 'password';
    if (btn) btn.textContent = 'Show';
  }
}
