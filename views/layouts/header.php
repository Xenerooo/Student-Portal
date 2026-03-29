<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'SIS Portal') ?></title>
    <link href="<?= APP_URL ?>/assets/css/bootstrap.css" rel="stylesheet"> 
    <link href="<?= APP_URL ?>/assets/css/app.css?view=last_<?= time() ?>_10" rel="stylesheet">
    <script defer src="<?= APP_URL ?>/assets/js/bootstrap.bundle.js"></script>
    <link rel="icon" href="<?= APP_URL ?>/assets/images/icon.png">
</head>
<body class="vh-100">
