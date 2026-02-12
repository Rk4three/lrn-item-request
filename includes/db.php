<?php
// includes/db.php

// Check if running in Docker/Render environment
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'item_request_db';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: 'password';
$port = getenv('DB_PORT') ?: '5432';

try {
    // Detect if we should use PgSQL or SQL Server based on env
    // For this migration, we prioritize PgSQL if generic DB_HOST is set to 'db' or 'dpg-...' (render)
    // Or just try specific driver.

    if (getenv('DB_HOST')) {
        // Postgres
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $conn = new PDO($dsn, $user, $pass);
    } else {
        // Fallback to SQL Server (Original)
        // We probably don't want this if we are fully migrating, but good for safety.
        // However, user said "migrate... via docker and postgresql". So lets default to Postgres for Docker.
        // But if I default to Postgres, the local XAMPP setup (without docker) might break?
        // User asked to migrate, so I will switch to Postgres.

        // If testing locally without Docker env vars, maybe fallback to localhost postgres?
        // use 'db' host from docker-compose
        $dsn = "pgsql:host=db;port=5432;dbname=item_request_db";
        $user = "postgres";
        $pass = "password";
        $conn = new PDO($dsn, $user, $pass);
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Set Schema to isolate tables
    // This allows using generic table names (Requests, Users) without conflict
    $schema = getenv('DB_SCHEMA') ?: 'item_request';
    $conn->exec("CREATE SCHEMA IF NOT EXISTS \"$schema\"");
    $conn->exec("SET search_path TO \"$schema\"");

} catch (PDOException $e) {
    // If connection fails, show error (for debugging) or friendly message
    // display_unavailable(); // Keep original function if possible
    die("Connection failed: " . $e->getMessage());
}

function display_unavailable()
{
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>Service Unavailable</title>
        <style>body{font-family:sans-serif;text-align:center;padding:50px;}</style>
    </head>
    <body>
        <h2>System Unavailable</h2>
        <p>Database connection failed.</p>
    </body>
    </html>
    ');
}
?>