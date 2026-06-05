<?php
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = 'booking';

    // Step 1
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Step 2
    $travel_date = $_POST['travel_date'] ?? '';
    $nights = $_POST['nights'] ?? '';
    $adults = $_POST['adults'] ?? '';
    $tour_package = $_POST['tour_package'] ?? '';
    $accommodation = $_POST['accommodation'] ?? ''; // Yes/No

    // Step 3
    $special_notes = trim($_POST['special_notes'] ?? '');
    $contact_method = $_POST['contact_method'] ?? '';

    // Turnstile Validation (Server Side)
    $settings_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'cf_secret_key'");
    $secret_key = $settings_stmt->fetchColumn();

    if ($secret_key && isset($_POST['cf-turnstile-response'])) {
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
        $result = file_get_contents($verify_url, false, $context);
        $response_data = json_decode($result);

        if (!$response_data->success) {
            header("Location: booking-inquiry.php?status=error&msg=captcha_failed");
            exit;
        }
    }

    try {
        // Construct detailed message for admin viewing
        $full_message = "New Booking Inquiry:\n\n";
        $full_message .= "Name: $title $name\n";
        $full_message .= "Country: $country\n";
        $full_message .= "Email: $email\n";
        $full_message .= "Phone: $phone\n\n";

        $full_message .= "Travel Details:\n";
        $full_message .= "Date: $travel_date\n";
        $full_message .= "Nights: $nights\n";
        $full_message .= "Adults: $adults\n";
        $full_message .= "Preferred Package: $tour_package\n";
        $full_message .= "Need Accommodation: $accommodation\n\n";

        $full_message .= "Additional Info:\n";
        $full_message .= "Preferred Contact Method: $contact_method\n";
        $full_message .= "Special Notes:\n$special_notes";

        // Save to Database (using existing inquiries table)
        // We use the 'message' column to store all details for simplicity
        $stmt = $pdo->prepare("INSERT INTO inquiries (type, name, email, phone, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$type, "$title $name", $email, $phone, $full_message]);

        // Send Notification Email
        require_once 'includes/mailer.php';
        sendInquiryEmail($pdo, "New Booking Inquiry: $title $name", $full_message);

        // Redirect with success message
        header("Location: booking-inquiry.php?status=success");
        exit;

    }
    catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        header("Location: booking-inquiry.php?status=error");
        exit;
    }
}
else {
    header("Location: index.php");
    exit;
}
?>
