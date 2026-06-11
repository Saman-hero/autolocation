<?php
/**
 * Auto-fetch vehicle images - Debugged version
 * Usage: http://localhost/location/fetch_images.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

$uploadDir = __DIR__ . '/uploads/vehicles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Check if image column exists
$checkCol = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'image'")->fetch();
if (!$checkCol) {
    echo "<h3>Error: 'image' column doesn't exist in vehicles table</h3>";
    echo "<p>Run this SQL first in phpMyAdmin:</p>";
    echo "<pre>ALTER TABLE `vehicles` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `couleur`;</pre>";
    exit;
}

// Check if curl is enabled
if (!function_exists('curl_init')) {
    echo "<h3>Error: cURL is not enabled in PHP</h3>";
    echo "<p>Enable it in php.ini: extension=curl</p>";
    exit;
}

$vehicles = $conn->query("SELECT id, marque, modele, numero FROM vehicles WHERE image IS NULL OR image = ''")->fetchAll();

echo "<h2>Vehicle Image Fetcher</h2>";
echo "<p>Found " . count($vehicles) . " vehicles without images.</p>";
echo "<p>Upload dir: {$uploadDir} (writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . ")</p><hr>";

foreach ($vehicles as $vehicle) {
    $marque = $vehicle['marque'];
    $modele = $vehicle['modele'];
    $numero = $vehicle['numero'];
    $id = $vehicle['id'];
    
    echo "<div style='margin:10px 0;padding:10px;border:1px solid #ddd;border-radius:5px;'>";
    echo "<strong>Processing:</strong> {$marque} {$modele} (#{$numero})<br>";
    
    // Build search query
    $searchQuery = "{$marque} {$modele}";
    $encodedQuery = urlencode($searchQuery);
    
    // Use picsum.photos which is more reliable
    $seed = abs(crc32($marque . $modele . $id));
    $imageUrl = "https://picsum.photos/seed/{$seed}/800/600";
    
    echo "Image URL: {$imageUrl}<br>";
    
    $filename = strtolower($marque . '_' . $modele) . '_' . $id . '.jpg';
    $filename = preg_replace('/[^a-z0-9_.]/', '_', $filename);
    $filepath = $uploadDir . $filename;
    
    echo "Saving to: {$filepath}<br>";
    
    if (downloadImage($imageUrl, $filepath)) {
        $stmt = $conn->prepare("UPDATE vehicles SET image = ? WHERE id = ?");
        $stmt->execute([$filename, $id]);
        echo "<span style='color:green'>✓ Success: {$filename}</span>";
    } else {
        echo "<span style='color:red'>✗ Failed to download</span>";
    }
    
    echo "</div>";
}

echo "<hr><h3>Done!</h3>";
echo "<p><a href='/location/public/index.php?url=vehicles'>→ View Vehicles</a></p>";

function downloadImage(string $url, string $filepath): bool {
    $ch = curl_init();
    
    $fp = fopen($filepath, 'w');
    if (!$fp) {
        echo "Cannot open file for writing<br>";
        return false;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    fclose($fp);
    
    if ($error) {
        echo "cURL Error: {$error}<br>";
    }
    
    echo "HTTP Code: {$httpCode}, File size: " . filesize($filepath) . "<br>";
    
    if ($result && $httpCode == 200 && filesize($filepath) > 500) {
        return true;
    }
    
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    return false;
}
