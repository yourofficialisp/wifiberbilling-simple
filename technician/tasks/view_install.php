<?php
require_once '../../includes/auth.php';
requireTechnicianLogin();

$tech = $_SESSION['technician'];
$customerId = $_GET['id'] ?? 0;

// Fetch Customer Detail
$customer = fetchOne("
    SELECT c.*, p.name as package_name 
    FROM customers c 
    LEFT JOIN packages p ON c.package_id = p.id 
    WHERE c.id = ? AND c.installed_by = ?
", [$customerId, $tech['id']]);

if (!$customer) {
    setFlash('error', 'Data customer not found atau bukan tugas You.');
    redirect('index.php?type=install');
}

// Handle Activation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serialNumber = trim($_POST['serial_number']);
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $photoPath = $customer['installation_photo'];
    
    // Validation
    if (empty($serialNumber)) {
        setFlash('error', 'Serial Number ONT is required!');
        redirect("view_install.php?id=$customerId");
    }
    
    // Handle Photo Upload (Required)
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newName = "install_{$customerId}_" . time() . ".jpg";
            $targetDir = "../../uploads/installations/";
            $targetFile = $targetDir . $newName;
            
            // Resize Image
            $source = $_FILES['photo']['tmp_name'];
            list($width, $height) = getimagesize($source);
            
            $newWidth = 800;
            $newHeight = ($height / $width) * $newWidth;
            
            $tmpImg = imagecreatetruecolor($newWidth, $newHeight);
            
            switch ($ext) {
                case 'jpg': case 'jpeg': $sourceImg = imagecreatefromjpeg($source); break;
                case 'png': $sourceImg = imagecreatefrompng($source); break;
                case 'webp': $sourceImg = imagecreatefromwebp($source); break;
            }
            
            imagecopyresampled($tmpImg, $sourceImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            if (imagejpeg($tmpImg, $targetFile, 70)) {
                $photoPath = "uploads/installations/" . $newName;
                unset($tmpImg);
                unset($sourceImg);
            } else {
                setFlash('error', 'Failed to process image.');
                redirect("view_install.php?id=$customerId");
            }
        } else {
            setFlash('error', 'Photo format must be JPG/PNG/WEBP.');
            redirect("view_install.php?id=$customerId");
        }
    } elseif (empty($customer['installation_photo'])) {
        setFlash('error', 'Must upload installation proof photo!');
        redirect("view_install.php?id=$customerId");
    }
    
    // Update DB: Activate Customer
    $updateData = [
        'status' => 'active',
        'serial_number' => $serialNumber,
        'installation_photo' => $photoPath,
        'installation_date' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Update Lat/Lng if provided
    if (!empty($lat) && !empty($lng)) {
        $updateData['lat'] = str_replace(',', '.', $lat);
        $updateData['lng'] = str_replace(',', '.', $lng);
    }
    
    if (update('customers', $updateData, 'id = ?', [$customerId])) {
        // Log Activity
        logActivity('INSTALL_COMPLETE', "Customer #$customerId activated by Tech #{$tech['id']}");
        setFlash('success', 'Installation complete! Customer is now Active.');
        redirect('index.php?type=install');
    } else {
        setFlash('error', 'Failed to save data.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Process - <?php echo htmlspecialchars($customer['name']); ?></title>
    <meta name="theme-color" content="#0a0a12">
    <link rel="manifest" href="../../manifest.json">
    <link rel="apple-touch-icon" href="../../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../../assets/icons/icon-192x192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00f5ff;
            --bg-dark: #0a0a12;
            --bg-card: #161628;
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            padding-bottom: 20px;
        }
        
        .header {
            background: var(--bg-card);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .back-btn {
            color: var(--text-primary);
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .container { padding: 20px; }
        
        .card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
            display: block;
        }
        
        .value {
            font-size: 1rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .map-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 245, 255, 0.1);
            color: var(--primary);
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-primary);
            margin-bottom: 15px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            border: none;
            border-radius: 10px;
            color: #000;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .photo-preview {
            width: 100%;
            height: 200px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
            border: 2px dashed rgba(255,255,255,0.1);
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gps-btn {
            background: #2ed573;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
            margin-bottom: 10px;
        }
        
        .coord-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php?type=install" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h2>Installation #C<?php echo $customerId; ?></h2>
    </div>

    <div class="container">
        <!-- Customer Info -->
        <div class="card">
            <h3 style="margin-bottom: 15px; color: var(--primary);">Customer Data</h3>
            
            <span class="label">Customer Name</span>
            <span class="value"><?php echo htmlspecialchars($customer['name']); ?></span>
            
            <span class="label">Address</span>
            <span class="value"><?php echo htmlspecialchars($customer['address']); ?></span>
            
            <span class="label">Internet Package</span>
            <span class="value"><?php echo htmlspecialchars($customer['package_name']); ?></span>
            
            <?php if ($customer['lat'] && $customer['lng']): ?>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $customer['lat'] . ',' . $customer['lng']; ?>" target="_blank" class="map-btn">
                    <i class="fas fa-directions"></i> Directions
                </a>
            <?php endif; ?>
        </div>

        <?php if ($customer['status'] === 'active'): ?>
            <div class="card" style="text-align: center; border-color: #00ff88;">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #00ff88; margin-bottom: 15px;"></i>
                <h3>Installation Completed</h3>
                <p style="color: var(--text-secondary);">This customer is already active.</p>
                <?php if ($customer['installation_photo']): ?>
                    <img src="../../<?php echo htmlspecialchars($customer['installation_photo']); ?>" style="width: 100%; border-radius: 8px; margin-top: 15px;">
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Action Form -->
            <div class="card">
                <h3 style="margin-bottom: 15px;">Activation Form</h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <span class="label">Serial Number (SN) ONT</span>
                    <input type="text" name="serial_number" class="form-control" placeholder="Example: ZTEGC8E..." required>
                    
                    <span class="label">Location Coordinates (Update if needed)</span>
                    <button type="button" class="gps-btn" onclick="getLocation()"><i class="fas fa-map-marker-alt"></i> Get My Location</button>
                    <div class="coord-grid">
                        <input type="text" name="lat" id="lat" class="form-control" placeholder="Latitude" value="<?php echo htmlspecialchars($customer['lat'] ?? ''); ?>">
                        <input type="text" name="lng" id="lng" class="form-control" placeholder="Longitude" value="<?php echo htmlspecialchars($customer['lng'] ?? ''); ?>">
                    </div>
                    
                    <span class="label">Installation Proof Photo (Required)</span>
                    <div class="photo-preview" onclick="document.getElementById('photo-input').click()">
                        <div id="placeholder" style="text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-camera" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                            Installed Device Photo
                        </div>
                        <img id="preview-img" style="display: none;">
                    </div>
                    <input type="file" name="photo" id="photo-input" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this)" required>
                    
                    <button type="submit" class="btn-submit" onclick="return confirm('Ensure all data is correct. Activate customer?');">Save & Activate</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Image Preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('preview-img').style.display = 'block';
                    document.getElementById('placeholder').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // GPS Location
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError, { enableHighAccuracy: true });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPosition(position) {
            document.getElementById("lat").value = position.coords.latitude;
            document.getElementById("lng").value = position.coords.longitude;
        }

        function showError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    alert("Location permission denied.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Location information unavailable.");
                    break;
                case error.TIMEOUT:
                    alert("Location request timed out.");
                    break;
                case error.UNKNOWN_ERROR:
                    alert("An unknown error occurred.");
                    break;
            }
        }
    </script>

    <?php require_once '../includes/bottom_nav.php'; ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../../sw.js');
            });
        }
    </script>
</body>
</html>
