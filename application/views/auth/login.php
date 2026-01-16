<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }

        .login-card {
            width: 100%;
            max-width: 500px;
            background: #ffffff;
            padding: 45px 40px;
            border-radius: 6px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        .underline-input {
            border: none;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 0;
            padding-left: 0;
            background-color: transparent;
        }

        .underline-input:focus {
            box-shadow: none;
            border-bottom-color: #34d399;
        }

        .form-floating > label {
            padding-left: 5;
            color: #9ca3af;
        }

        .btn-login {
            background: #34d399;
            color: #ffffff;
            border-radius: 50px;
            padding: 12px;
            font-weight: 500;
            border: none;
        }

        .btn-login:hover {
            background: #2fb989;
        }

        .social-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }

        .social-btn.fb { background: #3b5998; }
        .social-btn.tw { background: #1da1f2; }
        .social-btn.gg { background: #db4437; }
    </style>
</head>
<body>

<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="login-card text-center">

        <h3 class="fw-semibold mb-2">
            Sign In to Your Account
        </h3>

        <p class="text-muted small mb-4">
            Enter your email and password to access your account.
        </p>

        <?php if (validation_errors()): ?>
            <div class="alert alert-danger text-start">
                <?php echo validation_errors(); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger text-start">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo site_url('login'); ?>">

            <!-- Email -->
            <div class="form-floating mb-3 text-start">
                <input
                    type="email"
                    name="email"
                    class="form-control underline-input"
                    placeholder="Email"
                    value="<?php echo set_value('email'); ?>"
                    required
                >
                <label>Email</label>
            </div>

            <!-- Password -->
            <div class="form-floating mb-3 text-start">
                <input
                    type="password"
                    name="password"
                    class="form-control underline-input"
                    placeholder="Password"
                    required
                >
                <label>Password</label>
            </div>

            <!-- Remember + Forgot -->
            <div class="d-flex justify-content-between align-items-center mb-4 small">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label text-muted" for="remember">
                        Remember me
                    </label>
                </div>
                <a href="#" class="text-muted text-decoration-none">
                    Forgot Password
                </a>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn btn-login w-100 mb-4">
                Log In
            </button>
        </form>
    </div>
</div>

</body>
</html>
