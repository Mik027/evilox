<?php
// Charger les données JSON
$medias = json_decode(file_get_contents('medias.json'), true);

// Filtrer par catégorie
$categorieFiltre = isset($_GET['categorie']) ? $_GET['categorie'] : '';
if ($categorieFiltre) {
    $medias = array_filter($medias, function($media) use ($categorieFiltre) {
        return $media['category'] === $categorieFiltre;
    });
}

// Trier les médias par date (décroissant) ou par titre (alphabétique)
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'date_desc';
if ($tri === 'date_desc') {
    usort($medias, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
} elseif ($tri === 'date_asc') {
    usort($medias, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
} elseif ($tri === 'alpha') {
    usort($medias, function($a, $b) {
        return strcmp($a['title'], $b['title']);
    });
}

// Nombre de médias par page
$mediasParPage = 20;
$totalMedias = count($medias);
$totalPages = ceil($totalMedias / $mediasParPage);

// Récupérer le numéro de la page depuis l'URL, par défaut 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $totalPages)); // S'assurer que la page est valide

// Calculer l'index de départ pour la pagination
$indexDepart = ($page - 1) * $mediasParPage;

// Extraire les médias pour la page actuelle
$mediasPage = array_slice($medias, $indexDepart, $mediasParPage);

// Appliquer la recherche si un terme est fourni
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
if ($searchTerm) {
    $mediasPage = array_filter($mediasPage, function($media) use ($searchTerm) {
        return stripos($media['title'], $searchTerm) !== false || stripos($media['description'], $searchTerm) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Germania+One&display=swap" rel="stylesheet">
    <title>Archives Evilox</title>
    <style>
        body {
            font-family: 'Germania One', Arial, sans-serif;
            background-color: #000;
            color: white;
            margin: 0;
            padding: 20px;
            max-width: 1024px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #fff;
        }
        .logo {
            display: block;
            margin: 0 auto;
            text-align: center;
            cursor: pointer;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .media-item {
            background: #222;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(255, 0, 0, 0.5);
            transition: transform 0.2s;
            cursor: pointer;
        }
        .media-item:hover {
            transform: scale(1.05);
        }
        .media-item img {
            max-width: 100%;
            border-radius: 5px;
        }
        .pagination {
            text-align: center;
            margin: 20px 0;
        }
        .pagination a {
            margin: 0 5px;
            padding: 8px 12px;
            background: red;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .pagination a:hover {
            background: darkred;
        }
        .filter-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .filter-container select, .filter-container input[type="text"], .filter-container button {
            padding: 10px;
            margin: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background: #333;
            color: white;
        }
        .filter-container button {
            background: red;
            color: white;
            cursor: pointer;
        }
        .filter-container button:hover {
            background: darkred;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            position: relative;
        }
        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }
        a {
            color: red;
        }
    </style>
    <script>
        function applyFilters() {
            const categorie = document.getElementById('categorie').value;
            const tri = document.getElementById('tri').value;
            const search = document.getElementById('search').value;
            window.location.href = `?categorie=${categorie}&tri=${tri}&search=${encodeURIComponent(search)}`;
        }

        function openModal(mediaSrc, isVideo) {
            const modal = document.getElementById('mediaModal');
            const modalContent = document.getElementById('modalContent');
            modal.style.display = 'flex';
            modal.onclick = closeModal;
            if (isVideo) {
                modalContent.innerHTML = `<video controls autoplay style="max-width: 90%; max-height: 90%;"> <source src="${mediaSrc}" type="video/mp4"> Votre navigateur ne supporte pas la vidéo. </video>`;
            } else {
                modalContent.innerHTML = `<img src="${mediaSrc}" style="max-width: 90%; max-height: 90%;">`;
            }
        }

        function closeModal() {
            const modal = document.getElementById('mediaModal');
            modal.style.display = 'none';
        }
    </script>
</head>
<body>
    <img src="/assets/evilox.png" alt="Logo Evilox" class="logo" onclick="window.location.href='/'">
    <h1>Archives Evilox</h1>
    <p style="text-align: center;">Étant donné que le site d'Evilox n'est plus navigable, j'ai créé ce site pour automatiser l'archivage des médias, vous permettant ainsi d'accéder aux archives de manière simple et efficace.</p>
    <div class="filter-container">
        <label for="categorie">Filtrer par catégorie:</label>
        <select name="categorie" id="categorie">
            <option value="">Tous</option>
            <option value="images" <?php if ($categorieFiltre === 'images') echo 'selected'; ?>>Images</option>
            <option value="videos" <?php if ($categorieFiltre === 'videos') echo 'selected'; ?>>Vidéos</option>
            <option value="autres" <?php if ($categorieFiltre === 'autres') echo 'selected'; ?>>Autres</option>
        </select>
        <label for="tri">Trier par:</label>
        <select name="tri" id="tri">
            <option value="date_desc" <?php if ($tri === 'date_desc') echo 'selected'; ?>>Date (Récente)</option>
            <option value="date_asc" <?php if ($tri === 'date_asc') echo 'selected'; ?>>Date (Ancienne)</option>
            <option value="alpha" <?php if ($tri === 'alpha') echo 'selected'; ?>>Alphabétique</option>
        </select>
        <label for="search">Recherche:</label>
        <input type="text" id="search" placeholder="Rechercher un média..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button onclick="applyFilters()">Rechercher</button>
    </div>
    <div class="grid">
        <?php foreach ($mediasPage as $media): ?>
            <div class="media-item" onclick="openModal('medias/<?php echo htmlspecialchars($media['media_name']); ?>', '<?php echo htmlspecialchars($media['media_name']); ?>'.endsWith('.mp4'))">
                <h2><?php echo htmlspecialchars($media['title']); ?></h2>
                <?php if (strpos($media['media_name'], '.mp4') !== false): ?>
                    <video controls style="max-width: 100%; border-radius: 5px;">
                        <source src="medias/<?php echo htmlspecialchars($media['media_name']); ?>" type="video/mp4">
                        Votre navigateur ne supporte pas la vidéo.
                    </video>
                <?php else: ?>
                    <img src="medias/<?php echo htmlspecialchars($media['media_name']); ?>" alt="<?php echo htmlspecialchars($media['title']); ?>">
                <?php endif; ?>
                <p><?php echo htmlspecialchars($media['description']); ?></p>
                <p>Date: <?php echo date('d m Y', strtotime($media['date'])); ?></p>
                <p>Catégorie: <a href="?categorie=<?php echo urlencode($media['category']); ?>"><?php echo ucfirst(htmlspecialchars($media['category'])); ?></a></p>
                <?php 
                    $categoriesAccents = [
                        'images' => 'Images',
                        'videos' => 'Vidéos',
                        'autres' => 'Autres'
                    ];
                    $media['category'] = $categoriesAccents[$media['category']] ?? ucfirst(htmlspecialchars($media['category']));
                ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&categorie=<?php echo htmlspecialchars($categorieFiltre); ?>&tri=<?php echo htmlspecialchars($tri); ?>&search=<?php echo urlencode($searchTerm); ?>">Précédent</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&categorie=<?php echo htmlspecialchars($categorieFiltre); ?>&tri=<?php echo htmlspecialchars($tri); ?>&search=<?php echo urlencode($searchTerm); ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&categorie=<?php echo htmlspecialchars($categorieFiltre); ?>&tri=<?php echo htmlspecialchars($tri); ?>&search=<?php echo urlencode($searchTerm); ?>">Suivant</a>
        <?php endif; ?>
    </div>

    <!-- Modal pour la visualisation -->
    <div id="mediaModal" class="modal">
        <div id="modalContent" class="modal-content"></div>
    </div>
    <p style="text-align: center; font-size: 12px; font-family: Arial, sans-serif;">Dans le cadre de mon projet de scraping de médias à partir du site evilox.com, j'ai effectué des recherches approfondies concernant les droits d'auteur et les conditions d'utilisation du contenu disponible. Bien que le site ne fournisse pas d'informations explicites sur les droits d'auteur, il est important de noter que les médias récupérés proviennent de sources externes et sont marqués par un watermark, ce qui indique une intention de protection. En l'absence de mentions claires sur les droits d'utilisation, j'ai pris la décision de procéder avec prudence, en respectant les principes éthiques du scraping. Je m'engage à ne pas utiliser ces médias à des fins commerciales et à respecter les droits des créateurs. De plus, je suis ouvert à toute communication avec les propriétaires du site pour clarifier l'utilisation de leur contenu, afin de garantir que mon projet soit mené de manière responsable et respectueuse.</p>
</body>
</html>
