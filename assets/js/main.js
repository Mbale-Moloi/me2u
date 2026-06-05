document.addEventListener('DOMContentLoaded', function () {

    // ── Form validation ──
    const registerForm = document.querySelector('form[action="register.php"]');

    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            const password = document.getElementById('password');
            const confirm  = document.getElementById('confirm');

            if (password && confirm) {
                if (password.value !== confirm.value) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    confirm.focus();
                }

                if (password.value.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters.');
                    password.focus();
                }
            }
        });
    }

    // ── Delivery option selection highlight ──
    const deliveryOptions = document.querySelectorAll('.delivery-option-card');

    deliveryOptions.forEach(function (card) {
        const radio = card.querySelector('input[type="radio"]');

        if (radio) {
            card.addEventListener('click', function () {
                deliveryOptions.forEach(function (c) {
                    c.style.borderColor = '';
                    c.style.background  = '';
                });
                card.style.borderColor = '#E85D24';
                card.style.background  = '#fff5f2';
                radio.checked = true;
            });
        }
    });

    // ── Dynamic order total on checkout ──
    const quantityInput = document.getElementById('quantity');
    const priceDisplay  = document.querySelector('.checkout-price');

    if (quantityInput && priceDisplay) {
        const basePrice = parseFloat(
            priceDisplay.textContent.replace('R ', '').replace(',', '')
        );

        quantityInput.addEventListener('input', function () {
            const qty   = parseInt(this.value) || 1;
            const total = (basePrice * qty).toFixed(2);
            priceDisplay.textContent = 'R ' + 
                parseFloat(total).toLocaleString('en-ZA', {
                    minimumFractionDigits: 2
                });
        });
    }

    // ── Auto dismiss alerts after 5 seconds ──
    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity    = '0';
            setTimeout(function () {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // ── Confirm delete actions ──
    const deleteForms = document.querySelectorAll('[data-confirm]');

    deleteForms.forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

});