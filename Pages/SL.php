<?php
session_start();
require '../includes/connect.php';

$loginError = "";
$registerError = "";
$registerSuccess = "";

// --- SIGN IN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usernameOrEmail === '' || $password === '') {
        $loginError = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, isadmin FROM users WHERE username = :login OR email = :login LIMIT 1");
        $stmt->bindParam(':login', $usernameOrEmail);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['isadmin']  = $user['isadmin'];

            // Redirect based on role
            if ($user['isadmin'] == 1) {
                header("Location: pages/admin.php");
            } else {
                header("Location: orders.php");
            }
            exit;
        } else {
            $loginError = "Invalid credentials. Please try again.";
        }
    }
}

// --- SIGN UP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $registerError = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = "Invalid email address.";
    } elseif (strlen($password) < 8) {
        $registerError = "Password must be at least 8 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $registerError = "Username or email already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            // isadmin is intentionally ignored becausee i'll handle it in the DB
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed]);
            $registerSuccess = "Account created! You can now sign in.";
        }
    }
}
?>

<!-- Credit: template originally from freefrontend.com, modified to fit TrollPost theme -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrollPost</title>
    <link rel="stylesheet" href="styles/Normalize.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../img/gob.png">
    <link rel="stylesheet" href="../styles/SL.css?v=1.9">
</head>

<body>
    <main>
        <div class="container" id="container">

            <!-- Sign Up Form -->
            <div class="form-container sign-up-container">
                <form method="POST" action="SL.php">
                    <input type="hidden" name="action" value="register">
                    <h1>Create Account</h1>

                    <?php if ($registerError !== ""): ?>
                        <p style="color:red; font-size:0.85rem; margin:0 0 8px;">
                            <?= htmlspecialchars($registerError) ?>
                        </p>
                    <?php elseif ($registerSuccess !== ""): ?>
                        <p style="color:green; font-size:0.85rem; margin:0 0 8px;">
                            <?= htmlspecialchars($registerSuccess) ?>
                        </p>
                    <?php endif; ?>

                    <input type="text" name="username" placeholder="Username" required />
                    <input type="email" name="email" placeholder="Email" required />
                    <input type="password" name="password" placeholder="Passphrase (min 8 chars)" required />
                    <button type="submit">Sign Up</button>
                </form>
            </div>

            <!-- Sign In Form -->
            <div class="form-container sign-in-container">
                <form method="POST" action="SL.php">
                    <input type="hidden" name="action" value="login">
                    <h1>Sign in</h1>

                    <?php if ($loginError !== ""): ?>
                        <p style="color:red; font-size:0.85rem; margin:0 0 8px;">
                            <?= htmlspecialchars($loginError) ?>
                        </p>
                    <?php endif; ?>

                    <input type="text" name="username_or_email" placeholder="Username or Email" required />
                    <input type="password" name="password" placeholder="Passphrase" required />
                    <a href="#">Forgot your passphrase?</a>
                    <button type="submit">Sign In</button>
                </form>
            </div>

            <!-- Overlay -->
            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <img src="../img/gob.png" alt="Goblin Icon" class="mb-3" style="width: 80px; height: 80px;">
                        <h1>Welcome Back!</h1>
                        <p>To keep connected with us please login with your personal info</p>
                        <button class="ghost" id="signIn">Sign In</button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <img src="../img/gob.png" alt="Goblin Icon" class="mb-3" style="width: 80px; height: 80px;">
                        <h1>Greetings, Traveler!</h1>
                        <p>Enter your personal details and start your journey with us</p>
                        <button class="ghost" id="signUp">Sign Up</button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="../js/js.js"></script>
</body>

</html>