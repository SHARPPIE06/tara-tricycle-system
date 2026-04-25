<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>

    <section class="auth-section">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Join TARA today</p>
            </div>
            
            <div class="auth-body">
                <form action="php/auth_register.php" method="POST">
                    
                    <div id="registerAlert" class="alert"></div>

                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" name="username" class="form-control" required placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Create a strong password">
                    </div>

                    <div class="form-group">
                        <label for="role">I am a:</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="user">Commuter</option>
                            <!-- Note: Admin registration usually requires special process, but keeping it here for prototyping -->
                            <option value="admin">System Admin / Operator</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary auth-btn">Create Account</button>
                    
                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                        <p style="margin-top: 10px;"><a href="index.php">Back to Home</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
</body>
</html>
