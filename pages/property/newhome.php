<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../routes/main.php';
require_once __DIR__ . '/../../routes/admin.php';
require_once dirname(__DIR__, 2) . '/con_db.php';
require_once dirname(__DIR__, 2) . '/includes/account.php';
require_once dirname(__DIR__, 2) . '/includes/latvia_city_coords.php';

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

/** @return array{0: float, 1: float}|null */
function home_parse_lat_lng_post(?string $latIn, ?string $lngIn): ?array
{
    $latIn = trim(str_replace(',', '.', (string)$latIn));
    $lngIn = trim(str_replace(',', '.', (string)$lngIn));
    if ($latIn === '' || $lngIn === '') {
        return null;
    }
    if (!is_numeric($latIn) || !is_numeric($lngIn)) {
        return null;
    }
    $lat = (float)$latIn;
    $lng = (float)$lngIn;
    if ($lat < 55.0 || $lat > 59.5 || $lng < 18.0 || $lng > 30.5) {
        return null;
    }
    return [$lat, $lng];
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
$pin_lat = '';
$pin_lng = '';
if ($existingHome) {
    if (isset($existingHome['latitude']) && $existingHome['latitude'] !== null && $existingHome['latitude'] !== '') {
        $pin_lat = (string)$existingHome['latitude'];
    }
    if (isset($existingHome['longitude']) && $existingHome['longitude'] !== null && $existingHome['longitude'] !== '') {
        $pin_lng = (string)$existingHome['longitude'];
    }
}
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
// Apstrādā POST pieprasījumu (sludinājuma izveide vai rediģēšana)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $location_text = trim((string)($_POST['location_text'] ?? ''));
    $property_category = (string)($_POST['property_category'] ?? 'dzivoklis');
    // Darījuma tips (īre / pārdošana / īstermiņa īre)
    $rawType = (string)($_POST['type'] ?? '');
    if ($rawType === 'pardod') { $type = 'pardod'; } elseif ($rawType === 'istermina_ire') { $type = 'istermina_ire';
    } else { $type = 'ire'; }
    if ($type === 'ire') { $price_label = 'Cena (EUR / men.) *'; } elseif ($type === 'istermina_ire') { $price_label = 'Cena (EUR / nakti) *';
    } else { $price_label = 'Cena (EUR) *'; }
    $price = trim((string)($_POST['price'] ?? ''));
    $area = trim((string)($_POST['area'] ?? ''));
    $bedrooms = trim((string)($_POST['bedrooms'] ?? ''));
    $bathrooms = trim((string)($_POST['bathrooms'] ?? ''));
    $floor = trim((string)($_POST['floor'] ?? ''));
    $total_floors = trim((string)($_POST['total_floors'] ?? ''));
    // Apraksti un papildinformācija
    $description = trim((string)($_POST['description'] ?? ''));
    $layout_text = trim((string)($_POST['layout_text'] ?? ''));
    $map_text = trim((string)($_POST['map_text'] ?? ''));
    $amenities = trim((string)($_POST['amenities'] ?? ''));
    // Kartes koordinātas
    $pin_lat = trim((string)($_POST['latitude'] ?? ''));
    $pin_lng = trim((string)($_POST['longitude'] ?? ''));
    // Attēli
    $main_image_url = trim((string)($_POST['main_image_url'] ?? ''));
    // Īres papildizmaksas
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

    if (isset($_FILES['main_image_file']) && $_FILES['main_image_file']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['main_image_file']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Fails pārāk liels";
        }
    }
    if (isset($_FILES['gallery_files']) && is_array($_FILES['gallery_files']['name'])) {
        $count = count($_FILES['gallery_files']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                if ($_FILES['gallery_files']['size'][$i] > 5 * 1024 * 1024) {
                    $errors[] = "Fails pārāk liels";
                }
            }
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

        $postedPin = home_parse_lat_lng_post($pin_lat, $pin_lng);
        $latBind = null;
        $lngBind = null;
        if ($postedPin !== null) {
            $latBind = number_format($postedPin[0], 7, '.', '');
            $lngBind = number_format($postedPin[1], 7, '.', '');
        }

        if ($isEdit) {
            // Determine whether only price-related fields changed.
            // If the listing is currently 'Aktivs' and nothing else changed, keep it active
            // so the owner doesn't have to wait for mod approval after a simple price update.
            $existingStatus = (string)($existingHome['statuss'] ?? '');
            $priceOnlyFields = true;
            if ($existingStatus === 'Aktivs') {
                // Non-price fields that would require re-review
                $nonPriceChanged =
                    $title          !== (string)($existingHome['nosaukums'] ?? '')
                    || $city            !== (string)($existingHome['pilseta'] ?? '')
                    || $address         !== (string)($existingHome['adrese'] ?? '')
                    || $location_text   !== (string)($existingHome['atrasanas_vieta'] ?? '')
                    || $property_category !== (string)($existingHome['kategorija'] ?? '')
                    || $type            !== (string)($existingHome['veids'] ?? '')
                    || abs($areaVal  - (float)($existingHome['platiba']        ?? 0)) > 0.001
                    || $bedsVal      !== (int)($existingHome['gulamistabas']   ?? 0)
                    || $bathsVal     !== (int)($existingHome['vannasistabas']  ?? 0)
                    || $floorInfo    !== (string)($existingHome['stavu_info']  ?? '')
                    || $description  !== (string)($existingHome['apraksts']    ?? '')
                    || $layout_text  !== (string)($existingHome['planojums']   ?? '')
                    || $map_text     !== (string)($existingHome['karte']       ?? '')
                    || $amenities    !== (string)($existingHome['ertibas']     ?? '')
                    || $main_image   !== (string)($existingHome['galvenais_attels'] ?? '')
                    || $gallery_json !== (string)($existingHome['galerija']    ?? '[]')
                    || ($latBind !== null && abs((float)$latBind - (float)($existingHome['latitude']  ?? 0)) > 0.0000001)
                    || ($lngBind !== null && abs((float)$lngBind - (float)($existingHome['longitude'] ?? 0)) > 0.0000001);
                $priceOnlyFields = !$nonPriceChanged;
            } else {
                $priceOnlyFields = false;
            }
            // If only prices changed on an active listing, keep the existing status; otherwise require re-review
            $newStatus = ($priceOnlyFields) ? 'Aktivs' : 'Melnraksts';

            if ($isAdminOrMod) {
                $sql = "UPDATE est_homes SET 
                    nosaukums=?, pilseta=?, adrese=?, atrasanas_vieta=?, kategorija=?, veids=?, 
                    cena=?, platiba=?, gulamistabas=?, vannasistabas=?, stavs=?, stavu_info=?, 
                    apraksts=?, planojums=?, karte=?, ertibas=?, 
                    galvenais_attels=?, galerija=?, ires_maksa=?, komunalo_maksa=?, kopa_maksa=?, pirts_cena_diena=?, balla_cena_diena=?, latitude=?, longitude=?, statuss=?
                    WHERE id=?";
                $stmt = $savienojums->prepare($sql);
                if ($stmt) {
                    $adminBindTypes = 'ssssss' . 'dd' . 'iii' . str_repeat('s', 7) . 'ddddd' . 'ss' . 'si';
                    $stmt->bind_param(
                        $adminBindTypes,
                        $title, $city, $address, $location_text, $property_category, $type,
                        $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                        $description, $layout_text, $map_text, $amenities,
                        $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal,
                        $latBind, $lngBind,
                        $newStatus, $editId
                    );
                }

            } else {
                $sql = "UPDATE est_homes SET 
        nosaukums=?, pilseta=?, adrese=?, atrasanas_vieta=?, kategorija=?, veids=?, 
        cena=?, platiba=?, gulamistabas=?, vannasistabas=?, stavs=?, stavu_info=?, 
        apraksts=?, planojums=?, karte=?, ertibas=?, 
        galvenais_attels=?, galerija=?, ires_maksa=?, komunalo_maksa=?, kopa_maksa=?, pirts_cena_diena=?, balla_cena_diena=?, latitude=?, longitude=?, statuss=?
        WHERE id=? AND ipasnieka_id=?";
                $stmt = $savienojums->prepare($sql);
                if ($stmt) {
                    $ownerBindTypes = 'ssssss' . 'dd' . 'iii' . str_repeat('s', 7) . 'ddddd' . 'ss' . 'sii';
                    $stmt->bind_param(
                            $ownerBindTypes,
                            $title, $city, $address, $location_text, $property_category, $type,
                            $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                            $description, $layout_text, $map_text, $amenities,
                            $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal,
                            $latBind, $lngBind,
                            $newStatus, $editId, $ownerId
                    );
                }
            }
        } else {
            $sql = "INSERT INTO est_homes
                (ipasnieka_id, nosaukums, pilseta, adrese, atrasanas_vieta, kategorija, veids, cena, platiba, gulamistabas, vannasistabas, stavs, stavu_info, apraksts, planojums, karte, ertibas, galvenais_attels, galerija, ires_maksa, komunalo_maksa, kopa_maksa, pirts_cena_diena, balla_cena_diena, latitude, longitude, statuss)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Melnraksts')";
            $stmt = $savienojums->prepare($sql);
            if ($stmt) {
                $insertBindTypes = 'i' . str_repeat('s', 6) . 'dd' . 'iii' . str_repeat('s', 7) . str_repeat('d', 5) . 'ss';
                $stmt->bind_param(
                    $insertBindTypes,
                    $ownerId, $title, $city, $address, $location_text, $property_category, $type,
                    $priceVal, $areaVal, $bedsVal, $bathsVal, $floorVal, $floorInfo,
                    $description, $layout_text, $map_text, $amenities,
                    $main_image, $gallery_json, $rentVal, $utilVal, $totalVal, $pirtsVal, $ballaVal,
                    $latBind, $lngBind
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
                $_SESSION['property_success'] = $isEdit
                    ? ($priceOnlyFields ? 'edit_price_only' : 'edit')
                    : 'create';

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
$mapCenterForForm = latvia_city_coordinates($city !== '' ? $city : '');
$bodyData = [
    'gallery-limit' => $galleryLimit,
    'gallery-json' => $gallery_json,
    'has-existing-main' => $main_image ? 'true' : 'false',
    'app-url' => app_url(""),
    'map-center-lat' => (string)$mapCenterForForm[0],
    'map-center-lng' => (string)$mapCenterForForm[1],
    'geocode-url' => main_route('api.geocode'),
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
                <div class="newhome-map-block">
                    <label>Precīza vieta kartē (neobligāti)</label>
                    <p class="muted small">Noklikšķini kartē, lai novietotu tapu. Ja pin nav, sākumlapā tiek rādīta aptuvena pilsētas atrašanās vieta.</p>
                    <div class="newhome-map-toolbar">
                        <button type="button" class="btn-submit ghost" id="newhome-map-center-city">Centrēt pēc pilsētas</button>
                        <button type="button" class="btn-submit ghost" id="newhome-map-clear-pin">Noņemt pinu</button>
                    </div>
                    <div id="newhome-location-map" class="newhome-location-map" aria-label="Īpašuma novietojums"></div>
                    <input type="hidden" name="latitude" id="newhome-lat-input" value="<?php echo htmlspecialchars($pin_lat); ?>">
                    <input type="hidden" name="longitude" id="newhome-lng-input" value="<?php echo htmlspecialchars($pin_lng); ?>">
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

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var mapEl = document.getElementById('newhome-location-map');
        if (!mapEl) return;
        var body = document.body;
        var latInput = document.getElementById('newhome-lat-input');
        var lngInput = document.getElementById('newhome-lng-input');
        var cityInput = document.querySelector('#newhome-form input[name="city"]');
        var geocodeUrl = body.getAttribute('data-geocode-url') || '';
        var centerLat = parseFloat(body.getAttribute('data-map-center-lat') || '56.95');
        var centerLng = parseFloat(body.getAttribute('data-map-center-lng') || '24.1');
        if (!isFinite(centerLat)) centerLat = 56.95;
        if (!isFinite(centerLng)) centerLng = 24.1;
        var map = null;
        var marker = null;
        var mapBuilt = false;

        function parseHiddenPin() {
            if (!latInput || !lngInput) return null;
            var la = parseFloat(String(latInput.value).replace(',', '.'));
            var lo = parseFloat(String(lngInput.value).replace(',', '.'));
            if (!isFinite(la) || !isFinite(lo)) return null;
            return [la, lo];
        }

        function syncInputsFromLatLng(ll) {
            if (latInput) latInput.value = ll[0].toFixed(7);
            if (lngInput) lngInput.value = ll[1].toFixed(7);
        }

        function clearPin() {
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (marker && map) {
                map.removeLayer(marker);
                marker = null;
            }
        }

        function loadLeaflet(cb) {
            if (window.L) { cb(); return; }
            var lk = document.createElement('link');
            lk.rel = 'stylesheet';
            lk.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(lk);
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.onload = function () { cb(); };
            document.head.appendChild(s);
        }

        function buildMap() {
            if (mapBuilt || !mapEl) return;
            mapBuilt = true;
            loadLeaflet(function () {
                var pin = parseHiddenPin();
                var zl = pin ? 14 : 11;
                var clat = pin ? pin[0] : centerLat;
                var clng = pin ? pin[1] : centerLng;
                map = L.map(mapEl, { scrollWheelZoom: false }).setView([clat, clng], zl);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                if (pin) {
                    marker = L.marker(pin).addTo(map);
                }
                map.on('click', function (ev) {
                    var ll = [ev.latlng.lat, ev.latlng.lng];
                    syncInputsFromLatLng(ll);
                    if (marker) {
                        marker.setLatLng(ll);
                    } else {
                        marker = L.marker(ll).addTo(map);
                    }
                });
                setTimeout(function () { if (map) map.invalidateSize(); }, 200);
            });
        }

        document.addEventListener('newhome-step', function (ev) {
            if (!ev.detail || ev.detail.index !== 1) return;
            buildMap();
            setTimeout(function () { if (map) map.invalidateSize(); }, 350);
        });

        var btnClear = document.getElementById('newhome-map-clear-pin');
        if (btnClear) {
            btnClear.addEventListener('click', function () {
                clearPin();
                if (map) map.setView([centerLat, centerLng], 11);
            });
        }

        var btnCenter = document.getElementById('newhome-map-center-city');
        if (btnCenter) {
            btnCenter.addEventListener('click', function () {
                var q = cityInput && cityInput.value ? String(cityInput.value).trim() : '';
                if (!geocodeUrl) {
                    if (map) map.setView([centerLat, centerLng], 11);
                    return;
                }
                if (!q) {
                    if (map) map.setView([centerLat, centerLng], 11);
                    return;
                }
                try {
                    var url = new URL(geocodeUrl, window.location.href);
                    url.searchParams.set('q', q + ', Latvija');
                    fetch(url.toString()).then(function (r) { return r.json(); }).then(function (data) {
                        if (!data || !data.ok || !isFinite(data.lat) || !isFinite(data.lng)) return;
                        centerLat = data.lat;
                        centerLng = data.lng;
                        if (map) {
                            map.setView([centerLat, centerLng], 12);
                            map.invalidateSize();
                        }
                    }).catch(function () {});
                } catch (e) {}
            });
        }
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
