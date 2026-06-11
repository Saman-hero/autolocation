<?php
/**
 * Setup script - Fixed version
 * Run ONCE: http://localhost/location/setup_images.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Vehicle Image Setup</h2>";

// Database connection
try {
    $conn = new PDO("mysql:host=localhost;dbname=location;charset=utf8", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✓ Database connected</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>✗ Database error: " . $e->getMessage() . "</p>");
}

// Add image column if not exists
try {
    $conn->exec("ALTER TABLE `vehicles` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `couleur`");
    echo "<p style='color:green'>✓ Added 'image' column</p>";
} catch (PDOException $e) {
    echo "<p style='color:orange'>⚠ Column already exists or error: " . $e->getMessage() . "</p>";
}

// Create uploads directory
$uploadDir = __DIR__ . '/uploads/vehicles/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Check if writable
if (!is_writable($uploadDir)) {
    echo "<p style='color:red'>✗ Folder not writable: {$uploadDir}</p>";
    echo "<p>Please run in terminal: <code>chmod -R 777 /Applications/XAMPP/xamppfiles/htdocs/location/uploads/</code></p>";
    exit;
}

echo "<p style='color:green'>✓ Upload folder is writable</p>";

// Get vehicles without images
$vehicles = $conn->query("SELECT id, marque, modele, numero FROM vehicles WHERE image IS NULL OR image = ''")->fetchAll();

echo "<p>Found <strong>" . count($vehicles) . "</strong> vehicles without images.</p>";
echo "<hr>";

if (count($vehicles) == 0) {
    echo "<p style='color:green'>All vehicles already have images!</p>";
    echo "<p><a href='/location/public/index.php?url=vehicles'>→ View Vehicles</a></p>";
    exit;
}

foreach ($vehicles as $vehicle) {
    $id = $vehicle['id'];
    $marque = $vehicle['marque'];
    $modele = $vehicle['modele'];
    
    echo "<div style='margin:10px 0;padding:10px;background:#f5f5f5;border-radius:5px;'>";
    echo "<strong>{$marque} {$modele}</strong> (#{$vehicle['numero']})<br>";
    
    // Create filename
    $filename = strtolower(preg_replace('/[^a-z0-9]/', '_', $marque . '_' . $modele)) . '_' . $id . '.jpg';
    $filepath = $uploadDir . $filename;
    
    // Use picsum with seed
    $seed = abs(crc32($marque . $modele));
    $url = "https://picsum.photos/seed/{$seed}/800/600";
    
    echo "Downloading...<br>";
    
    // Download with curl
    $ch = curl_init($url);
    $fp = @fopen($filepath, 'w');
    
    if (!$fp) {
        echo "<span style='color:red'>✗ Cannot write to file: {$filepath}</span><br>";
        echo "</div>";
        continue;
    }
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    
    $fileSize = filesize($filepath);
    
    if ($result && $httpCode == 200 && $fileSize > 500) {
        $stmt = $conn->prepare("UPDATE vehicles SET image = ? WHERE id = ?");
        $stmt->execute([$filename, $id]);
        echo "<span style='color:green'>✓ Saved: {$filename} ({$fileSize} bytes)</span><br>";
    } else {
        echo "<span style='color:red'>✗ Failed (HTTP {$httpCode}, size: {$fileSize})</span><br>";
        @unlink($filepath);
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<h3>Done!</h3>";
echo "<p><a href='/location/public/index.php?url=vehicles'>→ View Vehicles</a></p>";
