<?php
session_start();
require_once __DIR__ . '/../../routes/main.php';

// Only logged-in owners with paid plan can create listings
$plan = $_SESSION['plan'] ?? '';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'ipasnieks' || !in_array($plan, ['Silver', 'Gold'])) {
    header('Location: ' . main_route('owner'));
    exit();
}

$configPath = dirname(__DIR__, 2) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php');
}
require $configPath;

$errors = [];
$success = '';

// Ensure the table has the columns we expect (avoids Unknown column errors on shared hosts)
ensureColumns($savienojums, 'est_homes', $errors);

// Ensure required columns exist (shared hosting safety net)
function ensureColumns(mysqli $conn, string $table, array &$errors): void {
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
        'property_category' => "VARCHAR(50) NOT NULL DEFAULT 'apartment'",
        'location_text'     => 'TEXT',
        'layout_text'       => 'TEXT',
        'map_text'          => 'TEXT',
        'amenities'         => 'TEXT',
        'main_image'        => 'VARCHAR(255) NOT NULL',
        'thumb1'            => 'VARCHAR(255) DEFAULT NULL',
        'thumb2'            => 'VARCHAR(255) DEFAULT NULL',
        'thumb3'            => 'VARCHAR(255) DEFAULT NULL',
        'floor_info'        => 'VARCHAR(20) DEFAULT NULL',
        'rent_price'        => 'DECIMAL(12,2) DEFAULT 0',
        'utilities_price'   => 'DECIMAL(12,2) DEFAULT 0',
        'total_price'       => 'DECIMAL(12,2) DEFAULT 0'
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

// Upload helper for image inputs (file preferred, URL as fallback)
$uploadDir = dirname(__DIR__, 2) . '/uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

function handleUploadOrUrl(string $fileKey, string $fallbackUrl, string $uploadDir): string {
    if (isset($_FILES[$fileKey]) && is_array($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[$fileKey]['tmp_name'];
        $origName = basename($_FILES[$fileKey]['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed, true)) {
            return '';
        }
        $safeName = uniqid('img_', true) . '.' . $ext;
        $targetPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($tmpName, $targetPath)) {
            return 'uploads/' . $safeName;
        }
    }
    return trim($fallbackUrl);
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
$price_label = 'Cena (€ / mēn.) *';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $location_text = trim($_POST['location_text'] ?? '');
    $property_category = $_POST['property_category'] ?? 'apartment';
    $type = ($_POST['type'] ?? '') === 'buy' ? 'buy' : 'rent';
    $price_label = $type === 'rent' ? 'Cena (€ / mēn.) *' : 'Cena (€) *';
    $price = trim($_POST['price'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $bedrooms = trim($_POST['bedrooms'] ?? '');
    $bathrooms = trim($_POST['bathrooms'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $total_floors = trim($_POST['total_floors'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $layout_text = trim($_POST['layout_text'] ?? '');
    $map_text = trim($_POST['map_text'] ?? '');
    $amenities = trim($_POST['amenities'] ?? '');

    $main_image_url = trim($_POST['main_image_url'] ?? '');
    $thumb1_url = trim($_POST['thumb1_url'] ?? '');
    $thumb2_url = trim($_POST['thumb2_url'] ?? '');
    $thumb3_url = trim($_POST['thumb3_url'] ?? '');

    $rent_price = trim($_POST['rent_price'] ?? '');
    $utilities_price = trim($_POST['utilities_price'] ?? '');
    $total_price = trim($_POST['total_price'] ?? '');

    $allowedCategories = ['apartment','house','commercial','land'];
    if (!in_array($property_category, $allowedCategories, true)) {
        $property_category = 'apartment';
    }

    if ($title === '') $errors[] = 'Nosaukums ir obligāts.';
    if ($city === '') $errors[] = 'Pilsēta ir obligāta.';
    if ($address === '') $errors[] = 'Adrese ir obligāta.';
    if ($location_text === '') $errors[] = 'Atrašanās vietas apraksts ir obligāts.';
    if ($price === '' || !is_numeric($price) || $price < 0) $errors[] = 'Cena nav derīga.';
    if ($area === '' || !ctype_digit($area) || (int)$area <= 0) $errors[] = 'Platība nav derīga.';
    if ($bedrooms === '' || !ctype_digit($bedrooms) || (int)$bedrooms < 0) $errors[] = 'Guļamistabu skaits nav derīgs.';
    if ($bathrooms === '' || !ctype_digit($bathrooms) || (int)$bathrooms < 0) $errors[] = 'Vannasistabu skaits nav derīgs.';

    if ($property_category === 'apartment') {
        if ($floor === '' || !ctype_digit($floor) || (int)$floor < 0) {
            $errors[] = 'Stāvs nav derīgs.';
        }
        if ($total_floors !== '' && (!ctype_digit($total_floors) || (int)$total_floors < 1)) {
            $errors[] = 'Stāvu skaits nav derīgs.';
        }
    }

    if ($type === 'rent') {
        if ($rent_price === '' || !is_numeric($rent_price) || $rent_price < 0) {
            $errors[] = 'Īres maksa nav derīga.';
        }
        if ($utilities_price === '') {
            $utilities_price = '0';
        } elseif (!is_numeric($utilities_price) || $utilities_price < 0) {
            $errors[] = 'Komunālie nav derīgi.';
        }
    }

    // Main image is required either as upload or URL
    $main_image = handleUploadOrUrl('main_image_file', $main_image_url, $uploadDir);
    $thumb1 = handleUploadOrUrl('thumb1_file', $thumb1_url, $uploadDir);
    $thumb2 = handleUploadOrUrl('thumb2_file', $thumb2_url, $uploadDir);
    $thumb3 = handleUploadOrUrl('thumb3_file', $thumb3_url, $uploadDir);
    if ($main_image === '') {
        $errors[] = 'Galvenais attēls ir obligāts (augšupielāde vai URL).';
    }

    if (!$errors) {
        $ownerId = (int)$_SESSION['user_id'];
        $priceVal = (float)$price;
        $areaVal = (int)$area;
        $bedVal = (int)$bedrooms;
        $bathVal = (int)$bathrooms;
        $floorVal = ($floor === '' || $property_category !== 'apartment') ? 0 : (int)$floor;
        $floorInfo = '';
        if ($property_category === 'apartment' && $floor !== '') {
            $floorInfo = $floorVal;
            if ($total_floors !== '' && ctype_digit($total_floors)) {
                $floorInfo .= '/' . (int)$total_floors;
            }
        }

        if ($type === 'rent') {
            $rentVal = (float)$rent_price;
            $utilVal = (float)$utilities_price;
            $totalVal = ($total_price === '' || !is_numeric($total_price)) ? ($rentVal + $utilVal) : (float)$total_price;
        } else {
            $rentVal = 0.0;
            $utilVal = 0.0;
            $totalVal = $priceVal;
        }

        $stmt = $savienojums->prepare("INSERT INTO est_homes (owner_id, title, city, address, location_text, property_category, type, price, area, bedrooms, bathrooms, floor, floor_info, description, layout_text, map_text, amenities, main_image, thumb1, thumb2, thumb3, rent_price, utilities_price, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')");
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
                $bedVal,
                $bathVal,
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
                $success = 'Sludinājums saglabāts kā drafts. Vari to vēlāk publicēt.';
            } else {
                $errors[] = 'Neizdevās saglabāt sludinājumu: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Neizdevās sagatavot pieprasījumu.';
        }
    }
}

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Izveidot sludinājumu - HomeEstate</title>
    <link rel="icon" type="image/png" href="<?php echo asset_path('Images/Logo.png'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_path('style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_path('css/newhome.css'); ?>">
</head>
<body class="owner-page">
    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="<?php echo main_route('home'); ?>">Sākums</a></li>
            <li><a href="<?php echo main_route('property.list'); ?>">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="<?php echo main_route('owner'); ?>">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="<?php echo main_route('property.create'); ?>" class="active">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="<?php echo main_route('about'); ?>">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; font-weight: bold; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="<?php echo main_route('logout'); ?>" class="btn-register" style="background-color: #c0392b;">Iziet</a>
            <?php else: ?>
                <a href="<?php echo main_route('login'); ?>" class="btn-login">Ielogoties</a>
                <a href="<?php echo main_route('register'); ?>" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <div class="newhome-shell">
        <h1>Izveidot sludinājumu</h1>
        <p class="sub">Pievieno info pa soļiem. Sludinājums tiks saglabāts kā drafts.</p>

        <?php if ($success): ?>
            <div class="notice success"><?php echo $success; ?></div>
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
                        <input type="text" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" data-required="1">
                    </div>
                    <div>
                        <label>Atrašanās vietas apraksts *</label>
                        <input type="text" name="location_text" placeholder="Piem.: Rīga, Brīvības iela" value="<?php echo htmlspecialchars($location_text ?? ''); ?>" data-required="1">
                    </div>
                    <div>
                        <label>Pilsēta *</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($city ?? ''); ?>" data-required="1">
                    </div>
                    <div>
                        <label>Adrese *</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($address ?? ''); ?>" data-required="1">
                    </div>
                    <div>
                        <label>Īpašuma tips *</label>
                        <select name="property_category" id="property-category" data-required="1">
                            <option value="apartment" <?php echo ($property_category==='apartment') ? 'selected' : ''; ?>>Dzīvoklis</option>
                            <option value="house" <?php echo ($property_category==='house') ? 'selected' : ''; ?>>Māja</option>
                            <option value="commercial" <?php echo ($property_category==='commercial') ? 'selected' : ''; ?>>Komercplatība</option>
                            <option value="land" <?php echo ($property_category==='land') ? 'selected' : ''; ?>>Zeme</option>
                        </select>
                    </div>
                    <div>
                        <label>Darījuma veids *</label>
                        <select name="type" id="deal-type" data-required="1">
                            <option value="rent" <?php echo ($type==='rent') ? 'selected' : ''; ?>>Izīrē</option>
                            <option value="buy" <?php echo ($type==='buy') ? 'selected' : ''; ?>>Pārdod</option>
                        </select>
                    </div>
                    <div>
                        <label id="price-label"><?php echo htmlspecialchars($price_label); ?></label>
                        <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($price ?? ''); ?>" data-required="1">
                    </div>
                </div>
                <div class="step-nav">
                    <button type="button" class="btn-submit btn-next" data-next="2">Tālāk</button>
                </div>
            </div>

            <div class="step" data-step="2">
                <div class="form-grid">
                    <div>
                        <label>Platība (m²) *</label>
                        <input type="number" min="1" name="area" value="<?php echo htmlspecialchars($area ?? ''); ?>" data-required="1">
                    </div>
                    <div>
                        <label>Guļamistabas *</label>
                        <input type="number" min="0" name="bedrooms" value="<?php echo htmlspecialchars($bedrooms ?? ''); ?>" data-required="1">
                    </div>
                    <div>
                        <label>Vannasistabas *</label>
                        <input type="number" min="0" name="bathrooms" value="<?php echo htmlspecialchars($bathrooms ?? ''); ?>" data-required="1">
                    </div>
                    <div class="apartment-only">
                        <label>Stāvs *</label>
                        <input type="number" min="0" name="floor" value="<?php echo htmlspecialchars($floor ?? ''); ?>" data-required="1">
                    </div>
                    <div class="apartment-only">
                        <label>Mājas stāvu skaits</label>
                        <input type="number" min="1" name="total_floors" value="<?php echo htmlspecialchars($total_floors ?? ''); ?>">
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
                        <label>Apraksts</label>
                        <textarea name="description" placeholder="Par īpašumu..." rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-grid full">
                    <div>
                        <label>Plānojums</label>
                        <textarea name="layout_text" placeholder="Plānojuma apraksts" rows="3"><?php echo htmlspecialchars($layout_text ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-grid full">
                    <div>
                        <label>Atrašanās vietas apraksts (karte)</label>
                        <textarea name="map_text" placeholder="Transports, apkārtne, infrastruktūra" rows="3"><?php echo htmlspecialchars($map_text ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="step-nav">
                    <button type="button" class="btn-submit ghost btn-back" data-prev="2">Atpakaļ</button>
                    <button type="button" class="btn-submit btn-next" data-next="4">Tālāk</button>
                </div>
            </div>

            <div class="step" data-step="4">
                <div class="form-grid full">
                    <div>
                        <label>Īpašības / Priekšrocības (viena rinda katrai)</label>
                        <textarea name="amenities" placeholder="Pilnībā mēbelēts&#10;Balkons ar panorāmu&#10;Pazemes stāvvieta&#10;Augstie griesti&#10;Apsardze 24/7" rows="4"><?php echo htmlspecialchars($amenities ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="step-nav">
                    <button type="button" class="btn-submit ghost btn-back" data-prev="3">Atpakaļ</button>
                    <button type="button" class="btn-submit btn-next" data-next="5">Tālāk</button>
                </div>
            </div>

            <div class="step" data-step="5">
                <div class="form-grid">
                    <div>
                        <label>Galvenā bilde (augšupielāde vai URL) *</label>
                        <input type="file" name="main_image_file" accept="image/*">
                        <input type="url" name="main_image_url" placeholder="Vai ievadi URL" value="<?php echo htmlspecialchars($main_image_url ?? ''); ?>">
                    </div>
                    <div>
                        <label>1. sīkbilde</label>
                        <input type="file" name="thumb1_file" accept="image/*">
                        <input type="url" name="thumb1_url" placeholder="URL" value="<?php echo htmlspecialchars($thumb1_url ?? ''); ?>">
                    </div>
                    <div>
                        <label>2. sīkbilde</label>
                        <input type="file" name="thumb2_file" accept="image/*">
                        <input type="url" name="thumb2_url" placeholder="URL" value="<?php echo htmlspecialchars($thumb2_url ?? ''); ?>">
                    </div>
                    <div>
                        <label>3. sīkbilde</label>
                        <input type="file" name="thumb3_file" accept="image/*">
                        <input type="url" name="thumb3_url" placeholder="URL" value="<?php echo htmlspecialchars($thumb3_url ?? ''); ?>">
                    </div>
                </div>

                <div class="form-grid pricing-grid">
                    <div class="rent-only">
                        <label>Īres maksa (€ / mēn.) *</label>
                        <input type="number" step="0.01" min="0" name="rent_price" value="<?php echo htmlspecialchars($rent_price ?? ''); ?>" data-required-rent="1" placeholder="520">
                    </div>
                    <div class="rent-only">
                        <label>Komunālie (apt., €)</label>
                        <input type="number" step="0.01" min="0" name="utilities_price" value="<?php echo htmlspecialchars($utilities_price ?? ''); ?>" placeholder="150">
                    </div>
                    <div class="rent-only">
                        <label>Kopā mēnesī (€)</label>
                        <input type="number" step="0.01" min="0" name="total_price" value="<?php echo htmlspecialchars($total_price ?? ''); ?>" placeholder="670">
                    </div>
                </div>

                <div class="step-nav">
                    <button type="button" class="btn-submit ghost btn-back" data-prev="4">Atpakaļ</button>
                    <button type="submit" class="btn-submit">Saglabāt sludinājumu</button>
                </div>
            </div>
        </form>
    </div>

    <script src="script.js"></script>
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

        const setStep = (idx) => {
            steps.forEach((step, i) => step.classList.toggle('active', i === idx));
            currentStep = idx;
            if (status) status.textContent = `${idx + 1}/5: ${['Pamatinformācija','Detalizācija','Apraksti','Priekšrocības','Mediji un cenas'][idx]}`;
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
            priceLabel.textContent = isRent ? 'Cena (€ / mēn.) *' : 'Cena (€) *';
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
</body>
</html>
