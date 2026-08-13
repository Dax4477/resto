<?php
$file = 'menu.json';

// Save data if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newMenu = [];
    $names = $_POST['name'] ?? [];
    $prices = $_POST['price'] ?? [];
    $categories = $_POST['category'] ?? [];

    for ($i = 0; $i < count($names); $i++) {
        // Only save if the dish name is not blank
        if (trim($names[$i]) !== '') {
            $newMenu[] = [
                'name' => htmlspecialchars(trim($names[$i])),
                'category' => htmlspecialchars(trim($categories[$i])),
                'price' => htmlspecialchars(trim($prices[$i]))
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
        .container { max-width: 600px; background: #fff; padding: 20px; border-radius: 8px; margin: auto; }
        .row { display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
        input { flex: 1; min-width: 120px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #28a745; color: #fff; border: none; padding: 12px 15px; cursor: pointer; border-radius: 4px; font-weight: bold; width: 100%; }
        .add-btn { background: #007bff; margin-bottom: 20px; }
        .msg { color: #155724; background: #d4edda; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;}
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Menu</h2>
        <?php if(isset($message)) echo "<div class='msg'>$message</div>"; ?>
        
        <form method="POST">
            <div id="menuItems">
                <?php foreach ($menuData as $item): ?>
                <div class="row">
                    <input type="text" name="name[]" value="<?= $item['name'] ?>" placeholder="Dish Name" required>
                    <input type="text" name="category[]" value="<?= $item['category'] ?>" placeholder="Category" required>
                    <input type="number" name="price[]" value="<?= $item['price'] ?>" placeholder="Price (₹)" required>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="add-btn" onclick="addRow()">+ Add New Item</button>
            <button type="submit">Save Menu</button>
        </form>
    </div>

    <script>
        // Appends a new blank row for a new menu item
        function addRow() {
            const row = document.createElement('div');
            row.className = 'row';
            row.innerHTML = `
                <input type="text" name="name[]" placeholder="Dish Name" required>
                <input type="text" name="category[]" placeholder="Category" required>
                <input type="number" name="price[]" placeholder="Price (₹)" required>
            `;
            document.getElementById('menuItems').appendChild(row);
        }
    </script>
</body>
</html>
