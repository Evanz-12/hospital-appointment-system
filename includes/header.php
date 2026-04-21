<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'Hospital Appointment System') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <?php if (isset($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body>

<?php
// Flash message display
if (!empty($_SESSION['flash'])):
  $flash = $_SESSION['flash'];
  unset($_SESSION['flash']);
?>
<div class="flash-message flash-<?= htmlspecialchars($flash['type']) ?>" id="flashMsg">
  <i class="fa <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
  <span class="flash-text"><?= htmlspecialchars($flash['message']) ?></span>
  <button class="flash-close" onclick="document.getElementById('flashMsg').remove()">&times;</button>
</div>
<?php endif; ?>
