<?php
// CORS headers - Allow requests from any origin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request from the browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// --- Database Connection ---
$host = "localhost";
$user = "root";
$pass = "";
$db = "mahavihara";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// --- Process Request ---
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username']) || !isset($data['password']) || empty($data['username']) || empty($data['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["message" => "Username and password are required."]);
    exit();
}

$username = $data['username'];
$password = password_hash($data['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode(["message" => "Admin registered successfully!"]);
} else {
    if ($conn->errno == 1062) { // Duplicate entry
        http_response_code(409); // Conflict
        echo json_encode(["message" => "Registration failed. The username may already exist."]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["message" => "Registration failed: " . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>
