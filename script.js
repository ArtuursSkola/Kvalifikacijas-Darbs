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
            anime.timeline({loop: true})
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
    const isIndexPage = window.location.pathname.endsWith('index.php') || window.location.pathname === '/';
    const isAboutPage = window.location.pathname.endsWith('about.php');
    const wantsTransparentOnTop = isIndexPage || isAboutPage;
    
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

    const appearOnScroll = new IntersectionObserver(function(entries, appearOnScroll) {
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
    (function() {
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
});