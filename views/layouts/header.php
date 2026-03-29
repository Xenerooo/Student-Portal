<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'SIS Portal') ?></title>
    <link href="/assets/css/bootstrap.css" rel="stylesheet"> 
    <link href="/assets/css/app.css?view=last_<?= time() ?>_10" rel="stylesheet">
    <script defer src="/assets/js/bootstrap.bundle.js"></script>
    <link rel="icon" href="/assets/images/icon.png">
</head>
<body class="vh-100">
