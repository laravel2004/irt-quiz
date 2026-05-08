<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IRT Exam System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card glass animate-fade-in">
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="font-size: 3rem; color: var(--accent); margin-bottom: 16px;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; margin-bottom: 8px;">Welcome Back</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Please enter your details to sign in</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="email" class="form-input" placeholder="admin@example.com" style="padding-left: 44px;" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" class="form-input" placeholder="••••••••" style="padding-left: 44px;" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-secondary); cursor: pointer;">
                        <input type="checkbox" style="accent-color: var(--accent);"> Remember me
                    </label>
                    <a href="#" style="color: var(--accent); font-size: 0.85rem; text-decoration: none;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">
                    Sign In <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div style="margin-top: 32px; text-align: center; font-size: 0.85rem; color: var(--text-secondary);">
                Don't have an account? <a href="#" style="color: var(--accent); text-decoration: none; font-weight: 600;">Register Now</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // In a real app, you'd handle AJAX login here.
            // For now, we allow the form to submit to the dashboard route as requested.
            console.log('Logging in...');
        });
    </script>
</body>
</html>
