<?php

/***************************************
 * cartevisite.php
 ***************************************/

/**
 * vCard pour le QR Code "secret" (dans la version précédente, on générait
 * un QR Code JS. Ici, on affiche simplement une image qrcode.png.
 * On conserve toutefois la variable si vous en avez besoin plus tard.
 */
$staticVcard  = "BEGIN:VCARD\r\n";
$staticVcard .= "VERSION:3.0\r\n";
$staticVcard .= "FN:Rémy PERRET\r\n";
$staticVcard .= "N:PERRET;Rémy;;;\r\n";
$staticVcard .= "TEL;TYPE=mobile,voice:+33631023304\r\n";
$staticVcard .= "EMAIL:reperret@gmail.com\r\n";
$staticVcard .= "PHOTO;TYPE=JPEG;VALUE=URL:https://www.remyperret.fr/avatar.jpg\r\n";
$staticVcard .= "END:VCARD\r\n";

// =======================
// Traitement du formulaire "M'envoyer vos coordonnées"
// =======================
$coordResult = "";
$vcardString = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['coord_form'])) {
    // Protection anti-spam : champ "honeypot" + temps minimal
    if (!empty($_POST['honeypot'])) {
        die("Spam détecté !");
    }
    $time_taken = time() - (int)$_POST['form_start_time'];
    if ($time_taken < 5) {
        die("Spam détecté !");
    }

    // Nettoyage des champs
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $nom    = htmlspecialchars(trim($_POST['nom']));
    $mobile = htmlspecialchars(trim($_POST['mobile']));
    $email  = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Validation
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($prenom) && !empty($nom) && !empty($mobile)) {

        // Fonction d'envoi par Mailjet
        function sendMailjetEmail($name, $email, $message)
        {
            $api_key     = "f5cce6f3c1cd07ff3c7045e0007a663e";
            $api_secret  = "b50ae69f9d0e26ff38b53ee6d37fcfde";
            $to_email    = "reperret@hotmail.com";
            $template_id = 6751001;

            $variables = [
                "titre"         => "Nouvelle demande de coordonnées",
                "mail"          => $email,
                "nom"           => $name,
                "message"       => $message,
                "libelleBouton" => "Répondre",
                "lienBouton"    => "mailto:$email"
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

            // Envoi via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.mailjet.com/v3.1/send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_USERPWD, "$api_key:$api_secret");
            $response    = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($http_status == 200);
        }

        // Envoi du mail
        $message = "Coordonnées reçues:\nPrénom: $prenom\nNom: $nom\nMobile: $mobile\nEmail: $email";
        if (sendMailjetEmail("$prenom $nom", $email, $message)) {
            $coordResult = "success";
            // Génération d'un QR Code basique contenant la vCard de l'expéditeur
            $vcardString  = "BEGIN:VCARD\r\n";
            $vcardString .= "VERSION:3.0\r\n";
            $vcardString .= "N:$nom;$prenom\r\n";
            $vcardString .= "FN:$prenom $nom\r\n";
            $vcardString .= "TEL;TYPE=CELL:$mobile\r\n";
            $vcardString .= "EMAIL:$email\r\n";
            $vcardString .= "END:VCARD\r\n";
        } else {
            $coordResult = "danger";
        }
    } else {
        $coordResult = "invalid";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact - Rémy PERRET</title>

    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- QRCode.js (nécessaire pour le QR dynamique après envoi du formulaire) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>

    <style>
    /* Remise à zéro globale */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        font-family: "Poppins", sans-serif;
        background-color: #000;
        color: #fff;
        text-align: center;
    }

    /* Logo avec petite marge en haut */
    .logo {
        margin: 25px 0 0 0;
        /* modifiez la valeur si besoin plus ou moins de marge */
        padding: 0;
    }

    .logo object {
        width: 180px;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    /* Avatar cliquable */
    .avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 2px solid #fff;
        margin: 10px auto;
        overflow: hidden;
        cursor: pointer;
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Boutons */
    .btn-container {
        width: 100%;
        max-width: 350px;
        margin: 10px auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .btn-custom {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 12px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        color: #fff;
        background: #000;
        border: 2px solid #fff;
        border-radius: 8px;
        transition: all 0.3s ease-in-out;
        cursor: pointer;
    }

    .btn-custom:hover {
        background: #fff;
        color: #000;
    }

    .btn-white {
        background: #fff;
        color: #000;
        border: 2px solid #000;
    }

    .btn-sep {
        margin-top: 8px;
    }

    /* Zone secrète (invisible) en bas */
    .secret-zone {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 50px;
        opacity: 0;
        cursor: pointer;
        z-index: 1000;
    }

    /* Modals */
    .modal-dialog {
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (min-width: 768px) {
        .modal-dialog {
            max-width: 30%;
        }
    }

    @media (max-width: 767px) {
        .modal-dialog {
            max-width: 100%;
            height: 100vh;
        }
    }

    .modal-content {
        width: 100%;
        height: auto;
        background-color: #fff !important;
        color: #000;
        border: none;
        border-radius: 0;
    }

    .modal-header .btn-close {
        filter: none;
    }

    .form-control {
        background-color: #fff;
        color: #000;
        border: 1px solid #000;
        border-radius: 4px;
    }

    .modal-body .btn-primary {
        background-color: #000;
        color: #fff;
        border: 2px solid #fff;
    }
    </style>
</head>

<body>
    <!-- LOGO -->
    <div class="logo">
        <object type="image/svg+xml" data="logo.svg">Rémy PERRET</object>
    </div>

    <!-- Avatar (modal photo) -->
    <div class="avatar" data-bs-toggle="modal" data-bs-target="#avatarModal">
        <img src="avatar.jpg" alt="Avatar" />
    </div>

    <!-- Boutons de navigation -->
    <div class="btn-container">
        <a class="btn-custom btn-white" href="download-vcard.php">
            <i class="fas fa-cloud-arrow-down"></i> Ajouter mes coordonnées
        </a>
        <button class="btn-custom btn-white" data-bs-toggle="modal" data-bs-target="#coordModal">
            <i class="fas fa-paper-plane"></i> M'envoyer vos coordonnées
        </button>
        <div class="btn-sep"></div>
        <a class="btn-custom" href="https://www.remyperret.fr" target="_blank">
            <i class="fas fa-home"></i> Mon site
        </a>
        <a class="btn-custom" href="https://www.remyperret.fr/#contact" target="_blank">
            <i class="fas fa-envelope"></i> Contact par mail
        </a>
        <a class="btn-custom" href="https://wa.me/33631023304" target="_blank">
            <i class="fab fa-whatsapp"></i> Contact par Whatsapp
        </a>
        <a class="btn-custom" href="https://www.linkedin.com/in/remyperret/" target="_blank">
            <i class="fab fa-linkedin"></i> Linkedin
        </a>
        <a class="btn-custom" href="tel:+33631023304">
            <i class="fas fa-phone"></i> Appeler
        </a>
    </div>

    <!-- Zone secrète pour le QR Code complet (affiche simplement qrcode.png) -->
    <div class="secret-zone" data-bs-toggle="modal" data-bs-target="#qrModal"></div>

    <!-- Modal : formulaire "M'envoyer vos coordonnées" -->
    <div class="modal fade" id="coordModal" tabindex="-1" aria-labelledby="coordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="coordModalLabel">
                        Envoyez-moi vos coordonnées
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($_POST['coord_form'])): ?>
                    <?php if ($coordResult === "success"): ?>
                    <p class="text-success">Email envoyé avec succès !</p>
                    <div id="qrcode" style="margin:20px auto;"></div>
                    <p>Si je suis avec vous, faites moi scannez cela !</p>
                    <?php elseif ($coordResult === "danger"): ?>
                    <p class="text-danger">
                        Erreur : Impossible d'envoyer votre demande..
                    </p>
                    <?php goto form_display; ?>
                    <?php elseif ($coordResult === "invalid"): ?>
                    <p class="text-danger">
                        Erreur : Veuillez vérifier vos informations.
                    </p>
                    <?php goto form_display; ?>
                    <?php endif; ?>
                    <?php else: ?>
                    <?php form_display: ?>
                    <form method="post" action="">
                        <div class="mb-3 text-start">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required />
                        </div>
                        <div class="mb-3 text-start">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required />
                        </div>
                        <div class="mb-3 text-start">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" required />
                        </div>
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required />
                        </div>
                        <!-- Champs cachés anti-spam -->
                        <input type="hidden" name="coord_form" value="1" />
                        <input type="hidden" name="form_start_time" value="<?= time(); ?>" />
                        <input type="text" name="honeypot" style="display:none;" />
                        <button type="submit" class="btn btn-primary w-100">
                            Envoyer
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal : QR Code "secret" (affiche juste l'image qrcode.png) -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">
                        QR Code - Ajouter mes coordonnées
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- On centre l'image et la rend responsive -->
                <div class="modal-body text-center">
                    <img src="qrcard.png" alt="QR Code" class="img-fluid" style="display: block; margin: 0 auto;" />
                    <p>Scannez ce QR Code pour ajouter directement mes coordonnées.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal : photo en pleine résolution -->
    <div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="avatarModalLabel">Rémy PERRET</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img src="avatar.jpg" alt="Avatar" style="width: 100%; height: auto;" />
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Réouverture automatique du modal si un envoi de formulaire a eu lieu
    <?php if (isset($_POST['coord_form'])): ?>
    let coordModal = new bootstrap.Modal(document.getElementById('coordModal'));
    coordModal.show();
    <?php endif; ?>

    // Génération du QR Code dans la page "M'envoyer vos coordonnées" (si email envoyé avec succès)
    <?php if ($coordResult === "success" && !empty($vcardString)): ?>
    document.addEventListener("DOMContentLoaded", function() {
        new QRCode(document.getElementById("qrcode"), {
            text: <?php echo json_encode($vcardString); ?>,
            width: 300,
            height: 300,
            colorDark: "#000",
            colorLight: "#fff",
            correctLevel: QRCode.CorrectLevel.L
        });
    });
    <?php endif; ?>
    </script>
</body>

</html>