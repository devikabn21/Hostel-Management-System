<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (is_admin()) {
    redirect('dashboard.php');
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim(post_string('username'));
    $email = strtolower(trim(post_string('email')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!preg_match('/^[A-Za-z0-9._-]{3,32}$/', $username)) {
        $errors[] = 'Username must be 3–32 characters using letters, numbers, dots, underscores, or hyphens.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address (up to 190 characters).';
    }
    if (strlen($password) < 12) {
        $errors[] = 'Password must contain at least 12 characters.';
    }
    if (!hash_equals($password, $passwordConfirmation)) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO admin_users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
            );
            $statement->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            flash('success', 'Account created. You can now sign in with your username or email.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if (is_constraint_violation($exception)) {
                $errors[] = 'That username or email is already registered. Choose different account details.';
            } else {
                error_log($exception->getMessage());
                $errors[] = 'The account could not be created. Please try again.';
            }
        }
    }
}
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | Haven Hostel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="login-body">
    <main class="login-layout">
        <section class="login-intro">
            <a class="brand brand-on-dark" href="login.php"><span class="brand-mark">⌂</span><span>Haven<span class="brand-light">Hostel</span></span></a>
            <div class="intro-copy"><span class="eyebrow">A CALMER WAY TO MANAGE</span><h1>Your hostel,<br><em>well organized.</em></h1><p>Create an account to manage student records, rooms, and bed allocations in one place.</p></div>
            <div class="intro-footer"><span class="status-dot"></span> Hostel administration portal</div>
        </section>
        <section class="login-panel">
            <form class="login-form" method="post" action="register.php" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <span class="eyebrow">NEW ACCOUNT</span><h2>Create account</h2><p class="muted">Choose a unique username and email address. Each account has administrator access to hostel records.</p>
                <?php if ($flashMessage): ?><div class="notice notice-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div><?php endif; ?>
                <?php if ($errors): ?><div class="notice notice-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <label for="username">Username</label><input id="username" name="username" type="text" minlength="3" maxlength="32" autocomplete="username" required value="<?= e($username) ?>">
                <label for="email">Email address</label><input id="email" name="email" type="email" maxlength="190" autocomplete="email" required value="<?= e($email) ?>">
                <label for="password">Password <span class="field-hint">At least 12 characters</span></label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
                <button class="button button-primary button-full" type="submit">Create account <span aria-hidden="true">→</span></button>
                <p class="login-note">Already registered? <a href="login.php">Sign in</a></p>
            </form>
        </section>
    </main>
</body>
</html>