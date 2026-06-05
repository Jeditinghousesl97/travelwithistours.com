<?php
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'] ?? 'contact'; // contact, booking, newsletter
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Server-side Validation
    if (empty($email)) {
        die("Email is required.");
    }

    try {
        // 1. Save to Database
        $stmt = $pdo->prepare("INSERT INTO inquiries (type, name, email, phone, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$type, $name, $email, $phone, $message]);

        // 2. Send Notification Email
        require_once 'includes/mailer.php';
        $email_body = "New Contact Inquiry:\n\n";
        $email_body .= "From: $name ($email)\n";
        $email_body .= "Phone: $phone\n\n";
        $email_body .= "Message:\n$message";

        sendInquiryEmail($pdo, "New Contact Inquiry: $name", $email_body);

        // Redirect with success message
        header("Location: contact.php?status=success");
        exit;

    }
    catch (PDOException $e) {
        // Log error and redirect with failure
        error_log("Database Error: " . $e->getMessage());
        header("Location: contact.php?status=error");
        exit;
    }
}
else {
    header("Location: index.php");
    exit;
}
?>
