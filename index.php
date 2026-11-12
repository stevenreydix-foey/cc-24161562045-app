<?php
// Buku tamu PHP + PostgreSQL
const PEMILIK = "24161562045 - steven reydix n.y";
const VERSI = "1.0";

function db(int $timeout = 5): PDO {
    $u = parse_url(getenv("DATABASE_URL"));
    parse_str($u["query"] ?? "", $q);

    $dsn = sprintf(
        "pgsql:host=%s;port=%d;dbname=%s",
        $u["host"],
        $u["port"] ?? 5432,
        ltrim($u["path"], "/")
    );

    if (!empty($q["sslmode"])) {
        $dsn .= ";sslmode=" . $q["sslmode"];
    }

    $pdo = new PDO(
        $dsn,
        urldecode($u["user"]),
        urldecode($u["pass"] ?? ""),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => $timeout,
        ]
    );

    $pdo->exec("CREATE TABLE IF NOT EXISTS tamu (
        id serial PRIMARY KEY,
        nama text NOT NULL,
        waktu timestamptz DEFAULT now()
    )");

    return $pdo;
}

function json(array $data, int $code = 200): void {
    http_response_code($code);
    header("Content-Type: application/json");
    echo json_encode($data);
}

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];

if ($path === "/healthz") {
    json(["status" => "ok"]);

} elseif ($path === "/health") {
    try {
        db(3)->query("SELECT 1");
        json(["status" => "ok", "db" => "ok"]);
    } catch (Throwable $e) {
        json(["status" => "error", "db" => "gagal"], 503);
    }

} elseif ($path === "/tamu" && $method === "POST") {
    $nama = trim($_POST["nama"] ?? "");

    if ($nama === "") {
        http_response_code(400);
        echo "nama wajib diisi";
        exit;
    }

    $st = db()->prepare("INSERT INTO tamu (nama) VALUES (?)");
    $st->execute([mb_substr($nama, 0, 80)]);

    header("Location: /", true, 302);

} elseif ($path === "/") {
    $sql = "SELECT nama, waktu FROM tamu ORDER BY id DESC LIMIT 20";
    $rows = db()->query($sql)->fetchAll();

    echo "<h1>Buku tamu " . htmlspecialchars(PEMILIK) . "</h1>";
    echo "<p>versi " . VERSI . " · server " . gethostname() . "</p>";
    echo '<form method="post" action="/tamu">';
    echo '<input name="nama" required>';
    echo '<button>Simpan</button></form><ul>';

    foreach ($rows as $r) {
        $waktu = date("d-m H:i", strtotime($r["waktu"]));
        echo "<li>" . htmlspecialchars($r["nama"]) . " · $waktu</li>";
    }

    echo "</ul>";

} else {
    http_response_code(404);
    echo "Halaman tidak ditemukan";
}
