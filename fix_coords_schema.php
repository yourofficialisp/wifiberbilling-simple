<?php
/**
 * Fix Coordinates Schema Script
 * Updates latitude and longitude columns to DECIMAL(11,8) to support full coordinate range.
 * 
 * Run this script once on your server.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h2>🛠️ Coordinate Schema Fix (Lat/Lng)</h2>";
echo "<p>This script will change latitude and longitude column types to <code>DECIMAL(11,8)</code> to support Pakistani coordinates (60-75 E).</p><hr>";

try {
    $pdo = getDB();
    
    // 1. Update table customers
    echo "1. Fixing table <b>customers</b>... ";
    try {
        // Check if table exists
        $check = $pdo->query("SHOW TABLES LIKE 'customers'");
        if ($check->rowCount() > 0) {
            $pdo->exec("ALTER TABLE customers MODIFY lat DECIMAL(11,8), MODIFY lng DECIMAL(11,8)");
            echo "<span style='color:green'>SUCCESS</span><br>";
        } else {
            echo "<span style='color:orange'>Table not found</span><br>";
        }
    } catch (PDOException $e) {
        echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
    }

    // 2. Update table odps
    echo "2. Fixing table <b>odps</b>... ";
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'odps'");
        if ($check->rowCount() > 0) {
            $pdo->exec("ALTER TABLE odps MODIFY lat DECIMAL(11,8), MODIFY lng DECIMAL(11,8)");
            echo "<span style='color:green'>SUCCESS</span><br>";
        } else {
            echo "<span style='color:orange'>Table not found</span><br>";
        }
    } catch (PDOException $e) {
        echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
    }

    // 3. Update table onu_locations
    echo "3. Fixing table <b>onu_locations</b>... ";
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'onu_locations'");
        if ($check->rowCount() > 0) {
            $pdo->exec("ALTER TABLE onu_locations MODIFY lat DECIMAL(11,8), MODIFY lng DECIMAL(11,8)");
            echo "<span style='color:green'>SUCCESS</span><br>";
        } else {
            echo "<span style='color:orange'>Table not found</span><br>";
        }
    } catch (PDOException $e) {
        echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
    }

    echo "<hr><h3>✅ Fix Complete!</h3>";
    echo "<p>Now you can save coordinate points accurately on the hosting server.</p>";
    echo "<p>Please delete this file for security.</p>";
    echo "<a href='admin/dashboard.php'>Back to Dashboard</a>";

} catch (PDOException $e) {
    echo "<hr><h3 style='color:red'>❌ Database Connection Error: " . $e->getMessage() . "</h3>";
}
