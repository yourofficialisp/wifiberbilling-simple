<?php
require_once '../../includes/auth.php';
requireTechnicianLogin();

$tech = $_SESSION['technician'];
$ticketId = $_GET['id'] ?? 0;

// Fetch Ticket Detail
$ticket = fetchOne("
    SELECT t.*, c.name as customer_name, c.address, c.phone, c.lat, c.lng 
    FROM trouble_tickets t 
    LEFT JOIN customers c ON t.customer_id = c.id 
    WHERE t.id = ? AND t.technician_id = ?
", [$ticketId, $tech['id']]);

if (!$ticket) {
    setFlash('error', 'Ticket not found or not assigned to you.');
    redirect('index.php');
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    $photoPath = $ticket['photo_proof'];
    
    // Validation status flow
    if ($status === 'resolved') {
        if (empty($notes)) {
            setFlash('error', 'Resolution notes are required!');
            redirect("view_ticket.php?id=$ticketId");
        }
        
        // Handle Photo Upload (Required if resolved)
        if (!empty($_FILES['photo']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newName = "ticket_{$ticketId}_" . time() . ".jpg"; // Convert all to jpg
                $targetDir = "../../uploads/tickets/";
                $targetFile = $targetDir . $newName;
                
                // Resize & Compress Image
                $source = $_FILES['photo']['tmp_name'];
                $imageInfo = @getimagesize($source);
                
                if (!$imageInfo) {
                    setFlash('error', 'File bukan gambar valid.');
                    redirect("view_ticket.php?id=$ticketId");
                }
                
                list($width, $height) = $imageInfo;
                
                // Target width 800px
                $newWidth = 800;
                $newHeight = ($height / $width) * $newWidth;
                
                $tmpImg = imagecreatetruecolor($newWidth, $newHeight);
                $sourceImg = null;
                
                switch ($ext) {
                    case 'jpg': case 'jpeg': $sourceImg = @imagecreatefromjpeg($source); break;
                    case 'png': $sourceImg = @imagecreatefrompng($source); break;
                    case 'webp': $sourceImg = @imagecreatefromwebp($source); break;
                }
                
                if (!$sourceImg) {
                    setFlash('error', 'Failed to process image. File may be corrupted.');
                    redirect("view_ticket.php?id=$ticketId");
                }
                
                // Handle transparency for PNG/WEBP
                if ($ext == 'png' || $ext == 'webp') {
                    imagecolortransparent($tmpImg, imagecolorallocatealpha($tmpImg, 0, 0, 0, 127));
                    imagealphablending($tmpImg, false);
                    imagesavealpha($tmpImg, true);
                }
                
                imagecopyresampled($tmpImg, $sourceImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                // Save as JPG with 70% quality
                if (imagejpeg($tmpImg, $targetFile, 70)) {
                    $photoPath = "uploads/tickets/" . $newName;
                    unset($tmpImg);
                    unset($sourceImg);
                } else {
                    setFlash('error', 'Failed memproses gambar.');
                    redirect("view_ticket.php?id=$ticketId");
                }
            } else {
                setFlash('error', 'Format foto harus JPG/PNG/WEBP.');
                redirect("view_ticket.php?id=$ticketId");
            }
        } elseif (empty($ticket['photo_proof'])) {
            setFlash('error', 'Must upload repair proof photo!');
            redirect("view_ticket.php?id=$ticketId");
        }
    }
    
    // Update DB
    $updateData = [
        'status' => $status,
        'notes' => $notes,
        'photo_proof' => $photoPath,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($status === 'resolved') {
        $updateData['resolved_at'] = date('Y-m-d H:i:s');
    }
    
    if (update('trouble_tickets', $updateData, 'id = ?', [$ticketId])) {
        setFlash('success', 'Status tiket updated successfully.');
        redirect('index.php');
    } else {
        setFlash('error', 'Failed update tiket.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tiket #<?php echo $ticketId; ?></title>
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
        
        .wa-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
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
        
        /* Custom Radio for Status */
        .status-options {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .status-opt input { display: none; }
        
        .status-opt label {
            display: block;
            padding: 10px;
            text-align: center;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid transparent;
        }
        
        .status-opt input:checked + label {
            background: rgba(0, 245, 255, 0.2);
            border-color: var(--primary);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h2>Ticket Detail #<?php echo $ticketId; ?></h2>
    </div>

    <div class="container">
        <!-- Customer Info -->
        <div class="card">
            <h3 style="margin-bottom: 15px; color: var(--primary);">Data Customer</h3>
            
            <span class="label">Customer Name</span>
            <span class="value"><?php echo htmlspecialchars($ticket['customer_name']); ?></span>
            
            <span class="label">Address</span>
            <span class="value"><?php echo htmlspecialchars($ticket['address']); ?></span>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if ($ticket['lat'] && $ticket['lng']): ?>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $ticket['lat'] . ',' . $ticket['lng']; ?>" target="_blank" class="map-btn">
                        <i class="fas fa-directions"></i> Directions
                    </a>
                <?php endif; ?>
                
                <?php if ($ticket['phone']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $ticket['phone'])); ?>" target="_blank" class="wa-btn">
                        <i class="fab fa-whatsapp"></i> Chat WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Issue Detail -->
        <div class="card">
            <h3 style="margin-bottom: 15px; color: #ff4757;">Masalah</h3>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
            </p>
        </div>

        <!-- Action Form -->
        <div class="card">
            <h3 style="margin-bottom: 15px;">Update Status</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="status-options">
                    <div class="status-opt">
                        <input type="radio" name="status" id="st_pending" value="pending" <?php echo $ticket['status'] === 'pending' ? 'checked' : ''; ?>>
                        <label for="st_pending">Pending</label>
                    </div>
                    <div class="status-opt">
                        <input type="radio" name="status" id="st_progress" value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'checked' : ''; ?>>
                        <label for="st_progress">In Progress</label>
                    </div>
                    <div class="status-opt">
                        <input type="radio" name="status" id="st_resolved" value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'checked' : ''; ?>>
                        <label for="st_resolved">Completed</label>
                    </div>
                </div>
                
                <span class="label">Resolution Notes</span>
                <textarea name="notes" class="form-control" rows="3" placeholder="Write the actions taken..."><?php echo htmlspecialchars($ticket['notes'] ?? ''); ?></textarea>
                
                <div id="photo-section" style="display: <?php echo $ticket['status'] === 'resolved' ? 'block' : 'none'; ?>;">
                    <span class="label">Proof Photo (Required if Completed)</span>
                    <div class="photo-preview" onclick="document.getElementById('photo-input').click()">
                        <?php if (!empty($ticket['photo_proof'])): ?>
                            <img src="../../<?php echo htmlspecialchars($ticket['photo_proof']); ?>" id="preview-img">
                        <?php else: ?>
                            <div id="placeholder" style="text-align: center; color: var(--text-secondary);">
                                <i class="fas fa-camera" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                                Click to take photo
                            </div>
                            <img id="preview-img" style="display: none;">
                        <?php endif; ?>
                    </div>
                    <input type="file" name="photo" id="photo-input" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this)">
                </div>
                
                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle photo section based on status
        const statusRadios = document.getElementsByName('status');
        const photoSection = document.getElementById('photo-section');
        
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'resolved') {
                    photoSection.style.display = 'block';
                } else {
                    photoSection.style.display = 'none';
                }
            });
        });

        // Image Preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('preview-img').style.display = 'block';
                    if(document.getElementById('placeholder')) {
                        document.getElementById('placeholder').style.display = 'none';
                    }
                }
                reader.readAsDataURL(input.files[0]);
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
