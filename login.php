<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
</head>
<body style="background-color: var(--navy);">

    <section class="auth-section">
        <div class="auth-overlay"></div>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="assets/tara-logo.png" alt="TARA Logo" class="auth-logo">
                    <p>Login to your TARA account</p>
                </div>
                
                <div class="auth-body">
                    <form action="php/auth_login.php" method="POST">
                        
                        <div id="loginAlert" class="alert"></div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary auth-btn">Login</button>
                        
                        <div class="auth-footer">
                            <p>Don't have an account? <a href="register.php">Create one here</a></p>
                            <p style="margin-top: 10px;"><a href="index.php">Back to Home</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
</body>
</html>
