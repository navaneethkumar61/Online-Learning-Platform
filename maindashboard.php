<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Dashboard</title>
    <style>
        /* Reset & basic styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f4f9; color: #333; }

        /* Header / Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ff6b81;
            padding: 15px 30px;
            color: #fff;
        }
        .navbar h1 { font-size: 24px; }
        .navbar ul { list-style: none; display: flex; gap: 20px; }
        .navbar ul li { cursor: pointer; }
        .navbar ul li a { text-decoration: none; color: #fff; font-weight: 500; transition: 0.3s; }
        .navbar ul li a:hover { color: #ffd6e0; }

        /* Hero Section */
.hero {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: url('https://img.freepik.com/free-vector/online-tutorials-concept_52683-37480.jpg') no-repeat center center;
    background-size: cover;
    text-align: center;
    color: #fff;
    overflow: hidden;
    filter: brightness(1);
    /* margin-top: 70px; adjust this to your navbar height */
}


/* Dark overlay to improve text visibility */
.hero::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* slightly darker for better contrast */
    z-index: 1;
}

/* Hero text and buttons above overlay */
.hero h2,
.hero p,
.hero .btn {
    position: relative;
    z-index: 2;
}

.hero h2 {
    margin-bottom: 15px;
}
.hero p {
    margin-bottom: 25px;
}


        .hero .btn {
            background-color: #ff4757;
            color: #fff;
            padding: 12px 25px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }
        .hero .btn:hover { background-color: #ff6b81; }

        /* Footer */
        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        footer a { color: #ff6b81; text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 10px; }
            .hero h2 { font-size: 36px; }
            .hero p { font-size: 16px; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <h1>LearnHub</h1>
        <ul>
            <li><a href="#about">About Us</a></li>
            <li><a href="#contact">Contact Us</a></li>
            <?php if(!isset($_SESSION['username'])): ?>
                <li><a href="SignUp_Login.php">Sign Up</a></li>
                <li><a href="SignUp_Login.php">Login</a></li>
            <?php else: ?>
                <!-- <li><a href="student_dashboard.php">Dashboard</a></li> -->
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <h2>Welcome to LearnHub</h2>
        <p>Your one-stop solution for learning and managing courses online.</p>
        <?php if(!isset($_SESSION['username'])): ?>
            <div>
                <a href="SignUp_Login.php" class="btn">Sign Up</a>
                <a href="SignUp_Login.php" class="btn">Login</a>
            </div>
        <?php else: ?>
            <div>
                <a href="student_dashboard.php" class="btn">Go to Dashboard</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- About Section -->
    <section id="about" style="padding:50px; text-align:center;">
        <h2>About Us</h2>
        <p>LearnHub is designed to provide a seamless online learning experience. We connect students and instructors in an interactive environment.</p>
    </section>

    <!-- Contact Section -->
    <section id="contact" style="padding:50px; text-align:center; background-color:#ffe6e9;">
        <h2>Contact Us</h2>
        <p>Email: navaneethbharadwaj@gmail.com | Phone: +91 6305892914</p>
        <p>Address: SRM University, Neerukonda, Guntur, Andhra Pradesh, India</p>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date("Y"); ?> LearnHub. All rights reserved.</p>
    </footer>

</body>
</html>
