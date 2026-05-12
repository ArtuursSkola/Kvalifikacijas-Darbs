<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../routes/main.php';
require_once __DIR__ . '/../../routes/admin.php';
require_once dirname(__DIR__, 2) . '/con_db.php';
require_once dirname(__DIR__, 2) . '/includes/account.php';

$currentUser = loadCurrentUserContext($savienojums);

if (!$currentUser && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'moderator'], true)) {
    $currentUser = [
            'lietotaja_id' => $_SESSION['user_id'] ?? 0,
            'loma'         => $_SESSION['role'],
            'plans'        => 'Zelta',
    ];
}

$currentUserId = (int)($currentUser['lietotaja_id'] ?? 0);


if (!$currentUser) {
    header('Location: ' . main_route('login'));
    exit;
}

$role = $currentUser['loma'] ?? '';
if ($role === 'lietotajs') {
    header('Location: ' . main_route('owner'));
    exit;
}
$isAdminOrMod = in_array($role, ['admin', 'moderator'], true);
$isOwner = ($role === 'ipasnieks');

if (!$isAdminOrMod && !$isOwner) {
    header('Location: ' . main_route('owner') . '#plans');
    exit;
}

if ($isOwner && !$isAdminOrMod && !userHasActiveOwnerPlan($currentUser)) {
    header('Location: ' . main_route('owner') . '#plans');
    exit;
}

$plan = (string)($currentUser['plans'] ?? 'Nekads');

$errors = [];
$success = '';

$source = $_GET['source'] ?? '';
$isAdminSource = ($source === 'admin');


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

if (!$isEdit && $plan === 'Bezmaksas') {
    $activeCount = 0;
    $cntStmt = $savienojums->prepare("SELECT COUNT(*) as c FROM est_homes WHERE ipasnieka_id = ? AND statuss = 'Aktivs'");
    if ($cntStmt) {
        $uid = (int)($currentUser['lietotaja_id'] ?? 0);
        $cntStmt->bind_param('i', $uid);
        $cntStmt->execute();
        $r = $cntStmt->get_result();
        $row = $r ? $r->fetch_assoc() : null;
        $activeCount = (int)($row['c'] ?? 0);
        $cntStmt->close();
    }
    if ($activeCount >= 1) {
        $_SESSION['owner_flash'] = ['type' => 'error', 'message' => 'Bezmaksas plānā var būt tikai 1 aktīvs sludinājums.'];
        header('Location: ' . main_route('property.myhomes'));
        exit;
    }
}

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


    if (!$isAdminOrMod && (int)$existingHome['ipasnieka_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
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
if ($plan === 'Sudraba') {
    $galleryLimit = 9;
} elseif ($plan === 'Zelta') {
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
	        if ($len($address) > 50) {
	            $errors[] = 'Adrese nedrīkst būt garāka par 50 rakstīmēm.';
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
        $ownerId = (int)($_SESSION['user_id'] ?? 0);

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
            if ($isAdminOrMod) {
                $sql = "UPDATE est_homes SET 
                    nosaukums=?, pilseta=?, adrese=?, atrasanas_vieta=?, kategorija=?, veids=?, 
                    cena=?, platiba=?, gulamistabas=?, vannasistabas=?, stavs=?, stavu_info=?, 
                    apraksts=?, planojums=?, karte=?, ertibas=?, 
                    galvenais_attels=?, galerija=?, ires_maksa=?, komunalo_maksa=?, kopa_maksa=?, pirts_cena_diena=?, balla_cena_diena=?, statuss='Melnraksts'
                    WHERE id=?";
                $stmt = $savienojums->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param(
                        'ssssssddiiisssssssdddddi',
                        $title, $city, $address, $location_text, $property_category, $type,
                        $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                        $description, $layout_text, $map_text, $amenities,
                        $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal,
                        $editId
                    );
                }

            } else {
                $sql = "UPDATE est_homes SET 
        nosaukums=?, pilseta=?, adrese=?, atrasanas_vieta=?, kategorija=?, veids=?, 
        cena=?, platiba=?, gulamistabas=?, vannasistabas=?, stavs=?, stavu_info=?, 
        apraksts=?, planojums=?, karte=?, ertibas=?, 
        galvenais_attels=?, galerija=?, ires_maksa=?, komunalo_maksa=?, kopa_maksa=?, pirts_cena_diena=?, balla_cena_diena=?, statuss='Melnraksts'
        WHERE id=? AND ipasnieka_id=?";
                $stmt = $savienojums->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param(
                            'ssssssddiiisssssssdddddi',
                            $title, $city, $address, $location_text, $property_category, $type,
                            $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                            $description, $layout_text, $map_text, $amenities,
                            $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal,
                            $editId, $ownerId
                    );
                }
            }
        } else {
            $sql = "INSERT INTO est_homes
                (ipasnieka_id, nosaukums, pilseta, adrese, atrasanas_vieta, kategorija, veids, cena, platiba, gulamistabas, vannasistabas, stavs, stavu_info, apraksts, planojums, karte, ertibas, galvenais_attels, galerija, ires_maksa, komunalo_maksa, kopa_maksa, pirts_cena_diena, balla_cena_diena, statuss)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Melnraksts')";
            $stmt = $savienojums->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    'issssssddiiisssssssdddddd',
                    $ownerId, $title, $city, $address, $location_text, $property_category, $type,
                    $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                    $description, $layout_text, $map_text, $amenities,
                    $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal
                );
            }
        }

        if ($stmt) {
            if ($stmt->execute()) {
                if ($isAdminOrMod && $isAdminSource) {
                    $_SESSION['admin_success'] = 'approve_property';
                    header('Location: ' . admin_route('listings'));
                    exit;
                }
                $_SESSION['property_success'] = $isEdit ? 'edit' : 'create';

                if (function_exists('main_redirect')) {
                    main_redirect('property.myhomes');
                } else {
                    header('Location: ' . main_route('property.myhomes'));
                    exit;
                }
            } else {
                $errors[] = 'Neizdevās saglabāt sludinājumu: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Neizdevās sagatavot pieprasījumu: ' . $savienojums->error;
        }
    }
}

$pageTitle = ($isEdit ? 'Rediģēt sludinājumu' : 'Izveidot sludinājumu') . ' - HomeEstate';
$extraStyles = ['newhome'];
$bodyClass = 'owner-page newhome-page';
$bodyData = [
    'gallery-limit' => $galleryLimit,
    'gallery-json' => $gallery_json,
    'has-existing-main' => $main_image ? 'true' : 'false',
    'app-url' => app_url("")
];
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
<?php if ($isEdit): ?>
    <div class="back-button-wrap">
        <?php if ($isAdminSource): ?>
            <a href="<?php echo admin_route('listings'); ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Atgriezties uz admin paneli
            </a>
        <?php else: ?>
            <a href="<?php echo main_route('property.myhomes'); ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Atgriezties uz mani sludinājumi
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="newhome-shell">
    <?php if ($success): ?>
        <div class="notice success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="notice error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>

    <div class="step-status" id="step-status">1/5: Pamatinformācija</div>

    <form method="POST" action="<?php echo main_route('property.create') . ($isAdminSource ? '?source=admin' : ''); ?>" enctype="multipart/form-data" id="newhome-form">
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
                    <div class="check-group">
                        <input type="checkbox" id="has-pirts" name="has_pirts" value="1" <?php echo $has_pirts ? 'checked' : ''; ?>>
                        <label for="has-pirts">Pirts (papildu maksa)</label>
                    </div>
                    <div id="pirts-price-wrap" class="check-price-wrap" style="<?php echo $has_pirts ? '' : 'display:none;'; ?>">
                        <input type="number" min="0" max="999" oninput=" this.value = this.value.replace(/[^0-9]/g, '');if (this.value.length > 3) {
            this.value = this.value.slice(0, 3);}" name="pirts_price_per_day" id="pirts-price-per-day" value="<?php echo htmlspecialchars($pirts_price_per_day); ?>" placeholder="50">
                        <p class="muted small">Cena par 1 dienu.</p>
                    </div>
                </div>
                <div class="short-rent-only hidden">
                    <div class="check-group">
                        <input type="checkbox" id="has-balla" name="has_balla" value="1" <?php echo $has_balla ? 'checked' : ''; ?>>
                        <label for="has-balla">Baļļa (papildu maksa)</label>
                    </div>
                    <div id="balla-price-wrap" class="check-price-wrap" style="<?php echo $has_balla ? '' : 'display:none;'; ?>">
                        <input type="number" min="0" max="999" oninput="this.value = this.value.replace(/[^0-9]/g, '');if (this.value.length > 3) {
            this.value = this.value.slice(0, 3);}" name="balla_price_per_day" id="balla-price-per-day" value="<?php echo htmlspecialchars($balla_price_per_day); ?>" placeholder="50">
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


<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {

    setTimeout(function() {

        const hasPirtsCheckbox = document.getElementById('has-pirts');
        const hasBallaCheckbox = document.getElementById('has-balla');

        const pirtsPriceWrap = document.getElementById('pirts-price-wrap');
        const ballaPriceWrap = document.getElementById('balla-price-wrap');

        if (hasPirtsCheckbox && pirtsPriceWrap) {
            hasPirtsCheckbox.addEventListener('change', function () {
                pirtsPriceWrap.style.display = this.checked ? 'block' : 'none';
            });
        }

        if (hasBallaCheckbox && ballaPriceWrap) {
            hasBallaCheckbox.addEventListener('change', function () {
                ballaPriceWrap.style.display = this.checked ? 'block' : 'none';
            });
        }
        

        const rentPriceDisplay = document.getElementById('short-rent-price-display');
        const shortRentPriceDisplay = document.getElementById('short-rent-price-display');
        const buyPriceDisplay = document.getElementById('buy-price-display');
        

        if (rentPriceDisplay) {
            rentPriceDisplay.style.display = 'block';
        }
        if (shortRentPriceDisplay) {
            shortRentPriceDisplay.style.display = 'block';
        }
        if (buyPriceDisplay) {
            buyPriceDisplay.style.display = 'block';
        }
        
        if (type === 'pardsod') {
            rentPriceDisplay.style.display = 'block';
            shortRentPriceDisplay.style.display = 'block';
            buyPriceDisplay.style.display = 'none';
        } else if (type === 'ire') {
            rentPriceDisplay.style.display = 'block';
            shortRentPriceDisplay.style.display = 'none';
            buyPriceDisplay.style.display = 'none';
        } else {
            rentPriceDisplay.style.display = 'none';
            shortRentPriceDisplay.style.display = 'block';
            buyPriceDisplay.style.display = 'block';
        }
        

        const propertyTypeRadios = document.querySelectorAll('input[name="veids"]');
        propertyTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updatePriceDisplay(this.value);
            });
        });
        

        const initialType = document.querySelector('input[name="veids"]:checked')?.value || 'pardsod';
        updatePriceDisplay(initialType);
    }, 100);

    const hasPirtsCheckbox = document.getElementById('has-pirts');
    const hasBallaCheckbox = document.getElementById('has-balla');
    const pirtsPriceWrap = document.getElementById('pirts-price-wrap');
    const ballaPriceWrap = document.getElementById('balla-price-wrap');
    
    if (hasPirtsCheckbox) {
        hasPirtsCheckbox.addEventListener('change', function() {
            pirtsPriceWrap.style.display = this.checked ? 'flex' : 'none';
        });
    }
    
    if (hasBallaCheckbox) {
        hasBallaCheckbox.addEventListener('change', function() {
            if (ballaPriceWrap) {
                ballaPriceWrap.style.display = this.checked ? 'flex' : 'none';
            }
        });
    }
    

    const rentPriceDisplay = document.getElementById('short-rent-price-display');
    const shortRentPriceDisplay = document.getElementById('short-rent-price-display');
    const buyPriceDisplay = document.getElementById('buy-price-display');
    

    function updatePriceDisplay(type) {
        if (rentPriceDisplay) {
            rentPriceDisplay.style.display = 'block';
        }
        if (shortRentPriceDisplay) {
            shortRentPriceDisplay.style.display = 'block';
        }
        if (buyPriceDisplay) {
            buyPriceDisplay.style.display = 'block';
        }
        
        if (type === 'pardsod') {
            if (rentPriceDisplay) rentPriceDisplay.style.display = 'block';
            if (shortRentPriceDisplay) shortRentPriceDisplay.style.display = 'block';
            if (buyPriceDisplay) buyPriceDisplay.style.display = 'none';
        } else if (type === 'ire') {
            if (rentPriceDisplay) rentPriceDisplay.style.display = 'block';
            if (shortRentPriceDisplay) shortRentPriceDisplay.style.display = 'block';
            if (buyPriceDisplay) buyPriceDisplay.style.display = 'none';
        } else {
            if (rentPriceDisplay) rentPriceDisplay.style.display = 'none';
            if (shortRentPriceDisplay) shortRentPriceDisplay.style.display = 'block';
            if (buyPriceDisplay) buyPriceDisplay.style.display = 'block';
        }
    }
    

    const propertyTypeRadios = document.querySelectorAll('input[name="veids"]');
    propertyTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updatePriceDisplay(this.value);
        });
    });
    

    const initialType = document.querySelector('input[name="veids"]:checked')?.value || 'pardsod';
    updatePriceDisplay(initialType);
});
</script>
