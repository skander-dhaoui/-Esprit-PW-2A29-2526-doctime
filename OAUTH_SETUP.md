# Configuration OAuth - MediConnect

## 📝 Vue d'ensemble

MediConnect supporte plusieurs fournisseurs OAuth pour la connexion sociale:
- ✅ Google
- ✅ GitHub
- ✅ Facebook
- ✅ LinkedIn

## 🔧 Configuration

### 1. Copier le fichier de configuration

```bash
cp .env.example .env
```

### 2. Obtenir les clés OAuth pour LinkedIn

#### Étapes:
1. Accédez à https://www.linkedin.com/developers/apps
2. Cliquez sur "Create app"
3. Remplissez les informations de l'application
4. Dans l'onglet "Auth", récupérez:
   - **Client ID**
   - **Client Secret**

5. Configurez les **Authorized redirect URLs**:
   ```
   http://localhost/valorys_Copie/index.php?page=social_login_callback&provider=linkedin
   https://your-production-domain.com/index.php?page=social_login_callback&provider=linkedin
   ```

### 3. Configurer le fichier `.env`

Éditez le fichier `.env` et remplacez les variables:

```env
LINKEDIN_CLIENT_ID=your_client_id_here
LINKEDIN_CLIENT_SECRET=your_client_secret_here
LINKEDIN_SCOPE=profile email openid
```

### 4. Tester la configuration

Visitez: http://localhost/valorys_Copie/debug_oauth.php

Ceci affichera l'état de toutes les configurations OAuth.

## 🔑 Configuration pour chaque fournisseur

### Google
- Console: https://console.developers.google.com/
- Scopes: `openid email profile`

### GitHub
- Settings: https://github.com/settings/developers
- Scopes: `read:user user:email`

### Facebook
- Apps: https://developers.facebook.com/apps/
- Scopes: `email public_profile`

### LinkedIn
- Apps: https://www.linkedin.com/developers/apps
- Scopes: `profile email openid`

## ⚠️ Notes de sécurité

- **Ne jamais commiter le fichier `.env`** en production
- Garder les secrets OAuth sécurisés
- Utiliser des variables d'environnement serveur en production
- Restreindre les redirect URLs aux domaines de confiance

## 🐛 Dépannage

### Message: "La connexion [Fournisseur] n'est pas encore configurée"

**Cause**: Les variables d'environnement ne sont pas définis

**Solution**:
1. Vérifiez que `.env` existe
2. Vérifiez que `LINKEDIN_CLIENT_ID` et `LINKEDIN_CLIENT_SECRET` ne sont pas vides
3. Redémarrez le serveur web
4. Visitez: http://localhost/valorys_Copie/debug_oauth.php

### Les boutons sociaux n'apparaissent pas

**Cause**: Le CSS n'est pas chargé

**Solution**:
1. Vérifiez la connexion aux ressources statiques
2. Videz le cache du navigateur (Ctrl+Shift+R)
3. Vérifiez la console navigateur pour les erreurs

## 📚 Ressources

- [LinkedIn OAuth Documentation](https://learn.microsoft.com/en-us/linkedin/shared/authentication/authentication)
- [MediConnect OAuth Documentation](./config/social_auth.php)

## 📞 Support

Pour toute question, consultez le fichier `config/social_auth.php` ou `controllers/AuthController.php`.
