# Guide des Modèles Restructurés

## 📋 Vue d'ensemble

Les modèles ont été restructurés selon les principes de la Programmation Orientée Objet en PHP avec une meilleure encapsulation et séparation des responsabilités :

- **Classes d'Entités** : Représentent les données avec propriétés privées, getters, setters, constructeurs et destructeurs
- **Classes Repository** : Gèrent les opérations de base de données (CRUD operations)

---

## 🏗️ Architecture

### Classes d'Entités
| Classe | Description | Fichier |
|--------|-------------|---------|
| `Evenement` | Représente un événement avec tous ses attributs | `model/Evenement.php` |
| `Participation` | Représente une inscription/participation | `model/Participation.php` |
| `Sponsor` | Représente un sponsor | `model/Sponsor.php` |

### Classes Repository
| Classe | Description | Fichier |
|--------|-------------|---------|
| `EvenementRepository` | Gère les opérations BD pour les événements | `model/EvenementRepository.php` |
| `ParticipationRepository` | Gère les opérations BD pour les participations | `model/ParticipationRepository.php` |
| `SponsorRepository` | Gère les opérations BD pour les sponsors | `model/SponsorRepository.php` |

---

## 💻 Utilisation

### Créer une entité

```php
// Créer un événement
$evenement = new Evenement(
    null,                          // id (null pour nouveau)
    'Congrès Cardiologie',         // titre
    'Description détaillée...',    // description
    'Cardiologie',                 // spécialité
    'Tunis',                       // lieu
    '2027-09-10',                  // date_debut
    '2027-09-12',                  // date_fin
    200,                           // capacité
    150.00,                        // prix
    'planifie'                     // statut
);

// Créer une participation
$participation = new Participation(
    null,
    'Ben Ali',
    'Ahmed',
    'ahmed@email.com',
    '20123456',
    'Médecin',
    1,  // evenement_id
    'confirme'
);

// Créer un sponsor
$sponsor = new Sponsor(
    null,
    'PharmaCorp',
    'contact@pharmacorp.com',
    '71234567',
    'https://pharmacorp.com',
    'or',  // niveau
    5000.00  // montant
);
```

### Utiliser les Getters

```php
// Accéder aux données d'une entité
$titre = $evenement->getTitre();
$capacite = $evenement->getCapacite();
$sponsors = $evenement->getSponsors();

// Pour les participations
$nomComplet = $participation->getNomComplet();
$estConfirmee = $participation->isConfirmed();

// Pour les sponsors
$estPremium = $sponsor->isPremium();
$niveau = $sponsor->getNiveauLabel();  // Retourne "Platine", "Or", etc.
```

### Utiliser les Setters

```php
// Modifier les propriétés (chaînage possible)
$evenement
    ->setTitre('Nouveau titre')
    ->setCapacite(250)
    ->setPrix(200.00)
    ->setStatut('en_cours');

// Pour les participations
$participation
    ->setStatut('confirme')
    ->setTelephone('20654321');

// Pour les sponsors
$sponsor
    ->setNiveau('platine')
    ->setMontant(10000.00);
```

### Opérations de Base de Données

```php
// ===== ÉVÉNEMENTS =====
$repoEvenement = new EvenementRepository();

// Créer
$evenement = new Evenement(/* ... */);
$repoEvenement->create($evenement);
$id = $evenement->getId();  // L'ID est maintenant set

// Récupérer
$evt = $repoEvenement->findById(1);
$tous = $repoEvenement->findAll();
$aVenir = $repoEvenement->findUpcoming();

// Mettre à jour
$evt->setTitre('Nouveau titre');
$repoEvenement->update($evt);

// Supprimer
$repoEvenement->delete(1);

// Utilitaires
$nbParticipants = $repoEvenement->countParticipations(1);
$placesRestantes = $repoEvenement->getPlacesRestantes(1);

// ===== PARTICIPATIONS =====
$repoParticipation = new ParticipationRepository();

// Créer
$part = new Participation(/* ... */);
$repoParticipation->create($part);

// Récupérer
$part = $repoParticipation->findById(1);
$parEvenement = $repoParticipation->findByEvenement(1);
$parEmail = $repoParticipation->findByEmail('user@email.com');

// Mettre à jour
$part->setStatut('annule');
$repoParticipation->update($part);

// Supprimer
$repoParticipation->delete(1);

// Utilitaires
$existe = $repoParticipation->isAlreadyRegistered('user@email.com', 1);
$confirmees = $repoParticipation->countConfirmed(1);
$enAttente = $repoParticipation->countPending(1);

// ===== SPONSORS =====
$repoSponsor = new SponsorRepository();

// Créer
$sponsor = new Sponsor(/* ... */);
$repoSponsor->create($sponsor);

// Récupérer
$sponsor = $repoSponsor->findById(1);
$tous = $repoSponsor->findAll();
$premium = $repoSponsor->findPremium();
$orOnly = $repoSponsor->findByNiveau('or');

// Mettre à jour
$sponsor->setMontant(7500.00);
$repoSponsor->update($sponsor);

// Supprimer
$repoSponsor->delete(1);

// Utilitaires
$exists = $repoSponsor->emailExists('contact@sponsor.com');
$events = $repoSponsor->countEvenements(1);
$total = $repoSponsor->getTotalMontant();
$moyenne = $repoSponsor->getAverageMontant();
```

### Conversion vers Tableau

```php
// Convertir une entité en tableau (utile pour les vues)
$data = $evenement->toArray();
// Retourne: ['id' => ..., 'titre' => ..., 'sponsors' => ...]

// Créer une entité à partir d'un tableau
$evenement = Evenement::fromArray($data);
$participation = Participation::fromArray($row);
$sponsor = Sponsor::fromArray($row);
```

---

## 🔄 Migration vers les nouveaux modèles

### Avant (DAO Pattern)
```php
require_once 'model/Evenement.php';

$dao = new Evenement();
$evt = $dao->findById(1);
$dao->update(1, ['titre' => 'Nouveau titre']);
```

### Après (Entity + Repository Pattern)
```php
require_once 'model/Evenement.php';
require_once 'model/EvenementRepository.php';

$repo = new EvenementRepository();
$evt = $repo->findById(1);
if ($evt) {
    $evt->setTitre('Nouveau titre');
    $repo->update($evt);
}
```

---

## 📋 Propriétés et Méthodes

### Evenement
- **Propriétés** : id, titre, description, specialite, lieu, dateDebut, dateFin, capacite, prix, statut, createdAt, sponsors
- **Méthodes utilitaires** : `toArray()`, `fromArray()`, `addSponsor()`

### Participation
- **Propriétés** : id, nom, prenom, email, telephone, profession, evenementId, statut, dateInscription
- **Getters spéciaux** : `getNomComplet()`
- **Méthodes utilitaires** : `toArray()`, `fromArray()`, `isConfirmed()`, `isCancelled()`, `isPending()`

### Sponsor
- **Propriétés** : id, nom, email, telephone, siteWeb, niveau, montant, createdAt
- **Getters spéciaux** : `getNiveauBadge()`, `getNiveauLabel()`
- **Méthodes utilitaires** : `toArray()`, `fromArray()`, `isPremium()`

---

## ✅ Avantages

✨ **Meilleure encapsulation** - Propriétés privées avec accès contrôlé via getters/setters
✨ **Validation intégrée** - Les setters valident les données
✨ **Chaînage de méthodes** - Les setters retournent `$this` pour un code plus fluide
✨ **Séparation des responsabilités** - Entités vs Repository
✨ **Conversions facilitées** - Méthodes `toArray()` et `fromArray()` intégrées
✨ **Méthodes utilitaires** - Vérifications et calculs intégrés dans les entités
✨ **Destructeurs** - Nettoyage automatique des ressources si nécessaire

---

## 🔗 Notes importantes

- Les **Classes d'Entités** représentent des DONNÉES, pas des opérations BD
- Les **Classes Repository** gèrent les opérations BD et retournent des entités
- Les setters retournent toujours `$this` pour permettre le chaînage
- La validation est effectuée dans les setters (ex: normalisation des énums, valeurs min/max)
- Utilisez `toArray()` pour convertir en tableau avant d'envoyer aux vues si nécessaire
