<?php

function displayStars($rating)
{
    $fullStar = "&#9733;"; // étoile pleine (★)
    $emptyStar = "&#9734;"; // étoile vide (☆)
    $stars = "";
    // On arrondit la note pour afficher un nombre entier d'étoiles pleines
    $rounded = round($rating);
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rounded) ? "<span style='color: gold; font-size: 1.2em;'>{$fullStar}</span>" : "<span style='color: gold; font-size: 1.2em;'>{$emptyStar}</span>";
    }
    return $stars;
}


// -------------------
// 1. Récupération AVIS GOOGLE
// -------------------
$apiKey = "AIzaSyCvDssxo9FFiMz38ceA54w8RR7GkwkDNOM"; // Ta clé API
$placeId = "ChIJsV5NGDvq9EcRAXFV7jUvoEo";
$fields = "name,rating,reviews,url";

// Construit l’URL pour appeler l’API Google
$url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$placeId&fields=$fields&key=$apiKey&language=fr";

// Initialisation de cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Pour le développement, si nécessaire (à retirer en production)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);

// Vérifier les erreurs cURL
if (curl_errno($ch)) {
    echo "Erreur cURL : " . curl_error($ch);
}

$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpStatus !== 200) {
    echo "Erreur HTTP : $httpStatus";
}

$reviewsData = json_decode($response, true);

// Traitement du formulaire de contact via Mailjet
$contactResult = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!empty($_POST['honeypot'])) {
        die("Spam détecté !");
    }

    $time_taken = time() - $_POST['form_start_time'];
    if ($time_taken < 5) { // Moins de 10 secondes
        die('
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Spam détecté</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f8d7da;
                    color: #721c24;
                    text-align: center;
                    padding: 50px;
                }
                .container {
                    max-width: 500px;
                    margin: auto;
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                }
                h1 {
                    font-size: 24px;
                }
                p {
                    font-size: 18px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Spam détecté !</h1>
                <p>Vous avez envoyé le formulaire trop rapidement.</p>
                <p>Il est peu probable que vous soyez un humain</p>
                <p>Veuillez attendre quelques secondes et réessayer.</p>
                <a href="javascript:history.back();" style="color: #721c24; font-weight: bold;">Retour</a>
            </div>
        </body>
        </html>
        ');
    }

    function sendMailjetEmail($name, $email, $message)
    {
        // Paramètres Mailjet (remplacez par vos identifiants réels)
        $api_key = "f5cce6f3c1cd07ff3c7045e0007a663e";
        $api_secret = "b50ae69f9d0e26ff38b53ee6d37fcfde";
        $to_email = "reperret@hotmail.com";
        $template_id = 6751001; // Utilise l'ID approprié

        // Variables dynamiques pour le template Mailjet, avec libelleBouton par défaut
        $variables = [
            "titre"         => "Nouvelle demande",
            "mail"          => $email,
            "nom"           => $name,
            "message"       => $message,
            "libelleBouton" => "Répondre", // Valeur par défaut,
            "lienBouton" => "mailto:$email"
        ];

        $data = [
            "Messages" => [
                [
                    "From" => [
                        "Email" => "noreply@remyperret.org",
                        "Name"  => "Contact perret.xyz"
                    ],
                    "To" => [
                        [
                            "Email" => $to_email,
                            "Name"  => "Rémy Perret"
                        ]
                    ],
                    "TemplateID"       => $template_id,
                    "TemplateLanguage" => true,
                    "Variables"        => $variables
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mailjet.com/v3.1/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_USERPWD, "$api_key:$api_secret");

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_status == 200);
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($_POST['message']);
    $name = htmlspecialchars($_POST['name']); // Assure-toi que le champ "name" est présent

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($message) && !empty($name)) {
        if (sendMailjetEmail($name, $email, $message)) {
            $contactResult = "success";
        } else {
            $contactResult = "danger";
        }
    } else {
        $contactResult = "invalid";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Développeur Web à Lyon | Rémy Perret - Création de sites & apps</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Développeur web freelance à Lyon, je crée des sites, applications et logiciels sur-mesure. Besoin d'un projet digital ? Contactez-moi !">
    <meta name="keywords"
        content="développeur web Lyon, création site internet, application web, freelance, site vitrine, e-commerce, développement sur-mesure, référencement SEO">
    <meta name="author" content="Rémy Perret">
    <meta name="robots" content="index, follow">

    <!-- Open Graph (Facebook, LinkedIn, etc.) -->
    <meta property="og:title" content="Développeur Web à Lyon | Rémy Perret - Sites & Applications">
    <meta property="og:description"
        content="Développeur freelance à Lyon, je réalise des sites internet, applications et logiciels personnalisés. Découvrez mes services et mon portfolio.">
    <meta property="og:image" content="https://www.perret.xyz/preview.jpg">
    <meta property="og:url" content="https://www.perret.xyz">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Développeur Web à Lyon | Rémy Perret">
    <meta name="twitter:description"
        content="Je crée des sites internet, applications et logiciels personnalisés à Lyon. Découvrez mon portfolio et mes services !">
    <meta name="twitter:image" content="https://www.perret.xyz/preview.jpg">
    <meta name="twitter:site" content="@tonTwitterHandle">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <!-- Google Fonts: Poppins, Inconsolata & Poiret One -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Inconsolata:wght@400;700&family=Poiret+One&display=swap"
        rel="stylesheet" />
    <!-- AOS CSS pour les animations -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <!-- Fancybox CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />


    <style>
    :root {
        /* Couleurs principales */
        --black: #000;
        --white: #fff;
        --dark-gray: #111;
        --text-dark: #000;
        --text-light: #fff;
        --accent-color: #888;
        /* gris subtil pour accents */

        /* Taille fixe pour la navbar */
        --navbar-height: 120px;
    }

    /* RESET DE BASE */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--black);
        color: var(--text-light);
        scroll-behavior: smooth;
    }

    a {
        color: var(--text-light);
        text-decoration: none;
        transition: color 0.3s;
    }


    /* Ajustement des marges internes du carrousel pour éviter que le contenu ne chevauche les contrôles */
    #reviewsCarousel .carousel-inner {
        padding: 0 50px;
        /* espace horizontal autour des items */
    }

    /* Optionnel : réduire la largeur des boutons de contrôle pour qu'ils ne couvrent pas trop le contenu */
    .carousel-control-prev,
    .carousel-control-next {
        width: 5%;
    }

    /* Si nécessaire, ajouter un fond transparent ou une ombre portée pour les flèches */
    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        background-color: rgba(0, 0, 0, 0.5);
        padding: 10px;
        border-radius: 50%;
    }


    /* Style du carrousel avis Google */
    #google-reviews .card {
        border: none;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    }

    #google-reviews .card-body {
        padding: 20px;
    }


    .portfolio-description {
        max-width: 800px;
        margin: 0 auto 30px auto;
        /* Ajoute 30px de marge en bas */
        text-align: justify;
        font-size: 1rem;
        line-height: 1.5;

    }


    a:hover {
        color: #ccc;
    }

    /* NAVBAR */
    .navbar {
        background: linear-gradient(90deg, #000, #333);
        border: none;
        min-height: var(--navbar-height);
        position: relative;
        z-index: 10;
    }

    /* Logo SVG personnalisé */
    .navbar-brand {
        margin-left: 1rem;
    }

    .navbar-brand object {
        height: calc(var(--navbar-height) - 40px);
        width: auto;
        display: block;
    }

    @media (max-width: 576px) {
        .navbar-brand object {
            max-width: 200px;
            /* Ajustez cette valeur selon vos besoins */
            height: auto;
        }
    }

    .navbar-toggler {
        border: none;
        background: none;
        outline: none !important;
        box-shadow: none !important;
    }

    .navbar-toggler:focus,
    .navbar-toggler:active {
        outline: 0 !important;
        box-shadow: none !important;
    }

    .navbar-toggler-icon {
        display: none;
    }

    .nav-icon {
        width: 30px;
        height: 22px;
        position: relative;
    }

    .nav-icon span {
        background: var(--white);
        display: block;
        height: 3px;
        width: 100%;
        border-radius: 3px;
        position: absolute;
        left: 0;
        transition: 0.3s;
    }

    .nav-icon span:nth-child(1) {
        top: 0;
    }

    .nav-icon span:nth-child(2) {
        top: 9px;
    }

    .nav-icon span:nth-child(3) {
        top: 18px;
    }

    .nav-icon.is-open span:nth-child(1) {
        transform: rotate(45deg);
        top: 9px;
    }

    .nav-icon.is-open span:nth-child(2) {
        opacity: 0;
    }

    .nav-icon.is-open span:nth-child(3) {
        transform: rotate(-45deg);
        top: 9px;
    }

    .nav-link {
        margin: 0 10px;
        color: var(--white) !important;
    }

    .nav-link:hover {
        color: #ccc !important;
    }



    /* HERO SECTION */
    /* HERO SECTION - Dégradé très subtil */
    #hero {
        position: relative;
        margin-top: calc(var(--navbar-height) - 150px);
        padding: 80px 20px 40px;
        text-align: center;
        background:
            linear-gradient(180deg,
                #000 0%,
                /* Noir en haut */
                #111 40%,
                /* Très légèrement plus clair */
                #111 60%,
                /* Centre légèrement plus clair */
                #000 100%
                /* Noir en bas */
            );
    }



    #hero h1 {
        font-size: 3rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: var(--white);
    }

    #hero p {
        font-size: 1.25rem;
        margin-bottom: 20px;
        color: var(--white);
    }


    .hero-avatar img {
        border-radius: 50%;
        width: 80px;
        height: 80px;
        border: 2px solid var(--white);
    }


    .hero-icons {
        margin-top: 10px;
        /* Ajuste cet espace si nécessaire */
    }

    .hero-icons a {
        margin: 0 5px;
    }

    .hero-icons img {
        width: 22px;
        height: 22px;
        filter: grayscale(100%);
        transition: filter 0.3s ease;
    }

    .hero-icons img:hover {
        filter: grayscale(0);
    }



    #portfolio {
        padding-top: 0px;
        /* Au lieu de 80px */
    }


    .hero-avatar {
        display: inline-block;
        margin-top: 20px;
        /* Par exemple, réduis à 10px si nécessaire */
        margin-bottom: 10px;
        /* Réduit l'espace sous l'avatar */
    }

    .portfolio-separator {
        width: 50%;
        margin: 30px auto;
        /* Réduit l'espacement global, notamment la marge supérieure */
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        text-align: center;
    }


    /* SECTIONS */
    section {
        padding: 80px 20px;
    }

    /* TITRES DE SECTION */
    .section-title {
        text-transform: uppercase;
        font-weight: 600;
        position: relative;
        margin-bottom: 1rem;
    }

    .section-title::before {
        content: attr(data-text);
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        color: rgba(255, 255, 255, 0.1);
        z-index: -1;
        filter: blur(1px);
    }

    /* PORTFOLIO (Fancybox) */
    #portfolio {
        background-color: var(--black);
        color: var(--white);
    }

    #portfolio .portfolio-item {
        overflow: hidden;
        border-radius: 8px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    #portfolio .portfolio-item img {
        width: 100%;
        display: block;
        border-radius: 8px;
    }

    #portfolio .portfolio-item:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(255, 255, 255, 0.2);
    }

    /* SERVICES (FOND BLANC) */
    #services,
    #google-reviews {
        background-color: var(--white);
        color: var(--text-dark);
    }

    #services .card {
        background-color: #f7f7f7;
        border: none;
        border-radius: 8px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    #services .card:hover {
        transform: translateY(-4px);
        box-shadow: 2px 2px 1px rgba(0, 0, 0, 0.1);
    }

    #services .card-body {
        text-align: center;
    }

    /* Titres de services en majuscules et gras, même police que les titres de section */
    .service-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 900;
        font-size: 1.2rem;
        margin-bottom: 10px;
        text-transform: uppercase;
        color: #888;
    }

    .emoji {
        font-size: 1.4rem;
        margin-right: 6px;
    }






    /* CONTACT (FOND NOIR) */
    #contact {
        background-color: var(--black);
        color: var(--white);
    }

    #contact .form-control {
        background-color: var(--dark-gray);
        border: 1px solid #333;
        color: var(--white);
        border-radius: 4px;
        padding: 12px 16px;
    }

    #contact .form-control:focus {
        border-color: var(--accent-color);
        box-shadow: none;
    }

    #contact .btn {
        border-radius: 4px;
        padding: 10px 30px;
        background-color: var(--white);
        border: none;
        transition: background-color 0.3s ease;
        color: var(--black);
        font-weight: 600;
    }

    #contact .btn:hover {
        background-color: #ccc;
    }

    #contact ::placeholder {
        color: #999;
        opacity: 0.8;
    }

    footer {
        background-color: var(--black);
        color: var(--white);
        text-align: center;
        padding: 20px;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    footer p {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin: 0;
    }

    /* Pour l'icône LinkedIn et l'icône CV */
    .linkedin-icon,
    .cv-icon {
        width: 22px;
        /* Taille petite */
        height: 22px;
        filter: grayscale(100%);
        transition: filter 0.3s ease;
    }

    .linkedin-icon:hover,
    .cv-icon:hover {
        filter: grayscale(0);
    }



    .linkedin-icon {
        width: 22px;
        /* Taille ajustée */
        height: 22px;
        filter: grayscale(100%);
        /* Icône noir et blanc */
        transition: filter 0.3s ease;
    }

    .linkedin-icon:hover {
        filter: grayscale(0);
        /* Recoloré au survol */
    }



    /* Toast Notification (centré en haut) */
    .toast-container {
        position: fixed;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1050;
        padding: 1rem;
    }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <!-- Logo SVG personnalisé -->
                <object type="image/svg+xml" data="logo.svg"
                    style="height: calc(var(--navbar-height) - 40px); width: auto;">
                    Rémy PERRET
                </object>
            </a>
            <button class="navbar-toggler" type="button" aria-label="Toggle navigation" id="navToggle"
                data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <div class="nav-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarContent">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="cv.pdf" target="_blank">CV</a></li>
                    <li class="nav-item"><a class="nav-link" href="#portfolio">Portfolio</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="#google-reviews">Avis</a></li>

                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="hero" data-aos="fade-up">
        <div class="container">
            <h1>Développeur à Lyon</h1>
            <h2>Sites web, apps, logiciels</h2>
            <div class="hero-avatar">
                <img src="avatar.jpg" alt="Avatar" />
            </div>
            <div class="hero-icons">
                <a href="https://www.linkedin.com/in/remyperret/" target="_blank">
                    <img src="https://cdn-icons-png.flaticon.com/512/174/174857.png" alt="LinkedIn">
                </a>

            </div>


        </div>
    </section>

    <!-- PORTFOLIO (Fancybox) -->
    <section id="portfolio" data-aos="fade-up">
        <div class="container">
            <div class="portfolio-separator"></div>

            <h2 class="section-title text-center mb-5" data-text="PORTFOLIO">PORTFOLIO</h2>
            <p class="portfolio-description text-center">Découvrez quelques-uns de mes derniers projets réalisés.</p>

            <div class="row g-4">
                <!-- Projet 1 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="portfolio-item">
                        <a data-fancybox="portfolio"
                            data-caption="Projet 1 - <a href='https://exemple1.com' target='_blank'>Visiter le site</a>"
                            href="1.png">
                            <img src="1.png" alt="Projet 1" />
                        </a>
                    </div>
                </div>
                <!-- Projet 2 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="portfolio-item">
                        <a data-fancybox="portfolio"
                            data-caption="Projet 2 - <a href='https://exemple2.com' target='_blank'>Visiter le site</a>"
                            href="2.png">
                            <img src="2.png" alt="Projet 2" />
                        </a>
                    </div>
                </div>
                <!-- Projet 3 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="portfolio-item">
                        <a data-fancybox="portfolio"
                            data-caption="Projet 3 - <a href='https://exemple3.com' target='_blank'>Visiter le site</a>"
                            href="3.png">
                            <img src="3.png" alt="Projet 3" />
                        </a>
                    </div>
                </div>
                <!-- Projet 4 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="portfolio-item">
                        <a data-fancybox="portfolio"
                            data-caption="Projet 4 - <a href='https://exemple4.com' target='_blank'>Visiter le site</a>"
                            href="4.png">
                            <img src="4.png" alt="Projet 4" />
                        </a>
                    </div>
                </div>
                <!-- Projet 5 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="portfolio-item">
                        <a data-fancybox="portfolio"
                            data-caption="Projet 5 - <a href='https://exemple5.com' target='_blank'>Visiter le site</a>"
                            href="5.png">
                            <img src="5.png" alt="Projet 5" />
                        </a>
                    </div>
                </div>
                <!-- Projet 6 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="portfolio-item">
                        <a data-fancybox="portfolio"
                            data-caption="Projet 6 - <a href='https://exemple6.com' target='_blank'>Visiter le site</a>"
                            href="6.png">
                            <img src="6.png" alt="Projet 6" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES (FOND BLANC) -->
    <section id="services" data-aos="fade-up">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-text="SERVICES">SERVICES</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="service-title"><span class="emoji">💼</span>SITES VITRINES</h5>
                            <p>Création de sites vitrines modernes et responsives pour présenter votre activité.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="service-title"><span class="emoji">⚙️</span>SITES DYNAMIQUES</h5>
                            <p>Développement de sites dynamiques avec gestion de contenu et interactivité.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="service-title"><span class="emoji">🛍️</span>SITES E-COMMERCE</h5>
                            <p>Création de boutiques en ligne sécurisées et performantes pour vendre vos produits.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="service-title"><span class="emoji">🤝</span>SITES POUR ASSOCIATIONS</h5>
                            <p>Sites sur-mesure pour associations avec des fonctionnalités adaptées.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="service-title"><span class="emoji">📄</span>GESTION CLIENT & PDF</h5>
                            <p>Solutions pour gestion de clients, génération de PDF et automatisation.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="service-title"><span class="emoji">💳</span>PAIEMENT & DASHBOARDS</h5>
                            <p>Intégration de paiements en ligne et création de tableaux de bord personnalisés.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT (FOND NOIR) -->
    <section id="contact" data-aos="fade-up">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-text="CONTACTEZ-MOI">CONTACTEZ-MOI</h2>
            <p class="portfolio-description text-center">Pour une question, un besoin, un devis.</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="" method="post">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Votre nom"
                                required />
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Votre email"
                                required />
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" id="message" name="message" rows="5"
                                placeholder="Votre message" required></textarea>
                        </div>

                        <input type="text" name="honeypot" id="honeypot" style="display:none;">
                        <input type="hidden" name="form_start_time" value="<?= time(); ?>">


                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION AVIS GOOGLE -->
    <section id="google-reviews" data-aos="fade-up">
        <div class="container">
            <h2 class="section-title text-center mb-5" data-text="AVIS GOOGLE">AVIS GOOGLE</h2>

            <!-- Carrousel Bootstrap -->
            <div id="reviewsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="10000">
                <div class="carousel-inner">
                    <?php
                    $googleUrl = NULL;
                    if ($reviewsData && isset($reviewsData['result']['reviews'])) {
                        $reviews = $reviewsData['result']['reviews'];
                        if (count($reviews) > 0) {
                            foreach ($reviews as $index => $review) {
                                $activeClass = ($index === 0) ? ' active' : '';
                                $author = isset($review['author_name']) ? htmlspecialchars($review['author_name']) : 'Inconnu';
                                $relative_time_description = isset($review['relative_time_description']) ? htmlspecialchars($review['relative_time_description']) : '';
                                $rating = isset($review['rating']) ? htmlspecialchars($review['rating']) : 'N/A';
                                $text = isset($review['text']) ? htmlspecialchars($review['text']) : '';
                                $googleUrl = isset($reviewsData['result']['url']) ? $reviewsData['result']['url'] : "#";


                                echo "
                    <div class='carousel-item{$activeClass}'>
                      <div class='card bg-dark text-light'>
                        <div class='card-body'>
                          <p class='card-text'>\"{$text}\"</p>
                          <p class='card-text'><small>– {$author} a noté  " . displayStars($rating) . "  </small></p>
                          <p class='card-text'><small>Avis {$relative_time_description}</small></p>
                          
                        </div>
                      </div>
                    </div>";
                            }
                        } else {
                            echo "
                <div class='carousel-item active'>
                  <div class='card bg-dark text-light'>
                    <div class='card-body'>
                      <p class='card-text'>Aucun avis trouvé.</p>
                    </div>
                  </div>
                </div>";
                        }
                    } else {
                        echo "
            <div class='carousel-item active'>
              <div class='card bg-dark text-light'>
                <div class='card-body'>
                  <p class='card-text'>Impossible de charger les avis.</p>
                </div>
              </div>
            </div>";
                    }
                    ?>
                </div>

                <!-- Contrôles du carrousel -->
                <button class="carousel-control-prev" type="button" data-bs-target="#reviewsCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Précédent</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#reviewsCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Suivant</span>
                </button>
            </div>

            <!-- Lien vers les avis Google -->
            <div class="text-center mt-4">
                <a href="<?= $googleUrl ?>" target="_blank" class="btn btn-outline-dark">
                    Consulter les avis Google
                </a>
            </div>
        </div>
    </section>





    <!-- FOOTER (FOND BLANC) -->
    <footer>
        <p>
            Rejoignez-moi sur
            <a href="https://www.linkedin.com/in/reperret/" target="_blank" class="linkedin-link">
                <img src="https://cdn-icons-png.flaticon.com/512/174/174857.png" alt="LinkedIn" class="linkedin-icon">
            </a>
            - Mon CV :
            <a href="cv.pdf" target="_blank">
                <img src="https://cdn-icons-png.flaticon.com/512/337/337946.png" alt="CV PDF" class="cv-icon">
            </a>
        </p>
    </footer>


    <!-- Toast Notification (centré en haut) -->
    <div class="toast-container">
        <div id="toast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toast-message">
                    Message de notification
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <!-- Fancybox JS -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
    <script>
    AOS.init({
        duration: 1000,
        easing: 'ease-out',
        once: true,
    });

    // MENU HAMBURGER : animer l'icône
    const navToggle = document.getElementById('navToggle');
    navToggle.addEventListener('click', function() {
        const navIcon = this.querySelector('.nav-icon');
        navIcon.classList.toggle('is-open');
    });

    // Initialiser Fancybox avec fonction de rappel pour la légende
    document.addEventListener("DOMContentLoaded", () => {
        Fancybox.bind("[data-fancybox]", {
            caption: function(instance, item) {
                return item.$trigger ? item.$trigger.getAttribute("data-caption") : "";
            }
        });
    });

    // Fonction pour afficher un toast de notification
    function showToast(message, type) {
        const toastEl = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');

        if (type === 'success') {
            toastEl.classList.add('bg-success');
            toastEl.classList.remove('bg-danger');
        } else {
            toastEl.classList.add('bg-danger');
            toastEl.classList.remove('bg-success');
        }
        toastMessage.innerText = message;

        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
    </script>

    <?php
    // Affichage du toast en fonction du résultat du traitement
    if (!empty($contactResult)) {
        if ($contactResult == "success") {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('Message envoyé ! Je vous réponds rapidement', 'success');
                });
              </script>";
        } else if ($contactResult == "danger") {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('Erreur : Impossible d\'envoyer votre message.', 'danger');
                });
              </script>";
        } else if ($contactResult == "invalid") {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('Erreur : Veuillez entrer un email valide et un message.', 'danger');
                });
              </script>";
        }
    }
    ?>

    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-XXXXX-Y"></script>
    <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'UA-XXXXX-Y');
    </script>


</body>

</html>