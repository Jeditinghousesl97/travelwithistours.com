<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: login.php");
        exit;
    }

    // Turnstile Validation (Server Side)
    $settings_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'cf_secret_key'");
    $secret_key = $settings_stmt->fetchColumn();

    if ($secret_key) {
        if (!isset($_POST['cf-turnstile-response']) || empty($_POST['cf-turnstile-response'])) {
            $_SESSION['error'] = "Security check missing. Please try again.";
            header("Location: login.php");
            exit;
        }

        $turnstile_response = $_POST['cf-turnstile-response'];
        $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        $data = [
            'secret' => $secret_key,
            'response' => $turnstile_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($verify_url, false, $context);
        if ($result !== false) {
            $response_data = json_decode($result);
            if (!$response_data->success) {
                $_SESSION['error'] = "Security check failed. Please try again.";
                header("Location: login.php");
                exit;
            }
        }
        else {
            $_SESSION['error'] = "Security check service unavailable. Please try again.";
            header("Location: login.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    }
    else {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: login.php");
        exit;
    }
}
else {
    header("Location: login.php");
    exit;
}
?>
