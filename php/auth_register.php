<?php
// auth_register.php
require_once 'session_init.php';
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract basic fields
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = $first_name . ' ' . $last_name; // Fallback for backwards compatibility
    $age = (int)($_POST['age'] ?? 0);
    $birthdate = $_POST['birthdate'] ?? '';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Classifications
    $classifications = isset($_POST['classification']) && is_array($_POST['classification']) ? $_POST['classification'] : [];
    $classifications_json = json_encode($classifications);
    
    // Driver fields
    $toda_name = trim($_POST['toda_name'] ?? '');
    $home_address = trim($_POST['home_address'] ?? '');
    $member_number = trim($_POST['member_number'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($birthdate)) {
        header("Location: ../register.php?error=Please fill all required fields");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.php?error=Invalid email format");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        header("Location: ../register.php?error=Email already exists");
        exit();
    }

    // Handle File Uploads (Base64 encoding for read-only environments)
    $id_documents = [];
    foreach ($_FILES as $key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            // Basic security check for extension
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'pdf'])) {
                header("Location: ../register.php?error=Invalid file type for " . htmlspecialchars($key));
                exit();
            }
            
            // Read file content and convert to base64 data URI
            $fileContent = file_get_contents($file['tmp_name']);
            if ($fileContent !== false) {
                // simple mime type detection based on extension
                $mime = 'application/octet-stream';
                if (in_array(strtolower($ext), ['jpg', 'jpeg'])) $mime = 'image/jpeg';
                else if (strtolower($ext) === 'png') $mime = 'image/png';
                else if (strtolower($ext) === 'pdf') $mime = 'application/pdf';

                $base64 = base64_encode($fileContent);
                $dataUri = 'data:' . $mime . ';base64,' . $base64;
                
                $id_documents[$key] = $dataUri;
            }
        }
    }
    $id_documents_json = json_encode($id_documents);

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO users (
                username, first_name, middle_name, last_name, age, birthdate, 
                email, password_hash, role, status, classifications, 
                toda_name, home_address, member_number, id_documents
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user', 'pending', ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);

    $params = [
        $username, $first_name, $middle_name, $last_name, $age, $birthdate,
        $email, $password_hash, $classifications_json,
        $toda_name, $home_address, $member_number, $id_documents_json
    ];

    if ($stmt->execute($params)) {
        header("Location: ../login.php?success=Account created successfully. Your account is currently pending Admin verification.");
    } else {
        header("Location: ../register.php?error=Registration failed. Please try again.");
    }

    $stmt = null;
    $conn = null;
} else {
    header("Location: ../register.php");
}
?>
