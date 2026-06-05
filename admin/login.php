<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
$login_site_title = 'Travel with IS Tours';
// Try to include DB to get site title
$db_path = __DIR__ . '/../config/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
    if (isset($pdo)) {
        try {
            $fetched = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_title'")->fetchColumn();
            if ($fetched)
                $login_site_title = $fetched;

            $cf_site_key_fetched = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'cf_site_key'")->fetchColumn();
            if ($cf_site_key_fetched)
                $cf_site_key = $cf_site_key_fetched;
        }
        catch (Exception $e) {
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($login_site_title); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00bcd4;
            --primary-dark: #0097a7;
        }
        body {
            font-family: 'Archivo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 360px;
            text-align: center;
        }
        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: contain;
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .login-container h2 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 24px;
        }
        .login-container p {
            margin: 0 0 30px 0;
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
        }
        .form-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
            outline: none;
        }
        .btn-login {
            width: 100%;
            background-color: var(--primary-color);
            color: #fff;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #c62828;
            text-align: left;
        }
        .footer-text {
            margin-top: 25px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="login-container">
    <img src="../assets/images/logo/logo.png" alt="Logo" class="logo-img">
    <h2>Welcome Back!</h2>
    <p>Sign in to your admin dashboard</p>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i> 
            <?php echo $_SESSION['error'];
    unset($_SESSION['error']); ?>
        </div>
    <?php
endif; ?>

    <form action="authenticate.php" method="POST">
        <div class="form-group">
            <label>Username</label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
        </div>
        
        <?php if (!empty($cf_site_key)): ?>
            <div class="form-group" style="display: flex; justify-content: center; margin-bottom: 20px;">
                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($cf_site_key); ?>"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            </div>
        <?php
endif; ?>

        <button type="submit" class="btn-login">
            Login Now <i class="fas fa-arrow-right" style="font-size: 14px;"></i>
        </button>
    </form>
    
    <div class="footer-text">
        &copy; <?php echo date('Y'); ?> Travel with IS Tours
    </div>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        var turnstileResponse = document.querySelector('[name="cf-turnstile-response"]');
        if (turnstileResponse && !turnstileResponse.value) {
            e.preventDefault();
            alert('Please complete the security check (Turnstile) before logging in.');
        }
    });
</script>

</body>
</html>
