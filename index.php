<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPSU Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f8f9fa;
            color: #2d4059;
            line-height: 1.6;
        }

        header {
            padding: 1rem 2rem;
            background: #ffffff;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .logo {
            height: 65px;
            transition: transform 0.3s;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        nav {
            flex-grow: 1;
            text-align: right;
        }

        .nav-button {
            background: #4a90e2;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            margin-left: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .nav-button:hover {
            background: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74,144,226,0.3);
        }

        .hero {
            text-align: center;
            padding: 180px 20px;
            position: relative;
            overflow: hidden;
            color: white;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(74, 144, 226, 0.9), rgba(42, 98, 168, 0.9)),
                        url('https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2076&q=80') center/cover;
            z-index: -1;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 25px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: slideUp 1s ease-out;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 35px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            animation: slideUp 1s ease-out 0.2s;
            animation-fill-mode: backwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section {
            padding: 60px 20px;
            background: white;
            margin: 40px 5%;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        .section h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 40px;
            color: #4a90e2;
        }

        .rules-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .rule-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            transition: transform 0.3s;
            border: 1px solid #e9ecef;
        }

        .rule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .how-it-works {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .step {
            padding: 30px;
            background: #ffffff;
            border-radius: 15px;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .step:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .step i {
            font-size: 2.5rem;
            color: #4a90e2;
            margin-bottom: 20px;
            background: #e8f4ff;
            padding: 20px;
            border-radius: 50%;
        }

        footer {
            background: #2d4059;
            padding: 50px 20px;
            text-align: center;
            color: white;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 40px auto;
        }

        .contact-item {
            padding: 20px;
        }

        .contact-item i {
            font-size: 2rem;
            color: #4a90e2;
            margin-bottom: 15px;
            background: #e8f4ff;
            padding: 15px;
            border-radius: 50%;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                padding: 1rem;
            }
            
            .logo {
                margin-bottom: 15px;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .nav-button {
                margin: 5px;
                padding: 10px 20px;
            }
            
            .section {
                margin: 20px 2%;
                padding: 40px 15px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 120px 15px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .section h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <img src="logo.png" alt="PPSU Logo" class="logo">
        <nav>
    <a href="login.php" class="nav-button">
        <i class="fas fa-sign-in-alt"></i> Login
    </a>
    <a href="signup.php" class="nav-button">
        <i class="fas fa-user-plus"></i> Sign Up
    </a>
</nav>

    </header>

    <div class="hero">
        <h1>Welcome to P P Savani University Library</h1>
        <p>Your Gateway to Knowledge and Innovation</p>
        <button class="nav-button" style="margin-top: 40px; padding: 15px 40px; animation: slideUp 1s ease-out 0.4s backwards;">
            <i class="fas fa-door-open"></i> Explore Now
        </button>
    </div>

    <div class="section">
        <h2><i class="fas fa-gavel"></i> Library Rules & Regulations</h2>
        <div class="rules-container">
            <div class="rule-card">
                <h3><i class="fas fa-volume-mute"></i> General Rules</h3>
                <p style="color: #6c757d;">• Maintain quiet study environment<br>
                • No food or drinks in library areas<br>
                • Valid ID required for access</p>
            </div>
            <div class="rule-card">
                <h3><i class="fas fa-book"></i> Borrowing Policy</h3>
                <p style="color: #6c757d;">• Students: 5 books (21 days)<br>
                • Faculty: 15 books (45 days)<br>
                • Renewals available online</p>
            </div>
            <div class="rule-card">
                <h3><i class="fas fa-laptop"></i> Digital Resources</h3>
                <p style="color: #6c757d;">• 24/7 E-library access<br>
                • Online research databases<br>
                • Virtual study rooms</p>
            </div>
        </div>
    </div>

    <div class="section">
        <h2><i class="fas fa-cogs"></i> How It Works</h2>
        <div class="how-it-works">
            <div class="step">
                <i class="fas fa-search"></i>
                <h3>1. Search & Discover</h3>
                <p style="color: #6c757d;">Use our smart search system to find resources across all collections</p>
            </div>
            <div class="step">
                <i class="fas fa-mobile-alt"></i>
                <h3>2. Reserve & Manage</h3>
                <p style="color: #6c757d;">Track loans and reservations through our mobile app</p>
            </div>
            <div class="step">
                <i class="fas fa-robot"></i>
                <h3>3. Smart Returns</h3>
                <p style="color: #6c757d;">24/7 automated return stations with instant updates</p>
            </div>
        </div>
    </div>

    <footer>
        <h2><i class="fas fa-address-card"></i> Contact Us</h2>
        <div class="contact-info">
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <h4>Email</h4>
                <p>library@ppsu.edu.in</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h4>Phone</h4>
                <p>+91 265 1234567</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <h4>Location</h4>
                <p>Main Campus Building, Block C<br>
                3rd Floor, Room 310</p>
            </div>
        </div>
        <p style="margin-top: 40px; opacity: 0.8;">© 2025 PPSAVANI University Library | Empowering Minds, Enriching Futures</p>
    </footer>
</body>
</html>