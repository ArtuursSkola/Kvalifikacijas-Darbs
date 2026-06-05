<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$response = [];

if ($userId > 0 && isset($savienojums) && $savienojums instanceof mysqli) {
    $favStmt = $savienojums->prepare("SELECT home_id FROM est_favoriti WHERE user_id = ? ORDER BY created_at DESC");
    $homeIds = [];
    if ($favStmt) {
        $favStmt->bind_param('i', $userId);
        $favStmt->execute();
        $favRes = $favStmt->get_result();
        while ($favRes && $row = $favRes->fetch_assoc()) {
            $homeIds[] = (int)$row['home_id'];
        }
        $favStmt->close();
    }

    if (!empty($homeIds)) {
        $safeIds = implode(',', array_map('intval', $homeIds));
        $homeStmt = $savienojums->prepare("SELECT id, ipasnieka_id, nosaukums, pilseta, atrasanas_vieta, veids, cena, platiba, gulamistabas, vannasistabas, apraksts, galvenais_attels, statuss, kategorija FROM est_homes WHERE id IN ($safeIds) AND statuss = 'Aktivs'");
        
        $rawHomes = [];
        $ownerIds = [];
        
        if ($homeStmt) {
            $homeStmt->execute();
            $homeRes = $homeStmt->get_result();
            while ($homeRes && $row = $homeRes->fetch_assoc()) {
                $rawHomes[$row['id']] = $row;
                if (!empty($row['ipasnieka_id'])) {
                    $ownerIds[] = (int)$row['ipasnieka_id'];
                }
            }
            $homeStmt->close();
        }


        $ownersMap = [];
        if (!empty($ownerIds)) {
            $uniqueOwnerIds = array_unique($ownerIds);
            $safeOwnerIds = implode(',', array_map('intval', $uniqueOwnerIds));
            $ownerStmt = $savienojums->prepare("SELECT lietotaja_id, lietotajvards, profila_bilde, plans FROM est_lietotaji WHERE lietotaja_id IN ($safeOwnerIds)");
            if ($ownerStmt) {
                $ownerStmt->execute();
                $ownerRes = $ownerStmt->get_result();
                while ($ownerRes && $o = $ownerRes->fetch_assoc()) {
                    $ownersMap[(int)$o['lietotaja_id']] = $o;
                }
                $ownerStmt->close();
            } else {
                $ownerStmtFallback = $savienojums->prepare("SELECT lietotaja_id, lietotajvards, profila_bilde FROM est_lietotaji WHERE lietotaja_id IN ($safeOwnerIds)");
                if ($ownerStmtFallback) {
                    $ownerStmtFallback->execute();
                    $ownerRes = $ownerStmtFallback->get_result();
                    while ($ownerRes && $o = $ownerRes->fetch_assoc()) {
                        $o['plans'] = 'Nekads';
                        $ownersMap[(int)$o['lietotaja_id']] = $o;
                    }
                    $ownerStmtFallback->close();
                }
            }
        }

        $fallbackImg = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';
        foreach ($homeIds as $hid) {
            if (!isset($rawHomes[$hid])) continue;
            
            $row = $rawHomes[$hid];
            $owner = $ownersMap[(int)($row['ipasnieka_id'] ?? 0)] ?? null;

            $desc = (string)($row['apraksts'] ?? '');
            $descShort = '';
            if ($desc !== '') {
                $slice = function_exists('mb_substr') ? mb_substr($desc, 0, 150) : substr($desc, 0, 150);
                $descShort = $slice . '...';
            }
            
            $city = (string)($row['pilseta'] ?? '');
            $locText = (string)($row['atrasanas_vieta'] ?? '');
            $location = $city . ($locText !== '' ? ', ' . $locText : '');
            
            $type = (string)($row['veids'] ?? '');
            if ($type === 'rent') $type = 'ire';
            if ($type === 'buy') $type = 'pardod';
            $badgeText = $type === 'ire' ? 'Īrēšana' : ($type === 'istermina_ire' ? 'Īstermiņa īre' : 'Pārdod');
            
            $response[] = [
                'id' => (int)$row['id'],
                'status' => (string)($row['statuss'] ?? ''),
                'is_own' => ($userId > 0 && (int)($row['ipasnieka_id'] ?? 0) === $userId),
                'title' => (string)($row['nosaukums'] ?? ''),
                'city' => $city,
                'location' => $location,
                'type' => $type,
                'category' => (string)($row['kategorija'] ?? ''),
                'price' => (float)($row['cena'] ?? 0),
                'beds' => (int)($row['gulamistabas'] ?? 0),
                'baths' => (int)($row['vannasistabas'] ?? 0),
                'size' => (int)($row['platiba'] ?? 0),
                'badge' => $badgeText,
                'image' => media_absolute_url(!empty($row['galvenais_attels']) ? (string)$row['galvenais_attels'] : $fallbackImg),
                'desc' => $descShort,
                'owner_username' => (string)($owner['lietotajvards'] ?? ''),
                'owner_pfp' => media_absolute_url((string)($owner['profila_bilde'] ?? '')),
                'owner_plan' => (string)($owner['plans'] ?? ''),
                'favorited' => true,
            ];
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
