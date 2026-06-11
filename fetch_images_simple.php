<?php
/**
 * Simple image fetcher - Alternative version
 * Usage: http://localhost/location/fetch_images_simple.php
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

$uploadDir = __DIR__ . '/uploads/vehicles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// First, add the image column if it doesn't exist
try {
    $conn->exec("ALTER TABLE `vehicles` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `couleur`");
    echo "<p>✓ Added 'image' column to vehicles table</p>";
} catch (Exception $e) {
    // Column already exists, that's fine
}

$vehicles = $conn->query("SELECT id, marque, modele, numero FROM vehicles WHERE image IS NULL OR image = ''")->fetchAll();

echo "<h2>Simple Image Fetcher</h2>";
echo "<p>Found " . count($vehicles) . " vehicles to process.</p><hr>";

foreach ($vehicles as $vehicle) {
    $id = $vehicle['id'];
    $marque = $vehicle['marque'];
    $modele = $vehicle['modele'];
    
    echo "<p><strong>{$marque} {$modele}</strong>: ";
    
    $filename = strtolower(preg_replace('/[^a-z0-9]/', '_', $marque . '_' . $modele)) . '_' . $id . '.jpg';
    $filepath = $uploadDir . $filename;
    
    // Use picsum.photos - random but consistent images based on seed
    $seed = abs(crc32($marque . $modele));
    $url = "https://picsum.photos/seed/{$seed}/800/600";
    
    // Download using file_get_contents
    $content = @file_get_contents($url);
    
    if ($content !== false && file_put_contents($filepath, $content) !== false) {
        // Verify it's a valid image
        $imageInfo = @getimagesize($filepath);
        if ($imageInfo !== false) {
            $stmt = $conn->prepare("UPDATE vehicles SET image = ? WHERE id = ?");
            $stmt->execute([$filename, $id]);
            echo "<span style='color:green'>✓ Saved: {$filename}</span>";
        } else {
            unlink($filepath);
            echo "<span style='color:red'>✗ Invalid image</span>";
        }
    } else {
        echo "<span style='color:red'>✗ Download failed</span>";
    }
    
    echo "</p>";
}

echo "<hr><p><a href='/location/public/index.php?url=vehicles'>→ View Vehicles</a></p>";
