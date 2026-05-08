<?php
session_start();
require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nepareiza metode.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Lūdzu piesakieties, lai nosūtītu jautājumu.']);
    exit;
}

$lietotaja_id = (int)$_SESSION['user_id'];

$tema = trim($_POST['tema'] ?? '');
$jautajuma_apraksts = trim($_POST['jautajuma_apraksts'] ?? '');

$allowedTemas = ['Maksājumi', 'Rezervācijas', 'Profils', 'Mans sludinājums', 'Cits'];
if (!in_array($tema, $allowedTemas, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Lūdzu izvēlieties derīgu tēmu.']);
    exit;
}

if ($jautajuma_apraksts === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Jautājuma apraksts ir obligāts.']);
    exit;
}

if (mb_strlen($jautajuma_apraksts) > 1000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Jautājuma apraksts nedrīkst pārsniegt 1000 rakstzīmes.']);
    exit;
}

$uploadedPaths = [];

if (!empty($_FILES['pievienotais_fails']['name'][0])) {
    $uploadDir = __DIR__ . '/../Images/palidziba/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $allowedMimes = ['image/jpeg', 'image/png'];
    $allowedExts  = ['jpg', 'jpeg', 'png'];
    $files        = $_FILES['pievienotais_fails'];
    $count        = count($files['name']);

    if ($count > 3) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Var pievienot ne vairāk kā 3 attēlus.']);
        exit;
    }

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $originalName = $files['name'][$i];
        $tmpPath      = $files['tmp_name'][$i];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime         = mime_content_type($tmpPath);

        if (!in_array($mime, $allowedMimes, true) || !in_array($ext, $allowedExts, true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Atļautie failu formāti: JPG, PNG.']);
            exit;
        }

        $maxBytes = 5 * 1024 * 1024;
        if ($files['size'][$i] > $maxBytes) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Katra attēla izmērs nedrīkst pārsniegt 5 MB.']);
            exit;
        }

        $newName = 'palidziba_' . $lietotaja_id . '_' . time() . '_' . $i . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($tmpPath, $destPath)) {
            $uploadedPaths[] = 'Images/palidziba/' . $newName;
        }
    }
}

$pievienotais_fails = !empty($uploadedPaths) ? implode(',', $uploadedPaths) : null;
$statuss = 'Iesūtīts';

$stmt = $savienojums->prepare(
    "INSERT INTO est_palidziba (lietotaja_id, tema, jautajuma_apraksts, pievienotais_fails, statuss, atbilde, created_at)
     VALUES (?, ?, ?, ?, ?, NULL, NOW())"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Servera kļūda. Mēģiniet vēlāk.']);
    exit;
}

$stmt->bind_param('issss', $lietotaja_id, $tema, $jautajuma_apraksts, $pievienotais_fails, $statuss);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Jūsu jautājums ir veiksmīgi nosūtīts! Mēs ar jums sazināsimies drīzumā.']);
} else {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Neizdevās saglabāt datus. Mēģiniet vēlāk.']);
}
