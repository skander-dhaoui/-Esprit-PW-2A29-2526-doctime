# Base de données DocTime — installation locale

Ce dossier contient tout le nécessaire pour créer une base vide et alignée avec le schéma attendu par l’application.

## Fichiers

| Fichier | Rôle |
|--------|------|
| `doctime_full.sql` | Crée la base `doctime_db`, les tables, puis les données initiales (catégories + admin). Les `INSERT IGNORE` permettent de ré-importer sans erreur **duplicate entry**. |

## Importer (phpMyAdmin)

1. Ouvrir phpMyAdmin → **Importer**.
2. Choisir le fichier `doctime_full.sql`.
3. Exécuter. Aucune étape préalable : le script contient `CREATE DATABASE` et `USE doctime_db`.

## Importer (ligne de commande)

Remplacez `root` et le mot de passe si besoin.

```bash
mysql -u root -p < doctime_full.sql
```

Sous Windows (PowerShell), depuis ce dossier :

```powershell
Get-Content .\doctime_full.sql | mysql -u root -p
```

## Configuration PHP

Dans `config/database.php`, les valeurs par défaut sont :

- **hôte** : `localhost`
- **nom de la base** : `doctime_db`
- **utilisateur** : `root`
- **mot de passe** : vide

Adaptez-les à votre environnement (XAMPP, WAMP, Docker, etc.).

## Données de démonstration (optionnel)

Le dépôt peut contenir d’autres scripts (`sample_data.sql`, migrations) qui ne sont **pas** tous compatibles avec ce schéma sans adaptation manuelle des colonnes. Pour un premier test, importez uniquement `doctime_full.sql`, créez un compte via l’écran d’inscription, ou insérez un utilisateur admin à la main si votre équipe en fournit un script validé.

## Après import

1. Vérifier que `setup_db.php` (si présent à la racine du projet) n’est pas requis pour votre branche, ou l’exécuter une seule fois si la documentation du projet l’indique.
2. Lancer le site depuis la racine du projet (point d’entrée `index.php`).
