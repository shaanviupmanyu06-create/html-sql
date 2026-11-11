<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!preg_match('/\A[a-zA-Z0-9_]{3,32}\z/', $username)) {
        $error = 'Choose a username with 3–32 letters, numbers, or underscores.';
    } elseif (!preg_match('/\A[0-9]{6}\z/', $password)) {
        $error = 'Your password must be exactly 6 digits.';
    } elseif ($password !== $passwordConfirmation) {
        $error = 'The passwords do not match.';
    } else {
        $statement = $db->prepare('SELECT id FROM `user` WHERE username = ?');
        $statement->bind_param('s', $username);
        $statement->execute();

        if ($statement->get_result()->fetch_assoc()) {
            $error = 'That username is already taken.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $statement = $db->prepare('INSERT INTO `user` (username, password_hash) VALUES (?, ?)');
                $statement->bind_param('ss', $username, $passwordHash);
                $statement->execute();
            } catch (mysqli_sql_exception $exception) {
                if ($exception->getCode() !== 1062) {
                    throw $exception;
                }
                $error = 'That username is already taken.';
            }

            if ($error === '') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $db->insert_id;
                $_SESSION['username'] = $username;
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | Member Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="auth-layout">
        <section class="intro">
            <a class="brand" href="index.php"><span class="brand-mark">M</span> Member Portal</a>
            <div class="intro-copy">
                <p class="eyebrow">A GOOD PLACE TO START</p>
                <h1>Your account, ready when you are.</h1>
                <p>Choose a username and password. We’ll take care of creating your unique account ID.</p>
            </div>
            <p class="intro-foot">Simple setup. Your own space.</p>
        </section>

        <section class="form-side">
            <div class="form-wrap">
                <p class="eyebrow">JOIN THE PORTAL</p>
                <h2>Create account</h2>
                <p class="form-subtitle">It only takes a moment to get started.</p>

                <?php if ($error !== ''): ?>
                    <p class="alert" role="alert"><?= e($error) ?></p>
                <?php endif; ?>

                <form method="post" action="register.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" required minlength="3" maxlength="32" pattern="[a-zA-Z0-9_]+" value="<?= e($username) ?>" aria-describedby="username-hint">
                    <small id="username-hint">3–32 characters: letters, numbers, and underscores.</small>

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" inputmode="numeric" pattern="[0-9]{6}" required minlength="6" maxlength="6">
                    <small>Use exactly 6 digits.</small>

                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" inputmode="numeric" pattern="[0-9]{6}" required minlength="6" maxlength="6">

                    <button class="button" type="submit">Create account <span aria-hidden="true">&rarr;</span></button>
                </form>

                <p class="switch-link">Already have an account? <a href="index.php">Sign in</a></p>
            </div>
        </section>
    </main>
</body>
</html>
