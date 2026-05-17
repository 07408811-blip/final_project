<?php
session_start();
require_once "vendor/autoload.php";

use App\Helpers\EnvParser;
use App\Helpers\Database;
use App\Model\UserModel;
use App\Controller\UserControl;

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$db = Database::getInstance();
$model = new UserModel();
$user_controller = new UserControl();

// Load ENV
try {
    $env = new EnvParser();
    $env->load('.env');
} catch (Exception $e) {
    die("ENV Error: " . $e->getMessage());
}

$regMsg = $_SESSION['reg_msg'] ?? null;
unset($_SESSION['reg_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'role' => $_POST['role'] ?? 'student'
    ];

    $result = $user_controller->register($data);
    $_SESSION['reg_msg'] = $result;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

    <header class="site-header">
        <img src="./assets/img/logo.png" alt="Company Logo" class="site-header__logo">
    </header>

    <main class="main-content">
        <section class="auth-surface">
            <h1 class="auth-surface__title">Create an account</h1>

            <?php if ($regMsg): ?>
            <div class="alert alert--<?php echo $regMsg['status'] ? 'success' : 'error'; ?>">
                <p><?php echo htmlspecialchars($regMsg['message']); ?></p>
                <?php if (!empty($regMsg['errors'])): ?>
                <ul>
                    <?php foreach ($regMsg['errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>

                <button type="submit" class="btn">Create account</button>
            </form>

            <div class="auth-links">
                <hr class="auth-links__separator">
                <span>Already have an account? <a href="./assets/pages/login.php">Sign in</a></span>
            </div>
        </section>
    </main>

    <script src="./assets/js/scripted.js"></script>
</body>
</html>

