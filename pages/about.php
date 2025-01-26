<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .section-title {
            text-align: center;
            margin-top: 30px;
            font-size: 2rem;
        }

        .about-description {
            text-align: justify;
            margin: 20px 0;
        }

        .feature-list {
            list-style: none;
            padding-left: 0;
        }

        .feature-list li {
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .team-member {
            text-align: center;
            margin-top: 30px;
        }

        .team-member img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
        }

        .team-member h4 {
            margin-top: 10px;
        }

        .team-member p {
            color: #888;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <!-- Introduction Section -->
        <section>
            <h2 class="section-title">About BuddyGo</h2>
            <p class="about-description">
                Welcome to BuddyGo! We are a platform designed to help you find friends and partners to join you for fun outdoor activities. Whether you want to play board games, explore new places, or just enjoy a day outdoors, BuddyGo connects you with like-minded individuals in your area.
            </p>
            <p class="about-description">
                Our mission is to make it easy for people to socialize, explore new activities, and create memorable experiences with others who share similar interests. Whether you're looking for someone to play a game with or to go on an adventure, BuddyGo is here to help you find the perfect companion.
            </p>
        </section>

        <!-- Features Section -->
        <section>
            <h3 class="section-title">Features</h3>
            <ul class="feature-list">
                <li>Find friends to participate in outdoor activities</li>
                <li>Post and join activities in your local area</li>
                <li>Connect with people through messages and events</li>
                <li>Create your profile and customize it</li>
                <li>Stay updated with new activities and events</li>
            </ul>
        </section>

        <!-- Team Section -->
        <section class="team-member">
            <h3 class="section-title">Meet the Team</h3>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <img src="https://via.placeholder.com/150" alt="Team Member" class="img-fluid">
                    <h4>John Doe</h4>
                    <p>Founder & CEO</p>
                </div>
                <div class="col-md-4">
                    <img src="https://via.placeholder.com/150" alt="Team Member" class="img-fluid">
                    <h4>Jane Smith</h4>
                    <p>Co-Founder & CTO</p>
                </div>
            </div>
        </section>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
