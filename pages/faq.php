<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../con_db.php';

$isLoggedIn = !empty($_SESSION['user_id']);

$bujEntries = [];
try {
    $bujStmt = $savienojums->prepare(
        "SELECT tema, jautajuma_apraksts, atbilde
         FROM est_palidziba
         WHERE radata_buj = 1 AND statuss = 'Atbildēts'
         ORDER BY created_at DESC"
    );
    if ($bujStmt) {
        $bujStmt->execute();
        $bujRes = $bujStmt->get_result();
        while ($row = $bujRes->fetch_assoc()) $bujEntries[] = $row;
        $bujStmt->close();
    }
} catch (\Exception $e) {
    $bujEntries = [];
}

$pageTitle   = 'Biežāk uzdotie jautājumi - HomeEstate';
$extraStyles = ['faq'];
$bodyClass   = 'faq-page';
include __DIR__ . '/../includes/header.php';
?>

<section class="faq-hero">
    <div class="faq-hero__inner">
        <div class="faq-hero__label">
            <i class="fas fa-circle-question"></i>
            Atbalsts
        </div>
        <h1>Biežāk uzdotie <span>jautājumi</span></h1>
        <p class="faq-hero__sub">Atrodiet atbildes uz visbiežāk uzdotajiem jautājumiem par HomeEstate platformu.</p>
    </div>
</section>

<div class="faq-body">

    <aside>
        <div class="faq-contact-card">
            <div class="faq-contact-card__icon">
                <i class="fas fa-headset"></i>
            </div>
            <div class="faq-contact-card__eyebrow">Neatradāt atbildi?</div>
            <div class="faq-contact-card__divider"></div>
            <h2>Mēs labprāt ar jums sazināsimies</h2>
            <p>Mūsu atbalsta komanda ir gatava palīdzēt jums ar jebkuru jautājumu. Nosūtiet mums ziņu un mēs atbildēsim pēc iespējas ātrāk.</p>
            <button class="faq-contact-btn" id="faqOpenBtn" type="button">
                <i class="fas fa-paper-plane"></i>
                Sazināties
            </button>
        </div>
    </aside>

    <main>
        <div class="faq-right">
            <div>
                <p class="faq-section-label">Jautājumi un atbildes</p>
                <h2 class="faq-section-title">Kā mēs varam palīdzēt?</h2>
                <p class="faq-section-sub">Noklikšķiniet uz jautājuma, lai skatītu atbildi.</p>
            </div>

            <div class="faq-list">

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Kā es varu reģistrēties HomeEstate?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>Noklikšķiniet uz pogas "Reģistrēties" augšējā navigācijas joslā. Aizpildiet veidlapu ar savu vārdu, e-pasta adresi un paroli. Pēc reģistrācijas jūs varēsiet pieteikties un izmantot visas platformas funkcijas.</p></div>
                </div>

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Kā kļūt par īpašnieku un publicēt sludinājumu?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>Pēc reģistrācijas dodieties uz konta iestatījumiem un aktivizējiet īpašnieka statusu. Pēc tam varēsiet izvēlēties plānu (Bezmaksas, Sudraba vai Zelta) un publicēt savus īpašumus platformā.</p></div>
                </div>

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Kādi maksājumu veidi ir pieejami?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>HomeEstate atbalsta drošus tiešsaistes maksājumus ar Stripe starpniecību. Varat norēķināties ar Visa, Mastercard un citām populārām maksājumu kartēm. Visi darījumi ir šifrēti un droši.</p></div>
                </div>

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Kā darbojas rezervācijas sistēma?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>Atrodiet interesējošo īpašumu un noklikšķiniet uz "Rezervēt". Izvēlieties vēlamās datumus un apstipriniet rezervāciju. Īpašnieks saņems paziņojumu un apstiprinās jūsu pieprasījumu.</p></div>
                </div>

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Ko nozīmē dažādie plāni (Bezmaksas, Sudraba, Zelta)?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>Bezmaksas plāns ļauj publicēt ierobežotu skaitu sludinājumu. Sudraba un Zelta plāni nodrošina vairāk sludinājumu, prioritāru parādīšanu meklēšanā un papildu funkcijas. Detalizētu salīdzinājumu skatiet lapā "Īpašniekiem".</p></div>
                </div>

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Kā rediģēt vai dzēst savu sludinājumu?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>Dodieties uz sadaļu "Mani sludinājumi" savā profilā. Tur redzēsiet visus savus publicētos sludinājumus ar rediģēšanas un dzēšanas opcijām.</p></div>
                </div>

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Vai mani personas dati ir droši?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>Jā. Mēs ievērojam stingras datu aizsardzības normas. Jūsu personas dati netiek pārdoti trešajām pusēm. Visi sensitīvie dati tiek šifrēti. Plašāku informāciju skatiet mūsu Privātuma politikā.</p></div>
                </div>

                <div class="faq-item">
                    <div class="faq-item__trigger">
                        <span class="faq-item__q">Kā mainīt konta paroli?</span>
                        <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-item__body"><p>Dodieties uz "Konta iestatījumi" un atrodiet sadaļu "Drošība". Ievadiet savu pašreizējo paroli, pēc tam jauno paroli un apstipriniet to.</p></div>
                </div>

                <?php if (!empty($bujEntries)): ?>
                    <?php foreach ($bujEntries as $buj): ?>
                    <div class="faq-item faq-item--buj">
                        <div class="faq-item__trigger">
                            <span class="faq-item__q">
                                <span class="faq-buj-badge"><i class="fas fa-star"></i> <?php echo htmlspecialchars($buj['tema']); ?></span>
                                <?php echo htmlspecialchars($buj['jautajuma_apraksts']); ?>
                            </span>
                            <div class="faq-item__icon"><i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div class="faq-item__body">
                            <p><?php echo nl2br(htmlspecialchars($buj['atbilde'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </main>
</div>

<div class="palidziba-overlay" id="faqOverlay" role="dialog" aria-modal="true" aria-labelledby="faqModalTitle">
    <div class="palidziba-modal" id="faqModal">
        <div class="palidziba-modal__header">
            <div class="palidziba-modal__header-text">
                <h3 id="faqModalTitle">Sazināties ar atbalstu</h3>
                <p>Aizpildiet veidlapu un mēs atbildēsim pēc iespējas ātrāk.</p>
            </div>
            <button class="palidziba-modal__close" id="faqCloseBtn" type="button" aria-label="Aizvērt">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="palidziba-modal__body">
            <?php if (!$isLoggedIn): ?>
            <div class="palidziba-login-notice">
                <i class="fas fa-lock"></i>
                <p>Lai nosūtītu jautājumu, lūdzu <a href="<?php echo main_route('login'); ?>">piesakieties</a> vai <a href="<?php echo main_route('register'); ?>">reģistrējieties</a>.</p>
            </div>
            <?php else: ?>
            <div id="faqAlert" style="display:none;margin-bottom:16px;"></div>
            <form class="palidziba-form" id="faqForm" enctype="multipart/form-data" novalidate>
                <div class="palidziba-field">
                    <label for="faqTema">Tēma <span class="required-star">*</span></label>
                    <select id="faqTema" name="tema" required>
                        <option value="" disabled selected>Izvēlieties tēmu...</option>
                        <option value="Maksājumi">Maksājumi</option>
                        <option value="Rezervācijas">Rezervācijas</option>
                        <option value="Profils">Profils</option>
                        <option value="Mans sludinājums">Mans sludinājums</option>
                        <option value="Cits">Cits</option>
                    </select>
                </div>
                <div class="palidziba-field">
                    <label for="faqApraksts">Jautājuma apraksts <span class="required-star">*</span></label>
                    <textarea id="faqApraksts" name="jautajuma_apraksts" maxlength="1000" placeholder="Detalizēts jautājuma teksts" required></textarea>
                    <div class="palidziba-field__hint"><span id="faqCharCount">0</span> / 1000</div>
                </div>
                <div class="palidziba-field">
                    <label>Pievienotais fails <span style="color:var(--gray-400);font-weight:500;">(neobligāts)</span></label>
                    <div class="palidziba-upload-zone" id="faqUploadZone">
                        <input type="file" id="faqFails" name="pievienotais_fails[]" accept="image/jpeg,image/png" multiple>
                        <div class="palidziba-upload-zone__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <p>Noklikšķiniet vai velciet attēlus šeit</p>
                        <span>JPG, PNG &bull; maks. 3 attēli &bull; maks. 5 MB katrs</span>
                    </div>
                    <div class="palidziba-upload-previews" id="faqPreviews"></div>
                </div>
                <button type="submit" class="palidziba-submit" id="faqSubmit">
                    <i class="fas fa-paper-plane"></i> Nosūtīt sūdzību
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

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

            if (openBtn)  openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (overlay)  overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
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
                    Array.from(this.files).forEach(function (f) { if (selFiles.length < 3) selFiles.push(f); });
                    renderPreviews(); syncInput();
                });
            }
            if (uploadZone) {
                uploadZone.addEventListener('dragover',  function (e) { e.preventDefault(); this.classList.add('drag-over'); });
                uploadZone.addEventListener('dragleave', function ()  { this.classList.remove('drag-over'); });
                uploadZone.addEventListener('drop',      function (e) {
                    e.preventDefault(); this.classList.remove('drag-over');
                    Array.from(e.dataTransfer.files).forEach(function (f) { if (selFiles.length < 3) selFiles.push(f); });
                    renderPreviews(); syncInput();
                });
            }

            var form      = document.getElementById('faqForm');
            var submitBtn = document.getElementById('faqSubmit');
            var alertBox  = document.getElementById('faqAlert');
            var apiUrl    = '<?php echo main_route('api.submit_palidziba'); ?>';

            function showAlert(msg, type) {
                if (!alertBox) return;
                alertBox.className = 'palidziba-alert palidziba-alert--' + type;
                alertBox.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
                alertBox.style.display = 'flex';
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function showPageAlert(msg, type) {
                var alertDiv = document.createElement('div');
                alertDiv.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;padding:16px 24px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);font-family:"Poppins",sans-serif;font-size:.95rem;font-weight:500;animation:slideInDown .4s ease;max-width:90%;';
                if (type === 'success') {
                    alertDiv.style.color = '#166534';
                    alertDiv.style.border = '1px solid #bbf7d0';
                    alertDiv.style.background = '#dcfce7';
                } else {
                    alertDiv.style.color = '#991b1b';
                    alertDiv.style.border = '1px solid #fecaca';
                    alertDiv.style.background = '#fef2f2';
                }
                alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
                document.body.appendChild(alertDiv);
                setTimeout(function() {
                    if (alertDiv.parentNode) alertDiv.parentNode.removeChild(alertDiv);
                }, 5000);
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
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Nosūta...'; }
                    fetch(apiUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
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
                        .catch(function () {
                            showAlert('Savienojuma kļūda.', 'error');
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Nosūtīt sūdzību'; }
                        });
                });
            }

        });
    </script>
    <script>
        function initFaq() {
            const items = document.querySelectorAll('.faq-item');

            items.forEach(item => {
                const trigger = item.querySelector('.faq-item__trigger');

                trigger.addEventListener('click', function (e) {
                    e.preventDefault();

                    const isOpen = item.classList.contains('open');


                    items.forEach(i => i.classList.remove('open'));


                    if (!isOpen) {
                        item.classList.add('open');
                    }
                });
            });
        }
    </script>

<?php include __DIR__ . '/../includes/footer.php'; ?>