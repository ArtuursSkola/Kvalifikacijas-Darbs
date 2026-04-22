<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php');
}
require $configPath;

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','moderator'], true)) {
    header('Location: ' . admin_route('login'));
    exit;
}

$errors = [];
$success = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $savienojums->prepare("DELETE FROM est_homes WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $delId);
        if ($stmt->execute()) {
            $success = 'Sludinājums dzēsts.';
        }
        $stmt->close();
    }
}

if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $approveId = (int)$_GET['approve'];
    $stmt = $savienojums->prepare("UPDATE est_homes SET status = 'Aktivs' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $approveId);
        if ($stmt->execute()) {
            $success = 'Sludinājums apstiprināts un tagad ir aktīvs!';
        }
        $stmt->close();
    }
}

if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $rejectId = (int)$_GET['reject'];
    $stmt = $savienojums->prepare("UPDATE est_homes SET status = 'Noraidīts' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $rejectId);
        if ($stmt->execute()) {
            $success = 'Sludinājums noraidīts.';
        }
        $stmt->close();
    }
}

if (isset($_POST['create_listing'])) {
    $title = trim($_POST['title'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $type = $_POST['type'] ?? 'rent';
    $price = (float)($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'Melnraksts';
    $description = trim($_POST['description'] ?? '');
    
    if ($title !== '' && $city !== '') {
        $stmt = $savienojums->prepare("INSERT INTO est_homes (title, city, type, price, status, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssdss', $title, $city, $type, $price, $status, $description);
        if ($stmt->execute()) {
            $success = 'Sludinājums izveidots.';
        }
        $stmt->close();
    }
}

if (isset($_POST['edit_listing'])) {
    $id = (int)($_POST['listing_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $type = $_POST['type'] ?? 'rent';
    $price = (float)($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'Melnraksts';
    $description = trim($_POST['description'] ?? '');
    
    if ($id > 0 && $title !== '') {
        $stmt = $savienojums->prepare("UPDATE est_homes SET title=?, city=?, type=?, price=?, status=?, description=? WHERE id=?");
        $stmt->bind_param('sssdssi', $title, $city, $type, $price, $status, $description, $id);
        if ($stmt->execute()) {
            $success = 'Sludinājums atjaunināts.';
        }
        $stmt->close();
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$whereConditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $whereConditions[] = "(title LIKE ? OR city LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}
if ($filterType !== '') {
    $whereConditions[] = "type = ?";
    $params[] = $filterType;
    $types .= 's';
}
if ($filterStatus !== '') {
    $whereConditions[] = "status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$countSql = "SELECT COUNT(*) FROM est_homes $whereClause";
if ($params) {
    $stmt = $savienojums->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalHomes = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
} else {
    $totalHomes = $savienojums->query($countSql)->fetch_row()[0];
}
$totalPages = max(1, ceil($totalHomes / $perPage));

$homes = [];
$sql = "SELECT h.*, u.lietotajvards as owner_name FROM est_homes h LEFT JOIN est_lietotaji u ON h.owner_id = u.lietotaja_id $whereClause ORDER BY h.created_at DESC LIMIT ? OFFSET ?";
if ($params) {
    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
} else {
    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $homes[] = $row;
}
$stmt->close();

$totalCount = $savienojums->query("SELECT COUNT(*) FROM est_homes")->fetch_row()[0];
$activeCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status='Aktivs'")->fetch_row()[0];
$draftCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status='Melnraksts' OR status='' OR status IS NULL")->fetch_row()[0];
$pendingCount = $draftCount; 
$rentCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE type='rent'")->fetch_row()[0];
$buyCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE type='buy'")->fetch_row()[0];
$rejectedCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status='Noraidīts'")->fetch_row()[0];

function buildUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    unset($params['delete']);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sludinājumi - Admin</title>
    <link rel="icon" type="image/png" href="../Images/Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">Home<span>Estate</span></div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo admin_route('dashboard'); ?>"><i class="fas fa-home"></i> Pārskats</a></li>
            <li><a href="<?php echo admin_route('users'); ?>"><i class="fas fa-users"></i> Lietotāji</a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo admin_route('moderators'); ?>"><i class="fas fa-user-shield"></i> Moderatori</a></li>
            <?php endif; ?>
            <li><a href="<?php echo admin_route('listings'); ?>" class="active"><i class="fas fa-building"></i> Sludinājumi</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Pirkumi</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> Statistika</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Iestatījumi</a></li>
        </ul>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            <div>
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-building"></i> Sludinājumi</h1>
            <button class="btn-add" onclick="openModal('createModal')"><i class="fas fa-plus"></i> Jauns sludinājums</button>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <div>
                    <div class="val"><?php echo $totalCount; ?></div>
                    <div class="lbl">Kopā</div>
                </div>
            </div>
            <div class="stat-card green">
                <i class="fas fa-check-circle"></i>
                <div>
                    <div class="val"><?php echo $activeCount; ?></div>
                    <div class="lbl">Aktīvi</div>
                </div>
            </div>
            <div class="stat-card purple">
                <i class="fas fa-clock"></i>
                <div>
                    <div class="val"><?php echo $draftCount; ?></div>
                    <div class="lbl">Gaida apstiprinājumu</div>
                </div>
            </div>
            <div class="stat-card blue">
                <i class="fas fa-key"></i>
                <div>
                    <div class="val"><?php echo $rentCount; ?></div>
                    <div class="lbl">Īre</div>
                </div>
            </div>
            <div class="stat-card orange">
                <i class="fas fa-tag"></i>
                <div>
                    <div class="val"><?php echo $buyCount; ?></div>
                    <div class="lbl">Pārdošana</div>
                </div>
            </div>
            <?php if ($rejectedCount > 0): ?>
            <div class="stat-card red">
                <i class="fas fa-ban"></i>
                <div>
                    <div class="val"><?php echo $rejectedCount; ?></div>
                    <div class="lbl">Noraidīti</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>Visi sludinājumi (<?php echo $totalHomes; ?>)</h3>
                <form class="filters" method="GET">
                    <input type="text" name="search" placeholder="Meklēt..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="type">
                        <option value="">Visi tipi</option>
                        <option value="rent" <?php echo $filterType === 'rent' ? 'selected' : ''; ?>>Īre</option>
                        <option value="buy" <?php echo $filterType === 'buy' ? 'selected' : ''; ?>>Pārdošana</option>
                    </select>
                    <select name="status">
                        <option value="">Visi statusi</option>
                        <option value="Aktivs" <?php echo $filterStatus === 'Aktivs' ? 'selected' : ''; ?>>Aktīvs</option>
                        <option value="Melnraksts" <?php echo $filterStatus === 'Melnraksts' ? 'selected' : ''; ?>>Melnraksts</option>
                        <option value="Noraidīts" <?php echo $filterStatus === 'Noraidīts' ? 'selected' : ''; ?>>Noraidīts</option>
                        <option value="Pardots" <?php echo $filterStatus === 'Pardots' ? 'selected' : ''; ?>>Pārdots</option>
                    </select>
                    <button type="submit"><i class="fas fa-filter"></i></button>
                    <?php if ($search || $filterType || $filterStatus): ?>
                        <a href="<?php echo admin_route('listings'); ?>" class="clear">Notīrīt</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nosaukums</th>
                            <th>Pilsēta</th>
                            <th>Tips</th>
                            <th>Cena</th>
                            <th>Īpašnieks</th>
                            <th>Statuss</th>
                            <th>Datums</th>
                            <th>Darbības</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($homes)): ?>
                            <tr><td colspan="9" style="text-align:center;color:#6b7a8f;padding:40px;">Nav sludinājumu</td></tr>
                        <?php else: ?>
                            <?php foreach ($homes as $h): ?>
                                <tr>
                                    <td><?php echo $h['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($h['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($h['city']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $h['type'] === 'rent' ? 'blue' : 'orange'; ?>">
                                            <?php echo $h['type'] === 'rent' ? 'Īre' : 'Pārdošana'; ?>
                                        </span>
                                    </td>
                                    <td class="price">€<?php echo number_format($h['price'], 0, ',', ' '); ?></td>
                                    <td><?php echo htmlspecialchars($h['owner_name'] ?? '—'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = ['Aktivs' => 'green', 'Melnraksts' => 'gray', 'Noraidīts' => 'red', 'Pardots' => 'blue'];
                                        $statusLabel = ['Aktivs' => 'Aktīvs', 'Melnraksts' => 'Gaida apstiprinājumu', 'Noraidīts' => 'Noraidīts', 'Pardots' => 'Pārdots'];
                                        $st = $h['status'] ?: 'Melnraksts';
                                        ?>
                                        <span class="badge <?php echo $statusClass[$st] ?? 'gray'; ?>">
                                            <?php echo $statusLabel[$st] ?? $st; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $h['created_at'] ? date('d.m.Y', strtotime($h['created_at'])) : '—'; ?></td>
                                    <td>
                                        <div class="actions">
                                            <?php if (in_array($h['status'], ['Melnraksts', '', null])): ?>
                                                <a href="<?php echo buildUrl(['approve' => $h['id']]); ?>" class="btn-sm approve" onclick="return confirm('Apstiprināt šo sludinājumu?')" title="Apstiprināt"><i class="fas fa-check"></i></a>
                                                <a href="<?php echo buildUrl(['reject' => $h['id']]); ?>" class="btn-sm reject" onclick="return confirm('Noraidīt šo sludinājumu?')" title="Noraidīt"><i class="fas fa-times"></i></a>
                                            <?php endif; ?>
                                            <button class="btn-sm edit" onclick='openEditModal(<?php echo json_encode($h); ?>)' title="Rediģēt"><i class="fas fa-edit"></i></button>
                                            <a href="<?php echo main_route('property.show', ['id' => $h['id']]); ?>" class="btn-sm view" target="_blank" title="Skatīt"><i class="fas fa-eye"></i></a>
                                            <a href="<?php echo buildUrl(['delete' => $h['id']]); ?>" class="btn-sm delete" onclick="return confirm('Vai tiešām dzēst?')" title="Dzēst"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildUrl(['page' => $page - 1]); ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo buildUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo buildUrl(['page' => $page + 1]); ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Create Modal -->
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Jauns sludinājums</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nosaukums *</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilsēta *</label>
                            <input type="text" name="city" required>
                        </div>
                        <div class="form-group">
                            <label>Cena (€)</label>
                            <input type="number" name="price" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tips</label>
                            <select name="type">
                                <option value="rent">Īre</option>
                                <option value="buy">Pārdošana</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statuss</label>
                            <select name="status">
                                <option value="Melnraksts">Melnraksts</option>
                                <option value="Aktivs">Aktīvs</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Apraksts</label>
                        <textarea name="description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Atcelt</button>
                    <button type="submit" name="create_listing" class="btn btn-primary">Izveidot</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Rediģēt sludinājumu</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="listing_id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nosaukums *</label>
                        <input type="text" name="title" id="edit_title" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilsēta *</label>
                            <input type="text" name="city" id="edit_city" required>
                        </div>
                        <div class="form-group">
                            <label>Cena (€)</label>
                            <input type="number" name="price" id="edit_price" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tips</label>
                            <select name="type" id="edit_type">
                                <option value="rent">Īre</option>
                                <option value="buy">Pārdošana</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statuss</label>
                            <select name="status" id="edit_status">
                                <option value="Melnraksts">Melnraksts</option>
                                <option value="Aktivs">Aktīvs</option>
                                <option value="Noraidīts">Noraidīts</option>
                                <option value="Pardots">Pārdots</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Apraksts</label>
                        <textarea name="description" id="edit_description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Atcelt</button>
                    <button type="submit" name="edit_listing" class="btn btn-primary">Saglabāt</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }
    function openEditModal(listing) {
        document.getElementById('edit_id').value = listing.id;
        document.getElementById('edit_title').value = listing.title || '';
        document.getElementById('edit_city').value = listing.city || '';
        document.getElementById('edit_price').value = listing.price || '';
        document.getElementById('edit_type').value = listing.type || 'rent';
        document.getElementById('edit_status').value = listing.status || 'Melnraksts';
        document.getElementById('edit_description').value = listing.description || '';
        openModal('editModal');
    }
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
    </script>
</body>
</html>
