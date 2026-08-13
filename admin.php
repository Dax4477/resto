<?php
$file = 'menu.json';
$uploadDir = 'uploads/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Save data if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newMenu = [];
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
                // Create a unique file name to prevent overwriting
                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['image']['name'][$i]));
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imagePath = $targetPath;
                }
            }

            $newMenu[] = [
                'name' => htmlspecialchars(trim($names[$i])),
                'category' => htmlspecialchars(trim($categories[$i])),
                'price' => htmlspecialchars(trim($prices[$i])),
                'image' => $imagePath
            ];
        }
    }
    
    // Write back to the JSON file
    file_put_contents($file, json_encode($newMenu, JSON_PRETTY_PRINT));
    $message = "Menu updated successfully!";
}

// Load current data to populate the form
$menuData = [];
if (file_exists($file)) {
    $menuData = json_decode(file_get_contents($file), true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 800px; background: #fff; padding: 20px; border-radius: 8px; margin: auto; }
        .row { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; background: #fafafa; padding: 15px; border: 1px solid #ddd; border-radius: 6px;}
        .inputs-col { flex: 1; display: flex; flex-direction: column; gap: 10px; min-width: 200px; }
        .flex-row { display: flex; gap: 10px; }
        input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { color: #fff; border: none; padding: 12px 15px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .save-btn { background: #28a745; width: 100%; font-size: 16px; margin-top: 10px; }
        .add-btn { background: #007bff; width: 100%; margin-bottom: 20px; }
        .delete-btn { background: #dc3545; padding: 10px 15px; height: 100%; }
        .msg { color: #155724; background: #d4edda; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;}
        .thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Menu</h2>
        <?php if(isset($message)) echo "<div class='msg'>$message</div>"; ?>
        
        <!-- enctype is required for file uploads -->
        <form method="POST" enctype="multipart/form-data">
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
                            <!-- Hidden input to keep the old image if a new one isn't uploaded -->
                            <input type="hidden" name="existing_image[]" value="<?= $item['image'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <button type="button" class="delete-btn" onclick="removeRow(this)">X</button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="add-btn" onclick="addRow()">+ Add New Item</button>
            <button type="submit" class="save-btn">Save Menu</button>
        </form>
    </div>

    <script>
        // Removes the item from the screen. It will be deleted from JSON upon saving.
        function removeRow(btn) {
            btn.closest('.row').remove();
        }

        // Appends a new blank row
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
                <button type="button" class="delete-btn" onclick="removeRow(this)">X</button>
            `;
            document.getElementById('menuItems').appendChild(row);
        }
    </script>
</body>
</html>
