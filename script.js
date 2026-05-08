document.addEventListener('DOMContentLoaded', () => {
    const initConfig = () => {
        if (window.__homeest && window.__homeest.favoritesIdsApi) return;
        const nav = document.getElementById('navbar');
        if (nav) {
            window.__homeest = window.__homeest || {};
            window.__homeest.favoritesIdsApi = nav.getAttribute('data-fav-ids-api');
            window.__homeest.favoritesToggleApi = nav.getAttribute('data-fav-toggle-api');
            window.__homeest.favoritesApi = nav.getAttribute('data-fav-api');
            window.__homeest.loginUrl = nav.getAttribute('data-login-url');
            window.__homeest.propertyRoute = nav.getAttribute('data-property-route');
            window.__homeest.isLoggedIn = nav.getAttribute('data-logged-in') === 'true';
        }
    };
    initConfig();

    function formatPrice(item) {
        if (item.type === 'istermina_ire') return `${item.price.toLocaleString('lv-LV')} \u20ac / nakti`;
        return (item.type === 'ire' || item.type === 'rent') ? `${item.price.toLocaleString('lv-LV')} € / mēn` : `${item.price.toLocaleString('lv-LV')} €`;
    }

    function badgeClass(type) {
        if (type === 'ire' || type === 'rent') return 'rent';
        if (type === 'istermina_ire') return 'short-rent';
        return 'sale';
    }


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


        function renderListings(list) {
            resultsWrap.innerHTML = '';
            updateResultsCount(list.length);

            if (!list.length) {
                if (emptyMsg) emptyMsg.style.display = 'block';
                return;
            }
            if (emptyMsg) emptyMsg.style.display = 'none';

            list.forEach(item => {
                const shieldIcon = (item.owner_plan === 'Zelta' || item.owner_plan === 'Sudraba') ? '<i class="fas fa-shield-alt" style="color: #30b607; margin-left: 5px;" title="Uzticams īpašnieks"></i>' : '';
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
                if (verifiedOnly && !(item.owner_plan === 'Zelta' || item.owner_plan === 'Sudraba')) return false;
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
        } catch (e) {
            console.error(e);
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
                const shieldIcon = (item.owner_plan === 'Zelta' || item.owner_plan === 'Sudraba') ? '<i class="fas fa-shield-alt" style="color: #30b607; margin-left: 5px;" title="Uzticams īpašnieks"></i>' : '';
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


    (function() {
        const form = document.getElementById('newhome-form');
        if (!form) return;

        const steps = Array.from(document.querySelectorAll('.step'));
        const status = document.getElementById('step-status');
        const dealType = document.getElementById('deal-type');
        const priceLabel = document.getElementById('price-label');
        const propertyCategory = document.getElementById('property-category');
        const rentBlocks = document.querySelectorAll('.rent-only');
        const buyBlocks = document.querySelectorAll('.buy-only');
        const shortRentBlocks = document.querySelectorAll('.short-rent-only');
        const aptBlocks = document.querySelectorAll('.apartment-only');
        const floorTotalLabel = document.getElementById('floor-total-label');
        const nextBtns = document.querySelectorAll('.btn-next');
        const backBtns = document.querySelectorAll('.btn-back');
        
        const mainPriceInput = document.getElementById('main-price');
        const rentDisplay = document.getElementById('rent-price-display');
        const buyDisplay = document.getElementById('buy-price-display');
        const shortRentDisplay = document.getElementById('short-rent-price-display');
        const utilitiesInput = document.getElementById('utilities-price');
        const totalCalcInput = document.getElementById('total-price-calc');

        const hasPirts = document.getElementById('has-pirts');
        const pirtsWrap = document.getElementById('pirts-price-wrap');
        const pirtsPrice = document.getElementById('pirts-price-per-day');
        const hasBalla = document.getElementById('has-balla');
        const ballaWrap = document.getElementById('balla-price-wrap');
        const ballaPrice = document.getElementById('balla-price-per-day');
        
        const mainImageInput = document.getElementById('main-image-input');
        const mainImageUrlInput = document.getElementById('main-image-url');
        const mainPreview = document.getElementById('main-preview');
        const galleryInput = document.getElementById('gallery-input');
        const galleryPreview = document.getElementById('gallery-preview');
        const galleryCounterText = document.getElementById('gallery-counter-text');

        const body = document.body;
        const galleryLimit = parseInt(body.dataset.galleryLimit || '2', 10);
        let existingGallery = JSON.parse(body.dataset.galleryJson || '[]');
        const hasExistingMain = body.dataset.hasExistingMain === 'true';
        const appUrl = body.dataset.appUrl || '';

        const titleInput = form.querySelector('input[name="title"]');
        const cityInput = form.querySelector('input[name="city"]');
        const locationInput = form.querySelector('input[name="location_text"]');
        const addressInput = form.querySelector('input[name="address"]');

        const existingKeepInput = document.getElementById('existing-gallery-keep');

        let currentStep = 0;
        const stepNames = ['Pamatinformācija', 'Apraksti', 'Priekšrocības', 'Mediji', 'Cenas'];

        let galleryFiles = [];

        const setStep = (idx) => {
            steps.forEach((step, i) => step.classList.toggle('active', i === idx));
            currentStep = idx;
            if (status) {
                let name = stepNames[idx] || '';
                if (idx === 4 && dealType && dealType.value === 'istermina_ire') name = 'Rezervacijas info';
                status.textContent = `${idx + 1}/5: ${name}`;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };

        const cpLen = (v) => Array.from(String(v || '')).length;
        const lettersOnlyRe = /^[\p{L}\s]+$/u;
        const countLetters = (v) => (String(v || '').match(/\p{L}/gu) || []).length;
        const sliceCp = (v, max) => Array.from(String(v || '')).slice(0, max).join('');

        const sanitizeTextOnly = (el) => {
            if (!el) return;
            const maxLen = parseInt(el.getAttribute('maxlength') || '0', 10) || 0;
            let v = String(el.value || '');
            v = v.replace(/[^\p{L}\s]/gu, '');
            v = v.replace(/\s+/g, ' ');
            v = v.replace(/^\s+/, '');
            if (maxLen > 0 && cpLen(v) > maxLen) v = sliceCp(v, maxLen);
            el.value = v;
        };

        if (titleInput) {
            titleInput.addEventListener('input', () => sanitizeTextOnly(titleInput));
            titleInput.addEventListener('blur', () => { titleInput.value = String(titleInput.value || '').trim(); });
        }
        if (cityInput) {
            cityInput.addEventListener('input', () => sanitizeTextOnly(cityInput));
            cityInput.addEventListener('blur', () => { cityInput.value = String(cityInput.value || '').trim(); });
        }
        if (locationInput) {
            locationInput.addEventListener('input', () => sanitizeTextOnly(locationInput));
            locationInput.addEventListener('blur', () => { locationInput.value = String(locationInput.value || '').trim(); });
        }

        const validateStep = (idx) => {
            const step = steps[idx];
            if (!step) return true;
            let ok = true;

            const checkEl = (el) => {
                const visible = el.offsetParent !== null;
                if (!visible) return true;

                const val = String(el.value || '');
                const trimmed = val.trim();
                let good = true;

                if (el.getAttribute('data-required') === '1' && trimmed === '') good = false;

                const minLen = parseInt(el.getAttribute('data-minlen') || '0', 10) || 0;
                if (good && minLen > 0 && cpLen(trimmed) < minLen) good = false;

                if (good && (el.name === 'title' || el.name === 'city' || el.name === 'location_text')) {
                    const maxLen = parseInt(el.getAttribute('maxlength') || '0', 10) || 0;
                    if (!lettersOnlyRe.test(trimmed)) good = false;
                    if (maxLen > 0 && cpLen(trimmed) > maxLen) good = false;
                }

                if (good && el.name === 'address' && trimmed !== '') {
                    const maxLen = parseInt(el.getAttribute('maxlength') || '0', 10) || 0;
                    if (maxLen > 0 && cpLen(trimmed) > maxLen) good = false;
                    if (countLetters(trimmed) < 4) good = false;
                }

                if (!good) {
                    ok = false;
                    el.classList.add('invalid');
                } else {
                    el.classList.remove('invalid');
                }
                return good;
            };

            step.querySelectorAll('[data-required="1"], [data-minlen], input[name="title"], input[name="city"], input[name="location_text"], input[name="address"]').forEach(checkEl);

            if (idx === 3) {
                const hasFile = mainImageInput && mainImageInput.files && mainImageInput.files.length > 0;
                const hasUrl = mainImageUrlInput && String(mainImageUrlInput.value || '').trim() !== '';
                if (!hasFile && !hasUrl && !hasExistingMain) {
                    ok = false;
                    if (mainImageInput) mainImageInput.classList.add('invalid');
                    if (mainImageUrlInput) mainImageUrlInput.classList.add('invalid');
                } else {
                    if (mainImageInput) mainImageInput.classList.remove('invalid');
                    if (mainImageUrlInput) mainImageUrlInput.classList.remove('invalid');
                }
            }

            return ok;
        };

        const calculateTotal = () => {
            const p = parseFloat(mainPriceInput.value) || 0;
            const u = parseFloat(utilitiesInput.value) || 0;
            if (dealType.value === 'ire') {
                totalCalcInput.value = (p + u).toFixed(2);
            } else {
                totalCalcInput.value = p.toFixed(2);
            }
            if (rentDisplay) rentDisplay.value = p;
            if (buyDisplay) buyDisplay.value = p;
            if (shortRentDisplay) shortRentDisplay.value = p;
        };

        const toggleDealFields = () => {
            const mode = dealType.value;
            const isRent = mode === 'ire';
            const isBuy = mode === 'pardod';
            const isShort = mode === 'istermina_ire';

            if (priceLabel) {
                priceLabel.textContent = isRent ? 'Cena (EUR / men.) *' : (isShort ? 'Cena (EUR / nakti) *' : 'Cena (EUR) *');
            }

            rentBlocks.forEach(block => block.classList.toggle('hidden', !isRent));
            buyBlocks.forEach(block => block.classList.toggle('hidden', !isBuy));
            shortRentBlocks.forEach(block => block.classList.toggle('hidden', !isShort));

            if (!isRent && utilitiesInput) utilitiesInput.value = '0';
            calculateTotal();
            if (status && currentStep === 4) setStep(4);
        };

        const syncShortRentExtras = () => {
            if (hasPirts && pirtsWrap && pirtsPrice) {
                pirtsWrap.style.display = hasPirts.checked ? '' : 'none';
                if (hasPirts.checked) {
                    pirtsPrice.setAttribute('data-required', '1');
                } else {
                    pirtsPrice.removeAttribute('data-required');
                    pirtsPrice.value = '';
                }
            }
            if (hasBalla && ballaWrap && ballaPrice) {
                ballaWrap.style.display = hasBalla.checked ? '' : 'none';
                if (hasBalla.checked) {
                    ballaPrice.setAttribute('data-required', '1');
                } else {
                    ballaPrice.removeAttribute('data-required');
                    ballaPrice.value = '';
                }
            }
        };

        const toggleCategoryFields = () => {
            const cat = propertyCategory.value;
            const isApt = cat === 'dzivoklis';
            const isHouse = cat === 'maja';
            aptBlocks.forEach(block => block.classList.toggle('hidden', !isApt));
            if (isHouse) {
                floorTotalLabel.textContent = 'Stāvu skaits mājā';
            } else if (isApt) {
                floorTotalLabel.textContent = 'Stāvu skaits ēkā';
            } else {
                floorTotalLabel.textContent = 'Stāvu skaits';
            }
        };

        const renderGallery = () => {
            if (!galleryPreview) return;
            galleryPreview.innerHTML = '';

            existingGallery.forEach((url, index) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = appUrl + '/' + url;
                img.onclick = () => window.open(img.src, '_blank');
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.type = 'button';
                removeBtn.onclick = (e) => {
                    e.stopPropagation();
                    existingGallery.splice(index, 1);
                    renderGallery();
                };
                
                const badge = document.createElement('div');
                badge.style = "position:absolute; bottom:0; background:rgba(0,0,0,0.5); color:white; width:100%; font-size:9px; text-align:center; padding:2px;";
                badge.textContent = "Saglabāts";

                div.appendChild(img);
                div.appendChild(removeBtn);
                div.appendChild(badge);
                galleryPreview.appendChild(div);
            });

            galleryFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onclick = () => window.open(img.src, '_blank');
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.type = 'button';
                removeBtn.onclick = (e) => {
                    e.stopPropagation();
                    galleryFiles.splice(index, 1);
                    renderGallery();
                };
                
                div.appendChild(img);
                div.appendChild(removeBtn);
                galleryPreview.appendChild(div);
            });
            
            if (galleryCounterText) {
                const span = galleryCounterText.querySelector('span');
                if (span) span.textContent = existingGallery.length + galleryFiles.length;
            }
        };

        if (mainImageInput) {
            mainImageInput.addEventListener('change', () => {
                if (!mainPreview) return;
                mainPreview.innerHTML = '';
                if (mainImageInput.files && mainImageInput.files[0]) {
                    const file = mainImageInput.files[0];
                    const div = document.createElement('div');
                    div.className = 'preview-item main-preview-item';
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.onclick = () => window.open(img.src, '_blank');
                    div.appendChild(img);
                    mainPreview.appendChild(div);
                }
            });
        }

        if (mainImageUrlInput) {
            mainImageUrlInput.addEventListener('input', () => {
                if (!mainPreview) return;
                if (mainImageUrlInput.value.trim() !== '') {
                    mainPreview.innerHTML = `<div class="preview-item main-preview-item"><img src="${mainImageUrlInput.value}" onerror="this.src='https://via.placeholder.com/300x180?text=Invalid+URL'"></div>`;
                }
            });
        }

        if (galleryInput) {
            galleryInput.addEventListener('change', () => {
                const newFiles = Array.from(galleryInput.files);
                newFiles.forEach(file => {
                    if ((existingGallery.length + galleryFiles.length) < galleryLimit) {
                        const exists = galleryFiles.some(f => f.name === file.name && f.size === file.size);
                        if (!exists) galleryFiles.push(file);
                    }
                });
                
                galleryInput.value = '';
                renderGallery();
            });
        }

        form.addEventListener('submit', (e) => {
            for (let i = 0; i < steps.length; i++) {
                if (!validateStep(i)) {
                    e.preventDefault();
                    setStep(i);
                    const first = steps[i].querySelector('.invalid');
                    if (first) first.focus();
                    return;
                }
            }
            if (galleryFiles.length > 0 && galleryInput) {
                const dt = new DataTransfer();
                galleryFiles.forEach(file => dt.items.add(file));
                galleryInput.files = dt.files;
            }
            if (existingKeepInput) {
                existingKeepInput.value = JSON.stringify(existingGallery);
            }
        });

        if (mainPriceInput) mainPriceInput.addEventListener('input', calculateTotal);
        if (utilitiesInput) utilitiesInput.addEventListener('input', calculateTotal);

        nextBtns.forEach(btn => btn.addEventListener('click', () => {
            const target = parseInt(btn.dataset.next, 10) - 1;
            if (validateStep(currentStep)) {
                setStep(target);
            } else {
                const step = steps[currentStep];
                const first = step ? step.querySelector('.invalid') : null;
                if (first) first.focus();
            }
        }));

        backBtns.forEach(btn => btn.addEventListener('click', () => {
            const target = parseInt(btn.dataset.prev, 10) - 1;
            setStep(target);
        }));

        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('input', () => el.classList.remove('invalid'));
            el.addEventListener('change', () => el.classList.remove('invalid'));
        });

        if (dealType) dealType.addEventListener('change', toggleDealFields);
        if (propertyCategory) propertyCategory.addEventListener('change', toggleCategoryFields);
        if (hasPirts) hasPirts.addEventListener('change', syncShortRentExtras);
        if (hasBalla) hasBalla.addEventListener('change', syncShortRentExtras);

        toggleDealFields();
        syncShortRentExtras();
        toggleCategoryFields();
        renderGallery();
        setStep(0);
    })();


    (function() {
        const detailPage = document.querySelector('.property-detail-page');
        if (!detailPage) return;

        document.querySelectorAll('.tab-link').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });

        window.changeImage = function(thumb) {
            const main = document.getElementById('gallery-main');
            if (main) {
                main.src = thumb.src.replace('w=600', 'w=1200');
                document.querySelectorAll('.thumb-images img').forEach(img => img.classList.remove('active'));
                thumb.classList.add('active');
            }
        };

        const api = document.body.getAttribute('data-homes-api') || '';
        const homeId = parseInt(document.body.getAttribute('data-home-id') || '0', 10);
        const homeType = (document.body.getAttribute('data-home-type') || '').trim();
        const cal = document.getElementById('sidebar-calendar');
        const modal = document.getElementById('application-form');

        if (api && homeId) {
            (async function () {
                if (!cal || homeType !== 'istermina_ire') return;
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

                cal.innerHTML = `<div class="sidebar-calendar__grid">${cells.map(c => `<div class="sidebar-day${c.out ? ' is-out' : ''}" data-date="${c.key}">${c.day}</div>`).join('')}</div>`;

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

                try {
                    const url = new URL(api, window.location.href);
                    url.searchParams.set('action', 'availability');
                    url.searchParams.set('home_id', String(homeId));
                    url.searchParams.set('month', monthKey);
                    const res = await fetch(url.toString(), { credentials: 'same-origin' });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || data.ok !== true) return;
                    const ranges = Array.isArray(data.ranges) ? data.ranges : [];
                    cal.querySelectorAll('.sidebar-day').forEach(d => {
                        const k = d.getAttribute('data-date') || '';
                        if (k && isTaken(k, ranges)) d.classList.add('is-taken');
                    });
                } catch (_) {
                }
            })();

            if (modal) {
                const alertBox = document.getElementById('application-alert');
                const showAlert = (msg, ok) => {
                    if (!alertBox) return;
                    alertBox.style.display = 'block';
                    alertBox.style.border = ok ? '1px solid rgba(48,182,7,0.35)' : '1px solid rgba(231,76,60,0.35)';
                    alertBox.style.background = ok ? 'rgba(48,182,7,0.08)' : 'rgba(231,76,60,0.08)';
                    alertBox.style.color = ok ? '#1f7a1f' : '#b02014';
                    alertBox.textContent = msg;
                };

                const submitBtn = modal.querySelector('.btn-submit');
                if (submitBtn) {
                    submitBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        if (alertBox) alertBox.style.display = 'none';

                        const fd = new FormData();
                        fd.set('action', 'pieteikums_create');
                        fd.set('home_id', String(homeId));

                        const pick = (name) => {
                            const el = modal.querySelector(`[name="${name}"]`);
                            return el ? (el.value || '').trim() : '';
                        };

                        let vards = '';
                        let epasts = '';
                        let telefons = '';
                        let komentars = '';

                        if (homeType === 'ire') {
                            vards = pick('lt_full_name');
                            epasts = pick('lt_email');
                            telefons = pick('lt_phone');
                            komentars = (modal.querySelector('[name="lt_comment"]')?.value || '').trim();
                            fd.set('ires_menesi', pick('lt_rent_months'));
                            fd.set('nav_zinams', modal.querySelector('[name="lt_rent_unknown"]')?.checked ? '1' : '0');
                            fd.set('ires_sakuma_datums', pick('lt_start_date'));
                        } else if (homeType === 'istermina_ire') {
                            vards = pick('st_full_name');
                            epasts = pick('st_email');
                            telefons = pick('st_phone');
                            komentars = (modal.querySelector('[name="st_comment"]')?.value || '').trim();
                            fd.set('sakuma_datums', pick('st_start_date'));
                            fd.set('beigu_datums', pick('st_end_date'));
                        } else {
                            vards = pick('sale_full_name');
                            epasts = pick('sale_email');
                            telefons = pick('sale_phone');
                            komentars = pick('sale_comment');
                            fd.set('piedavata_summa', pick('sale_offer'));
                            fd.set('finansesanas_veids', (document.getElementById('pay-method')?.value || '').trim());
                        }

                        fd.set('vards_uzvards', vards);
                        fd.set('epasts', epasts);
                        fd.set('telefons', telefons);
                        fd.set('komentars', komentars);

                        try {
                            const res = await fetch(api, { method: 'POST', body: fd, credentials: 'same-origin' });
                            const data = await res.json().catch(() => null);
                            if (!res.ok || !data || data.ok !== true) {
                                showAlert((data && data.error) ? data.error : 'Neizdevās nosūtīt pieteikumu.', false);
                                return;
                            }
                            showPageAlert('Pieteikums veiksmīgi nosūtīts', 'success');
                            setTimeout(() => { window.location.hash = '#'; }, 700);
                        } catch (_) {
                            showAlert('Neizdevās nosūtīt pieteikumu.', false);
                        }
                    });
                }
            }
        }
    })();


    (function() {
        if (!document.querySelector('.favorites-page')) return;

        (async function() {
            const api = (window.__homeest || {});
            const wrap = document.getElementById('favorites-page-results');
            const empty = document.getElementById('favorites-page-empty');
            if (!wrap || !empty || !api.favoritesApi) return;
            try {
                const res = await fetch(api.favoritesApi, { credentials: 'same-origin' });
                const list = await res.json();
                if (!Array.isArray(list) || list.length === 0) {
                    empty.style.display = 'block';
                    return;
                }
                list.forEach(item => {
                    const isSold = item.status === 'Pardots';
                    const card = document.createElement('div');
                    card.className = `property-card ${isSold ? 'sold' : ''}`;
                    card.innerHTML = `
                        <div class="property-image">
                            ${isSold ? '<div class="status-sold-label">Pārdots</div>' : ''}
                            <img src="${item.image}" alt="${item.title}" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';">
                            <span class="property-badge ${item.type === 'ire' ? 'ire' : 'sale'}">${item.badge}</span>
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
                            <div class="property-footer">
                                <span class="property-price">${item.type === 'ire' ? `${Number(item.price || 0).toLocaleString('lv-LV')} € / mēn` : `${Number(item.price || 0).toLocaleString('lv-LV')} €`}</span>
                                ${isSold ? '<span class="btn-view-property disabled" style="background:#ccc;cursor:default;">Izslēgts</span>' : `<a href="${api.propertyRoute}?id=${item.id}" class="btn-view-property">Skatīt <i class="fas fa-arrow-right"></i></a>`}
                            </div>
                        </div>
                    `;
                    wrap.appendChild(card);
                });
            } catch (_) {
                empty.textContent = 'Neizdevās ielādēt favorītus.';
                empty.style.display = 'block';
            }
        })();
    })();


    (function() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        window.openModal = function(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('active');
        };

        window.closeModal = function(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('active');
        };

        window.openEditModal = function(data) {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val;
            };

            setVal('edit_id', data.id || data.admin_id || data.lietotaja_id || '');
            setVal('edit_title', data.nosaukums || '');
            setVal('edit_city', data.pilseta || '');
            setVal('edit_price', data.cena || '');
            setVal('edit_type', data.veids || '');
            setVal('edit_status', data.statuss || '');
            setVal('edit_description', data.apraksts || '');
            setVal('edit_username', data.lietotajvards || '');
            setVal('edit_email', data.epasts || '');
            setVal('edit_role', data.loma || '');
            setVal('edit_plan', data.plans || data.plan || '');
            setVal('edit_password', '');

            openModal('editModal');
        };

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });

        const autoModal = document.body.getAttribute('data-auto-modal');
        if (autoModal) {
            if (autoModal === 'edit' && document.body.hasAttribute('data-auto-modal-data')) {
                try {
                    const data = JSON.parse(document.body.getAttribute('data-auto-modal-data'));
                    openEditModal(data);
                } catch(_) {}
            } else {
                openModal(autoModal);
            }
        }
    })();


    (function() {
        const radios = document.querySelectorAll('input[name="role"]');
        const note = document.getElementById('role-note-register');
        if (!note || !radios.length) return;
        const update = () => {
            const val = Array.from(radios).find(r => r.checked)?.value;
            note.textContent = val === 'ipasnieks' ? 'Pašlaik izvēlēts: Īpašnieks' : 'Pašlaik izvēlēts: Lietotājs';
        };
        radios.forEach(r => r.addEventListener('change', update));
        update();
    })();


    (function() {
        const fadeElements = document.querySelectorAll('.fade-up');
        if (!fadeElements.length) return;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        fadeElements.forEach(el => observer.observe(el));
    })();

    (function() {
        const cards = document.querySelectorAll('.mission-card, .value-card');
        if (!cards.length) return;
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left - rect.width / 2) / rect.width;
                const y = (e.clientY - rect.top - rect.height / 2) / rect.height;
                card.style.transform = `translateY(-8px) perspective(1000px) rotateX(${y * -5}deg) rotateY(${x * 5}deg)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    })();

    loadFavoriteIds();
});

// FAQ Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    var overlay  = document.getElementById('faqOverlay');
    var openBtn  = document.getElementById('faqOpenBtn');
    var closeBtn = document.getElementById('faqCloseBtn');

    function openModal() {
        if (!overlay) return;
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        if (!overlay) return;
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    var textarea  = document.getElementById('faqApraksts');
    var charCount = document.getElementById('faqCharCount');
    if (textarea && charCount) {
        textarea.addEventListener('input', function () { charCount.textContent = this.value.length; });
    }

    var fileInput   = document.getElementById('faqFails');
    var previewsBox = document.getElementById('faqPreviews');
    var uploadZone  = document.getElementById('faqUploadZone');
    var selFiles    = [];

    function renderPreviews() {
        if (!previewsBox) return;
        previewsBox.innerHTML = '';
        selFiles.forEach(function (file, idx) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var wrap = document.createElement('div');
                wrap.className = 'palidziba-upload-preview';
                var img = document.createElement('img');
                img.src = e.target.result;
                var rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'palidziba-upload-preview__remove';
                rm.innerHTML = '<i class="fas fa-times"></i>';
                rm.onclick = function () { selFiles.splice(idx, 1); renderPreviews(); syncInput(); };
                wrap.appendChild(img); wrap.appendChild(rm);
                previewsBox.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    }
    function syncInput() {
        if (!fileInput) return;
        var dt = new DataTransfer();
        selFiles.forEach(function (f) { dt.items.add(f); });
        fileInput.files = dt.files;
    }
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var files = Array.from(fileInput.files);
            files.forEach(function (file) {
                if (file.type.startsWith('image/')) {
                    selFiles.push(file);
                }
            });
            renderPreviews();
        });
    }

    var faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(function (item) {
        var trigger = item.querySelector('.faq-item__trigger');
        if (!trigger) return;
        trigger.addEventListener('click', function () {
            var isOpen = item.classList.contains('open');
            faqItems.forEach(function (i) { i.classList.remove('open'); });
            if (!isOpen) item.classList.add('open');
        });
    });

    // FAQ form submission
    var form = document.getElementById('faqForm');
    var alertBox  = document.getElementById('faqAlert');
    var apiUrl    = '/api/submit_palidziba';

    function showAlert(msg, type) {
        if (!alertBox) return;
        alertBox.className = 'palidziba-alert palidziba-alert--' + type;
        alertBox.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
        alertBox.style.display = 'flex';
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (alertBox) alertBox.style.display = 'none';
            var tema = document.getElementById('faqTema');
            var apraksts = document.getElementById('faqApraksts');
            if (!tema || tema.value === '') { showAlert('Lūdzu izvēlieties tēmu.', 'error'); return; }
            if (!apraksts || apraksts.value.trim() === '') { showAlert('Lūdzu aizpildiet jautājuma aprakstu.', 'error'); return; }
            var fd = new FormData(form);
            fd.append('files', JSON.stringify(selFiles.map(function(f) { return {name: f.name, size: f.size, type: f.type}; })));
            var submitBtn = document.getElementById('faqSubmit');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Nosūta...'; }
            fetch(apiUrl, {method: 'POST', body: fd})
                .then(function (d) {
                    if (d.success) {
                        closeModal();
                        showPageAlert('Jūsu jautājums ir veiksmīgi nosūtīts! Mēs ar jums sazināsimies drīzumā.', 'success');
                        form.reset(); selFiles = []; if (previewsBox) previewsBox.innerHTML = ''; if (charCount) charCount.textContent = '0';
                    } else {
                        showAlert(d.message || 'Kļūda.', 'error');
                    }
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Nosūtīt sūdzību'; }
                })
                .catch(function (err) {
                    console.error('Fetch error:', err);
                    showAlert('Sistēmas kļūda. Mēģiniet vēlāk.', 'error');
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Nosūtīt sūdzību'; }
                });
        });
    };
});

// Admin Palidziba JavaScript
if (document.getElementById('replyOverlay')) {
    function openReplyModal(id, question, existing) {
        document.getElementById('replyMsgId').value = id;
        document.getElementById('replyQuestion').innerText = question;
        document.getElementById('replyText').value = existing || '';
        document.getElementById('replyOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    
    function closeReplyModal() {
        document.getElementById('replyOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }
    
    function closeLightbox() {
        document.getElementById('imgLightbox').classList.remove('open');
    }
    
    document.getElementById('replyOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeReplyModal();
    });
}





