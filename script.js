document.addEventListener('DOMContentLoaded', () => {

    const cursor = document.querySelector('.custom-cursor');

    if (cursor) {
        document.addEventListener('mousemove', (e) => {
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
        });

        const clickableElements = document.querySelectorAll('a, button, input[type="submit"], .btn-search, .btn-register, .btn-login, .mission-box, .value-item');

        clickableElements.forEach(el => {
            el.addEventListener('mouseenter', () => {
                cursor.style.transform = 'translate(-50%, -50%) scale(0.6)';
                cursor.style.backgroundColor = '#2ecc71';
            });
            el.addEventListener('mouseleave', () => {
                cursor.style.transform = 'translate(-50%, -50%) scale(1)';
                cursor.style.backgroundColor = 'var(--accent-color)';
            });
        });
    }

    if (document.querySelector('.ml6')) {
        var h1Wrapper = document.querySelector('.ml6 .letters');
        if (h1Wrapper) {

            h1Wrapper.innerHTML = h1Wrapper.textContent.replace(/\S/g, "<span class='letter'>$&</span>");

            anime.timeline({ loop: true })
                .add({
                    targets: '.ml6 .letter',
                    translateY: ["1.1em", 0],
                    translateZ: 0,
                    duration: 750,
                    delay: (el, i) => 50 * i
                }).add({
                    targets: '.ml6',
                    opacity: 0,
                    duration: 1000,
                    easing: "easeOutExpo",
                    delay: 1000
                });
            const pElement = document.querySelector('.header-p-anim');
            if (pElement) {
                pElement.classList.add('visible');
            }
        }
    }

    const navbar = document.querySelector('.navbar');
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const authButtons = document.querySelector('.auth-buttons');

    if (hamburger && navLinks) {
        if (authButtons && !navLinks.querySelector('.auth-buttons-mobile')) {
            const mobileAuth = authButtons.cloneNode(true);
            mobileAuth.classList.remove('auth-buttons');
            mobileAuth.classList.add('auth-buttons-mobile');
            navLinks.appendChild(mobileAuth);
        }

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.querySelector('i').classList.toggle('fa-bars');
            hamburger.querySelector('i').classList.toggle('fa-times');
            document.body.classList.toggle('no-scroll');
        });

        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                if (authButtons) authButtons.classList.remove('active');
                hamburger.querySelector('i').classList.add('fa-bars');
                hamburger.querySelector('i').classList.remove('fa-times');
            });
        });
    }
    const authArea = document.querySelector('.auth-buttons');
    if (authArea && !authArea.querySelector('.profile-menu')) {
        const greeting = authArea.querySelector('span');
        const logout = authArea.querySelector('a[href*=\"logout.php\"]');

        if (greeting && logout) {
            const raw = (greeting.textContent || '').trim();
            const username = raw.replace(/^Sveiki,?/i, '').replace(/!$/, '').trim() || 'Lietotajs';

            let settingsHref = '';
            try {
                const logoutUrl = new URL(logout.getAttribute('href') || '', window.location.href);
                const settingsUrl = new URL(logoutUrl.toString());
                if (/(^|\/)login\/logout\.php$/i.test(logoutUrl.pathname)) {
                    settingsUrl.pathname = logoutUrl.pathname.replace(/login\/logout\.php$/i, 'account/settings.php');
                }
                settingsHref = settingsUrl.toString();
            } catch (_) {
                const href = logout.getAttribute('href') || '';
                settingsHref = href.replace(/login\/logout\.php(\?.*)?$/i, 'account/settings.php');
            }

            const profileMenu = document.createElement('details');
            profileMenu.className = 'profile-menu';
            profileMenu.innerHTML = `
                <summary class="profile-trigger" aria-haspopup="true" aria-label="Atvert profila izvelni">
                    <i class="fas fa-user profile-trigger__icon" aria-hidden="true"></i>
                </summary>
                <div class="profile-dropdown">
                    <div class="profile-dropdown__summary">
                        <div class="profile-dropdown__identity">
                            <div class="profile-dropdown__avatar">
                                <span class="profile-avatar-fallback">${username.slice(0, 1).toUpperCase()}</span>
                            </div>
                            <div><strong>${username}</strong></div>
                        </div>
                    </div>
                    <a class="profile-dropdown__link" href="#settings-modal"><i class="fas fa-user-cog"></i>Iestatijumi</a>
                    <a class="profile-dropdown__link" href="${logout.getAttribute('href')}"><i class="fas fa-sign-out-alt"></i>Iziet</a>
                </div>
            `;

            authArea.innerHTML = '';
            authArea.appendChild(profileMenu);

        }
    }

    if (navbar) {
        const hasHero = !!document.querySelector('.hero, .homes-hero, .owner-hero, .myhomes-hero, .property-hero, .property-hero-v2, .newhome-hero');
        if (hasHero) {
            navbar.classList.add('navbar--hero');
        }

        const syncNavbarState = () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                return;
            }

            navbar.classList.remove('scrolled');
        };

        syncNavbarState();
        window.addEventListener('scroll', syncNavbarState, { passive: true });
    }

    const faders = document.querySelectorAll('.fade-in, .timeline-item');

    const appearOptions = {
        threshold: 0.2,
        rootMargin: "0px 0px -50px 0px"
    };

    const appearOnScroll = new IntersectionObserver(function (entries, appearOnScroll) {
        entries.forEach(entry => {
            if (!entry.isIntersecting) {
                return;
            } else {
                entry.target.classList.add('visible');
                appearOnScroll.unobserve(entry.target);
            }
        });
    }, appearOptions);

    faders.forEach(fader => {
        appearOnScroll.observe(fader);
    });

    function startBackgroundSlider() {
        const hero = document.querySelector('.hero');
        const heroContent = document.querySelector('.hero-content');

        if (!hero || !heroContent) return;

        const images = ['bg-1', 'bg-2', 'bg-3'];
        let currentImageIndex = 0;

        function changeBackground() {
            heroContent.style.transition = 'transform 0.5s ease-in, opacity 0.5s ease-in';
            heroContent.style.transform = 'translateX(100px)';
            heroContent.style.opacity = '0';

            setTimeout(() => {
                hero.classList.remove('bg-1', 'bg-2', 'bg-3');
                hero.classList.add(images[currentImageIndex]);

                currentImageIndex = (currentImageIndex + 1) % images.length;

                heroContent.style.transition = 'none';
                heroContent.style.transform = 'translateX(-100px)';

                setTimeout(() => {
                    heroContent.style.transition = 'transform 0.8s cubic-bezier(0.23, 1, 0.32, 1), opacity 0.5s ease-out';
                    heroContent.style.transform = 'translateX(0)';
                    heroContent.style.opacity = '1';
                }, 50);

            }, 500);
        }

        setInterval(changeBackground, 8000);
    }

    if (document.querySelector('.hero')) {
        startBackgroundSlider();
    }

    function startCounterAnimation() {
        const counters = document.querySelectorAll('.counter');
        const speed = 200;

        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText.replace(/,/g, '').replace(/\s/g, '');
                const increment = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + increment).toLocaleString('lv-LV');
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target.toLocaleString('lv-LV');
                }
            };
            counter.innerText = '0';
            updateCount();
        });
    }

    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        const observerOptions = { threshold: 0.5 };
        const statsObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    startCounterAnimation();
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        statsObserver.observe(statsSection);
    }

    (function () {
        const header = document.querySelector('.about-header');
        if (!header) return;
        const images = ['Images/bg1.jpg', 'Images/bg2.jpg', 'Images/bg3.jpeg'];
        let current = 0;
        const applyBg = (i) => {
            header.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('${images[i]}')`;
        };
        applyBg(current);
        setInterval(() => {
            current = (current + 1) % images.length;
            applyBg(current);
        }, 8000);
    })();

    (function () {
        const minRange = document.getElementById('index-min-price');
        const maxRange = document.getElementById('index-max-price');
        const minLabel = document.getElementById('index-price-min-val');
        const maxLabel = document.getElementById('index-price-max-val');

        if (!minRange || !maxRange || !minLabel || !maxLabel) return;

        function updateLabels() {
            minLabel.textContent = parseInt(minRange.value).toLocaleString('lv-LV') + ' €';
            maxLabel.textContent = parseInt(maxRange.value).toLocaleString('lv-LV') + ' €';
        }

        minRange.addEventListener('input', () => {
            if (parseInt(minRange.value) > parseInt(maxRange.value)) {
                minRange.value = maxRange.value;
            }
            updateLabels();
        });

        maxRange.addEventListener('input', () => {
            if (parseInt(maxRange.value) < parseInt(minRange.value)) {
                maxRange.value = minRange.value;
            }
            updateLabels();
        });

        updateLabels();
    })();

    (function () {
        const resultsWrap = document.getElementById('homes-results');
        if (!resultsWrap) return;

        let listingsData = [];
        const resultsCount = document.getElementById('results-count');

        const citySelect = document.getElementById('filter-city');
        const typeSelect = document.getElementById('filter-type');
        const priceMinInput = document.getElementById('filter-price-min');
        const priceMaxInput = document.getElementById('filter-price-max');
        const priceMinField = document.getElementById('price-min-field');
        const priceMaxField = document.getElementById('price-max-field');
        const rangeTrack = document.querySelector('.range-track');
        const bedsInput = document.getElementById('filter-beds');
        const bathsInput = document.getElementById('filter-baths');
        const areaMinInput = document.getElementById('filter-area-min');
        const areaMaxInput = document.getElementById('filter-area-max');
        const categorySelect = document.getElementById('filter-category');
        const verifiedCheckbox = document.getElementById('filter-verified');
        const toggleAdvancedBtn = document.getElementById('toggle-advanced-filters');
        const advancedPanel = document.getElementById('advanced-filters-panel');

        const applyBtn = document.getElementById('filter-apply');
        const heroApply = document.getElementById('filter-hero');
        const filterShell = document.querySelector('.filter-shell');
        const emptyMsg = document.getElementById('homes-empty');
        const homesApiUrl = document.body.dataset.homesApi || 'api/get_homes.php';
        const propertyRoute = document.body.dataset.propertyRoute || 'home.php';

        const modal = document.getElementById('homes-modal');
        const modalClose = document.getElementById('homes-modal-close');
        const modalImg = document.getElementById('homes-modal-img');
        const modalBadge = document.getElementById('homes-modal-badge');
        const modalTitle = document.getElementById('homes-modal-title');
        const modalLocation = document.getElementById('homes-modal-location');
        const modalBeds = document.getElementById('homes-modal-beds');
        const modalSize = document.getElementById('homes-modal-size');
        const modalDesc = document.getElementById('homes-modal-desc');
        const modalPrice = document.getElementById('homes-modal-price');
        const modalContact = document.getElementById('homes-modal-contact');

        async function loadHomes() {
            try {
                const response = await fetch(homesApiUrl, { cache: 'no-store' });
                if (!response.ok) throw new Error(response.status);
                const text = await response.text();

                try {
                    listingsData = JSON.parse(text);
                } catch (e) {
                    throw new Error("Invalid JSON");
                }

                if (!Array.isArray(listingsData)) throw new Error("Not an array");

                populateCities();
                initPriceSlider();

                const urlParams = new URLSearchParams(window.location.search);
                const cityParam = urlParams.get('city');
                const typeParam = urlParams.get('type');
                const priceMinParam = urlParams.get('min_price');
                const priceMaxParam = urlParams.get('max_price');
                const bedsParam = urlParams.get('beds');
                const bathsParam = urlParams.get('baths');
                const areaMinParam = urlParams.get('area_min');
                const areaMaxParam = urlParams.get('area_max');
                const categoryParam = urlParams.get('category');
                const verifiedParam = urlParams.get('verified');

                if (cityParam && citySelect) {
                    let match = "";
                    for (let i = 0; i < citySelect.options.length; i++) {
                        if (citySelect.options[i].value.toLowerCase() === cityParam.toLowerCase()) {
                            match = citySelect.options[i].value;
                            break;
                        }
                    }
                    if (match) {
                        citySelect.value = match;
                    } else if (cityParam.trim() !== "") {
                        const opt = document.createElement('option');
                        opt.value = cityParam;
                        opt.textContent = cityParam;
                        citySelect.appendChild(opt);
                        citySelect.value = cityParam;
                    }
                }

                if (typeParam && typeSelect) {
                    typeSelect.value = typeParam;
                }

                initPriceSlider();

                if (priceMinParam && priceMinField) {
                    priceMinField.value = priceMinParam;
                    priceMinInput.value = priceMinParam;
                }
                if (priceMaxParam && priceMaxField) {
                    priceMaxField.value = priceMaxParam;
                    priceMaxInput.value = priceMaxParam;
                }
                updateSliderUI();
                if (bedsParam && bedsInput) bedsInput.value = bedsParam;
                if (bathsParam && bathsInput) bathsInput.value = bathsParam;
                if (areaMinParam && areaMinInput) areaMinInput.value = areaMinParam;
                if (areaMaxParam && areaMaxInput) areaMaxInput.value = areaMaxParam;
                if (categoryParam && categorySelect) categorySelect.value = categoryParam;
                if (verifiedParam === '1' && verifiedCheckbox) verifiedCheckbox.checked = true;

                if (cityParam || typeParam || priceMinParam || priceMaxParam || bedsParam || bathsParam || areaMinParam || areaMaxParam || categoryParam || verifiedParam) {
                    applyFilters();
                } else {
                    renderListings(listingsData);
                }
            } catch (error) {
                console.error(error);
                const hasFallback = resultsWrap && resultsWrap.children && resultsWrap.children.length > 0;
                if (!hasFallback) {
                    if (emptyMsg) emptyMsg.style.display = 'block';
                    updateResultsCount(0);
                }
            }
        }

        function updateResultsCount(count) {
            if (resultsCount) {
                resultsCount.textContent = count + ' rezultāt' + (count === 1 ? 's' : 'i');
            }
        }

        function populateCities() {
            if (!citySelect) return;
            while (citySelect.options.length > 1) {
                citySelect.remove(1);
            }
            const cities = Array.from(new Set(listingsData.map(l => l.city).filter(c => c))).sort();
            cities.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                citySelect.appendChild(opt);
            });
        }

	        function formatPrice(item) {
	            if (item.type === 'istermina_ire') return `${item.price.toLocaleString('lv-LV')} \u20ac / nakti`;
	            return (item.type === 'ire' || item.type === 'rent') ? `${item.price.toLocaleString('lv-LV')} € / mēn` : `${item.price.toLocaleString('lv-LV')} €`;
	        }

	        function badgeClass(type) {
	            if (type === 'ire' || type === 'rent') return 'rent';
	            if (type === 'istermina_ire') return 'short-rent';
	            return 'sale';
	        }

        function renderListings(list) {
            resultsWrap.innerHTML = '';
            updateResultsCount(list.length);

            if (!list.length) {
                if (emptyMsg) emptyMsg.style.display = 'block';
                return;
            }
            if (emptyMsg) emptyMsg.style.display = 'none';

            list.forEach(item => {
                const shieldIcon = (item.owner_plan === 'Gold' || item.owner_plan === 'Silver') ? '<i class="fas fa-shield-alt" style="color: #30b607; margin-left: 5px;" title="Uzticams īpašnieks"></i>' : '';
                const ownerInitial = (item.owner_username || 'U').charAt(0).toUpperCase();
                const ownerPfpHtml = item.owner_pfp
                    ? `<img src="${item.owner_pfp}" alt="${item.owner_username}" class="owner-mini-pfp" onerror="this.parentElement.innerHTML='<span class=\'owner-mini-initial\'>${ownerInitial}</span>';">`
                    : `<span class="owner-mini-initial">${ownerInitial}</span>`;

                const card = document.createElement('div');
                card.className = 'property-card';
                card.innerHTML = `
                    <div class="property-image">
                        <img src="${item.image}" alt="${item.title}" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';">
                        <span class="property-badge ${badgeClass(item.type)}">${item.badge}</span>
                        <button class="property-favorite" title="Pievienot favorītiem" type="button" data-home-id="${item.id}">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <div class="property-details">
                        <h3>${item.title}</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> ${item.location}</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> ${item.beds} guļamist.</span>
                            <span><i class="fas fa-ruler-combined"></i> ${item.size} m²</span>
                            <span><i class="fas fa-bath"></i> ${item.baths || 1} vannas</span>
                        </div>
                        <div class="property-owner-bar">
                            <div class="property-owner-info">
                                ${ownerPfpHtml}
                                <span class="owner-username">${item.owner_username || 'Īpašnieks'}${shieldIcon}</span>
                            </div>
                        </div>
                        <div class="property-footer">
                            <div class="property-price">${formatPrice(item)}</div>
                            <a href="${propertyRoute}?id=${item.id}" class="btn-view-property">Skatīt <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                `;
                resultsWrap.appendChild(card);
            });
            if (window.initMiniCalendars) window.initMiniCalendars(resultsWrap);
        }

        function initPriceSlider() {
            if (!priceMinInput || !priceMaxInput || listingsData.length === 0) return;

            const selectedType = typeSelect ? typeSelect.value : "";
            const filteredPrices = listingsData
                .filter(l => !selectedType || l.type === selectedType)
                .map(l => l.price);

            if (filteredPrices.length === 0) return;

            const min = Math.min(...filteredPrices);
            const max = Math.max(...filteredPrices);

            priceMinInput.min = min;
            priceMinInput.max = max;
            priceMinInput.value = min;
            if (priceMinField) priceMinField.value = min;

            priceMaxInput.min = min;
            priceMaxInput.max = max;
            priceMaxInput.value = max;
            if (priceMaxField) priceMaxField.value = max;

            updateSliderUI();
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', () => {
                initPriceSlider();
                applyFilters();
            });
        }

        [priceMinInput, priceMaxInput].forEach(input => {
            input.addEventListener('input', () => {
                if (parseInt(priceMinInput.value) > parseInt(priceMaxInput.value)) {
                    if (input === priceMinInput) {
                        priceMinInput.value = priceMaxInput.value;
                    } else {
                        priceMaxInput.value = priceMinInput.value;
                    }
                }
                if (priceMinField) priceMinField.value = priceMinInput.value;
                if (priceMaxField) priceMaxField.value = priceMaxInput.value;
                updateSliderUI();
                applyFilters();
            });
        });

        if (priceMinField && priceMaxField) {
            [priceMinField, priceMaxField].forEach(field => {
                field.addEventListener('change', () => {
                    let val = parseInt(field.value) || 0;
                    const min = parseInt(priceMinInput.min);
                    const max = parseInt(priceMinInput.max);

                    if (val < min) val = min;
                    if (val > max) val = max;
                    field.value = val;

                    if (field === priceMinField) {
                        if (val > parseInt(priceMaxField.value)) {
                            field.value = priceMaxField.value;
                        }
                        priceMinInput.value = field.value;
                    } else {
                        if (val < parseInt(priceMinField.value)) {
                            field.value = priceMinField.value;
                        }
                        priceMaxInput.value = field.value;
                    }
                    updateSliderUI();
                    applyFilters();
                });
            });
        }

        function updateSliderUI() {
            if (!priceMinInput || !priceMaxInput || !priceMinField || !priceMaxField || !rangeTrack) return;
            const min = parseInt(priceMinInput.value);
            const max = parseInt(priceMaxInput.value);
            const rangeMin = parseInt(priceMinInput.min);
            const rangeMax = parseInt(priceMinInput.max);

            const left = ((min - rangeMin) / (rangeMax - rangeMin)) * 100;
            const right = 100 - (((max - rangeMin) / (rangeMax - rangeMin)) * 100);

            rangeTrack.style.left = left + '%';
            rangeTrack.style.right = right + '%';
        }

        if (toggleAdvancedBtn && advancedPanel) {
            toggleAdvancedBtn.addEventListener('click', () => {
                const isVisible = advancedPanel.style.display === 'block';
                advancedPanel.style.display = isVisible ? 'none' : 'block';
                toggleAdvancedBtn.querySelector('i').className = isVisible ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
            });
        }

        function applyFilters() {
            if (!listingsData || listingsData.length === 0) return;
            const city = citySelect ? citySelect.value : "";
            const type = typeSelect ? typeSelect.value : "";
            const pMin = priceMinField ? parseInt(priceMinField.value, 10) : null;
            const pMax = priceMaxField ? parseInt(priceMaxField.value, 10) : null;
            const beds = (bedsInput && bedsInput.value) ? parseInt(bedsInput.value, 10) : null;
            const baths = (bathsInput && bathsInput.value) ? parseInt(bathsInput.value, 10) : null;
            const areaMin = (areaMinInput && areaMinInput.value) ? parseInt(areaMinInput.value, 10) : null;
            const areaMax = (areaMaxInput && areaMaxInput.value) ? parseInt(areaMaxInput.value, 10) : null;
            const category = categorySelect ? categorySelect.value : "";
            const verifiedOnly = verifiedCheckbox ? verifiedCheckbox.checked : false;

            const filtered = listingsData.filter(item => {
                if (city && item.city !== city) {
                    if (item.city.toLowerCase() !== city.toLowerCase()) return false;
                }
                if (type && item.type !== type) return false;
                if (pMin !== null && item.price < pMin) return false;
                if (pMax !== null && item.price > pMax) return false;
                if (beds !== null && !isNaN(beds) && item.beds < beds) return false;
                if (baths !== null && !isNaN(baths) && item.baths < baths) return false;
                if (areaMin !== null && !isNaN(areaMin) && item.size < areaMin) return false;
                if (areaMax !== null && !isNaN(areaMax) && item.size > areaMax) return false;
                if (category && item.category !== category) return false;
                if (verifiedOnly && !(item.owner_plan === 'Gold' || item.owner_plan === 'Silver')) return false;
                return true;
            });
            renderListings(filtered);
        }

        loadHomes();

        if (applyBtn) applyBtn.addEventListener('click', applyFilters);
        if (heroApply) {
            heroApply.addEventListener('click', () => {
                applyFilters();
                if (filterShell) {
                    filterShell.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }

        if (modalClose) modalClose.addEventListener('click', () => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        });
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.classList.contains('listing-modal__backdrop')) {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        }
    })();


    (function () {
        const popItems = document.querySelectorAll('.property-page .pop-in');
        if (!popItems.length) return;
        const popObserver = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });
        popItems.forEach(item => popObserver.observe(item));
    })();


    (function () {
        if (!document.body.classList.contains('property-premium-v2')) return;


        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const tabId = link.dataset.tab;

                tabLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                tabContents.forEach(content => {
                    if (content.id === tabId) {
                        content.classList.add('active');
                    } else {
                        content.classList.remove('active');
                    }
                });
            });
        });


        const mainImage = document.querySelector('.main-image img');
        const thumbImages = document.querySelectorAll('.thumb-images img');

        thumbImages.forEach(thumb => {
            thumb.addEventListener('click', () => {
                mainImage.src = thumb.src.replace('&w=600&q=70', '&w=1200&q=80');

                thumbImages.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    })();

    const api = (window.__homeest || {});
    let favoriteIds = new Set();

    function setFavoriteActive(btn, isActive) {
        if (!btn) return;
        btn.classList.toggle('active', !!isActive);
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('far', !isActive);
            icon.classList.toggle('fas', !!isActive);
        }
    }

    function syncFavoriteButtons() {
        document.querySelectorAll('.property-favorite[data-home-id], .favorite-btn[data-home-id]').forEach(btn => {
            const id = parseInt(btn.getAttribute('data-home-id') || '0', 10) || 0;
            setFavoriteActive(btn, id > 0 && favoriteIds.has(id));
        });
    }

    async function loadFavoriteIds() {
        if (!api.favoritesIdsApi) return;
        try {
            const res = await fetch(api.favoritesIdsApi, { credentials: 'same-origin' });
            const data = await res.json();
            favoriteIds = new Set((Array.isArray(data) ? data : []).map(n => parseInt(n, 10)).filter(n => n > 0));
            syncFavoriteButtons();
        } catch (_) {
        }
    }

    async function toggleFavorite(homeId) {
        if (!api.favoritesToggleApi) return null;
        const fd = new FormData();
        fd.append('home_id', String(homeId));
        const res = await fetch(api.favoritesToggleApi, { method: 'POST', body: fd, credentials: 'same-origin' });
        if (res.status === 401) {
            if (api.loginUrl) window.location.href = api.loginUrl;
            return null;
        }
        return res.json();
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.property-favorite[data-home-id], .favorite-btn[data-home-id]') : null;
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const homeId = parseInt(btn.getAttribute('data-home-id') || '0', 10) || 0;
        if (homeId <= 0) return;

        if (!api.isLoggedIn) {
            if (api.loginUrl) window.location.href = api.loginUrl;
            return;
        }

        const out = await toggleFavorite(homeId);
        if (!out || typeof out.favorited !== 'boolean') return;

        if (out.favorited) favoriteIds.add(homeId);
        else favoriteIds.delete(homeId);

        syncFavoriteButtons();
    });

    async function loadFavoritesIntoModal() {
        const wrap = document.getElementById('favorites-results');
        const empty = document.getElementById('favorites-empty');
        if (!wrap || !empty || !api.favoritesApi) return;

        wrap.innerHTML = '';
        empty.style.display = 'none';

        try {
            const res = await fetch(api.favoritesApi, { credentials: 'same-origin' });
            if (res.status === 401) {
                empty.textContent = 'Nepieciešams ielogoties.';
                empty.style.display = 'block';
                return;
            }
            const list = await res.json();
            if (!Array.isArray(list) || list.length === 0) {
                empty.textContent = 'Nav favorītu.';
                empty.style.display = 'block';
                return;
            }

            list.forEach(item => {
                const shieldIcon = (item.owner_plan === 'Gold' || item.owner_plan === 'Silver') ? '<i class="fas fa-shield-alt" style="color: #30b607; margin-left: 5px;" title="Uzticams īpašnieks"></i>' : '';
                const ownerInitial = (item.owner_username || 'U').charAt(0).toUpperCase();
                const ownerPfpHtml = item.owner_pfp
                    ? `<img src="${item.owner_pfp}" alt="${item.owner_username || ''}" class="owner-mini-pfp" onerror="this.parentElement.innerHTML='<span class=\\'owner-mini-initial\\'>${ownerInitial}</span>';">`
                    : `<span class="owner-mini-initial">${ownerInitial}</span>`;

                const card = document.createElement('div');
                card.className = 'property-card';
                card.innerHTML = `
                    <div class="property-image">
                        <img src="${item.image}" alt="${item.title}" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';">
                        <span class="property-badge ${badgeClass(item.type)}">${item.badge}</span>
                        <button class="property-favorite active" title="Noņemt no favorītiem" type="button" data-home-id="${item.id}">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    <div class="property-details">
                        <h3>${item.title}</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> ${item.location}</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> ${item.beds} guļamist.</span>
                            <span><i class="fas fa-ruler-combined"></i> ${item.size} m²</span>
                            <span><i class="fas fa-bath"></i> ${item.baths || 1} vannas</span>
                        </div>
                        <div class="property-owner-bar">
                            <div class="property-owner-info">
                                ${ownerPfpHtml}
                                <span class="owner-username">${item.owner_username || 'Īpašnieks'}${shieldIcon}</span>
                            </div>
                        </div>
                        <div class="property-footer">
                            <div class="property-price">${formatPrice(item)}</div>
                            <a href="${api.propertyRoute}?id=${item.id}" class="btn-view-property">Skatīt <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                `;
                wrap.appendChild(card);
            });
            if (window.initMiniCalendars) window.initMiniCalendars(wrap);

            list.forEach(item => {
                const id = parseInt(item.id || 0, 10) || 0;
                if (id > 0) favoriteIds.add(id);
            });
            syncFavoriteButtons();
        } catch (_) {
            empty.textContent = 'Neizdevās ielādēt favorītus.';
            empty.style.display = 'block';
        }
    }

    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#favorites-modal') {
            loadFavoritesIntoModal();
        }
    });
    if (window.location.hash === '#favorites-modal') {
        loadFavoritesIntoModal();
    }

    (function () {
        const apiUrl = document.body.getAttribute('data-homes-api') || '';
        if (!apiUrl) return;
        const pad2 = (n) => String(n).padStart(2, '0');
        const now = new Date();
        const y = now.getFullYear();
        const m = now.getMonth() + 1;
        const monthKey = `${y}-${pad2(m)}`;
        const monthStart = new Date(y, m - 1, 1);
        const monthEnd = new Date(y, m, 0);
        const startDow = (monthStart.getDay() + 6) % 7;
        const daysInMonth = monthEnd.getDate();
        const dateKey = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;

        const cells = [];
        for (let i = 0; i < startDow; i++) {
            const d = new Date(y, m - 1, 1 - (startDow - i));
            cells.push({ day: d.getDate(), key: dateKey(d), out: true });
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const d = new Date(y, m - 1, day);
            cells.push({ day, key: dateKey(d), out: false });
        }
        const total = Math.ceil(cells.length / 7) * 7;
        for (let i = 1; cells.length < total; i++) {
            const d = new Date(y, m - 1, daysInMonth + i);
            cells.push({ day: d.getDate(), key: dateKey(d), out: true });
        }

        const isTaken = (key, ranges) => {
            const ts = new Date(key + 'T00:00:00').getTime();
            for (const r of ranges) {
                if (!r.from || !r.to) continue;
                const a = new Date(r.from + 'T00:00:00').getTime();
                const b = new Date(r.to + 'T00:00:00').getTime();
                if (ts >= a && ts < b) return true;
            }
            return false;
        };

        window.initMiniCalendars = async (scope) => {
            const root = scope && scope.querySelectorAll ? scope : document;
            const minis = Array.from(root.querySelectorAll('.mini-calendar[data-home-id]'));
            if (minis.length === 0) return;
            await Promise.all(minis.map(async (el) => {
                if (el.getAttribute('data-inited') === '1') return;
                const id = parseInt(el.getAttribute('data-home-id') || '0', 10);
                if (!id) return;
                el.setAttribute('data-inited', '1');
                el.innerHTML = cells.map(c => `<div class="mini-day${c.out ? ' is-out' : ''}" data-date="${c.key}">${c.day}</div>`).join('');
                try {
                    const url = new URL(apiUrl, window.location.href);
                    url.searchParams.set('action', 'availability');
                    url.searchParams.set('home_id', String(id));
                    url.searchParams.set('month', monthKey);
                    const res = await fetch(url.toString(), { credentials: 'same-origin' });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || data.ok !== true) return;
                    const ranges = Array.isArray(data.ranges) ? data.ranges : [];
                    el.querySelectorAll('.mini-day').forEach(d => {
                        const k = d.getAttribute('data-date') || '';
                        if (k && isTaken(k, ranges)) d.classList.add('is-taken');
                    });
                } catch (_) {
                }
            }));
        };

        window.initMiniCalendars(document);
    })();

    loadFavoriteIds();
});
