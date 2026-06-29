<?php
// ====== RDS Database Configuration ======
// These values with RDS information.
$db_host = getenv("DB_HOST");
$db_name = getenv("DB_NAME");
$db_user = getenv("DB_USER");
$db_pass = getenv("DB_PASSWORD");

$message = "";
$rows = [];

try {
    // Connect to MySQL RDS
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if it does not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            product_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert data when form is submitted
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $customer_name = trim($_POST["customer_name"] ?? "");
        $product_name = trim($_POST["product_name"] ?? "");

        if ($customer_name !== "" && $product_name !== "") {
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_name, product_name)
                VALUES (:customer_name, :product_name)
            ");
            $stmt->execute([
                ":customer_name" => $customer_name,
                ":product_name" => $product_name
            ]);

            $message = "Order submitted successfully. This record was written to the RDS MySQL database.";
        } else {
            $message = "Please enter both customer name and product name.";
        }
    }

    // Read latest records from RDS
    $stmt = $pdo->query("
        SELECT id, customer_name, product_name, created_at
        FROM orders
        ORDER BY id DESC
        LIMIT 10
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Database connection failed: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FreshBasket AWS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8fb;
            margin: 0;
            padding: 40px;
            color: #222;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            color: #2c5f2d;
        }

        .note {
            background: #eef7ee;
            border-left: 5px solid #2c5f2d;
            padding: 12px;
            margin-bottom: 20px;
        }

        form {
            margin-top: 20px;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            margin-top: 18px;
            padding: 10px 18px;
            background: #2c5f2d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #244d25;
        }

        .message {
            margin: 16px 0;
            padding: 12px;
            background: #fff8d8;
            border-left: 5px solid #e0b400;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background: #f0f0f0;
        }

        .footer {
            margin-top: 24px;
            font-size: 14px;
            color: #555;
        }

        .status-ok {
            color: #2c5f2d;
            font-weight: bold;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>FreshBasket AWS Application</h1>
    
    <!-- 
        Each request enters through the Load Balancer, is served by an EC2 instance in the Auto Scaling Group,
        and reads from or writes to the Amazon RDS MySQL database.
    -->

    <?php if ($message !== ""): ?>
        <div class="message">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <h2>Submit a FreshBasket Order</h2>

    <form method="POST" action="">
        <label for="customer_name">Customer Name</label>
        <input type="text" id="customer_name" name="customer_name" placeholder="Example: Linda" required>

        <label for="product_name">Product Name</label>
        <input type="text" id="product_name" name="product_name" placeholder="Example: Tomato Box" required>

        <button type="submit">Submit Order to RDS</button>
    </form>

    <h2>Latest Orders from RDS MySQL</h2>

    <?php if (count($rows) > 0): ?>
        <p class="status-ok">RDS connection is working. The records below were read from the database.</p>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Product Name</th>
                <th>Created At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["id"]); ?></td>
                    <td><?php echo htmlspecialchars($row["customer_name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["product_name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["created_at"]); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No orders found yet. Submit the form above to write the first record to RDS.</p>
    <?php endif; ?>

</div>
</body>
</html>