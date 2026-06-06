<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role, is_approved FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['is_approved'] == 0) {
                    $error = "Your account is awaiting Admin approval. Please check back later.";
                } else {
                    // Password is correct and user is approved, start session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    if ($user['role'] == 'Admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login'); ?> - <?php echo t('app_title'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><a href="index.php"><?php echo t('app_title'); ?></a></div>
    </nav>
    <div class="container">
        <div class="form-container">
            <h2 style="text-align:center;"><?php echo t('login'); ?></h2>
            
            <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>
            
            <form action="" method="post">
                <div class="form-group">
                    <label><?php echo t('email'); ?></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('password'); ?></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="width:100%"><?php echo t('login'); ?></button>
                </div>
                <p style="text-align:center;"><?php echo t('dont_have_account'); ?> <a href="register.php"><?php echo t('register'); ?></a></p>
            </form>
        </div>
    </div>
</body>
</html>
