<?php
require '../includes/connect.php';

// ------------------
// RECAPTCHA CONFIG | I know im posting open secrets. idgaf
// ------------------
define('RECAPTCHA_SITE_KEY', '6LfvrqYsAAAAAJRx9QbNuO9ki591owa55L5n5e_S');
define('RECAPTCHA_SECRET_KEY', '6LfvrqYsAAAAACjCCDaFI_5limu6KRxlQRK609JE');

function verifyCaptcha(): bool
{
    // Bypass reCAPTCHA on localhost for development
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === 'localhost' || $host === '127.0.0.1') {
        return true;
    }

    $token = $_POST['g-recaptcha-response'] ?? '';
    if (empty($token))
        return false;

    $response = file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?secret='
        . RECAPTCHA_SECRET_KEY . '&response=' . urlencode($token)
    );
    $data = json_decode($response, true);
    return $data['success'] ?? false;
}


$loginError = "";
$registerError = "";
$registerSuccess = "";

// --- SIGN IN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {

    if (!verifyCaptcha()) {
        $loginError = "Please complete the CAPTCHA.";
    } else {
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
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['isadmin'] = $user['isadmin'];

                header("Location: ../index.php");

                exit;
            } else {
                $loginError = "Invalid credentials. Please try again.";
            }
        }
    }
}

// --- SIGN UP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {

    if (!verifyCaptcha()) {
        $registerError = "Please complete the CAPTCHA.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
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
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed]);

                // Auto login the new user and redirect to feed
                $stmt = $pdo->prepare("SELECT id, username, isadmin FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUser['id'];
                $_SESSION['username'] = $newUser['username'];
                $_SESSION['isadmin'] = $newUser['isadmin'];
                header("Location: /~Mars200561234/TrollPost/index.php");
                exit;
            }
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
    <title>TrollPost – Sign In / Sign Up</title>
    <link rel="stylesheet" href="../styles/Normalize.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <!-- Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link rel="icon" type="image/png" href="../img/gob.png">
    <link rel="stylesheet" href="../styles/SL.css?v=1.9">
    <style>
        /* makes recaptcha fit inside the form panels */
        .g-recaptcha {
            transform: scale(0.85);
            transform-origin: 0 0;
            margin: 8px 0 4px;
        }

        .back-btn {
            display: inline-block;
            background: linear-gradient(to bottom, #c8a84b, #8b6914);
            border-top: 2px solid #f0d070;
            border-left: 2px solid #f0d070;
            border-bottom: 2px solid #3a2200;
            border-right: 2px solid #3a2200;
            color: #1a0e00;
            font-family: Verdana, Arial, sans-serif;
            font-size: 10px;
            font-weight: bold;
            padding: 3px 10px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 12px;
            /* fixed it. fuck this button bro */
            position: absolute;
            top: 20px;
            left: 10px;
        }

        .back-btn:hover {
            background: linear-gradient(to bottom, #e0c060, #a07820);
            color: #1a0e00;
            text-decoration: none;
        }

        .back-btn:active {
            border-top: 2px solid #3a2200;
            border-left: 2px solid #3a2200;
            border-bottom: 2px solid #f0d070;
            border-right: 2px solid #f0d070;
        }
    </style>
</head>

<body>
    <main>
        <a href="../index.php" class="back-btn">Back to TrollPost</a>

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

                    <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>

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

                    <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>

                    <button type="submit">Sign In</button>
                </form>
            </div>

            <!-- Overlay Container -->
            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <img src="../img/gob.png" alt="Goblin Icon" class="mb-3" style="width:80px;height:80px;">
                        <h1>Welcome Back!</h1>
                        <p>To keep connected with us please login with your personal info</p>
                        <button class="ghost" id="signIn">Sign In</button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <img src="../img/gob.png" alt="Goblin Icon" class="mb-3" style="width:80px;height:80px;">
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