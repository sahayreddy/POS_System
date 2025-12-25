
    <?php
    session_start();
    include 'db_connect.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_data'])) {
        
        // --- Data Sanitization and Retrieval ---
        $cart_data = json_decode($_POST['cart_data'], true);
        $total_amount = floatval($_POST['total_amount']);
        $payment_method = $_POST['payment_method'] ?? 'Cash';
        $customer_id = 1; // Default to 'Walk-in Customer'
        $total_cost = 0.00; // Will calculate this during item loop

        // --- TRANSACTION START (Ensures data integrity across tables) ---
        $conn->begin_transaction();
        $success = true;

        try {
            // 1. INSERT into Orders Table (Table 6)
            $order_sql = "INSERT INTO orders (customer_id, employee_id, total_amount, order_status) VALUES (?, ?, ?, 'Completed')";
            $stmt_order = $conn->prepare($order_sql);
            $stmt_order->bind_param("iid", $customer_id, $employee_id, $total_amount);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            $stmt_order->close();
            
            // 2. Loop through cart to insert into Order_Items (Table 7) & Inventory Logs (Table 10)
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, cost) VALUES (?, ?, ?, ?, ?)");
            $stmt_log = $conn->prepare("INSERT INTO inventory_logs (product_id, type, quantity_change) VALUES (?, 'SALE', ?)");
            $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");

            foreach ($cart_data as $productId => $item) {
                $qty = intval($item['quantity']);
                $price = floatval($item['price']);
                $cost = floatval($item['cost']);
                $item_profit = ($price - $cost) * $qty;
                $total_cost += $item_profit; // Accumulate profit
                
                // Insert into Order Items (Table 7)
                $stmt_item->bind_param("iiidd", $order_id, $productId, $qty, $price, $cost);
                $stmt_item->execute();
                
                // Insert into Inventory Log (Table 10)
                $negative_qty = -$qty;
                $stmt_log->bind_param("ii", $productId, $negative_qty);
                $stmt_log->execute();

                // Update Stock in Products (Table 2)
                $stmt_stock->bind_param("ii", $qty, $productId);
                $stmt_stock->execute();
            }

            // 3. Update Profit in Orders Table (Table 6)
            $final_profit = $total_amount - ($total_amount - $total_cost); 
            $stmt_profit = $conn->prepare("UPDATE orders SET profit = ? WHERE order_id = ?");
            $stmt_profit->bind_param("di", $final_profit, $order_id);
            $stmt_profit->execute();
            $stmt_profit->close();
            
            // 4. Insert into Transactions Table (Table 8)
            $stmt_trans = $conn->prepare("INSERT INTO transactions (order_id, payment_method, amount_paid) VALUES (?, ?, ?)");
            $stmt_trans->bind_param("isd", $order_id, $payment_method, $total_amount);
            $stmt_trans->execute();
            $stmt_trans->close();
            
            // --- Commit Transaction ---
            $conn->commit();
            $_SESSION['cart'] = []; // Clear cart on success
            header("Location: order_details.php?order_id=$order_id&status=success");
            
        } catch (Exception $e) {
            // --- Rollback on Error ---
            $conn->rollback();
            $success = false;
            header("Location: index.php?status=error&message=" . urlencode($e->getMessage()));
        }

        $conn->close();
    } else {
        header("Location: index.php");
    }
    ?>