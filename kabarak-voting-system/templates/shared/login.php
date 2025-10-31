<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Login';
$bodyClass = $bodyClass ?? 'auth-page';

require __DIR__ . '/header.php';

$csrfToken = kvs_csrf_token();
?>
<section class="auth-container">
    <div class="auth-card" id="authCard">
        <div class="auth-card__intro">
            <h2>Welcome to the Kabarak Blockchain Voting System</h2>
            <p>Securely participate in student elections with tamper-proof blockchain verification and real-time transparency.</p>
        </div>
        <div class="auth-card__forms">
            <form id="loginForm" class="auth-form active" autocomplete="on">
                <h3>Sign in</h3>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label>
                    <span>Email</span>
                    <input type="email" name="username" required placeholder="name@kabarak.ac.ke">
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required placeholder="Enter your password">
                </label>
                <button type="submit" class="primary-button full">Access account</button>
                <p class="form-switch">New voter? <a href="#" data-switch="register">Create your account</a></p>
                <p class="form-feedback" role="alert"></p>
            </form>

            <form id="registerForm" class="auth-form" autocomplete="on">
                <h3>Create student account</h3>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label>
                    <span>Full name</span>
                    <input type="text" name="full_name" required placeholder="Jane Doe">
                </label>
                <label>
                    <span>Student ID</span>
                    <input type="text" name="student_id" required placeholder="e.g. AB123/2020">
                </label>
                <label>
                    <span>University email</span>
                    <input type="email" name="username" required placeholder="name@kabarak.ac.ke">
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required placeholder="Create a strong password">
                </label>
                <button type="submit" class="primary-button full">Register &amp; sign in</button>
                <p class="form-switch">Already registered? <a href="#" data-switch="login">Back to sign in</a></p>
                <p class="form-feedback" role="alert"></p>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>

