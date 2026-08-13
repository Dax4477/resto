<?php
// --- FIX 1: FORCE BROWSERS NOT TO CACHE THIS PAGE ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$file = 'menu.json';
$uploadDir = 'uploads/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 1. LOAD CURRENT DATA FIRST to map existing images
$oldMenu = [];
$oldImages = [];
if (file_exists($file)) {
    $oldMenu = json_decode(file_get_contents($file), true);
    if(is_array($oldMenu)) {
        foreach ($oldMenu as $item) {
            if (!empty($item['image'])) {
                $oldImages[] = $item['image'];
            }
        }
    }
}

// 2. PROCESS FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newMenu = [];
    $newImagesInUse = []; // Track images we are keeping
    
    $names = $_POST['name'] ?? [];
    $prices = $_POST['price'] ?? [];
    $categories = $_POST['category'] ?? [];
    $existingImages = $_POST['existing_image'] ?? [];

    for ($i = 0; $i < count($names); $i++) {
        // Only save if the dish name is not blank
        if (trim($names[$i]) !== '') {
            $imagePath = $existingImages[$i] ?? '';

            // Handle new file upload if the user selected an image
            if (isset($_FILES['image']['name'][$i]) && $_FILES['image']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['image']['tmp_name'][$i];
                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['image']['name'][$i]));
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imagePath = $targetPath; // Overwrite old path with new path
                }
            }

            $newMenu[] = [
                'name' => htmlspecialchars(trim($names[$i])),
                'category' => htmlspecialchars(trim($categories[$i])),
                'price' => htmlspecialchars(trim($prices[$i])),
                'image' => $imagePath
            ];
            
            // Log this image as "in use"
            if (!empty($imagePath)) {
                $newImagesInUse[] = $imagePath;
            }
        }
    }
    
    // 3. FILE CLEANUP LOGIC
    $imagesToDelete = array_diff($oldImages, $newImagesInUse);
    foreach ($imagesToDelete as $delImage) {
        if (file_exists($delImage)) {
            unlink($delImage); 
        }
    }
    
    // Write back to the JSON file
    file_put_contents($file, json_encode($newMenu, JSON_PRETTY_PRINT));
    
    // --- FIX 2: POST-REDIRECT-GET ---
    // Instantly redirect the user so refreshing the page doesn't resubmit the form
    header("Location: admin.php?success=1");
    exit;
} 

// If not a POST request, load whatever is currently in the file
$menuData = [];
if (file_exists($file)) {
    $menuData = json_decode(file_get_contents($file), true);
    if (!is_array($menuData)) $menuData = []; // Failsafe if file gets corrupted
}

// Check if we just redirected after a successful save
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Menu updated and storage cleaned successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Menu Dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            padding: 10px; 
            background: #f0f2f5; 
            margin: 0; 
        }
        .container { 
            max-width: 900px; 
            background: #fff; 
            padding: 15px; 
            border-radius: 12px; 
            margin: auto; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
        }
        h2 { text-align: center; color: #1a1a1a; margin-top: 5px; }
        .msg { 
            color: #065f46; 
            background: #d1fae5; 
            padding: 12px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            text-align: center; 
            font-weight: 600;
        }

        .row { 
            display: flex; 
            flex-direction: column; 
            gap: 15px; 
            margin-bottom: 20px; 
            background: #ffffff; 
            padding: 15px; 
            border: 1px solid #e5e7eb; 
            border-radius: 10px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        
        .thumb { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; }
        .inputs-col { display: flex; flex-direction: column; gap: 12px; width: 100%; }
        .flex-row { display: flex; flex-direction: column; gap: 12px; }

        input { 
            width: 100%; padding: 14px; border: 1px solid #d1d5db; 
            border-radius: 8px; font-size: 16px; background: #f9fafb; transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: #3b82f6; background: #fff; }
        input[type="file"] { padding: 10px; font-size: 14px; background: #fff; }

        button { 
            color: #fff; border: none; padding: 15px; cursor: pointer; 
            border-radius: 8px; font-weight: bold; font-size: 16px; transition: opacity 0.2s; 
        }
        button:active { opacity: 0.8; }
        
        .delete-btn { background: #ef4444; width: 100%; }
        .add-btn { background: #3b82f6; width: 100%; margin-bottom: 20px; }
        .save-btn { background: #10b981; width: 100%; margin-top: 10px; font-size: 18px; padding: 18px; }

        @media (min-width: 650px) {
            body { padding: 30px 20px; }
            .container { padding: 30px; }
            .row { flex-direction: row; align-items: center; }
            .thumb { width: 80px; height: 80px; align-self: center; }
            .flex-row { flex-direction: row; }
            .inputs-col { flex: 1; justify-content: center; }
            .delete-btn { width: auto; height: 100%; align-self: stretch; padding: 0 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Menu</h2>
        <?php if(isset($message)) echo "<div class='msg'>$message</div>"; ?>
        
        <form method="POST" action="admin.php" enctype="multipart/form-data">
            <div id="menuItems">
                <?php foreach ($menuData as $item): ?>
                <div class="row">
                    <?php if(!empty($item['image'])): ?>
                        <img src="<?= $item['image'] ?>" class="thumb" alt="Item">
                    <?php endif; ?>
                    
                    <div class="inputs-col">
                        <div class="flex-row">
                            <input type="text" name="name[]" value="<?= $item['name'] ?>" placeholder="Dish Name" required>
                            <input type="number" name="price[]" value="<?= $item['price'] ?>" placeholder="Price (₹)" required>
                        </div>
                        <div class="flex-row">
                            <input type="text" name="category[]" value="<?= $item['category'] ?>" placeholder="Category" required>
                            <input type="file" name="image[]" accept="image/*">
                            <input type="hidden" name="existing_image[]" value="<?= $item['image'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <button type="button" class="delete-btn" onclick="removeRow(this)">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="add-btn" onclick="addRow()">+ Add New Item</button>
            <button type="submit" class="save-btn">Save & Publish Menu</button>
        </form>
    </div>

    <script>
        function removeRow(btn) {
            btn.closest('.row').remove();
        }

        function addRow() {
            const row = document.createElement('div');
            row.className = 'row';
            row.innerHTML = `
                <div class="inputs-col">
                    <div class="flex-row">
                        <input type="text" name="name[]" placeholder="Dish Name" required>
                        <input type="number" name="price[]" placeholder="Price (₹)" required>
                    </div>
                    <div class="flex-row">
                        <input type="text" name="category[]" placeholder="Category" required>
                        <input type="file" name="image[]" accept="image/*">
                        <input type="hidden" name="existing_image[]" value="">
                    </div>
                </div>
                <button type="button" class="delete-btn" onclick="removeRow(this)">Remove</button>
            `;
            document.getElementById('menuItems').appendChild(row);
        }
    </script>
</body>
</html>
