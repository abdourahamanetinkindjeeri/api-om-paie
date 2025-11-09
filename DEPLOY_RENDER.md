# Déploiement OM-Paie API sur Render

## 🚀 Guide de déploiement

### Prérequis
- Compte Render.com
- Repository GitHub avec le code source
- MongoDB Atlas ou Railway MongoDB (configuré)

### Étapes de déploiement

1. **Connecter le repository à Render**
   - Aller sur [render.com](https://render.com)
   - Cliquer sur "New +" → "Web Service"
   - Connecter votre repository GitHub

2. **Configuration du service**
   - **Name**: `om-paie-api`
   - **Runtime**: Docker
   - **Build Command**: Automatique (utilise le Dockerfile)
   - **Start Command**: Automatique (défini dans le Dockerfile)

3. **Variables d'environnement**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=[Généré automatiquement]
   APP_URL=https://votre-service.onrender.com
   DB_CONNECTION=mongodb
   MONGODB_URI=mongodb://user:password@host:port/database
   L5_SWAGGER_USE_ABSOLUTE_PATH=true
   FORCE_HTTPS=true
   TRUSTED_PROXIES=*
   ```

4. **Déploiement**
   - Cliquer sur "Create Web Service"
   - Attendre la completion du build (5-10 minutes)

## 🔗 URLs importantes

- **API Base URL**: `https://votre-service.onrender.com/api`
- **Documentation Swagger**: `https://votre-service.onrender.com/api/documentation`
- **Health Check**: `https://votre-service.onrender.com/api/documentation`

## 🔧 Configuration HTTPS

L'application est configurée pour :
- ✅ Forcer HTTPS en production
- ✅ Faire confiance aux proxies Render
- ✅ Gérer les headers X-Forwarded
- ✅ Documentation Swagger avec HTTPS

## 📝 Endpoints principaux

### Authentication
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion

### Transferts
- `POST /api/transfer` - Effectuer un transfert
- `GET /api/transfer/balance` - Obtenir le solde
- `GET /api/transfer/history` - Historique des transferts

### Paiements
- `POST /api/payment` - Payer un marchand
- `GET /api/payment/history` - Historique des paiements

## 🛠️ Commandes utiles

### Redéployer
```bash
git push origin main  # Auto-deploy configuré
```

### Logs en temps réel
Via le dashboard Render ou :
```bash
curl https://votre-service.onrender.com/api/documentation
```

## 🔍 Monitoring

- **Health Check**: `/api/documentation`
- **Logs**: Dashboard Render
- **Métriques**: Dashboard Render

## ⚠️ Notes importantes

1. **Cold Start**: Premier appel peut prendre 30s (plan gratuit)
2. **Persistance**: Utiliser MongoDB externe (Railway/Atlas)
3. **SSL**: Géré automatiquement par Render
4. **Domaine personnalisé**: Configurable dans les settings

## 🔐 Sécurité

- HTTPS forcé en production
- Tokens JWT avec Laravel Passport
- Validation des proxies
- Headers de sécurité configurés