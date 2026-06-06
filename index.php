<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app_title'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <?php echo t('app_title'); ?>
        </div>
        <div class="nav-links">
            <span><a href="?lang=en" class="lang-switch">EN</a> | <a href="?lang=hi" class="lang-switch">HI</a></span>
            <a href="login.php" class="btn btn-outline"><?php echo t('login'); ?></a>
            <a href="register.php" class="btn btn-primary"><?php echo t('register'); ?></a>
        </div>
    </nav>

    <div class="container">
        <div class="hero">
            <h1><?php echo t('welcome'); ?></h1>
            <p><?php echo t('home_desc'); ?></p>
            <br>
            <a href="register.php" class="btn btn-primary"><?php echo t('register'); ?></a>
            <a href="login.php" class="btn btn-outline" style="margin-left: 10px;"><?php echo t('login'); ?></a>
        </div>
        
        <div class="card-grid">
            <div class="card">
                <h3>For Donors</h3>
                <p>Have extra food? Post a donation in seconds and let nearby volunteers or receivers know.</p>
            </div>
            <div class="card">
                <h3>For Receivers</h3>
                <p>Need food? Browse the map to find nearby donations and request them easily.</p>
            </div>
            <div class="card">
                <h3>For Volunteers</h3>
                <p>Want to help? Monitor nearby donations and help distribute them to those in need.</p>
            </div>
        </div>
    </div>
</body>
</html>
