// SETHU THANGA NAGAI MAALIGAI - INTERACTIVE FRONTEND ENGINE

// 1. LIVE RATES DATA & FLUCTUATION ENGINE
let baseRates = {
    gold22: 13195,
    gold24: 14395,
    gold18: 11342,
    silver: 215.49,
    platinum: 5050
};

let currentRates = { ...baseRates };
const rateDirections = {
    gold22: 'up',
    gold24: 'up',
    gold18: 'up',
    silver: 'down',
    platinum: 'up'
};

let fixRates = {
    gold22: false,
    gold24: false,
    gold18: false,
    silver: false,
    platinum: false
};

// Update rates slightly to simulate live fluctuations
function fluctuateRates() {
    Object.keys(currentRates).forEach(metal => {
        if (fixRates[metal]) {
            currentRates[metal] = baseRates[metal];
            return;
        }

        const base = baseRates[metal];
        const fluctuationPercent = metal === 'silver' ? 0.005 : 0.002; // silver is slightly more volatile
        const maxChange = base * fluctuationPercent;
        const change = (Math.random() * maxChange * 2) - maxChange; // value between -maxChange and +maxChange

        const previousValue = currentRates[metal];
        currentRates[metal] = Math.max(base * 0.9, parseFloat((currentRates[metal] + change).toFixed(2)));

        rateDirections[metal] = currentRates[metal] >= previousValue ? 'up' : 'down';
    });

    updateDOMRates();
    updateCalculators();
    renderProducts(); // Re-render to update dynamic pricing in gallery!
}

// Format currency in Indian Style (Lakhs, Crores)
function formatINR(number) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(number);
}

// Update Rates displayed in Header Ticker and Live Rate Board
function updateDOMRates() {
    // Header Ticker
    const tickerG22 = document.getElementById('tickerGold22');
    const tickerG24 = document.getElementById('tickerGold24');
    const tickerG18 = document.getElementById('tickerGold18');
    const tickerSil = document.getElementById('tickerSilver');
    const tickerPlat = document.getElementById('tickerPlatinum');

    if (tickerG22) tickerG22.textContent = `₹${currentRates.gold22.toFixed(0)}/g`;
    if (tickerG24) tickerG24.textContent = `₹${currentRates.gold24.toFixed(0)}/g`;
    if (tickerG18) tickerG18.textContent = `₹${currentRates.gold18.toFixed(0)}/g`;
    if (tickerSil) tickerSil.textContent = `₹${currentRates.silver.toFixed(2)}/g`;
    if (tickerPlat) tickerPlat.textContent = `₹${currentRates.platinum.toFixed(0)}/g`;

    // Board Card Rates
    const boardG22 = document.getElementById('boardGold22');
    const boardG24 = document.getElementById('boardGold24');
    const boardG18 = document.getElementById('boardGold18');
    const boardSil = document.getElementById('boardSilver');

    if (boardG22) boardG22.textContent = `₹${currentRates.gold22.toFixed(0)}`;
    if (boardG24) boardG24.textContent = `₹${currentRates.gold24.toFixed(0)}`;
    if (boardG18) boardG18.textContent = `₹${currentRates.gold18.toFixed(0)}`;
    if (boardSil) boardSil.textContent = `₹${currentRates.silver.toFixed(2)}`;

    // Updates changes chevrons
    updateChevronIndicator('changeGold22', currentRates.gold22, baseRates.gold22);
    updateChevronIndicator('changeGold24', currentRates.gold24, baseRates.gold24);
    updateChevronIndicator('changeGold18', currentRates.gold18, baseRates.gold18);
    updateChevronIndicator('changeSilver', currentRates.silver, baseRates.silver);

    // Update Ticker Time
    const tickerTime = document.getElementById('tickerTime');
    if (tickerTime) {
        const now = new Date();
        tickerTime.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}

function updateChevronIndicator(elementId, current, base) {
    const el = document.getElementById(elementId);
    if (!el) return;

    const diff = current - base;
    if (Math.abs(diff) < 0.01) {
        el.className = 'rate-change up';
        el.innerHTML = '<i class="fa-solid fa-circle-check"></i> Current fixed rate';
        return;
    }

    const pct = ((diff / base) * 100).toFixed(2);
    const sign = diff >= 0 ? '+' : '';

    if (diff >= 0) {
        el.className = 'rate-change up';
        el.innerHTML = `<i class="fa-solid fa-circle-chevron-up"></i> ${sign}₹${Math.abs(diff).toFixed(diff % 1 === 0 ? 0 : 2)} (${sign}${pct}%)`;
    } else {
        el.className = 'rate-change down';
        el.innerHTML = `<i class="fa-solid fa-circle-chevron-down"></i> ${sign}₹${diff.toFixed(diff % 1 === 0 ? 0 : 2)} (${pct}%)`;
    }
}

// 2. PRODUCT DATABASE (DYNAMC PRICE CALCULATION)
let productsData = [
    {
        id: 'gold-neck-01',
        name: 'Maharani Lakshmi Kasu Haram',
        category: 'gold',
        subcategory: 'Necklace',
        baseWeight: 32.50, // in grams
        purity: '22K (916 Hallmark)',
        metalType: 'gold22',
        image: 'assets/gold_necklace.png',
        style: 'Chettinad Heritage Antique',
        wastagePercent: 12, // making & wastage in %
        description: 'An iconic heritage Kasu Mala featuring meticulously carved Lakshmi coins, framed by premium ruby accents. A signature bridal jewel.'
    },
    {
        id: 'gold-bangle-01',
        name: 'Royal Astha Filigree Kada',
        category: 'gold',
        subcategory: 'Bangle',
        baseWeight: 24.00,
        purity: '22K (916 Hallmark)',
        metalType: 'gold22',
        image: 'assets/gold_necklace.png',
        style: 'Traditional Filigree',
        wastagePercent: 10,
        description: 'Stunning openable thick gold kada with intricate wirework, detailed floral motifs, and a polished gold clasp.'
    },
    {
        id: 'gold-ring-01',
        name: 'Vaira Mayil Peacock Ring',
        category: 'gold',
        subcategory: 'Ring',
        baseWeight: 6.50,
        purity: '22K (916 Hallmark)',
        metalType: 'gold22',
        image: 'assets/gold_necklace.png',
        style: 'Antique Temple',
        wastagePercent: 8,
        description: 'An elegant statement ring showcasing a majestic peacock with emerald feathers and enamel highlights.'
    },
    {
        id: 'gold-chain-01',
        name: 'South Indian Mangai Mugappu Chain',
        category: 'gold',
        subcategory: 'Chain',
        baseWeight: 16.20,
        purity: '22K (916 Hallmark)',
        metalType: 'gold22',
        image: 'assets/gold_necklace.png',
        style: 'Classic Mugappu',
        wastagePercent: 9,
        description: 'A traditional four-line designer gold chain featuring a side mugappu clasp detailed with rubies and CZ stones.'
    },
    {
        id: 'diamond-neck-01',
        name: 'Brilliant Princess Choker Set',
        category: 'diamond',
        subcategory: 'Necklace',
        baseWeight: 45.80, // metal weight
        purity: '18K Gold with VVS-EF Diamonds',
        metalType: 'gold18',
        image: 'assets/gold_necklace.png', // Fallback or placeholder styled beautifully
        style: 'Modern Luxury',
        wastagePercent: 18,
        description: 'A luxurious choker necklace containing premium round brilliant diamonds and hanging pear-shaped Colombian emerald drops.'
    },
    {
        id: 'diamond-ring-01',
        name: 'Eternity Solitaire Engagement Ring',
        category: 'diamond',
        subcategory: 'Ring',
        baseWeight: 4.20,
        purity: '18K White Gold with 1ct Solitaire',
        metalType: 'gold18',
        image: 'assets/gold_necklace.png',
        style: 'Solitaire Classic',
        wastagePercent: 15,
        description: 'A timeless solitaire ring featuring a 1-carat certified diamond in an elegant six-prong crown setting.'
    },
    {
        id: 'silver-art-01',
        name: '925 Sterling Nakshi Pooja Set',
        category: 'silver-articles',
        subcategory: 'Pooja Articles',
        baseWeight: 250.00,
        purity: '92.5% Sterling Silver',
        metalType: 'silver',
        image: 'assets/gold_necklace.png',
        style: 'Handcrafted Nakshi',
        wastagePercent: 10,
        description: 'Includes a heavy detailed Silver Plate, a Nakshi Kamatchi Vilakku lamp, incense stand, and sweet bowls. Perfect for housewarming.'
    },
    {
        id: 'silver-anklet-01',
        name: 'Ganga Yamuna Bridal Ghungroo Anklets',
        category: 'silver-jewellery',
        subcategory: 'Anklets',
        baseWeight: 65.00,
        purity: 'Sterling Silver',
        metalType: 'silver',
        image: 'assets/gold_necklace.png',
        style: 'Bridal Heavy',
        wastagePercent: 8,
        description: 'Traditionally thick silver leg chains featuring ringing clusters of tiny hand-soldered silver ghungroos.'
    },
    {
        id: 'coin-gold-08',
        name: '999 Certified Sovereign Gold Coin',
        category: 'coins',
        subcategory: 'Coins',
        baseWeight: 8.00,
        purity: '24K (99.9% Pure Gold)',
        metalType: 'gold24',
        image: 'assets/gold_necklace.png',
        style: 'Hallmarked Coin',
        wastagePercent: 2, // very low wastage for investment coins
        description: 'A tamper-proof carded 8 gram pure gold coin embossed with the emblem of Lakshmi. Ideal for savings and investment.'
    },
    {
        id: 'coin-gold-04',
        name: '999 Pure Lakshmi Gold Coin 4g',
        category: 'coins',
        subcategory: 'Coins',
        baseWeight: 4.00,
        purity: '24K (99.9% Pure Gold)',
        metalType: 'gold24',
        image: 'assets/gold_necklace.png',
        style: 'Hallmarked Coin',
        wastagePercent: 2.5,
        description: 'A hallmarked 24 Karat pure gold coin, certified 999 purity. Embossed with Lakshmi blessing motifs.'
    }
];

async function loadSiteData() {
    try {
        const response = await fetch('api/site-data.php', { cache: 'no-store' });
        if (!response.ok) return;

        const data = await response.json();

        if (data.rates) {
            baseRates = {
                gold22: Number(data.rates.gold22) || baseRates.gold22,
                gold24: Number(data.rates.gold24) || baseRates.gold24,
                gold18: Number(data.rates.gold18) || baseRates.gold18,
                silver: Number(data.rates.silver) || baseRates.silver,
                platinum: Number(data.rates.platinum) || baseRates.platinum
            };
            currentRates = { ...baseRates };
            fixRates = {
                gold22: !!data.rates.fixGold22,
                gold24: !!data.rates.fixGold24,
                gold18: !!data.rates.fixGold18,
                silver: !!data.rates.fixSilver,
                platinum: !!data.rates.fixPlatinum
            };
        }

        if (Array.isArray(data.products) && data.products.length > 0) {
            productsData = data.products.map(product => ({
                ...product,
                baseWeight: Number(product.baseWeight) || 0,
                wastagePercent: Number(product.wastagePercent) || 0
            }));
        }

        if (data.settings) {
            applySiteSettings(data.settings);
        }
    } catch (error) {
        console.warn('Using local fallback data because PHP site data is unavailable.', error);
    }
}

function applySiteSettings(settings) {
    const phone = settings.phone || '9600877706';
    const whatsapp = (settings.whatsapp || phone).replace(/\D/g, '');
    const showroom = settings.showroom || 'Karaikudi Showroom';
    const rateLabel = settings.rateLabel || "Today's Jewelry Rates (Karaikudi)";

    const topPhone = document.getElementById('topPhone');
    const topLoc = document.getElementById('topLoc');
    const whatsappQuickContact = document.getElementById('whatsappQuickContact');
    const ratesTitle = document.querySelector('#rates-board-section .calc-title h3');

    if (topPhone) {
        topPhone.href = `tel:+91${phone.replace(/\D/g, '')}`;
        topPhone.innerHTML = `<i class="fa-solid fa-phone"></i> +91 ${phone}`;
    }
    if (topLoc) topLoc.innerHTML = `<i class="fa-solid fa-location-dot"></i> ${showroom}`;
    if (ratesTitle) ratesTitle.textContent = rateLabel;
    if (whatsappQuickContact) {
        whatsappQuickContact.href = `https://wa.me/91${whatsapp}?text=${encodeURIComponent("Hello Sethu Thanga Nagai Maaligai, I'd like to inquire about your jewellery collections.")}`;
    }
}

// Calculate current price of a product based on active live rate
function getProductPrice(product) {
    const metalRate = currentRates[product.metalType];
    const rawMetalCost = product.baseWeight * metalRate;
    const wastageCost = rawMetalCost * (product.wastagePercent / 100);
    const totalPrice = rawMetalCost + wastageCost;

    // In actual practice, 3% GST is added to the total (we can include GST in final display)
    return Math.round(totalPrice);
}

// Render dynamic catalog products
let activeFilter = 'all';
let searchQuery = '';

function renderProducts() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;

    // Filter products
    const filteredProducts = productsData.filter(product => {
        const matchesCategory = activeFilter === 'all' || product.category === activeFilter;
        const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            product.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
            product.style.toLowerCase().includes(searchQuery.toLowerCase());
        return matchesCategory && matchesSearch;
    });

    if (filteredProducts.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: span 4; text-align: center; padding: 50px 0; color: var(--text-muted);">
                <i class="fa-solid fa-hourglass-empty" style="font-size: 40px; margin-bottom: 15px; color: var(--border-glass);"></i>
                <p>No jewelry items found matching "${searchQuery}".</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = filteredProducts.map(prod => {
        const price = getProductPrice(prod);
        const formattedPrice = formatINR(price);

        return `
            <div class="product-card" data-id="${prod.id}">
                <div class="product-image-area">
                    <span class="product-badge">${prod.style}</span>
                    <img src="${prod.image}" alt="${prod.name}">
                    <div class="product-card-actions">
                        <button type="button" class="product-btn-circle" onclick="openQuickView('${prod.id}')" title="Quick View"><i class="fa-solid fa-eye"></i></button>
                        <button type="button" class="product-btn-circle" onclick="addToCart('${prod.id}')" title="Add to Cart"><i class="fa-solid fa-cart-plus"></i></button>
                    </div>
                </div>
                <div class="product-card-info">
                    <span class="product-category-label">${prod.subcategory}${prod.subSubcategory ? ' (' + prod.subSubcategory + ')' : ''} &bull; ${prod.purity}</span>
                    <h3 class="product-title">${prod.name}</h3>
                    
                    <div class="product-meta-specs">
                        <div class="spec-item">Weight: <span>${prod.baseWeight.toFixed(2)}g</span></div>
                        <div class="spec-item">Wastage: <span>${prod.wastagePercent}%</span></div>
                    </div>
                    
                    <div class="product-price-row">
                        <div>
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: -4px;">Estimated Price</span>
                            <span class="product-price">${formattedPrice}</span>
                        </div>
                        <a href="https://wa.me/919600877706?text=${encodeURIComponent(`Hello Sethu Thanga Nagai Maaligai, I would like to inquire about "${prod.name}" (Code: ${prod.id}, Weight: ${prod.baseWeight}g, Est Price: ${formattedPrice}). Is this available?`)}" target="_blank" class="btn-inquire-whatsapp" title="Inquire on WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// 3. INTERACTIVE METAL WEIGHT CALCULATOR
function updateCalculators() {
    // Live Metal Cost Estimator
    const metalTypeEl = document.getElementById('calcMetalType');
    const weightInput = document.getElementById('calcWeight');
    const metalType = metalTypeEl ? metalTypeEl.value : 'gold22';
    const weight = weightInput ? Math.max(parseFloat(weightInput.value) || 0, 0) : 0;
    const totalEl = document.getElementById('calcTotalPrice');
    const detailEl = document.getElementById('calcDetailText');

    if (totalEl && detailEl) {
        let ratePerGram = 0;
        let label = '';

        if (metalType === 'gold22') {
            ratePerGram = currentRates.gold22;
            label = 'Gold 22K (Hallmarked)';
        } else if (metalType === 'gold24') {
            ratePerGram = currentRates.gold24;
            label = 'Gold 24K (Pure Gold)';
        } else if (metalType === 'gold18') {
            ratePerGram = currentRates.gold18;
            label = 'Gold 18K (Diamond Grade)';
        } else if (metalType === 'silver') {
            ratePerGram = currentRates.silver;
            label = 'Fine Silver';
        }

        const price = ratePerGram * weight;
        totalEl.textContent = formatINR(price);
        detailEl.textContent = `Based on current ${label} rate: ₹${ratePerGram.toFixed(metalType === 'silver' ? 2 : 0)}/g. Total cost excludes wastage, making charges, and GST.`;
    }

    // Chit Savings Scheme Calculator
    const schemeInput = document.getElementById('schemeMonthlyPay');
    const schemeVal = schemeInput ? parseInt(schemeInput.value, 10) || 0 : 0;
    const totalContribution = schemeVal * 11;

    // Gold accumulated is total contribution divided by 22K Gold Rate on that day
    const goldAccumulated = currentRates.gold22 > 0 ? totalContribution / currentRates.gold22 : 0;

    const schemeMonthlyVal = document.getElementById('schemeMonthlyVal');
    const schemeTotalInvest = document.getElementById('schemeTotalInvest');
    const schemeGoldAcc = document.getElementById('schemeGoldAcc');
    const schemeMaturityVal = document.getElementById('schemeMaturityVal');

    if (schemeMonthlyVal) schemeMonthlyVal.textContent = schemeVal.toLocaleString('en-IN');
    if (schemeTotalInvest) schemeTotalInvest.textContent = formatINR(totalContribution);
    if (schemeGoldAcc) schemeGoldAcc.textContent = `${goldAccumulated.toFixed(2)} Grams (Approx)`;
    if (schemeMaturityVal) {
        // Assume maturity has a bonus of roughly 50% of 1 month's installment
        const bonusValue = schemeVal * 0.5;
        schemeMaturityVal.textContent = `${formatINR(totalContribution)} + ${formatINR(bonusValue)} Bonus`;
    }
}

// 4. QUICK VIEW MODAL
let activeProductId = null;

function openQuickView(id) {
    const prod = productsData.find(p => p.id === id);
    if (!prod) return;

    activeProductId = id;
    const price = getProductPrice(prod);
    const formattedPrice = formatINR(price);

    document.getElementById('modalProductImage').src = prod.image;
    document.getElementById('modalProductName').textContent = prod.name;
    document.getElementById('modalProductCategory').textContent = prod.subcategory + (prod.subSubcategory ? ' (' + prod.subSubcategory + ')' : '');
    document.getElementById('modalProductWeight').textContent = `${prod.baseWeight.toFixed(2)} Grams`;
    document.getElementById('modalProductPurity').textContent = prod.purity;
    document.getElementById('modalProductStyle').textContent = prod.style;
    document.getElementById('modalProductPrice').textContent = formattedPrice;

    // Setup WhatsApp Enquiry link inside modal
    const whatsappMsg = `Hello Sethu Thanga Nagai Maaligai, I'm interested in buying this item: "${prod.name}" (Code: ${prod.id}, Weight: ${prod.baseWeight}g, Est. Price: ${formattedPrice}). Please guide me on next steps.`;
    document.getElementById('modalWhatsappLink').href = `https://wa.me/919600877706?text=${encodeURIComponent(whatsappMsg)}`;

    const modal = document.getElementById('quickViewModal');
    modal.classList.add('active');
}

function closeQuickView() {
    const modal = document.getElementById('quickViewModal');
    modal.classList.remove('active');
    activeProductId = null;
}

// 5. SHOPPING CART LOCAL STORAGE ENGINE
let cart = JSON.parse(localStorage.getItem('stm_cart')) || [];

function saveCart() {
    localStorage.setItem('stm_cart', JSON.stringify(cart));
    updateCartUI();
}

function addToCart(productId) {
    const prod = productsData.find(p => p.id === productId);
    if (!prod) return;

    const existingIndex = cart.findIndex(item => item.id === productId);

    if (existingIndex > -1) {
        // Alert user if product is already in cart
        showNotification('Item is already in your cart!');
    } else {
        cart.push({
            id: prod.id,
            name: prod.name,
            weight: prod.baseWeight,
            image: prod.image,
            metalType: prod.metalType,
            wastagePercent: prod.wastagePercent
        });
        saveCart();
        showNotification('Added to Cart successfully!');

        // Also close modal if open
        closeQuickView();
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
}

function updateCartUI() {
    const badge = document.getElementById('cartBadgeCount');
    const titleCount = document.getElementById('cartCountTitle');
    const cartList = document.getElementById('cartItemsList');
    const totalEl = document.getElementById('cartTotalPriceVal');

    if (badge) badge.textContent = cart.length;
    if (titleCount) titleCount.textContent = `${cart.length} item${cart.length === 1 ? '' : 's'}`;

    if (!cartList) return;

    if (cart.length === 0) {
        cartList.innerHTML = `
            <div class="cart-empty-state">
                <i class="fa-solid fa-basket-shopping"></i>
                <p>Your shopping cart is currently empty.</p>
            </div>
        `;
        if (totalEl) totalEl.textContent = formatINR(0);
        return;
    }

    let totalCartPrice = 0;

    cartList.innerHTML = cart.map(item => {
        // Re-find full product data to calculate prices dynamically with live rates
        const origProd = productsData.find(p => p.id === item.id);
        const price = getProductPrice(origProd || item);
        totalCartPrice += price;

        return `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}">
                <div>
                    <h4 class="cart-item-title">${item.name}</h4>
                    <span class="cart-item-meta">Weight: ${item.weight.toFixed(2)}g</span>
                </div>
                <div style="text-align: right;">
                    <div class="cart-item-price">${formatINR(price)}</div>
                    <button type="button" class="cart-item-remove" onclick="removeFromCart('${item.id}')"><i class="fa-solid fa-trash-can"></i> Remove</button>
                </div>
            </div>
        `;
    }).join('');

    if (totalEl) totalEl.textContent = formatINR(totalCartPrice);

    // Dynamic checkout link containing cart list to WhatsApp
    const checkoutBtn = document.getElementById('cartCheckoutBtn');
    if (checkoutBtn) {
        let checkoutText = `Hello Sethu Thanga Nagai Maaligai, I'd like to check out my cart:\n\n`;
        cart.forEach((item, index) => {
            const origProd = productsData.find(p => p.id === item.id);
            const price = getProductPrice(origProd || item);
            checkoutText += `${index + 1}. ${item.name} (Code: ${item.id}) - Weight: ${item.weight}g, Price: ${formatINR(price)}\n`;
        });
        checkoutText += `\nTotal Cart Estimated Value: ${formatINR(totalCartPrice)} (Pre-Tax)`;
        checkoutBtn.href = `https://wa.me/919600877706?text=${encodeURIComponent(checkoutText)}`;
    }
}

// Simple Alert Toast notification
function showNotification(message) {
    const notifyBox = document.createElement('div');
    notifyBox.style.position = 'fixed';
    notifyBox.style.bottom = '30px';
    notifyBox.style.right = '30px';
    notifyBox.style.background = 'linear-gradient(135deg, var(--teal-dark), var(--teal-mid))';
    notifyBox.style.color = 'white';
    notifyBox.style.padding = '12px 24px';
    notifyBox.style.borderRadius = '30px';
    notifyBox.style.boxShadow = 'var(--shadow-hover)';
    notifyBox.style.zIndex = '2000';
    notifyBox.style.fontWeight = '600';
    notifyBox.style.fontSize = '14px';
    notifyBox.style.border = '1px solid var(--gold-primary)';
    notifyBox.style.transition = 'all 0.5s ease';
    notifyBox.style.opacity = '0';
    notifyBox.style.transform = 'translateY(20px)';

    notifyBox.textContent = message;
    document.body.appendChild(notifyBox);

    // Trigger transition animation
    setTimeout(() => {
        notifyBox.style.opacity = '1';
        notifyBox.style.transform = 'translateY(0)';
    }, 50);

    // Fade out and remove
    setTimeout(() => {
        notifyBox.style.opacity = '0';
        notifyBox.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            notifyBox.remove();
        }, 500);
    }, 3000);
}

// 6. HERO SLIDER AUTOMATIC CYCLE
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.slider-dot');

function showSlide(index) {
    if (slides.length === 0) return;

    slides[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');

    currentSlide = (index + slides.length) % slides.length;

    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

function nextSlide() {
    showSlide(currentSlide + 1);
}

// Auto slider loop
let sliderInterval = setInterval(nextSlide, 6000);

// 7. INITIALIZATION AND EVENT LISTENERS
document.addEventListener('DOMContentLoaded', async () => {

    await loadSiteData();

    // Set up fixed current rates and initial calculator values
    updateDOMRates();
    updateCalculators();
    renderProducts();
    updateCartUI();

    // Calculator event listeners
    const metalCalcForm = document.getElementById('metalCalcForm');
    const calcMetal = document.getElementById('calcMetalType');
    const calcWeight = document.getElementById('calcWeight');

    if (metalCalcForm) {
        metalCalcForm.addEventListener('submit', (e) => {
            e.preventDefault();
            updateCalculators();
        });
    }
    if (calcMetal) calcMetal.addEventListener('change', updateCalculators);
    if (calcWeight) calcWeight.addEventListener('change', updateCalculators);

    // Savings scheme slider event listener
    const schemeSlider = document.getElementById('schemeMonthlyPay');
    if (schemeSlider) schemeSlider.addEventListener('input', updateCalculators);

    // Hero dots control
    const dotsContainer = document.getElementById('sliderDots');
    if (dotsContainer) {
        dotsContainer.addEventListener('click', (e) => {
            const dot = e.target.closest('.slider-dot');
            if (!dot) return;

            clearInterval(sliderInterval);
            const index = parseInt(dot.getAttribute('data-index'));
            showSlide(index);
            sliderInterval = setInterval(nextSlide, 6000); // Restart interval
        });
    }

    // Category Circle Card Clicks
    const categoryCircles = document.querySelectorAll('.category-circle-card');
    categoryCircles.forEach(card => {
        card.addEventListener('click', () => {
            const cat = card.getAttribute('data-category');

            // Switch tabs
            const tabs = document.querySelectorAll('.filter-tab');
            tabs.forEach(tab => {
                if (tab.getAttribute('data-filter') === cat) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });

            activeFilter = cat;
            renderProducts();

            // Scroll smoothly to catalog
            document.getElementById('collections').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Catalog Filter Tabs Clicks
    const filterTabs = document.getElementById('filterTabs');
    if (filterTabs) {
        filterTabs.addEventListener('click', (e) => {
            const tab = e.target.closest('.filter-tab');
            if (!tab) return;

            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            activeFilter = tab.getAttribute('data-filter');
            renderProducts();
        });
    }

    // Search Box Listener
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            renderProducts();
        });
    }
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            if (searchInput) {
                searchQuery = searchInput.value;
                renderProducts();
                document.getElementById('collections').scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    // Quick View Modal Close
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalOverlay = document.getElementById('quickViewModal');

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeQuickView);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeQuickView();
        });
    }

    // Cart Add button from Modal
    const modalAddToCartBtn = document.getElementById('modalAddToCartBtn');
    if (modalAddToCartBtn) {
        modalAddToCartBtn.addEventListener('click', () => {
            if (activeProductId) {
                addToCart(activeProductId);
            }
        });
    }

    // Cart Drawer Toggle
    const btnCartToggle = document.getElementById('btnCartToggle');
    const cartCloseBtn = document.getElementById('cartCloseBtn');
    const cartDrawerOverlay = document.getElementById('cartDrawerOverlay');

    if (btnCartToggle) {
        btnCartToggle.addEventListener('click', () => {
            cartDrawerOverlay.classList.add('active');
            updateCartUI();
        });
    }
    if (cartCloseBtn) {
        cartCloseBtn.addEventListener('click', () => {
            cartDrawerOverlay.classList.remove('active');
        });
    }
    if (cartDrawerOverlay) {
        cartDrawerOverlay.addEventListener('click', (e) => {
            if (e.target === cartDrawerOverlay) {
                cartDrawerOverlay.classList.remove('active');
            }
        });
    }

    // Login button redirect to admin panel
    const btnLoginToggle = document.getElementById('btnLoginToggle');
    if (btnLoginToggle) {
        btnLoginToggle.addEventListener('click', () => {
            window.location.href = 'admin/';
        });
    }

    // Mobile Navigation Toggle Menu Drawer
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navigationBar = document.getElementById('navigationBar');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            if (navigationBar.style.display === 'block') {
                navigationBar.style.display = 'none';
            } else {
                navigationBar.style.display = 'block';
                navigationBar.style.background = 'var(--teal-dark)';
            }
        });
    }

    // Virtual Shopping Appointment Form Booking Submit
    const bookingForm = document.getElementById('virtualBookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const name = document.getElementById('bookName').value;
            const phone = document.getElementById('bookPhone').value;
            const date = document.getElementById('bookDate').value;
            const time = document.getElementById('bookTime').value;
            const notes = document.getElementById('bookNotes').value || 'Bridal Ornaments';

            const whatsappMsg = `Hi Sethu Thanga Nagai Maaligai, I'd like to schedule a Virtual Video Shopping appointment.
Name: ${name}
Phone: ${phone}
Date: ${date}
Preferred Slot: ${time}
Interests: ${notes}`;

            const apiLink = `https://wa.me/919600877706?text=${encodeURIComponent(whatsappMsg)}`;

            // Redirect to WhatsApp
            window.open(apiLink, '_blank');
            showNotification('Booking requested! Opening WhatsApp for confirmation.');
            bookingForm.reset();
        });
    }
});
