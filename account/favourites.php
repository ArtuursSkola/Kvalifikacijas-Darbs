<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../includes/account.php';

$currentUser = loadCurrentUserContext($savienojums);
if (!$currentUser) {
    header('Location: ' . main_route('login'));
    exit;
}

$pageTitle = 'Favorīti - HomeEstate';
$extraStyles = ['homes'];
$bodyClass = 'favorites-page';
include __DIR__ . '/../includes/header.php';
?>

<section class="search-listings-section" style="padding-top: 30px;">
    <div class="results-header" style="margin-top: 0;">
        <h2>Favorīti</h2>
    </div>
    <div id="favorites-page-empty" style="display:none; font-weight:700; color: rgba(44, 62, 80, 0.7); padding: 10px 2px;">Nav favorītu.</div>
    <div id="favorites-page-results" class="listing-grid"></div>
</section>

<script>
document.addEventListener('DOMContentLoaded', async () => {
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
            const card = document.createElement('div');
            card.className = 'property-card';
            card.innerHTML = `
                <div class="property-image">
                    <img src="${item.image}" alt="${item.title}" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';">
                    <span class="property-badge ${item.type === 'rent' ? 'rent' : 'sale'}">${item.badge}</span>
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
                        <span class="property-price">${item.type === 'rent' ? `${Number(item.price || 0).toLocaleString('lv-LV')} € / mēn` : `${Number(item.price || 0).toLocaleString('lv-LV')} €`}</span>
                        <a href="${api.propertyRoute}?id=${item.id}" class="btn-view-property">Skatīt <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            `;
            wrap.appendChild(card);
        });
    } catch (_) {
        empty.textContent = 'Neizdevās ielādēt favorītus.';
        empty.style.display = 'block';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

