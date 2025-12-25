<?php
session_start();
// Include the database connection file
include 'db_connect.php'; 

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error_message = '';

// Handle login attempt
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Use SHA2(password, 256) for hashing the input password to compare with the database
    $hashed_password = hash('sha256', $password);

    // SQL to fetch user by username and check the hashed password
    $sql = "SELECT user_id, username, role, password_hash, status FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if ($user) {
        // 1. Verify Password Hash
        if ($user['password_hash'] === $hashed_password) {
            // 2. Check User Status
            if ($user['status'] === 'Active') {
                // Login successful: Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect to the dashboard
                header("Location: index.php");
                exit;
            } else {
                $error_message = "Your account is currently inactive.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Phoenix POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .input-field {
            /* Styles to ensure high contrast in dark mode */
            background-color: #374151 !important; /* gray-700 */
            color: #FFFFFF !important; /* white text */
            @apply p-3 border border-gray-600 rounded-xl shadow-inner focus:ring-indigo-500 focus:border-indigo-500 transition duration-150;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-gray-800 rounded-2xl shadow-2xl border border-gray-700 p-8 space-y-6 transform transition duration-300 hover:scale-[1.01]">
        
        <h2 class="text-4xl font-black text-green-400 border-b border-gray-700 pb-4 text-center">
            Phoenix POS Login
        </h2>
        <p class="text-gray-400 text-center">Sign in to access the point of sale terminal.</p>

        <?php if ($error_message): ?>
            <div class="p-3 bg-red-600/70 text-white rounded-xl font-medium text-sm text-center">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                <input type="text" id="username" name="username" required class="input-field w-full" placeholder="admin">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <input type="password" id="password" name="password" required class="input-field w-full" placeholder="password">
            </div>

            <button type="submit" name="login" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white text-lg font-bold transition duration-200 shadow-lg">
                Log In
            </button>
        </form>
        
        <div class="text-center text-xs text-gray-500 pt-4">
            Default Admin: <strong>admin</strong> / <strong>password</strong>
        </div>
    </div>

</body>
</html>
