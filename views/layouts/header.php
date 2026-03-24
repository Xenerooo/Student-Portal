<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'SIS Portal') ?></title>
    <link href="/Student-Portal/assets/css/bootstrap.css" rel="stylesheet"> 
    <link href="/Student-Portal/assets/css/app.css" rel="stylesheet">
    <script defer src="/Student-Portal/assets/js/bootstrap.bundle.js"></script>
    <link rel="icon" href="/Student-Portal/assets/images/icon.png">
</head>
<body class="portal-body">
