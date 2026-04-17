<?php
session_start();
require '../includes/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: SL.php");
    exit;
}

$userId = $_SESSION['user_id'];
$success = "";
$error = "";

// POST handling

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Updates profile 
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $currentPw = $_POST['current_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Check if current password is correct
        if (!$user || !password_verify($currentPw, $user['password'])) {
            $error = "Current passphrase is incorrect.";
        } 
        
        // Check if username or email is empty
        elseif (empty($newUsername) || empty($newEmail)) {
            $error = "Username and email cannot be empty.";
        } 
        
        // Check if username or email is valid
        elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } 
        
        else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$newUsername, $newEmail, $userId]);
            
            // if stmt fetches then username or email is already taken
            if ($stmt->fetch()) {
                $error = "That username or email is already taken.";
            } 
            
            else {
            
            if (!empty($newPassword)) {
                // Check if new password is at least 8 characters
                if (strlen($newPassword) < 8) {
                        $error = "New passphrase must be at least 8 characters.";
                    } 
                    /* didnt realised i forgot proper unique passwords
                    preg_match is just for regex 
                    elseif (preg_match('/[A-Z]/', $newPassword) < 1) {
                        $error = "New passphrase must contain at least one uppercase letter.";
                    } 
                    elseif (preg_match('/[a-z]/', $newPassword) < 1) {
                        $error = "New passphrase must contain at least one lowercase letter.";
                    } 
                    elseif (preg_match('/[0-9]/', $newPassword) < 1) {
                        $error = "New passphrase must contain at least one number.";
                    }
                    This is for me later <3 */

                    // Hash new password
                    else {
                        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$newUsername, $newEmail, $hashed, $userId]);
                    }
                } 

                // Just update username and email
                else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$newUsername, $newEmail, $userId]);
                }

                // Check if update was successful
                if (empty($error)) {
                    $_SESSION['username'] = $newUsername;
                    $success = "Profile updated successfully!";
                }
            }
        }
    }

    // Delete account 
    if (isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        $confirmPw = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($confirmPw, $user['password'])) {
            $error = "Passphrase incorrect. Account not deleted.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            session_unset();
            session_destroy();
            header("Location: ../index.php");
            exit;
        }
    }
}

// Loads the associated user to userid
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrollPost – My Profile</title>
    <link rel="stylesheet" href="../styles/Normalize.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/gob.png">
    <link rel="stylesheet" href="../styles/style.css?v=1.10">
    <style>
        /* Beloved back button */
        .back-btn {
            display: inline-block;
            background: linear-gradient(to bottom, #c8a84b, #8b6914);
            border-top: 2px solid #f0d070;
            border-left: 2px solid #f0d070;
            border-bottom: 2px solid #3a2200;
            border-right: 2px solid #3a2200;
            color: #1a0e00;
            font-family: Verdana, Arial, sans-serif;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        body {
            display: block;
        }

        .profile-wrap {
            max-width: 520px;
            margin: 40px auto;
            background: var(--container-bg);
            border-top: 3px solid #d4aa50;
            border-left: 3px solid #d4aa50;
            border-bottom: 3px solid #3a2200;
            border-right: 3px solid #3a2200;
            outline: 2px solid #1a0e00;
            padding: 20px;
        }

        .profile-wrap h2 {
            display: block;
            background: linear-gradient(to bottom, #5a3a0a, #2b1a00);
            color: #ffcc44;
            font-size: 0.85rem;
            font-weight: bold;
            text-align: center;
            padding: 4px 8px;
            margin: -20px -20px 16px -20px;
            border-bottom: 2px solid #8b6914;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .profile-wrap label {
            font-size: 11px;
            font-weight: bold;
            color: var(--headings);
            display: block;
            margin-top: 10px;
        }

        .profile-wrap input[type="text"],
        .profile-wrap input[type="email"],
        .profile-wrap input[type="password"] {
            width: 100%;
            background: var(--input-bg);
            border-top: 2px solid #3a2200;
            border-left: 2px solid #3a2200;
            border-bottom: 2px solid #d4aa50;
            border-right: 2px solid #d4aa50;
            color: var(--headings);
            font-family: Verdana, Arial, sans-serif;
            font-size: 11px;
            padding: 5px;
            margin-top: 4px;
            margin-bottom: 2px;
            box-sizing: border-box;
        }

        .danger-zone {
            border-top: 2px solid #8b0000;
            margin-top: 24px;
            padding-top: 14px;
        }

        .danger-zone h3 {
            color: #8b0000;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .btn-danger-rs {
            background: linear-gradient(to bottom, #c84b4b, #8b1414);
            border-top: 2px solid #f07070;
            border-left: 2px solid #f07070;
            border-bottom: 2px solid #3a0000;
            border-right: 2px solid #3a0000;
            color: #fff;
            font-family: Verdana, Arial, sans-serif;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            cursor: pointer;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .btn-danger-rs:hover {
            background: linear-gradient(to bottom, #e06060, #a02020);
        }

        .back-link {
            display: block;
            max-width: 520px;
            margin: 20px auto 0;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <main style="padding: 16px;">

        <a href="../index.php" class="back-btn">Back to TrollPost</a>

        <div class="profile-wrap">
            <h2> My Profile</h2>

            <?php if ($success): ?>
                <p style="color:green; font-size:11px; margin:0 0 10px;"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p style="color:red; font-size:11px; margin:0 0 10px;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <!-- UPDATE FORM -->
            <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="update">

                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username']) ?>" required>

                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required>

                <label>New Passphrase
                    <span style="font-weight:normal;">(leave blank to keep current)</span>
                </label>
                <input type="password" name="new_password" placeholder="Min 8 characters">

                <label>Current Passphrase <span style="color:red;">*required to save</span></label>
                <input type="password" name="current_password" required placeholder="Enter your current passphrase">

                <input type="submit" value="Save Changes" class="btn-interaction" style="margin-top:12px;">
            </form>

            <!-- DELETE ACCOUNT -->
            <div class="danger-zone">
                <h3>Danger!</h3>
                <p style="font-size:11px; color:#8b0000; margin:0 0 8px;">
                    Deleting your account is permanent. All your posts will be removed too.
                </p>
                <form method="POST" action="profile.php"
                    onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete_account">
                    <label>Confirm Passphrase</label>
                    <input type="password" name="confirm_password" required placeholder="Enter passphrase to confirm">
                    <button type="submit" class="btn-danger-rs">Delete My Account</button>
                </form>
            </div>
        </div>

    </main>
</body>

</html>