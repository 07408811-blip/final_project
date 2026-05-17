<?php
session_start();
require_once "../../vendor/autoload.php";

use App\Helpers\EnvParser;
use App\Helpers\Database;
use App\Model\UserModel;
use App\Controller\UserControl;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ./dashboard.php');
    exit;
}

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$db = Database::getInstance();
$model = new UserModel();
$user_controller = new UserControl();


try {
    $env = new EnvParser();
    $env->load('../../.env');
} catch (Exception $e) {
    die("ENV Error: " . $e->getMessage());
}

$loginMsg = $_SESSION['login_msg'] ?? null;
unset($_SESSION['login_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? ''
    ];

    $result = $user_controller->login($data);

    if ($result['status']) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['role'] = $result['user']['role'];
        header('Location: ./dashboard.php');
        exit;
    } else {
        $_SESSION['login_msg'] = $result;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <header class="site-header">
        <img src="../img/logo.png" alt="Company Logo" class="site-header__logo">
    </header>

    <main class="main-content">
        <section class="auth-surface">
            <h1 class="auth-surface__title">Welcome back</h1>

            <?php if ($loginMsg): ?>
            <div class="alert alert--<?php echo $loginMsg['status'] ? 'success' : 'error'; ?>">
                <p><?php echo htmlspecialchars($loginMsg['message']); ?></p>
                <?php if (!empty($loginMsg['errors'])): ?>
                <ul>
                    <?php foreach ($loginMsg['errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="toggle-password" id="toggle-password" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn">Sign in</button>
            </form>

            <div class="auth-links">
                <a href="./forgot.php">Forgot password?</a>
                <hr class="auth-links__separator">
                <span>Don’t have an account? <a href="../../index.php">Register</a></span>
            </div>
        </section>
    </main>

    <script src="../js/scripted.js"></script>
</body>
</html>

