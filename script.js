document.addEventListener('DOMContentLoaded', () => {

    // =========================================
    // --- 1. Custom Cursor Loģika ---
    // =========================================
    const cursor = document.querySelector('.custom-cursor');

    if (cursor) {
        document.addEventListener('mousemove', (e) => {
            // Iestata kursora pozīciju
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
        });

        // Efekts: Samazina apli, kad pele ir virs klikšķināma elementa
        const clickableElements = document.querySelectorAll('a, button, input[type="submit"], .btn-search, .btn-register, .btn-login, .mission-box, .value-item');

        clickableElements.forEach(el => {
            el.addEventListener('mouseenter', () => {
                cursor.style.transform = 'translate(-50%, -50%) scale(0.6)'; // Samazinām apli
                cursor.style.backgroundColor = '#2ecc71'; // Mainām krāsu uz zaļu
            });
            el.addEventListener('mouseleave', () => {
                cursor.style.transform = 'translate(-50%, -50%) scale(1)'; // Atjaunojam apli
                cursor.style.backgroundColor = 'var(--accent-color)'; // Atjaunojam krāsu uz akcenta krāsu
            });
        });
    }

    // =========================================
    // --- 2. H1 ANIME.JS Animācija (Tikai about.php) ---
    // =========================================
    // Pārliecina, ka animācija notiek tikai "Par mums" lapā
    if (document.querySelector('.ml6')) {
        var h1Wrapper = document.querySelector('.ml6 .letters');
        if (h1Wrapper) {
            // Ietin katru burtu span elementā (kā pieprasīts)
            h1Wrapper.innerHTML = h1Wrapper.textContent.replace(/\S/g, "<span class='letter'>$&</span>");

            // Animācijas laika josla: burti ieslīd no augšas un tad visa H1 pazūd (kā pieprasīts)
            anime.timeline({ loop: true })
                .add({
                    targets: '.ml6 .letter',
                    translateY: ["1.1em", 0],
                    translateZ: 0,
                    duration: 750,
                    delay: (el, i) => 50 * i // Lietojam 50*i kā pieprasīts
                }).add({
                    targets: '.ml6',
                    opacity: 0,
                    duration: 1000,
                    easing: "easeOutExpo",
                    delay: 1000
                });

            // Pārliecināmies, ka p elements ir redzams, jo H1 tagad ir animācijas cilpā
            const pElement = document.querySelector('.header-p-anim');
            if (pElement) {
                pElement.classList.add('visible');
            }
        }
    }

    // =========================================
    // --- 3. Pārējā Lapas Loģika ---
    // =========================================

    // --- Navbar maiņa ritinot (Sticky Navbar) ---
    const navbar = document.querySelector('.navbar');
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const authButtons = document.querySelector('.auth-buttons');

    if (hamburger && navLinks) {
        // Clone auth buttons for mobile if not already present in navLinks
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

        // Close menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                if (authButtons) authButtons.classList.remove('active');
                hamburger.querySelector('i').classList.add('fa-bars');
                hamburger.querySelector('i').classList.remove('fa-times');
            });
        });
    }

    const isIndexPage = window.location.pathname.endsWith('index.php') || window.location.pathname === '/' || window.location.pathname.endsWith('/');
    const isAboutPage = window.location.pathname.endsWith('about.php');
    const isHomesPage = window.location.pathname.endsWith('homes.php');
    const isOwnerPage = window.location.pathname.endsWith('owner.php');
    const wantsTransparentOnTop = isIndexPage || isAboutPage || isHomesPage || isOwnerPage;

    if (navbar) {
        // Initial state: transparent if page wants it
        if (!wantsTransparentOnTop) {
            navbar.classList.add('scrolled');
        }

        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                if (wantsTransparentOnTop) {
                    navbar.classList.remove('scrolled');
                }
            }
        });
    }

    // --- Elementu "Fade-in" animācija ritinot uz leju (Ieskaitot Timeline) ---
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

    // --- Fona attēlu automātiskā maiņa ar SLIDE efektu (Tikai index.php) ---
    function startBackgroundSlider() {
        const hero = document.querySelector('.hero');
        const heroContent = document.querySelector('.hero-content');

        const images = ['bg-1', 'bg-2', 'bg-3'];
        let currentImageIndex = 0;

        if (!hero || !heroContent) return;

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

    if (isIndexPage && document.querySelector('.hero')) {
        startBackgroundSlider();
    }

    // --- Statistiku skaitītāja animācija (Counter) ---
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

    // --- About page hero background slider (no background.jpg) ---
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

    // --- Homes page listings, filters, modal ---
    (function () {
        const resultsWrap = document.getElementById('homes-results');
        if (!resultsWrap) return; // only run on homes.php

        let listingsData = []; // Will be loaded from database
        const resultsCount = document.getElementById('results-count');

        const citySelect = document.getElementById('filter-city');
        const typeSelect = document.getElementById('filter-type');
        const priceInput = document.getElementById('filter-price');
        const bedsInput = document.getElementById('filter-beds');
        const applyBtn = document.getElementById('filter-apply');
        const heroApply = document.getElementById('filter-hero');
        const filterShell = document.querySelector('.filter-shell');
        const emptyMsg = document.getElementById('homes-empty');

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

        // Fetch homes from database
        async function loadHomes() {
            try {
                const response = await fetch('api/get_homes.php');
                if (!response.ok) throw new Error('Failed to fetch');
                listingsData = await response.json();
                populateCities();
                renderListings(listingsData);
                updateResultsCount(listingsData.length);
            } catch (error) {
                console.error('Error loading homes:', error);
                // Show empty state on error
                if (emptyMsg) emptyMsg.style.display = 'block';
                updateResultsCount(0);
            }
        }

        function updateResultsCount(count) {
            if (resultsCount) {
                resultsCount.textContent = count + ' rezultāt' + (count === 1 ? 's' : 'i');
            }
        }

        function populateCities() {
            // Clear existing options except the first one
            while (citySelect.options.length > 1) {
                citySelect.remove(1);
            }
            const cities = Array.from(new Set(listingsData.map(l => l.city))).sort();
            cities.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                citySelect.appendChild(opt);
            });
        }

        function formatPrice(item) {
            return item.type === 'rent' ? `${item.price.toLocaleString('lv-LV')} € / mēn` : `${item.price.toLocaleString('lv-LV')} €`;
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
                const card = document.createElement('div');
                card.className = 'property-card';
                card.innerHTML = `
                    <div class="property-image">
                        <img src="${item.image}" alt="${item.title}">
                        <span class="property-badge ${item.type === 'rent' ? 'rent' : 'sale'}">${item.badge}</span>
                        <button class="property-favorite" title="Pievienot favorītiem">
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
                        <div class="property-footer">
                            <span class="property-price">${formatPrice(item)}${item.type === 'rent' ? '' : ''}</span>
                            <a href="home.php?id=${item.id}" class="btn-view-property">Skatīt <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                `;
                resultsWrap.appendChild(card);
            });

            // Add favorite button functionality
            document.querySelectorAll('.property-favorite').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    btn.classList.toggle('active');
                    const icon = btn.querySelector('i');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                });
            });
        }

        function applyFilters() {
            const city = citySelect.value;
            const type = typeSelect.value;
            const price = priceInput.value ? parseInt(priceInput.value, 10) : null;
            const beds = bedsInput.value ? parseInt(bedsInput.value, 10) : null;

            const filtered = listingsData.filter(item => {
                if (city && item.city !== city) return false;
                if (type && item.type !== type) return false;
                if (price !== null && item.price > price) return false;
                if (beds !== null && item.beds < beds) return false;
                return true;
            });
            renderListings(filtered);
        }

        function openModal(id) {
            const item = listingsData.find(x => x.id === id);
            if (!item) return;
            modalImg.src = item.image;
            modalImg.alt = item.title;
            modalBadge.textContent = item.badge;
            modalBadge.className = `badge ${item.type === 'rent' ? 'rent' : 'sale'}`;
            modalTitle.textContent = item.title;
            modalLocation.querySelector('span').textContent = item.location;
            modalBeds.innerHTML = `<i class="fas fa-bed"></i> ${item.beds} guļamist.`;
            modalSize.innerHTML = `<i class="fas fa-ruler-combined"></i> ${item.size} m²`;
            modalDesc.textContent = item.desc;
            modalPrice.textContent = formatPrice(item);
            modalContact.href = 'mailto:info@homeestate.lv?subject=Interese%20par%20%C4%ABpa%C5%A1umu%20' + encodeURIComponent(item.title);
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Init - Load homes from database
        loadHomes();

        applyBtn.addEventListener('click', applyFilters);
        if (heroApply) {
            heroApply.addEventListener('click', () => {
                applyFilters();
                if (filterShell) {
                    filterShell.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }

        resultsWrap.addEventListener('click', (e) => {
            const target = e.target;
            if (target.matches('.btn-view-property') || target.closest('.btn-view-property')) {
                // allow default navigation to detailed page
                return;
            }
        });

        if (modalClose) modalClose.addEventListener('click', closeModal);
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.classList.contains('listing-modal__backdrop')) {
                    closeModal();
                }
            });
        }
    })();

    // --- Property detail pop-in animations ---
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

    // --- Property Premium v2 Tabs & Gallery ---
    (function () {
        if (!document.body.classList.contains('property-premium-v2')) return;

        // Tab functionality
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

        // Gallery functionality
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
});