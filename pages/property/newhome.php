<?php
session_start();
require_once __DIR__ . '/../../routes/main.php';

// Only logged-in owners with paid plan can create listings.
$plan = $_SESSION['plan'] ?? '';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'ipasnieks' || !in_array($plan, ['Silver', 'Gold'], true)) {
    header('Location: ' . main_route('owner') . '#plans');
    exit;
}

require_once dirname(__DIR__, 2) . '/con_db.php';

$errors = [];
$success = '';

// Ensure the table has the columns we expect (avoids Unknown column errors on shared hosts).
ensureColumns($savienojums, 'est_homes', $errors);

// Ensure required columns exist (shared hosting safety net).
function ensureColumns(mysqli $conn, string $table, array &$errors): void
{
    $dbRes = $conn->query('SELECT DATABASE() as db');
    if (!$dbRes) {
        $errors[] = 'Neizdevās noteikt datubāzi: ' . $conn->error;
        return;
    }
    $dbRow = $dbRes->fetch_assoc();
    $db = $dbRow['db'] ?? '';
    if ($db === '') {
        $errors[] = 'Datubāzes nosaukums nav pieejams.';
        return;
    }

    $defs = [
        'owner_id' => 'INT NOT NULL DEFAULT 0',
        'property_category' => "VARCHAR(50) NOT NULL DEFAULT 'apartment'",
        'address' => 'VARCHAR(255) DEFAULT NULL',
        'location_text' => 'TEXT',
        'layout_text' => 'TEXT',
        'map_text' => 'TEXT',
        'amenities' => 'TEXT',
        'main_image' => 'VARCHAR(255) NOT NULL',
        'thumb1' => 'VARCHAR(255) DEFAULT NULL',
        'thumb2' => 'VARCHAR(255) DEFAULT NULL',
        'thumb3' => 'VARCHAR(255) DEFAULT NULL',
        'floor_info' => 'VARCHAR(20) DEFAULT NULL',
        'rent_price' => 'DECIMAL(12,2) DEFAULT 0',
        'utilities_price' => 'DECIMAL(12,2) DEFAULT 0',
        'total_price' => 'DECIMAL(12,2) DEFAULT 0',
    ];

    foreach ($defs as $col => $definition) {
        $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
        if (!$stmt) {
            $errors[] = 'Kolonnu pārbaude neizdevās: ' . $conn->error;
            return;
        }
        $stmt->bind_param('sss', $db, $table, $col);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : ['cnt' => 0];
        $stmt->close();

        if ((int)($row['cnt'] ?? 0) === 0) {
            $alterSql = "ALTER TABLE `" . $conn->real_escape_string($table) . "` ADD COLUMN `$col` $definition";
            if (!$conn->query($alterSql)) {
                $errors[] = "Neizdevās pievienot kolonnu $col: " . $conn->error;
                return;
            }
        }
    }
}

// Upload helper for image inputs (file preferred, URL as fallback).
$uploadDir = dirname(__DIR__, 2) . '/uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

function handleUploadOrUrl(string $fileKey, string $fallbackUrl, string $uploadDir): string
{
    if (isset($_FILES[$fileKey]) && is_array($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[$fileKey]['tmp_name'];
        $origName = basename((string)$_FILES[$fileKey]['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return '';
        }
        $safeName = uniqid('img_', true) . '.' . $ext;
        $targetPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($tmpName, $targetPath)) {
            return 'uploads/' . $safeName;
        }
    }

    return trim((string)$fallbackUrl);
}

// Initialize form fields to avoid undefined notices on first load.
$title = $city = $address = $location_text = $price = $area = $bedrooms = $bathrooms = '';
$floor = $total_floors = '';
$description = $layout_text = $map_text = $amenities = '';
$property_category = 'apartment';
$type = 'rent';
$main_image = $thumb1 = $thumb2 = $thumb3 = '';
$main_image_url = $thumb1_url = $thumb2_url = $thumb3_url = '';
$rent_price = $utilities_price = $total_price = '';
$price_label = 'Cena (EUR / men.) *';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $location_text = trim((string)($_POST['location_text'] ?? ''));
    $property_category = (string)($_POST['property_category'] ?? 'apartment');
    $type = ((string)($_POST['type'] ?? '')) === 'buy' ? 'buy' : 'rent';
    $price_label = $type === 'rent' ? 'Cena (EUR / men.) *' : 'Cena (EUR) *';
    $price = trim((string)($_POST['price'] ?? ''));
    $area = trim((string)($_POST['area'] ?? ''));
    $bedrooms = trim((string)($_POST['bedrooms'] ?? ''));
    $bathrooms = trim((string)($_POST['bathrooms'] ?? ''));
    $floor = trim((string)($_POST['floor'] ?? ''));
    $total_floors = trim((string)($_POST['total_floors'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $layout_text = trim((string)($_POST['layout_text'] ?? ''));
    $map_text = trim((string)($_POST['map_text'] ?? ''));
    $amenities = trim((string)($_POST['amenities'] ?? ''));

    $main_image_url = trim((string)($_POST['main_image_url'] ?? ''));
    $thumb1_url = trim((string)($_POST['thumb1_url'] ?? ''));
    $thumb2_url = trim((string)($_POST['thumb2_url'] ?? ''));
    $thumb3_url = trim((string)($_POST['thumb3_url'] ?? ''));

    $rent_price = trim((string)($_POST['rent_price'] ?? ''));
    $utilities_price = trim((string)($_POST['utilities_price'] ?? ''));
    $total_price = trim((string)($_POST['total_price'] ?? ''));

    if ($title === '' || $city === '' || $location_text === '' || $price === '') {
        $errors[] = 'Lūdzu aizpildi obligātos laukus (nosaukums, pilsēta, atrašanās vieta, cena).';
    }

    $main_image = handleUploadOrUrl('main_image_file', $main_image_url, $uploadDir);
    $thumb1 = handleUploadOrUrl('thumb1_file', $thumb1_url, $uploadDir);
    $thumb2 = handleUploadOrUrl('thumb2_file', $thumb2_url, $uploadDir);
    $thumb3 = handleUploadOrUrl('thumb3_file', $thumb3_url, $uploadDir);

    if ($main_image === '') {
        $errors[] = 'Lūdzu pievieno galveno attēlu (fails vai URL).';
    }

    if ($errors === []) {
        $ownerId = (int)$_SESSION['user_id'];

        $floorInfo = '';
        if ($floor !== '' || $total_floors !== '') {
            $floorInfo = trim($floor . '/' . $total_floors, '/');
        }

        $priceVal = (float)str_replace(',', '.', $price);
        $areaVal = (float)str_replace(',', '.', $area);
        $bedsVal = (int)$bedrooms;
        $bathsVal = (int)$bathrooms;
        $floorVal = (int)$floor;
        $rentVal = (float)str_replace(',', '.', $rent_price);
        $utilVal = (float)str_replace(',', '.', $utilities_price);
        $totalVal = (float)str_replace(',', '.', $total_price);

        $stmt = $savienojums->prepare("INSERT INTO est_homes
            (owner_id, title, city, address, location_text, property_category, type, price, area, bedrooms, bathrooms, floor, floor_info, description, layout_text, map_text, amenities, main_image, thumb1, thumb2, thumb3, rent_price, utilities_price, total_price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')");

        if ($stmt) {
            $bindTypes = 'issssssdiiiisssssssssddd';
            $stmt->bind_param(
                $bindTypes,
                $ownerId,
                $title,
                $city,
                $address,
                $location_text,
                $property_category,
                $type,
                $priceVal,
                $areaVal,
                $bedsVal,
                $bathsVal,
                $floorVal,
                $floorInfo,
                $description,
                $layout_text,
                $map_text,
                $amenities,
                $main_image,
                $thumb1,
                $thumb2,
                $thumb3,
                $rentVal,
                $utilVal,
                $totalVal
            );

            if ($stmt->execute()) {
                $success = 'Sludinājums saglabāts kā melnraksts. Vari to vēlāk publicēt.';
            } else {
                $errors[] = 'Neizdevās saglabāt sludinājumu: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Neizdevās sagatavot pieprasījumu.';
        }
    }
}

$pageTitle = 'Izveidot sludinājumu - HomeEstate';
$extraStyles = ['newhome'];
$bodyClass = 'owner-page newhome-page';
include __DIR__ . '/../../includes/header.php';
?>

<header class="newhome-hero">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="newhome-hero__inner">
        <div class="hero-badge">
            <i class="fas fa-plus-circle"></i>
            Jauns sludinājums
        </div>
        <h1>Izveidot <span class="highlight">sludinājumu</span></h1>
        <p>Pievieno informāciju pa soļiem. Sludinājums tiks saglabāts kā melnraksts.</p>
    </div>
</header>

<div class="newhome-shell">
    <?php if ($success): ?>
        <div class="notice success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="notice error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>

    <div class="step-status" id="step-status">1/5: Pamatinformācija</div>

    <form method="POST" action="<?php echo main_route('property.create'); ?>" enctype="multipart/form-data" id="newhome-form">
        <div class="step active" data-step="1">
            <div class="form-grid">
                <div>
                    <label>Nosaukums *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" data-required="1">
                </div>
                <div>
                    <label>Atrašanās vietas apraksts *</label>
                    <input type="text" name="location_text" placeholder="Piem.: Rīga, Brīvības iela" value="<?php echo htmlspecialchars($location_text); ?>" data-required="1">
                </div>
                <div>
                    <label>Pilsēta *</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" data-required="1">
                </div>
                <div>
                    <label>Adrese</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>">
                </div>

                <div>
                    <label>Darījuma tips *</label>
                    <select name="type" id="deal-type" data-required="1">
                        <option value="rent" <?php echo $type === 'rent' ? 'selected' : ''; ?>>Izīrēt</option>
                        <option value="buy" <?php echo $type === 'buy' ? 'selected' : ''; ?>>Pārdot</option>
                    </select>
                </div>
                <div>
                    <label>Kategorija</label>
                    <select name="property_category" id="property-category">
                        <option value="apartment" <?php echo $property_category === 'apartment' ? 'selected' : ''; ?>>Dzīvoklis</option>
                        <option value="house" <?php echo $property_category === 'house' ? 'selected' : ''; ?>>Māja</option>
                        <option value="land" <?php echo $property_category === 'land' ? 'selected' : ''; ?>>Zeme</option>
                    </select>
                </div>

                <div>
                    <label id="price-label"><?php echo htmlspecialchars($price_label); ?></label>
                    <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($price); ?>" data-required="1" placeholder="520">
                </div>
                <div>
                    <label>Platība (m2)</label>
                    <input type="number" step="0.01" min="0" name="area" value="<?php echo htmlspecialchars($area); ?>" placeholder="65">
                </div>
                <div class="apartment-only">
                    <label>Stāvs</label>
                    <input type="number" min="0" name="floor" value="<?php echo htmlspecialchars($floor); ?>" placeholder="3">
                </div>
                <div class="apartment-only">
                    <label>Stāvu skaits</label>
                    <input type="number" min="0" name="total_floors" value="<?php echo htmlspecialchars($total_floors); ?>" placeholder="9">
                </div>
                <div>
                    <label>Guļamistabas</label>
                    <input type="number" min="0" max="50" name="bedrooms" value="<?php echo htmlspecialchars($bedrooms); ?>" placeholder="2">
                </div>
                <div>
                    <label>Vannasistabas</label>
                    <input type="number" min="0" max="50" name="bathrooms" value="<?php echo htmlspecialchars($bathrooms); ?>" placeholder="1">
                </div>
            </div>

            <div class="step-nav">
                <button type="button" class="btn-submit btn-next" data-next="2">Tālāk</button>
            </div>
        </div>

        <div class="step" data-step="2">
            <div class="form-grid full">
                <div>
                    <label>Apraksts</label>
                    <textarea name="description" placeholder="Īpašuma apraksts..."><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div>
                    <label>Plānojums</label>
                    <textarea name="layout_text" placeholder="Plānojuma apraksts..."><?php echo htmlspecialchars($layout_text); ?></textarea>
                </div>
                <div>
                    <label>Karte / atrašanās vieta</label>
                    <textarea name="map_text" placeholder="Kā nokļūt / ko iekļaut kartē..."><?php echo htmlspecialchars($map_text); ?></textarea>
                </div>
            </div>

            <div class="step-nav">
                <button type="button" class="btn-submit ghost btn-back" data-prev="1">Atpakaļ</button>
                <button type="button" class="btn-submit btn-next" data-next="3">Tālāk</button>
            </div>
        </div>

        <div class="step" data-step="3">
            <div class="form-grid full">
                <div>
                    <label>Ērtības / priekšrocības</label>
                    <textarea name="amenities" placeholder="Piem.: Balkons, lifts, autostāvvieta, kondicionieris..."><?php echo htmlspecialchars($amenities); ?></textarea>
                </div>
            </div>
            <div class="step-nav">
                <button type="button" class="btn-submit ghost btn-back" data-prev="2">Atpakaļ</button>
                <button type="button" class="btn-submit btn-next" data-next="4">Tālāk</button>
            </div>
        </div>

        <div class="step" data-step="4">
            <div class="form-grid">
                <div>
                    <label>Galvenais attēls *</label>
                    <input type="file" name="main_image_file" accept="image/*">
                    <input type="url" name="main_image_url" placeholder="URL (ja nav faila)" value="<?php echo htmlspecialchars($main_image_url); ?>">
                </div>
                <div>
                    <label>Attēls 1</label>
                    <input type="file" name="thumb1_file" accept="image/*">
                    <input type="url" name="thumb1_url" placeholder="URL" value="<?php echo htmlspecialchars($thumb1_url); ?>">
                </div>
                <div>
                    <label>Attēls 2</label>
                    <input type="file" name="thumb2_file" accept="image/*">
                    <input type="url" name="thumb2_url" placeholder="URL" value="<?php echo htmlspecialchars($thumb2_url); ?>">
                </div>
                <div>
                    <label>Attēls 3</label>
                    <input type="file" name="thumb3_file" accept="image/*">
                    <input type="url" name="thumb3_url" placeholder="URL" value="<?php echo htmlspecialchars($thumb3_url); ?>">
                </div>
            </div>

            <div class="step-nav">
                <button type="button" class="btn-submit ghost btn-back" data-prev="3">Atpakaļ</button>
                <button type="button" class="btn-submit btn-next" data-next="5">Tālāk</button>
            </div>
        </div>

        <div class="step" data-step="5">
            <div class="form-grid pricing-grid">
                <div class="rent-only">
                    <label>Īres maksa (EUR / men.) *</label>
                    <input type="number" step="0.01" min="0" name="rent_price" value="<?php echo htmlspecialchars($rent_price); ?>" data-required-rent="1" placeholder="520">
                </div>
                <div class="rent-only">
                    <label>Komunālie (apt., EUR)</label>
                    <input type="number" step="0.01" min="0" name="utilities_price" value="<?php echo htmlspecialchars($utilities_price); ?>" placeholder="150">
                </div>
                <div class="rent-only">
                    <label>Kopā mēnesī (EUR)</label>
                    <input type="number" step="0.01" min="0" name="total_price" value="<?php echo htmlspecialchars($total_price); ?>" placeholder="670">
                </div>
            </div>

            <div class="step-nav">
                <button type="button" class="btn-submit ghost btn-back" data-prev="4">Atpakaļ</button>
                <button type="submit" class="btn-submit">Saglabāt sludinājumu</button>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    const steps = Array.from(document.querySelectorAll('.step'));
    const status = document.getElementById('step-status');
    const dealType = document.getElementById('deal-type');
    const priceLabel = document.getElementById('price-label');
    const propertyCategory = document.getElementById('property-category');
    const rentBlocks = document.querySelectorAll('.rent-only');
    const aptBlocks = document.querySelectorAll('.apartment-only');
    const nextBtns = document.querySelectorAll('.btn-next');
    const backBtns = document.querySelectorAll('.btn-back');
    let currentStep = 0;

    const stepNames = ['Pamatinformācija', 'Apraksti', 'Priekšrocības', 'Mediji', 'Cenas'];

    const setStep = (idx) => {
        steps.forEach((step, i) => step.classList.toggle('active', i === idx));
        currentStep = idx;
        if (status) status.textContent = `${idx + 1}/5: ${stepNames[idx] || ''}`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const validateStep = (idx) => {
        const step = steps[idx];
        if (!step) return true;
        const isRent = dealType && dealType.value === 'rent';
        let ok = true;
        const selectors = ['[data-required="1"]'];
        if (isRent) selectors.push('[data-required-rent="1"]');
        step.querySelectorAll(selectors.join(',')).forEach(el => {
            const visible = !el.closest('.rent-only.hidden') && !el.closest('.apartment-only.hidden');
            if (!visible) return;
            if (!String(el.value || '').trim()) {
                ok = false;
                el.classList.add('invalid');
            } else {
                el.classList.remove('invalid');
            }
        });
        return ok;
    };

    const toggleDealFields = () => {
        const isRent = dealType.value === 'rent';
        if (priceLabel) priceLabel.textContent = isRent ? 'Cena (EUR / men.) *' : 'Cena (EUR) *';
        rentBlocks.forEach(block => block.classList.toggle('hidden', !isRent));
    };

    const toggleCategoryFields = () => {
        const isApartment = propertyCategory.value === 'apartment';
        aptBlocks.forEach(block => block.classList.toggle('hidden', !isApartment));
    };

    nextBtns.forEach(btn => btn.addEventListener('click', () => {
        const target = parseInt(btn.dataset.next, 10) - 1;
        if (validateStep(currentStep)) setStep(target);
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

    toggleDealFields();
    toggleCategoryFields();
    setStep(0);
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
