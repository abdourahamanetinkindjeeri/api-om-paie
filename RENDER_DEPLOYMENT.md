# Guide de déploiement sur Render

## URL de déploiement

https://tinkin-transfer.onrender.com

## Configuration Render

### 1. Variables d'environnement requises

```
APP_KEY=base64:... (généré automatiquement)
MONGODB_URI=mongodb://mongo:TyUsJXCHYKEMcEzELakgMgjBMdjEFqfv@gondola.proxy.rlwy.net:14762
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tinkin-transfer.onrender.com
```

### 2. Endpoints de test

-   **Health Check**: `https://tinkin-transfer.onrender.com/api/health`
-   **Status**: `https://tinkin-transfer.onrender.com/api/status`
-   **Documentation**: `https://tinkin-transfer.onrender.com/api/documentation`

### 3. Optimisations appliquées

#### Dockerfile

-   ✅ Permissions optimisées avec `--chown` lors du COPY
-   ✅ Répertoires créés en une seule commande
-   ✅ Health check avec timeout étendu (180s start-period)
-   ✅ Commande serveur avec `--no-reload`

#### Entrypoint

-   ✅ Configuration minimale au démarrage
-   ✅ Tâches lourdes en arrière-plan
-   ✅ Répertoire `/tmp/views` pour les vues compilées
-   ✅ Gestion des erreurs avec `|| true`

#### Configuration Laravel

-   ✅ Logs vers stderr pour Render
-   ✅ Vues compilées dans `/tmp/views`
-   ✅ Cache de configuration uniquement
-   ✅ Génération différée de la documentation

### 4. Résolution des timeouts

**Problème initial :** Build timeout après ~15 minutes
**Solutions appliquées :**

1. Réduction des opérations dans l'entrypoint
2. Déplacement des tâches lourdes en arrière-plan
3. Health check avec délai de démarrage étendu
4. Optimisation des permissions Docker

### 5. Test de déploiement

```bash
# Test local avec le même Dockerfile
docker build -t om-paie-test .
docker run -p 8000:8000 -e APP_KEY=base64:test om-paie-test

# Test des endpoints
curl https://tinkin-transfer.onrender.com/api/health
curl https://tinkin-transfer.onrender.com/api/status
```

### 6. Surveillance

#### Logs Render

```bash
# Vérifier les logs de démarrage
# Rechercher: "✅ Application prête"
```

#### Métriques importantes

-   Temps de démarrage initial : < 180s
-   Réponse health check : < 30s
-   Utilisation mémoire : < 256MB

### 7. Dépannage

#### Si timeout au déploiement

1. Vérifier les logs de build Render
2. S'assurer que MongoDB est accessible
3. Vérifier les variables d'environnement

#### Si l'app ne répond pas

1. Tester `/api/health` en premier
2. Vérifier les logs d'application
3. Redémarrer le service si nécessaire

### 8. Prochaines optimisations

-   [ ] CDN pour les assets statiques
-   [ ] Cache Redis pour les sessions
-   [ ] Monitoring avec Sentry
-   [ ] Backup automatique MongoDB
