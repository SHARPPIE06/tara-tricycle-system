<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TARA - Tricycle Assistance for Routes and Fares</title>
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="TARA: A route and fare tracking system that helps commuters and drivers access clearer tricycle routes, fare estimates, terminals, and transport information in Rizal Province.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/welcome.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
</head>
<body>
    <main class="welcome-screen">
        <div class="overlay"></div>

        <section class="welcome-content">
            <p class="welcome-text">Welcome to</p>

            <img 
                src="assets/tara-logo.png" 
                alt="TARA Logo" 
                class="tara-logo"
            />

            <h1 class="tagline">Tricycle Assistance for Routes and Fares</h1>

            <p class="description">
                A route and fare tracking system that helps commuters and drivers access clearer tricycle routes, fare estimates, terminals, and transport information.
            </p>
        </section>

        <!-- Using anchor tag directly to login.php to mimic the button design -->
        <button class="proceed-btn" id="proceedBtn" onclick="window.location.href='login.php'">
            Proceed
        </button>
    </main>
</body>
</html>
