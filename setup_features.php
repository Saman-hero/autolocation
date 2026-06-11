<?php
/**
 * setup_features.php - Run once to set up I18N, GPS, and public booking
 * http://localhost/location/setup_features.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h2>Feature Setup</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=location;charset=utf8", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✓ Database connected</p>";
} catch (PDOException $e) {
    die("<p>✗ DB: " . $e->getMessage() . "</p>");
}

// Create gps_positions table
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `gps_positions` (
          `id`          INT(11)      NOT NULL AUTO_INCREMENT,
          `vehicle_id`  INT(11)      NOT NULL,
          `latitude`    DECIMAL(10,7) NOT NULL,
          `longitude`   DECIMAL(10,7) NOT NULL,
          `speed`       DECIMAL(5,1)  DEFAULT 0.0,
          `altitude`    DECIMAL(7,1)  DEFAULT NULL,
          `heading`     DECIMAL(5,1)  DEFAULT NULL,
          `recorded_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `vehicle_id` (`vehicle_id`),
          KEY `recorded_at` (`recorded_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ GPS table created</p>";
} catch (PDOException $e) {
    echo "<p style='color:orange'>⚠ GPS table: " . $e->getMessage() . "</p>";
}

// Add image column if missing
try {
    $conn->exec("ALTER TABLE `vehicles` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `couleur`");
    echo "<p>✓ Image column added</p>";
} catch (PDOException $e) {
    echo "<p style='color:orange'>⚠ Image column: " . $e->getMessage() . "</p>";
}

echo "<hr><h3>Setup complete!</h3>";
echo "<p><a href='/location/public/index.php?url=public'>→ Public booking page</a></p>";
echo "<p><a href='/location/public/index.php?url=gps'>→ GPS tracking</a></p>";
echo "<p><a href='/location/public/index.php?url=vehicles'>→ Vehicles</a></p>";
