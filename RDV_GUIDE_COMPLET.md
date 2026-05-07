# 📖 Guide Complet : RDV Avancée avec Commentaires Enrichis

## 🎯 Vue d'ensemble

Ce système améliore les rendez-vous (RDV) en ajoutant :
1. **Page détails complète** - Affichage de toutes les infos du RDV
2. **Commentaires enrichis** - Support images, emojis, formatage
3. **Lien Disponibilité** - FK entre RDV et Disponibilités du médecin
4. **Éditeur Quill.js** - WYSIWYG pour riche contenu

---

## 🚀 Démarrage Rapide

### 1. Installation
```bash
# Accéder à la page d'installation
http://localhost/valorys_Copie/install_rdv_avancee.php

# Cliquer "Installer" pour créer les tables
# Page teste et crée automatiquement tout ce qui manque
```

### 2. Accéder à la Fonctionnalité
```
Naviguer vers :
  Admin Dashboard → Rendez-vous → Cliquer "Voir" (icône oeil)
  
  Ou accès direct :
  http://localhost/valorys_Copie/index.php?page=admin_rendezvous&action=view&id=1
```

### 3. Utiliser les Commentaires
```
Sur la page détails du RDV :
  1. Scroller vers "Commentaires"
  2. Cliquer "Ajouter Emoji" ou "Upload Image"
  3. Taper le commentaire
  4. Cliquer "Publier le commentaire"
```

---

## 💾 Architecture Base de Données

### Table : rendez_vous (modifiée)
```sql
ALTER TABLE rendez_vous 
ADD COLUMN disponibilite_id INT NULL;

-- Nouvelle FK
ALTER TABLE rendez_vous 
ADD FOREIGN KEY (disponibilite_id) 
  REFERENCES disponibilites(id) ON DELETE SET NULL;
```

### Table : event_comments (nouveau)
```sql
CREATE TABLE event_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,           -- ID du RDV
    user_id INT NOT NULL,            -- Admin qui a commenté
    comment LONGTEXT NOT NULL,       -- JSON Delta format
    status ENUM(...) DEFAULT 'approuvé',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event (event_id),
    INDEX idx_status (status)
);
```

---

## 🔀 Flux Utilisateur

```
┌─────────────────────────────────────────────────────────┐
│ Admin Dashboard                                         │
└──────────────────┬──────────────────────────────────────┘
                   │ Clic "Rendez-vous"
                   ↓
┌─────────────────────────────────────────────────────────┐
│ Liste RDV (rendezvous/list.php)                         │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Patient │ Médecin │ Date │ Actions                │ │
│ │ Jean    │ Dr. Bob │ 15/1 │ [👁️ Voir][✏️][🗑️]      │ │
│ └─────────────────────────────────────────────────────┘ │
└──────────────────┬──────────────────────────────────────┘
                   │ Clic bouton 👁️ Voir
                   ↓
┌─────────────────────────────────────────────────────────┐
│ Détails RDV (rendezvous_detail.php)                     │
│                                                         │
│ 📋 INFOS RDV                                            │
│  Patient: Jean Martin (jean@email.com)                 │
│  Médecin: Dr. Bob Smith (Cardiologue)                  │
│  Date: 15/01/2024 à 14:30                              │
│  Motif: Consultation cardiaque                         │
│  Statut: Confirmé                                      │
│                                                         │
│ 📅 DISPONIBILITÉ LIÉE                                  │
│  Médecin: Dr. Bob Smith                                │
│  Jour: Lundi                                           │
│  Horaire: 14:00 - 17:00                                │
│                                                         │
│ 💬 COMMENTAIRES (2)                                    │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Admin Jean - 15/01/2024 14:32                    │ │
│  │ Patient arrivé à l'heure ✓                       │ │
│  └──────────────────────────────────────────────────┘ │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Admin Jean - 15/01/2024 14:45                    │ │
│  │ 🎉 Consultation complétée ! 📋 Résultats OK    │ │
│  │ [Image d'une feuille médicale]                  │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│ ✏️ AJOUTER COMMENTAIRE                                │
│ ┌──────────────────────────────────────────────────┐ │
│ │ [Éditeur Quill.js]                              │ │
│ │ B I _ < > □ ≡ " ○  [😊] [📷]                 │ │
│ │                                                │ │
│ │ Entrez votre commentaire ici...                │ │
│ │                                                │ │
│ │ [Annuler]           [Publier Commentaire]       │ │
│ └──────────────────────────────────────────────────┘ │
│                                                         │
│ [⬅️ Retour] [✏️ Modifier] [🗑️ Supprimer]            │
└─────────────────────────────────────────────────────────┘
```

---

## 📝 Formatage Commentaires

### Options Disponibles

```
Toolbar Quill.js :
┌────────────────────────────────────────┐
│ [B]old [I]talic [U]nderline [S]trike  │  Formatage texte
│ [H1] [H2] [H3]                        │  Titres
│ [•] Bullets [1.] Ordered              │  Listes
│ [|] Code  ["] Blockquote              │  Blocs
│ [Link] [Image]                        │  Liens/Images
│ [⌫] Clear                             │  Réinitialiser
│ [😊] Emoji picker                    │  Emojis
│ [📷] Image upload                     │  Upload image
└────────────────────────────────────────┘
```

### Exemples de Contenu

#### Exemple 1 : Texte Simple
```
Commentaire de suivi.
Patient va bien, traitement en cours.
Rendez-vous de suivi prévu dans 2 semaines.
```

#### Exemple 2 : Avec Formatage
```
Consultation Réussie! ✓

Patient: Alérgies alimentaires
Traitement: Prescrit antibiotiques
Suivi: Dans 7 jours

Résultat tests: NÉGATIF ✓ 
Notes: Bien suivi, patient compliant
```

#### Exemple 3 : Avec Emoji
```
🎉 Excellent résultat!

Tension artérielle: 120/80 ✓
Poids: OK 
IMC: 24.5 ✓

Recommandations:
• Continuer le régime 🥗
• Exercice 3x/semaine 🏃‍♂️
• Suivi mensuel 📅

Prochaine visite: 15 Février 📆
```

#### Exemple 4 : Avec Image
```
Résultat d'examen

[Image attachée : radio_poumon.jpg]

Diagnostic: Pneumonie légère
Traitement: Antibiotique 5 jours
Résultat: Bon pronostic ✓
```

---

## 🛠️ Code Utilisé

### Contrôleur : viewRendezVous()
```php
public function viewRendezVous(int $id): void {
    // Récupère RDV avec patient/médecin/disponibilité
    $stmt = $db->prepare("
        SELECT rv.*,
               u_patient.prenom AS patient_prenom,
               u_medecin.prenom AS medecin_prenom,
               m.specialite,
               d.jour_semaine,
               d.heure_debut
        FROM rendez_vous rv
        JOIN users u_patient ON rv.patient_id = u_patient.id
        JOIN users u_medecin ON rv.medecin_id = u_medecin.id
        LEFT JOIN medecins m ON rv.medecin_id = m.user_id
        LEFT JOIN disponibilites d ON rv.disponibilite_id = d.id
        WHERE rv.id = ?
    ");
    
    // Récupère commentaires
    $commentsStmt = $db->prepare("
        SELECT ec.*, u.nom, u.prenom
        FROM event_comments ec
        LEFT JOIN users u ON ec.user_id = u.id
        WHERE ec.event_id = ? AND ec.status = 'approuvé'
        ORDER BY ec.created_at DESC
    ");
}
```

### Contrôleur : addCommentRendezVous()
```php
public function addCommentRendezVous(int $id): void {
    // Valide RDV existe
    $rdvStmt = $db->prepare("SELECT id FROM rendez_vous WHERE id = ?");
    $rdvStmt->execute([$id]);
    if (!$rdvStmt->fetch()) {
        header("Location: index.php?page=admin_rendezvous&action=view&id={$id}");
        exit;
    }
    
    // Récupère commentaire JSON
    $comment = $_POST['comment'] ?? '';
    
    // Insère en BD
    $insertStmt = $db->prepare("
        INSERT INTO event_comments (event_id, user_id, comment, status)
        VALUES (?, ?, ?, 'approuvé')
    ");
    $insertStmt->execute([$id, $_SESSION['user_id'], $comment]);
}
```

### Vue : Affichage Contenu Quill
```php
function renderQuillContent(string $json_content): string {
    if (isJson($json_content)) {
        $data = json_decode($json_content, true);
        $html = '';
        
        foreach ($data['ops'] ?? [] as $op) {
            if (isset($op['insert'])) {
                $text = $op['insert'];
                $attributes = $op['attributes'] ?? [];
                
                // Applique le formatage
                if (isset($attributes['bold'])) {
                    $text = "<strong>$text</strong>";
                }
                if (isset($attributes['italic'])) {
                    $text = "<em>$text</em>";
                }
                if (isset($attributes['image'])) {
                    $text = "<img src='{$attributes['image']}' />";
                }
                
                $html .= $text;
            }
        }
        
        return $html;
    }
    
    return htmlspecialchars($json_content);
}
```

---

## 🧪 Tests

### Test 1 : Page Détails
```
URL: http://localhost/valorys_Copie/index.php?page=admin_rendezvous&action=view&id=1
Attendu:
✓ Page charge sans erreur
✓ Affiche infos patient, médecin
✓ Affiche date/heure RDV
✓ Affiche disponibilité liée
✓ Affiche liste commentaires
```

### Test 2 : Ajouter Commentaire Text
```
1. Ouvrir page détails RDV
2. Scroller vers section commentaires
3. Taper "Test commentaire"
4. Cliquer "Publier"
Attendu:
✓ Commentaire apparaît dans la liste
✓ Affiche nom admin + timestamp
✓ Page ne recharge pas
```

### Test 3 : Ajouter Emoji
```
1. Ouvrir page détails RDV
2. Cliquer bouton "😊 Emoji"
3. Choisir emoji (ex: 🎉)
4. Emoji inséré dans éditeur
5. Taper "Visite réussie 🎉"
6. Cliquer "Publier"
Attendu:
✓ Commentaire s'affiche avec emoji
✓ Emoji s'affiche correctement
```

### Test 4 : Ajouter Image
```
1. Ouvrir page détails RDV
2. Cliquer bouton "📷 Upload Image"
3. Choisir fichier image
4. Image prévisionnée
5. Cliquer "Publier"
Attendu:
✓ Commentaire s'affiche avec image
✓ Image redimensionnée automatiquement
✓ Pas de fichier sauvegardé (encodé en base64)
```

---

## ⚠️ Dépannage

### Problème : Page détails RDV donne 404
```
Cause: rendezvous_detail.php n'existe pas
Solution:
1. Vérifier fichier existe
2. Vérifier chemin correct dans AdminController
3. Recréer fichier si nécessaire
```

### Problème : Commentaires ne s'affichent pas
```
Cause: Table event_comments n'existe pas
Solution:
1. Exécuter install_rdv_avancee.php
2. Ou exécuter migration manuellement:
   CREATE TABLE event_comments (...)
```

### Problème : FK disponibilite_id génère erreur
```
Cause: Colonne pas créée ou FK mal configurée
Solution:
1. Exécuter migration ADD COLUMN disponibilite_id
2. Vérifier table disponibilites existe
3. Vérifier pas de doublons FK
```

### Problème : Images ne s'affichent pas dans commentaires
```
Cause: Contenu pas en JSON Delta ou pas encodé base64
Solution:
1. Vérifier contenu = JSON valide
2. Vérifier format image = data:image/jpeg;base64,...
3. Vérifier fonction renderQuillContent() existe
```

---

## 📊 Statistiques Tables

### Table : event_comments
```
Colonnes:
  - id: INT PK AUTO_INCREMENT
  - event_id: INT (RDV ID)
  - user_id: INT FK (Admin qui a commenté)
  - comment: LONGTEXT (JSON Delta)
  - status: ENUM (en_attente, approuvé, rejeté)
  - created_at: TIMESTAMP (Création)
  - updated_at: TIMESTAMP (Modification)

Index:
  - idx_event (event_id)
  - idx_status (status)
  - idx_user (user_id)
  - idx_created (created_at)

Taille typique par commentaire: ~2-50 KB
(dépend images attachées)
```

### Table : rendez_vous
```
Colonne ajoutée:
  - disponibilite_id: INT NULL (FK disponibilites)

Index ajouté:
  - idx_disponibilite (disponibilite_id)

Relation:
  RDV ──FK──> Disponibilite du médecin
  1 RDV peut être lié à 1 Disponibilité
  1 Disponibilité peut avoir N RDV
```

---

## 🎓 Concepts

### JSON Delta Format (Quill.js)
```json
{
  "ops": [
    {"insert": "Coucou"},
    {"insert": " "},
    {"insert": "monde", "attributes": {"bold": true}},
    {"insert": "\n"},
    {"insert": {"image": "data:image/jpeg;base64,..."}}
  ]
}
```

### Base64 Encoding Images
```
Image JPEG 100KB → Base64 ~133KB (stocké en DB)
Avantages:
  ✓ Image embedded dans JSON
  ✓ Pas de fichier externe
  ✓ Portable et standalone
  
Inconvénients:
  ✗ Taille DB augmente
  ✗ Requête plus lente
```

### WYSIWYG vs Code
```
WYSIWYG (Quill.js): 
  Utilisateur tape et formate visuel
  Backend reçoit JSON Delta
  
Affichage:
  JSON Delta → Parsé → HTML rendu
  
Avantage:
  Format portable, éditable, typé
```

---

## 📞 Support

Pour plus d'aide :
1. Consulter `RDV_AVANCEE_README.md`
2. Exécuter `install_rdv_avancee.php`
3. Exécuter `test_rdv_avancee.php`
4. Vérifier les migrations SQL

---

**Version:** 1.0  
**Date:** Janvier 2024  
**Auteur:** Système DocTime  
**Statut:** ✅ Production Ready
