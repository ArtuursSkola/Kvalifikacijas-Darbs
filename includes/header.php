<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';


if (!function_exists('isActive')) {
    function isActive($pageName) {
        $current = basename($_SERVER['PHP_SELF']);
        return ($current === $pageName) ? 'active' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'HomeEstate - Tavs majoklis'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset_path('Images/Logo.png'); ?>">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php
    $assetVer = function (string $rel): string {
        $rel = ltrim($rel, '/');
        $p = dirname(__DIR__) . '/' . $rel;
        if (is_file($p)) {
            return (string)filemtime($p);
        }
        return '1.0.1';
    };
    ?>
    <link rel="stylesheet" href="<?php echo asset_path('style.css'); ?>?v=<?php echo $assetVer('style.css'); ?>">
    <?php if (isset($extraStyles)): ?>
        <?php foreach ($extraStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo asset_path('css/' . $style . '.css'); ?>?v=<?php echo $assetVer('css/' . $style . '.css'); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    

    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['lietotajs', 'ipasnieks'], true)): ?>
        <link rel="stylesheet" href="<?php echo asset_path('css/support.css'); ?>?v=<?php echo $assetVer('css/support.css'); ?>">
    <?php endif; ?>
</head>
<?php
$resolvedBodyClass = trim((string)($bodyClass ?? ''));

$resolvedBodyAttributes = '';
if (isset($bodyData) && is_array($bodyData)) {
    foreach ($bodyData as $dataKey => $dataValue) {
        if ($dataValue === null) continue;
        $attrName = 'data-' . preg_replace('/[^a-z0-9\\-]/i', '', (string)$dataKey);
        $resolvedBodyAttributes .= ' ' . $attrName . '="' . htmlspecialchars((string)$dataValue, ENT_QUOTES) . '"';
    }
}
if (isset($bodyAttributes) && is_string($bodyAttributes) && trim($bodyAttributes) !== '') {
    $resolvedBodyAttributes .= ' ' . trim($bodyAttributes);
}
?>
<body class="<?php echo htmlspecialchars($resolvedBodyClass, ENT_QUOTES); ?>"<?php echo $resolvedBodyAttributes; ?>>

<?php include __DIR__ . '/navbar.php'; ?>
<?php include __DIR__ . '/popup_system.php'; ?>

<?php
if (strpos($_SERVER['PHP_SELF'], '/Admin/') === false) {
    @include_once __DIR__ . '/cron_trigger.php';
}
?>


<?php include_once __DIR__ . '/support.php'; ?>
