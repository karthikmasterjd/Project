<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

try {
    // Helper to sanitize strings
    function sanitize(string $val): string {
        return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
    }

    // 1. Retrieve and Sanitize Form Fields
    $schemeName = sanitize($_POST['scheme'] ?? '');
    $amountRaw = trim($_POST['amount'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $tcAccepted = isset($_POST['tc_accept']) && $_POST['tc_accept'] === 'on';

    // 2. Perform Validations
    if ($schemeName === '' || $amountRaw === '' || $name === '' || $address === '' || $city === '' || $pincode === '' || $phone === '' || $email === '') {
        throw new RuntimeException('All mandatory fields must be completed.');
    }

    if (!$tcAccepted) {
        throw new RuntimeException('Terms & Conditions must be accepted before joining.');
    }

    // Validate Amount
    if (!is_numeric($amountRaw)) {
        throw new RuntimeException('The saving amount must be a valid number.');
    }
    $amount = (float) $amountRaw;

    if (stripos($schemeName, 'daily') !== false) {
        if ($amount < 100) {
            throw new RuntimeException('Minimum daily savings amount is ₹100.');
        }
    } else {
        // Weekly & Monthly Scheme Amounts validation
        $validAmounts = [500, 1000, 1500, 2000, 2500, 3000, 5000, 7000, 10000];
        if (!in_array((int)$amount, $validAmounts, true)) {
            throw new RuntimeException('Invalid saving amount chosen for the selected scheme.');
        }
    }

    // Validate Phone Number (digits only, length 10 to 15)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if ($cleanPhone === '' || strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
        throw new RuntimeException('Phone number must contain a valid 10-15 digit mobile number.');
    }

    // Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    // 3. Save into Database
    $db = get_db_connection();
    $stmt = $db->prepare('INSERT INTO `scheme_registrations` 
        (`scheme_name`, `amount`, `name`, `address`, `city`, `pincode`, `phone`, `email`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        $schemeName,
        $amount,
        $name,
        $address,
        $city,
        $pincode,
        $phone,
        $email
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Congratulations! You have successfully registered for the ' . $schemeName . '.'
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
