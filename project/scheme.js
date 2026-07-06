/**
 * Sethu Jewellery - Client side Gold Schemes Logic
 * Handles interactive rules modals, dynamic registration forms, and validations.
 */

// Rules Modals selectors
const modalDailyRules = document.getElementById('modalDailyRules');
const modalWeeklyMonthlyRules = document.getElementById('modalWeeklyMonthlyRules');
const weeklyMonthlyRulesTitle = document.getElementById('weeklyMonthlyRulesTitle');

// Registration Modal selectors
const modalRegisterForm = document.getElementById('modalRegisterForm');
const regScheme = document.getElementById('regScheme');
const amountContainer = document.getElementById('amountContainer');
const schemeRegisterForm = document.getElementById('schemeRegisterForm');

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fetch live jewellery rates for top bar ticker
    fetch('api/site-data.php')
        .then(res => res.json())
        .then(data => {
            if (data.rates && data.settings) {
                const ratesLabel = document.getElementById('topBarRatesLabel');
                if (ratesLabel) {
                    const g22 = data.rates.gold22 ? `₹${data.rates.gold22.toLocaleString('en-IN')}/g` : '';
                    const g24 = data.rates.gold24 ? `₹${data.rates.gold24.toLocaleString('en-IN')}/g` : '';
                    const silver = data.rates.silver ? `₹${data.rates.silver.toLocaleString('en-IN')}/g` : '';
                    ratesLabel.innerHTML = `Today's Rates (Karaikudi): <i class="fa-solid fa-gem" style="color: var(--gold-light); margin-left: 10px;"></i> Gold 22K: <span style="color:white; font-weight:700;">${g22}</span> | Gold 24K: <span style="color:white; font-weight:700;">${g24}</span> | Silver: <span style="color:white; font-weight:700;">${silver}</span>`;
                }
            }
        })
        .catch(err => console.error('Failed to load rates in schemes:', err));

    // 2. Add event listener to dynamically switch saving amount inputs
    if (regScheme) {
        regScheme.addEventListener('change', (e) => {
            updateAmountField(e.target.value);
        });
        // Initial setup
        updateAmountField(regScheme.value);
    }

    // 3. Handle Form Submission
    if (schemeRegisterForm) {
        schemeRegisterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            submitRegistration();
        });
    }
});

// Rules Modal open/close actions
function openRulesModal(type) {
    if (type === 'daily') {
        modalDailyRules.classList.add('active');
        document.body.style.overflow = 'hidden';
    } else if (type === 'weekly' || type === 'monthly') {
        weeklyMonthlyRulesTitle.textContent = type === 'weekly' ? 'Weekly Gold Scheme Rules' : 'Monthly Gold Scheme Rules';
        modalWeeklyMonthlyRules.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeRulesModal(type) {
    if (type === 'daily') {
        modalDailyRules.classList.remove('active');
        document.body.style.overflow = '';
    } else {
        modalWeeklyMonthlyRules.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modals when clicking on background overlays
window.addEventListener('click', (e) => {
    if (e.target === modalDailyRules) closeRulesModal('daily');
    if (e.target === modalWeeklyMonthlyRules) closeRulesModal('weekly');
    if (e.target === modalRegisterForm) closeRegisterModal();
});

// Registration Modal actions
function openRegisterModal(schemeName) {
    if (regScheme) {
        regScheme.value = schemeName;
        updateAmountField(schemeName);
    }
    modalRegisterForm.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeRegisterModal() {
    modalRegisterForm.classList.remove('active');
    document.body.style.overflow = '';
}

// Helper to swap input layout based on scheme chosen
function updateAmountField(schemeName) {
    if (schemeName.toLowerCase().indexOf('daily') !== -1) {
        // Daily: Render manual amount field with min 100 validation
        amountContainer.innerHTML = `
            <label for="regAmount">Daily Saving Installment Amount (₹)</label>
            <input type="number" id="regAmount" name="amount" min="100" value="100" placeholder="Minimum ₹100" required>
            <small style="color: var(--text-muted); font-size:11px; margin-top: 4px; display:block;">Enter ₹100 or more (e.g. ₹500, ₹1000).</small>
        `;
    } else {
        // Weekly or Monthly: Render select option dropdown
        amountContainer.innerHTML = `
            <label for="regAmount">Fixed Saving Installment Amount</label>
            <select id="regAmount" name="amount" required>
                <option value="500">₹500</option>
                <option value="1000" selected>₹1,000</option>
                <option value="1500">₹1,500</option>
                <option value="2000">₹2,000</option>
                <option value="2500">₹2,500</option>
                <option value="3000">₹3,000</option>
                <option value="5000">₹5,000</option>
                <option value="7000">₹7,000</option>
                <option value="10000">₹10,000</option>
            </select>
            <small style="color: var(--text-muted); font-size:11px; margin-top: 4px; display:block;">Select a fixed installment amount.</small>
        `;
    }
}

// Client validation and AJAX form submit
function submitRegistration() {
    const name = document.getElementById('regName').value.trim();
    const phone = document.getElementById('regPhone').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const city = document.getElementById('regCity').value.trim();
    const pincode = document.getElementById('regPincode').value.trim();
    const address = document.getElementById('regAddress').value.trim();
    const terms = document.getElementById('regTerms').checked;
    const amountVal = document.getElementById('regAmount').value;

    // Client-side validations
    if (name === '' || phone === '' || email === '' || city === '' || pincode === '' || address === '') {
        alert('All mandatory fields must be completed.');
        return;
    }

    if (!terms) {
        alert('Terms & Conditions must be accepted before joining.');
        return;
    }

    // Phone checks
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    if (cleanPhone === '' || cleanPhone.length < 10 || cleanPhone.length > 15) {
        alert('Phone number must contain a valid 10-15 digit mobile number.');
        return;
    }

    // Email checks
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return;
    }

    // Setup payload
    const formData = new FormData(schemeRegisterForm);

    // Call API
    fetch('api/join-scheme.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            schemeRegisterForm.reset();
            closeRegisterModal();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('AJAX Error:', err);
        alert('An unexpected network error occurred. Please try again.');
    });
}
