<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (is_admin()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login = post_string('login');
    $password = (string) ($_POST['password'] ?? '');

    try {
        $normalizedLogin = strtolower($login);
        $statement = db()->prepare('SELECT id, username, email, password_hash FROM admin_users WHERE lower(username) = :login OR lower(email) = :login LIMIT 1');
        $statement->execute(['login' => $normalizedLogin]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            flash('success', 'Welcome back, ' . $admin['username'] . '.');
            redirect('dashboard.php');
        }
        $error = 'The username/email or password was not recognized.';
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $error = 'Unable to connect to the database. Check the setup instructions.';
    }
}
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in | Haven Hostel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="login-body">
    <main class="login-layout">
        <section class="login-intro">
            <a class="brand brand-on-dark" href="login.php"><span class="brand-mark">⌂</span><span>Haven<span class="brand-light">Hostel</span></span></a>
            <div class="intro-copy"><span class="eyebrow">A CALMER WAY TO MANAGE</span><h1>Welcome to your<br><em>hostel office.</em></h1><p>One clear view of the people, rooms, and everyday work that keep campus life running.</p></div>
            <div class="intro-footer"><span class="status-dot"></span> Hostel administration portal</div>
        </section>
        <section class="login-panel">
            <form class="login-form" method="post" action="login.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <span class="eyebrow">ADMIN ACCESS</span><h2>Sign in</h2><p class="muted">Use your administrator account to continue.</p>
                <?php if ($flashMessage): ?><div class="notice notice-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div><?php endif; ?>
                <?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= e($error) ?></div><?php endif; ?>
                <label for="login">Username or email</label><input id="login" name="login" type="text" autocomplete="username" required value="<?= e($_POST['login'] ?? '') ?>">
                <label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="button button-primary button-full" type="submit">Sign in <span aria-hidden="true">→</span></button>
                <p class="login-note">New here? <a href="register.php">Create an account</a></p>
            </form>
        </section>
    </main>
</body>
</html>