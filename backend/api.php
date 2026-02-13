<?php
// api.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // para demos; en prod restringe
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$DB_HOST = 'mysql';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'demo_app';
$DB_PORT = 3306;

function respond(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_errno) {
  respond(500, ['ok' => false, 'error' => 'DB connection failed', 'detail' => $mysqli->connect_error]);
}
$mysqli->set_charset('utf8mb4');

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    $res = $mysqli->query("SELECT id, name, created_at FROM items ORDER BY id DESC");
    if (!$res) {
      respond(500, ['ok' => false, 'error' => 'Query failed', 'detail' => $mysqli->error]);
    }
    $items = [];
    while ($row = $res->fetch_assoc()) {
      $items[] = $row;
    }
    respond(200, ['ok' => true, 'items' => $items]);
  }

  if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data) || !isset($data['name'])) {
      respond(400, ['ok' => false, 'error' => 'Invalid JSON body. Expected: {"name":"..."}']);
    }

    $name = trim((string)$data['name']);
    if ($name === '' || mb_strlen($name) > 120) {
      respond(400, ['ok' => false, 'error' => 'Name is required (1..120 chars)']);
    }

    $stmt = $mysqli->prepare("INSERT INTO items (name) VALUES (?)");
    if (!$stmt) {
      respond(500, ['ok' => false, 'error' => 'Prepare failed', 'detail' => $mysqli->error]);
    }
    $stmt->bind_param("s", $name);
    if (!$stmt->execute()) {
      respond(500, ['ok' => false, 'error' => 'Insert failed', 'detail' => $stmt->error]);
    }

    respond(201, ['ok' => true, 'id' => $stmt->insert_id]);
  }

  if ($method === 'DELETE') {
    // DELETE /api.php?id=123
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
      respond(400, ['ok' => false, 'error' => 'Missing or invalid id parameter']);
    }

    $stmt = $mysqli->prepare("DELETE FROM items WHERE id = ?");
    if (!$stmt) {
      respond(500, ['ok' => false, 'error' => 'Prepare failed', 'detail' => $mysqli->error]);
    }
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
      respond(500, ['ok' => false, 'error' => 'Delete failed', 'detail' => $stmt->error]);
    }

    respond(200, ['ok' => true, 'deleted' => $stmt->affected_rows]);
  }

  respond(405, ['ok' => false, 'error' => 'Method not allowed']);
} finally {
  $mysqli->close();
}
