<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../con_db.php';

header('Content-Type: application/json');

$user_id = (int)$_SESSION['user_id'];
$message_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$tema = isset($_POST['tema']) ? trim((string)$_POST['tema']) : '';
$apraksts = isset($_POST['apraksts']) ? trim((string)$_POST['apraksts']) : '';

if ($message_id <= 0 || $tema === '' || $apraksts === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$allowedTemas = ['Maksājumi', 'Rezervācijas', 'Profils', 'Mans sludinājums', 'Cits'];
if (!in_array($tema, $allowedTemas, true)) {
    echo json_encode(['success' => false, 'message' => 'Lūdzu izvēlieties derīgu tēmu.']);
    exit();
}

try {
    $kept_images = isset($_POST['kept_images']) ? explode(',', $_POST['kept_images']) : [];
    $kept_images = array_filter(array_map('trim', $kept_images));

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
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $originalName = $files['name'][$i];
            $tmpPath      = $files['tmp_name'][$i];
            $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $mime         = mime_content_type($tmpPath);
            if (!in_array($mime, $allowedMimes, true) || !in_array($ext, $allowedExts, true)) {
                echo json_encode(['success' => false, 'message' => 'Atļautie failu formāti: JPG, PNG.']);
                exit;
            }
            $maxBytes = 5 * 1024 * 1024;
            if ($files['size'][$i] > $maxBytes) {
                echo json_encode(['success' => false, 'message' => 'Fails pārāk liels']);
                exit;
            }
            $newName = 'palidziba_' . $user_id . '_' . time() . '_' . $i . '.' . $ext;
            $destPath = $uploadDir . $newName;
            if (move_uploaded_file($tmpPath, $destPath)) {
                $uploadedPaths[] = 'Images/palidziba/' . $newName;
            }
        }
    }

    $final_images = array_merge($kept_images, $uploadedPaths);
    if (count($final_images) > 3) {
        echo json_encode(['success' => false, 'message' => 'Kopā var pievienot ne vairāk kā 3 attēlus.']);
        exit();
    }

    $pievienotais_fails = !empty($final_images) ? implode(',', $final_images) : null;

    $query = "UPDATE est_palidziba SET tema = ?, jautajuma_apraksts = ?, pievienotais_fails = ? WHERE id = ? AND lietotaja_id = ?";
    $stmt = $savienojums->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    $stmt->bind_param('sssii', $tema, $apraksts, $pievienotais_fails, $message_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Message updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Message not found or no changes made']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
