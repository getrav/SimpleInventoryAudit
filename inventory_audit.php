
<?php
session_start();

// Connect to the SQLite database
$db = new SQLite3('/mnt/nvme/inventoryaudit/db/inventory_audit.db');


// If the session array for scanned items doesn't exist, create it
if (!isset($_SESSION['user_group'])) {
    $_SESSION['user_group'] = isset($_POST['user_group']) ? $_POST['user_group'] : null;
}

if (!isset($_SESSION['scanned_items'])) {
    $_SESSION['scanned_items'] = [];
}

if (!isset($_SESSION['scanned_items'])) {
    $_SESSION['scanned_items'] = [];
}

// Assuming barcode input is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];
    
    if (strlen($barcode) == 3) {
        // Store BIN CODE temporarily in session
        $_SESSION['bin_code'] = $barcode;
    } else {
        // Treat as Lot No.
        $lot_no = $barcode;

        // Look up in the "Lookup" table
        $item = lookup_lot_no($lot_no);
        
        if ($item) {
            // If a BIN CODE was scanned prior, append it to the record
            if (isset($_SESSION['bin_code'])) {
                $item['bin_code'] = $_SESSION['bin_code'];
                unset($_SESSION['bin_code']);  // Clear the stored BIN CODE
            }
            
            // Store the scanned item in the database
            $stmt = $db->prepare('INSERT INTO scanned_items (bin_code, lot_no, item_name, remaining_quantity) VALUES (?, ?, ?, ?)');
            $stmt->bindValue(1, $item['bin_code']);
            $stmt->bindValue(2, $item['lot_no']);
            $stmt->bindValue(3, $item['item_name']);
            
$stmt->bindValue(4, $item['remaining_quantity']);
$stmt->bindValue(5, $_SESSION['user_group']);

            $stmt->execute();

            // Add to the session scanned items list (for frontend display)
            $_SESSION['scanned_items'][] = $item;
        } else {
            // Handle error for unidentified Lot No.
            echo "Error: Lot No. not found!";
        }
    }
}

// Function to look up Lot No. in the "Lookup" table
function lookup_lot_no($lot_no) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM lookup WHERE lot_no = ?');
    $stmt->bindValue(1, $lot_no);
    $result = $stmt->execute();
    $item = $result->fetchArray(SQLITE3_ASSOC);
    return $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Audit Web App</title>
    <style>
        /* Styles for the mockup */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        #scanned-items {
            height: 200px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .duplicate {
            background-color: #ffcccc;
        }
    </style>
</head>
<body>


<!-- User Group Selection -->
<?php if (!isset($_SESSION['user_group'])): ?>
<h3>Select Your Group:</h3>
<form action="" method="POST">
    <select name="user_group">
        <option value="group1">Group 1</option>
        <option value="group2">Group 2</option>
    </select>
    <input type="submit" value="Submit">
</form>
<?php else: ?>
<h2>Inventory Audit</h2>

<!-- Scanned Items Area -->
<div id="scanned-items">
    <?php
    // Display scanned items from the database
    $results = $db->query("SELECT * FROM scanned_items ORDER BY id DESC");
    while ($row = $results->fetchArray()) {
        echo "<div class='item-row'>";
        echo "<span>" . $row['bin_code'] . " - " . $row['lot_no'] . " - " . $row['item_name'] . " - " . $row['remaining_quantity'] . "</span>";
        echo "<span><button>Edit</button><button>Delete</button></span>";
        echo "</div>";
    }
    ?>
</div>

<!-- Input Area -->
<h3>Scan Barcode:</h3>
<form action="" method="POST">
    <input type="text" name="barcode" id="barcode-input" placeholder="Scan barcode here...">
    <input type="submit" id="submit-barcode" value="Submit">
</form>

<?php endif; ?>
</body>
</html>
