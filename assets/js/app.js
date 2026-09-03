(function () {
    'use strict';

    var products = Array.isArray(window.MOJODI_PRODUCTS) ? window.MOJODI_PRODUCTS : [];
    var menuButton = document.getElementById('mobileMenuBtn');
    var mainNav = document.getElementById('mainNav');
    var advancedToggle = document.getElementById('advancedToggle');
    var advancedFields = document.getElementById('advancedFields');
    var modal = document.getElementById('productModal');
    var modalClose = document.getElementById('modalClose');
    var modalTitle = document.getElementById('modalProductTitle');
    var modalMeta = document.getElementById('modalProductMeta');
    var modalImage = document.getElementById('modalProductImage');
    var modalDescription = document.getElementById('modalProductDescription');
    var modalProductPage = document.getElementById('modalProductPage');
    var offerList = document.getElementById('offerList');
    var lastFocusedElement = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatPrice(value) {
        try {
            return new Intl.NumberFormat('fa-IR').format(Number(value)) + ' تومان';
        } catch (error) {
            return String(value) + ' تومان';
        }
    }

    function initials(name) {
        var cleanName = String(name || '').trim();
        return cleanName ? cleanName.slice(0, 1) : 'ف';
    }

    function toggleMenu() {
        if (!mainNav || !menuButton) return;
        var isOpen = mainNav.classList.toggle('is-open');
        menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menuButton.setAttribute('aria-label', isOpen ? 'بستن منو' : 'باز کردن منو');
    }

    if (menuButton) {
        menuButton.addEventListener('click', toggleMenu);
    }

    if (mainNav) {
        mainNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mainNav.classList.contains('is-open')) toggleMenu();
            });
        });
    }

    document.addEventListener('click', function (event) {
        if (!mainNav || !menuButton || !mainNav.classList.contains('is-open')) return;
        if (!mainNav.contains(event.target) && !menuButton.contains(event.target)) toggleMenu();
    });

    if (advancedToggle && advancedFields) {
        advancedToggle.addEventListener('click', function () {
            var isOpen = advancedFields.classList.toggle('is-open');
            advancedToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    function renderOffers(offers) {
        if (!offers || !offers.length) {
            return '<div class="no-results"><p>برای این کالا پیشنهادی ثبت نشده است.</p></div>';
        }

        return offers.map(function (offer) {
            var callLink = 'tel:+98' + String(offer.phone || '').replace(/^0/, '');
            var verification = offer.verified ? 'تأییدشده' : 'در حال بررسی';
            return '<div class="offer-row">' +
                '<div class="offer-seller">' +
                    '<span class="seller-avatar">' + escapeHtml(initials(offer.seller)) + '</span>' +
                    '<span class="offer-seller-copy"><strong>' + escapeHtml(offer.seller) + '</strong><span>' + escapeHtml(offer.city) + ' · ' + escapeHtml(verification) + '</span></span>' +
                '</div>' +
                '<div class="offer-cell"><span class="offer-cell-label">وضعیت</span><span class="offer-cell-value">' + escapeHtml(offer.condition) + '</span></div>' +
                '<div class="offer-cell"><span class="offer-cell-label">شرایط</span><span class="offer-cell-value">' + escapeHtml(offer.warranty === 'دارد' ? 'گارانتی دارد' : 'مهلت ' + offer.test) + '</span></div>' +
                '<strong class="offer-price">' + escapeHtml(formatPrice(offer.price)) + '</strong>' +
                '<a class="offer-call" href="' + escapeHtml(callLink) + '" aria-label="تماس با ' + escapeHtml(offer.seller) + '">' +
                    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M7 4h3l1.5 4-2 1.5a13 13 0 0 0 5 5l1.5-2 4 1.5v3c0 1.1-.9 2-2 2C11.4 19.8 4.2 12.6 4 5.9 4 4.9 5 4 6 4h1Z" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                '</a>' +
            '</div>';
        }).join('');
    }

    function openProduct(product, trigger) {
        if (!modal || !product) return;
        lastFocusedElement = trigger || document.activeElement;
        modalTitle.textContent = product.title || 'جزئیات کالا';
        modalMeta.textContent = 'کد ' + (product.code || '') + ' · ' + (product.offers ? product.offers.length : 0) + ' پیشنهاد فروشنده';
        modalImage.src = product.image || 'assets/images/cylinder-head.svg';
        modalImage.alt = 'تصویر آزمایشی ' + (product.title || 'کالا');
        modalDescription.textContent = product.description || 'اطلاعات تکمیلی این کالا در نسخه نمایشی.';
        if (modalProductPage) modalProductPage.href = 'product.php?id=' + encodeURIComponent(product.id);
        offerList.innerHTML = renderOffers(product.offers || []);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        if (modalClose) modalClose.focus();
    }

    function closeProduct() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') lastFocusedElement.focus();
    }

    document.querySelectorAll('.js-product-detail').forEach(function (button) {
        button.addEventListener('click', function () {
            var id = button.getAttribute('data-product-id');
            var product = products.find(function (item) { return item.id === id; });
            openProduct(product, button);
        });
    });

    if (modalClose) modalClose.addEventListener('click', closeProduct);
    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeProduct();
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) closeProduct();
    });

    // اگر صفحه با فیلتر باز شده، نتیجه‌ها در اولین نگاه دیده شوند؛ بدون پرش برای صفحه اصلی.
    if (window.location.hash === '#results' && document.getElementById('results')) {
        window.setTimeout(function () {
            document.getElementById('results').scrollIntoView({ block: 'start' });
        }, 80);
    }
})();
