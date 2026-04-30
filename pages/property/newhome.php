<?php
session_start();
require_once __DIR__ . '/../../routes/main.php';


$plan = $_SESSION['plan'] ?? '';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'ipasnieks' || !in_array($plan, ['Silver', 'Gold'], true)) {
    header('Location: ' . main_route('owner') . '#plans');
    exit;
}

require_once dirname(__DIR__, 2) . '/con_db.php';

$errors = [];
$success = '';




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



$editId = (int)($_GET['id'] ?? $_POST['edit_id'] ?? 0);
$isEdit = $editId > 0;
$existingHome = null;

if ($isEdit) {
    $stmt = $savienojums->prepare("SELECT * FROM est_homes WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $existingHome = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existingHome) {
        header('Location: ' . main_route('property.list'));
        exit;
    }


    if ((int)$existingHome['ipasnieka_id'] !== (int)$_SESSION['user_id']) {
        header('Location: ' . main_route('property.list'));
        exit;
    }
}


$title = $existingHome['nosaukums'] ?? '';
$city = $existingHome['pilseta'] ?? '';
$address = $existingHome['adrese'] ?? '';
$location_text = $existingHome['atrasanas_vieta'] ?? '';
$price = $existingHome['cena'] ?? '';
$area = $existingHome['platiba'] ?? '';
$bedrooms = $existingHome['gulamistabas'] ?? '';
$bathrooms = $existingHome['vannasistabas'] ?? '';
$floor = $existingHome['stavs'] ?? '';
$total_floors = ''; 
if ($existingHome && $existingHome['stavu_info']) {
   
    if (strpos($existingHome['stavu_info'], '/') !== false) {
        list($f, $tf) = explode('/', $existingHome['stavu_info']);
        $floor = $f;
        $total_floors = $tf;
    } elseif (strpos($existingHome['stavu_info'], 'stāvu māja') !== false) {
        $total_floors = (int)$existingHome['stavu_info'];
    }
}

$description = $existingHome['apraksts'] ?? '';
$layout_text = $existingHome['planojums'] ?? '';
$map_text = $existingHome['karte'] ?? '';
$amenities = $existingHome['ertibas'] ?? '';
$property_category = $existingHome['kategorija'] ?? 'dzivoklis';
$type = $existingHome['veids'] ?? 'ire';
$main_image = $existingHome['galvenais_attels'] ?? '';
$main_image_url = '';
$gallery_json = $existingHome['galerija'] ?? '[]';
$rent_price = $existingHome['ires_maksa'] ?? '';
$utilities_price = $existingHome['komunalo_maksa'] ?? '';
$total_price = $existingHome['kopa_maksa'] ?? '';
$pirts_price_per_day = $existingHome['pirts_cena_diena'] ?? '';
$balla_price_per_day = $existingHome['balla_cena_diena'] ?? '';
$has_pirts = $pirts_price_per_day !== '' && (float)$pirts_price_per_day > 0;
$has_balla = $balla_price_per_day !== '' && (float)$balla_price_per_day > 0;
if ($type === 'ire') {
    $price_label = 'Cena (EUR / men.) *';
} elseif ($type === 'istermina_ire') {
    $price_label = 'Cena (EUR / nakti) *';
} else {
    $price_label = 'Cena (EUR) *';
}


$galleryLimit = 2;
if ($plan === 'Silver') {
    $galleryLimit = 9;
} elseif ($plan === 'Gold') {
    $galleryLimit = 50;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $location_text = trim((string)($_POST['location_text'] ?? ''));
    $property_category = (string)($_POST['property_category'] ?? 'dzivoklis');
    $rawType = (string)($_POST['type'] ?? '');
    if ($rawType === 'pardod') {
        $type = 'pardod';
    } elseif ($rawType === 'istermina_ire') {
        $type = 'istermina_ire';
    } else {
        $type = 'ire';
    }
    if ($type === 'ire') {
        $price_label = 'Cena (EUR / men.) *';
    } elseif ($type === 'istermina_ire') {
        $price_label = 'Cena (EUR / nakti) *';
    } else {
        $price_label = 'Cena (EUR) *';
    }
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
    
    $utilities_price = $type === 'ire' ? trim((string)($_POST['utilities_price'] ?? '0')) : '0';
    $rent_price = $type === 'ire' ? $price : '0';

    $has_pirts = isset($_POST['has_pirts']) && (string)$_POST['has_pirts'] === '1';
    $has_balla = isset($_POST['has_balla']) && (string)$_POST['has_balla'] === '1';
    $pirts_price_per_day = $has_pirts ? trim((string)($_POST['pirts_price_per_day'] ?? '')) : '0';
    $balla_price_per_day = $has_balla ? trim((string)($_POST['balla_price_per_day'] ?? '')) : '0';
    
    if ($type === 'ire') {
        $total_price = (float)str_replace(',', '.', $rent_price) + (float)str_replace(',', '.', $utilities_price);
    } else {
        $total_price = (float)str_replace(',', '.', $price);
    }

	    $len = function (string $v): int {
	        return function_exists('mb_strlen') ? (int)mb_strlen($v) : (int)strlen($v);
	    };
	    $lettersOnly = function (string $v): bool {
	        return $v !== '' && (bool)preg_match('/^[\\p{L}\\s]+$/u', $v);
	    };

	    if ($title === '' || $city === '' || $location_text === '' || $price === '') {
	        $errors[] = 'Lūdzu aizpildi obligātos laukus (nosaukums, pilsēta, atrašanās vieta, cena).';
	    }

	    if ($title !== '' && (!$lettersOnly($title) || $len($title) > 30)) {
	        $errors[] = 'Nosaukums drīkst saturēt tikai burtus un atstarpes un nedrīkst būt garāks par 30 rakstīmēm.';
	    }
	    if ($city !== '' && (!$lettersOnly($city) || $len($city) > 30)) {
	        $errors[] = 'Pilsēta drīkst saturēt tikai burtus un atstarpes un nedrīkst būt garāka par 30 rakstīmēm.';
	    }
	    if ($location_text !== '' && (!$lettersOnly($location_text) || $len($location_text) > 50)) {
	        $errors[] = 'Atrašanās vietas apraksts drīkst saturēt tikai burtus un atstarpes un nedrīkst būt garāks par 50 rakstīmēm.';
	    }
	    if ($address !== '') {
	        if ($len($address) > 35) {
	            $errors[] = 'Adrese nedrīkst būt garāka par 35 rakstīmēm.';
	        } else {
	            $m = [];
	            preg_match_all('/\\p{L}/u', $address, $m);
	            if (count($m[0] ?? []) < 4) {
	                $errors[] = 'Adresē jābūt vismaz 4 burtiem.';
	            }
	        }
	    }
	    if ($len($description) < 5) {
	        $errors[] = 'Aprakstam jābūt vismaz 5 rakstīmēm.';
	    }
	    if ($len($layout_text) < 5) {
	        $errors[] = 'Plānojumam jābūt vismaz 5 rakstīmēm.';
	    }

    if ($type === 'istermina_ire') {
        if ($has_pirts && $pirts_price_per_day === '') {
            $errors[] = 'Lūdzu norādiet pirts cenu par dienu.';
        }
        if ($has_balla && $balla_price_per_day === '') {
            $errors[] = 'Lūdzu norādiet baļļas cenu par dienu.';
        }
    }

    $currentOldMain = $existingHome['galvenais_attels'] ?? '';
    $mainImageFallback = ($main_image_url !== '') ? $main_image_url : $currentOldMain;
    $main_image = handleUploadOrUrl('main_image_file', $mainImageFallback, $uploadDir);

    if (empty($main_image)) {
        $errors[] = 'Lūdzu pievieno galveno attēlu (fails vai URL).';
    }

    $gallery_paths = [];

    if (isset($_FILES['gallery_files']) && is_array($_FILES['gallery_files']['name']) && $_FILES['gallery_files']['name'][0] !== '') {
        $count = count($_FILES['gallery_files']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['gallery_files']['tmp_name'][$i];
                $origName = basename((string)$_FILES['gallery_files']['name'][$i]);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed, true)) {
                    $safeName = uniqid('gallery_', true) . '.' . $ext;
                    $targetPath = $uploadDir . '/' . $safeName;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $gallery_paths[] = 'uploads/' . $safeName;
                    }
                }
            }
        }
    }

    $kept_existing = [];
    $raw_keep = $_POST['existing_gallery_keep'] ?? '';
    if ($raw_keep !== '') {
        $kept_existing = json_decode($raw_keep, true) ?: [];
    } elseif ($isEdit && !isset($_POST['existing_gallery_keep'])) {
        $kept_existing = json_decode($existingHome['galerija'] ?? '[]', true) ?: [];
    }

    $combined_gallery = array_merge($kept_existing, $gallery_paths);
    $gallery_json = json_encode(array_slice($combined_gallery, 0, $galleryLimit));

    if ($errors === []) {
        $ownerId = (int)$_SESSION['user_id'];

        $floorInfo = '';
        if ($property_category === 'maja') {
            $floorInfo = $total_floors !== '' ? $total_floors . ' stāvu māja' : '';
        } else {
            if ($floor !== '' || $total_floors !== '') {
                $floorInfo = trim($floor . '/' . $total_floors, '/');
            }
        }

        $priceVal = (float)str_replace(',', '.', $price);
        $areaVal = (float)str_replace(',', '.', $area);
        $bedsVal = (int)$bedrooms;
        $bathsVal = (int)$bathrooms;
        $floorVal = (int)$floor;
        $rentVal = (float)str_replace(',', '.', $rent_price);
        $utilVal = (float)str_replace(',', '.', $utilities_price);
        $totalVal = (float)$total_price;
        $pirtsVal = (float)str_replace(',', '.', (string)$pirts_price_per_day);
        $ballaVal = (float)str_replace(',', '.', (string)$balla_price_per_day);

        if ($isEdit) {
            $sql = "UPDATE est_homes SET 
                nosaukums=?, pilseta=?, adrese=?, atrasanas_vieta=?, kategorija=?, veids=?, 
                cena=?, platiba=?, gulamistabas=?, vannasistabas=?, stavs=?, stavu_info=?, 
                apraksts=?, planojums=?, karte=?, ertibas=?, 
                galvenais_attels=?, galerija=?, ires_maksa=?, komunalo_maksa=?, kopa_maksa=?, pirts_cena_diena=?, balla_cena_diena=?, statuss='Melnraksts'
                WHERE id=? AND ipasnieka_id=?";
            $stmt = $savienojums->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    'ssssssddiiisssssssdddddii',
                    $title, $city, $address, $location_text, $property_category, $type,
                    $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                    $description, $layout_text, $map_text, $amenities,
                    $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal,
                    $editId, $ownerId
                );
            }
        } else {
            $sql = "INSERT INTO est_homes
                (ipasnieka_id, nosaukums, pilseta, adrese, atrasanas_vieta, kategorija, veids, cena, platiba, gulamistabas, vannasistabas, stavs, stavu_info, apraksts, planojums, karte, ertibas, galvenais_attels, galerija, ires_maksa, komunalo_maksa, kopa_maksa, pirts_cena_diena, balla_cena_diena, statuss)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Melnraksts')";
            $stmt = $savienojums->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    'issssssddiiisssssssddddd',
                    $ownerId, $title, $city, $address, $location_text, $property_category, $type,
                    $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                    $description, $layout_text, $map_text, $amenities,
                    $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal
                );
            }
        }

        if ($stmt) {
            if ($stmt->execute()) {
                main_redirect('property.myhomes');
            } else {
                $errors[] = 'Neizdevās saglabāt sludinājumu: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Neizdevās sagatavot pieprasījumu.';
        }
    }
}

$pageTitle = ($isEdit ? 'Rediģēt sludinājumu' : 'Izveidot sludinājumu') . ' - HomeEstate';
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
            <i class="fas <?php echo $isEdit ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
            <?php echo $isEdit ? 'Rediģēt sludinājumu' : 'Jauns sludinājums'; ?>
        </div>
        <h1><?php echo $isEdit ? 'Rediģēt' : 'Izveidot'; ?> <span class="highlight">sludinājumu</span></h1>
        <p><?php echo $isEdit ? 'Veic nepieciešamās izmaiņas savā sludinājumā.' : 'Pievieno informāciju pa soļiem. Sludinājums tiks saglabāts kā melnraksts.'; ?></p>
    </div>
</header>

<style>
    .image-preview-container {
        margin-top: 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .preview-item {
        width: 120px;
        height: 120px;
        position: relative;
        border-radius: var(--radius-sm);
        overflow: hidden;
        border: 2px solid var(--gray-200);
        transition: transform 0.2s;
    }
    .preview-item:hover {
        border-color: var(--accent);
    }
    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        cursor: pointer;
    }
    .preview-item .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(231, 76, 60, 0.9);
        color: white;
        border: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        transition: all 0.2s;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .preview-item .remove-btn:hover {
        background: #c0392b;
        transform: scale(1.1);
    }
    .main-preview-item {
        width: 100%;
        max-width: 300px;
        height: 180px;
    }
    .gallery-counter {
        margin-top: 8px;
        font-weight: 600;
        color: var(--primary);
        font-size: 0.9rem;
    }
    .gallery-counter span {
        color: var(--accent);
    }
</style>

<div class="newhome-shell">
    <?php if ($success): ?>
        <div class="notice success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="notice error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>

    <div class="step-status" id="step-status">1/5: Pamatinformācija</div>

    <form method="POST" action="<?php echo main_route('property.create'); ?>" enctype="multipart/form-data" id="newhome-form">
        <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
        <div class="step active" data-step="1">
            <div class="form-grid">
	                <div>
	                    <label>Nosaukums *</label>
	                    <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" data-required="1" maxlength="30">
	                </div>
	                <div>
	                    <label>Atrašanās vietas apraksts *</label>
	                    <input type="text" name="location_text" placeholder="Piem.: Centra vidus" value="<?php echo htmlspecialchars($location_text); ?>" data-required="1" maxlength="50">
	                </div>
	                <div>
	                    <label>Pilsēta *</label>
	                    <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" data-required="1" maxlength="30">
	                </div>
	                <div>
	                    <label>Adrese</label>
	                    <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" maxlength="35">
	                </div>

                <div>
                    <label>Darījuma tips *</label>
                    <select name="type" id="deal-type" data-required="1">
                        <option value="istermina_ire" <?php echo $type === 'istermina_ire' ? 'selected' : ''; ?>>Īstermiņa īre</option>
                        <option value="ire" <?php echo $type === 'ire' ? 'selected' : ''; ?>>Izīrēt</option>
                        <option value="pardod" <?php echo $type === 'pardod' ? 'selected' : ''; ?>>Pārdot</option>
                    </select>
                </div>
                <div>
                    <label>Kategorija</label>
                    <select name="property_category" id="property-category">
                        <option value="dzivoklis" <?php echo $property_category === 'dzivoklis' ? 'selected' : ''; ?>>Dzīvoklis</option>
                        <option value="maja" <?php echo $property_category === 'maja' ? 'selected' : ''; ?>>Māja</option>
                        <option value="apartaments" <?php echo $property_category === 'apartaments' ? 'selected' : ''; ?>>Apartaments</option>
                    </select>
                </div>

                <div>
                    <label id="price-label"><?php echo htmlspecialchars($price_label); ?></label>
                    <input type="number" step="0.01" min="0" name="price" id="main-price" value="<?php echo htmlspecialchars($price); ?>" data-required="1" placeholder="520">
                </div>
                <div>
                    <label>Platība (m2)</label>
                    <input type="number" step="0.01" min="0" name="area" value="<?php echo htmlspecialchars($area); ?>" placeholder="65">
                </div>
                <div class="apartment-only">
                    <label>Stāvs</label>
                    <input type="number" min="0" name="floor" value="<?php echo htmlspecialchars($floor); ?>" placeholder="3">
                </div>
                <div class="floor-info-block">
                    <label id="floor-total-label">Stāvu skaits</label>
                    <input type="number" min="0" name="total_floors" value="<?php echo htmlspecialchars($total_floors); ?>" placeholder="2">
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
	                    <textarea name="description" placeholder="Īpašuma apraksts..." data-required="1" data-minlen="5"><?php echo htmlspecialchars($description); ?></textarea>
	                </div>
	                <div>
	                    <label>Plānojums</label>
	                    <textarea name="layout_text" placeholder="Plānojuma apraksts..." data-required="1" data-minlen="5"><?php echo htmlspecialchars($layout_text); ?></textarea>
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
                    <input type="file" name="main_image_file" accept="image/*" id="main-image-input">
                    <input type="url" name="main_image_url" placeholder="URL (ja nav faila)" value="<?php echo htmlspecialchars($main_image_url); ?>" id="main-image-url">
                    <div id="main-preview" class="image-preview-container">
                        <?php if ($main_image): ?>
                            <div class="preview-item main-preview-item">
                                <img src="<?php echo htmlspecialchars(media_url($main_image)); ?>" alt="Esošais attēls" onclick="window.open(this.src, '_blank')">
                                <div style="position:absolute; bottom:0; background:rgba(0,0,0,0.5); color:white; width:100%; font-size:10px; text-align:center; padding:2px;">Esošais attēls</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="full">
                    <label>Galerija (Max <?php echo $galleryLimit; ?> attēli)</label>
                    <div class="gallery-upload-zone">
                        <input type="file" name="gallery_files[]" accept="image/*" multiple id="gallery-input">
                        <input type="hidden" name="existing_gallery_keep" id="existing-gallery-keep">
                        <div id="gallery-counter-text" class="gallery-counter">Izvēlēti <span>0</span> no <?php echo $galleryLimit; ?> attēliem</div>
                        <p class="muted">Vari pievienot attēlus pa vienam vai vairākus kopā. Tie tiks pievienoti esošajai izvēlei.</p>
                        <?php if ($isEdit && !empty($gallery_json) && $gallery_json !== '[]'): ?>
                            <p class="muted small" style="color: var(--accent);">Piezīme: Esošie attēli ir saglabāti. Vari tos dzēst pa vienam.</p>
                        <?php endif; ?>
                    </div>
                    <div id="gallery-preview" class="image-preview-container"></div>
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
                    <label>Īres maksa (EUR / men.)</label>
                    <input type="number" id="rent-price-display" value="<?php echo htmlspecialchars($price); ?>" disabled>
                    <p class="muted small">Tiek ņemta no pamatinformācijas.</p>
                </div>
                <div class="rent-only">
                    <label>Komunālie (apt., EUR)</label>
                    <input type="number" step="0.01" min="0" name="utilities_price" id="utilities-price" value="<?php echo htmlspecialchars($utilities_price); ?>" placeholder="150">
                </div>
                <div class="rent-only">
                    <label>Kopā mēnesī (EUR)</label>
                    <input type="number" step="0.01" min="0" name="total_price_calc" id="total-price-calc" value="<?php echo htmlspecialchars($total_price); ?>" readonly>
                </div>
                <div class="buy-only hidden">
                    <label>Pārdošanas cena (EUR)</label>
                    <input type="text" id="buy-price-display" value="<?php echo htmlspecialchars($price); ?>" disabled>
                </div>
                <div class="short-rent-only hidden">
                    <label>Cena (EUR / nakti)</label>
                    <input type="number" id="short-rent-price-display" value="<?php echo htmlspecialchars($price); ?>" disabled>
                    <p class="muted small">Tiek ņemta cena par nakti.</p>
                </div>
                <div class="short-rent-only hidden">
                    <label style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" id="has-pirts" name="has_pirts" value="1" <?php echo $has_pirts ? 'checked' : ''; ?>>
                        Pirts (papildu maksa)
                    </label>
                    <div id="pirts-price-wrap" style="<?php echo $has_pirts ? '' : 'display:none;'; ?>">
                        <input type="number" step="0.01" min="0" name="pirts_price_per_day" id="pirts-price-per-day" value="<?php echo htmlspecialchars($pirts_price_per_day); ?>" placeholder="50">
                        <p class="muted small">Cena par 1 dienu.</p>
                    </div>
                </div>
                <div class="short-rent-only hidden">
                    <label style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" id="has-balla" name="has_balla" value="1" <?php echo $has_balla ? 'checked' : ''; ?>>
                        Balla (papildu maksa)
                    </label>
                    <div id="balla-price-wrap" style="<?php echo $has_balla ? '' : 'display:none;'; ?>">
                        <input type="number" step="0.01" min="0" name="balla_price_per_day" id="balla-price-per-day" value="<?php echo htmlspecialchars($balla_price_per_day); ?>" placeholder="50">
                        <p class="muted small">Cena par 1 dienu.</p>
                    </div>
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
    const form = document.getElementById('newhome-form');
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
	    const galleryLimit = <?php echo $galleryLimit; ?>;
	    const titleInput = form.querySelector('input[name="title"]');
	    const cityInput = form.querySelector('input[name="city"]');
	    const locationInput = form.querySelector('input[name="location_text"]');
	    const addressInput = form.querySelector('input[name="address"]');

    let currentStep = 0;
    const stepNames = ['Pamatinformācija', 'Apraksti', 'Priekšrocības', 'Mediji', 'Cenas'];

	    let galleryFiles = [];
	    let existingGallery = <?php echo $gallery_json; ?>;
	    const existingKeepInput = document.getElementById('existing-gallery-keep');
	    const hasExistingMain = <?php echo $main_image ? 'true' : 'false'; ?>;

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

	    [titleInput, cityInput, locationInput].forEach(el => {
	        if (!el) return;
	        el.addEventListener('input', () => sanitizeTextOnly(el));
	        el.addEventListener('blur', () => {
	            el.value = String(el.value || '').trim();
	        });
	    });

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

	        step.querySelectorAll('[data-required=\"1\"], [data-minlen], input[name=\"title\"], input[name=\"city\"], input[name=\"location_text\"], input[name=\"address\"]').forEach(checkEl);

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
        galleryPreview.innerHTML = '';

        existingGallery.forEach((url, index) => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            
            const img = document.createElement('img');
            img.src = '<?php echo app_url(""); ?>' + '/' + url;
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
        
        galleryCounterText.querySelector('span').textContent = existingGallery.length + galleryFiles.length;
    };

    mainImageInput.addEventListener('change', () => {
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

    mainImageUrlInput.addEventListener('input', () => {
        if (mainImageUrlInput.value.trim() !== '') {
            mainPreview.innerHTML = `<div class="preview-item main-preview-item"><img src="${mainImageUrlInput.value}" onerror="this.src='https://via.placeholder.com/300x180?text=Invalid+URL'"></div>`;
        }
    });

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
	        if (galleryFiles.length > 0) {
	            const dt = new DataTransfer();
	            galleryFiles.forEach(file => dt.items.add(file));
	            galleryInput.files = dt.files;
	        }
        if (existingKeepInput) {
            existingKeepInput.value = JSON.stringify(existingGallery);
        }
    });

    mainPriceInput.addEventListener('input', calculateTotal);
    utilitiesInput.addEventListener('input', calculateTotal);

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
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
