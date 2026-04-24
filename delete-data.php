<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppression des donnees - DocTime</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }
        .container {
            max-width: 860px;
            margin: 48px auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        h1, h2 {
            color: #0f172a;
        }
        p, li {
            line-height: 1.7;
        }
        code {
            background: #eef2ff;
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <main class="container">
        <h1>Suppression des donnees utilisateur</h1>
        <p>Un utilisateur peut demander la suppression de ses donnees associees a DocTime en envoyant une demande a l'administrateur de la plateforme.</p>

        <h2>Procedure</h2>
        <ol>
            <li>Envoyer une demande de suppression depuis l'adresse email liee au compte.</li>
            <li>Indiquer l'identifiant du compte ou l'adresse email concernee.</li>
            <li>Attendre la verification et le traitement de la demande par l'equipe DocTime.</li>
        </ol>

        <h2>Traitement</h2>
        <p>Apres verification, les donnees seront supprimees ou anonymisees dans un delai raisonnable, sauf obligation legale de conservation.</p>

        <h2>Reference technique</h2>
        <p>Cette page peut etre utilisee comme URL de suppression des donnees utilisateur dans la configuration Meta de l'application.</p>
    </main>
</body>
</html>
