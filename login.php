<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// --- Database Connection ---
// Vercel හි පරිසර විචල්‍ය (Environment Variables) භාවිත කිරීම වඩාත් සුදුසුයි.
// උදා: $host = getenv('DB_HOST');
$host = "localhost"; // ඔබගේ දත්ත සමුදා host එක
$user = "root";      // ඔබගේ දත්ත සමුදා username එක
$pass = "";          // ඔබගේ දත්ත සමුදා මුරපදය
$db = "mahavihara";  // ඔබගේ දත්ත සමුදායේ නම

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// --- Process Request ---
$data = json_decode(file_get_contents("php://input"), true);

// React යෙදුමෙන් 'email' ලෙස එවන නිසා, එය username ලෙස සලකමු
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username and password are required."]);
    exit();
}

$username = $data['email'];
$password = $data['password'];

// SQL Query එක
$stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    // Hash කරන ලද මුරපදය තහවුරු කිරීම
    if (password_verify($password, $admin['password_hash'])) {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Login successful!"]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(["success" => false, "message" => "Login failed. Please check your username and password."]);
    }
} else {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "message" => "Login failed. Please check your username and password."]);
}

$stmt->close();
$conn->close();
?>
