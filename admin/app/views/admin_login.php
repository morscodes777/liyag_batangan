<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Liyag Batangan Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/assets/css/admin.css">
</head>
<body>
    <div class="login-page">
        <!-- LEFT SIDE (Branding) -->
        <div class="branding-container">
            <div class="animated-bg"></div>
            <h1 class="logo-title">
                <span class="liyag">Liyag</span> <span class="batangan">Batangan</span>
            </h1>
            <p class="tagline">Empowering Batangueño Products — Admin Access</p>
        </div>

        <!-- RIGHT SIDE (Form) -->
        <div class="form-container">
            <form method="POST" class="login-form">
                <h2>Admin Sign In</h2>

                <div class="input-group">
                    <input type="text" name="email" placeholder="Admin Email" required>
                    <i class="bi bi-person-circle"></i>
                </div>

                <div class="input-group password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="bi bi-lock-fill"></i>
                    <i class="bi bi-eye-slash-fill toggle-password" id="togglePassword"></i>
                </div>

                <button type="submit" class="submit-btn">Login <i class="bi bi-arrow-right"></i></button>

                <?php if (!empty($error)): ?>
                    <p class="message" style="color: red; text-align:center; margin-top:15px;">
                        <?= htmlspecialchars($error) ?>
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const password = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                this.classList.toggle('bi-eye-fill');
                this.classList.toggle('bi-eye-slash-fill');
            });
        });
    </script>
</body>
</html>
