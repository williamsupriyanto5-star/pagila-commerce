<?php
function koneksiDB() {
    $databaseUrl = getenv('DATABASE_URL');

    if ($databaseUrl) {
        $database = parse_url($databaseUrl);
        $host = $database['host'] ?? 'localhost';
        $port = $database['port'] ?? '5432';
        $dbname = isset($database['path']) ? ltrim($database['path'], '/') : 'project_dwh';
        $user = $database['user'] ?? 'postgres';
        $password = $database['pass'] ?? '';
    } else {
        // Railway PostgreSQL menyediakan PG*, sedangkan lokal memakai DB* fallback.
        $host     = getenv('PGHOST') ?: (getenv('DB_HOST') ?: 'localhost');
        $port     = getenv('PGPORT') ?: (getenv('DB_PORT') ?: '5432');
        $dbname   = getenv('PGDATABASE') ?: (getenv('DB_NAME') ?: 'project_dwh');
        $user     = getenv('PGUSER') ?: (getenv('DB_USER') ?: 'postgres');
        $password = getenv('PGPASSWORD') ?: (getenv('DB_PASS') ?: 'root');
    }

    try {
        $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Koneksi Database Gagal: " . $e->getMessage());
    }
}
?>
