# 🔧 Correction urgente MAIL_PASSWORD sur Render

## ❌ Problème identifié

L'envoi d'emails échoue en production car le mot de passe Gmail est mal configuré sur Render.

### Diagnostic

-   **Symptôme** : Jobs traités mais emails non reçus
-   **Cause** : Format du mot de passe Gmail App Password incorrect dans les variables d'environnement
-   **Impact** : Authentification SMTP échouée (error 535 - Bad Credentials)

## ✅ Solution

### 1. Corriger MAIL_PASSWORD sur Render

Connectez-vous à Render Dashboard et modifiez la variable :

**❌ ACTUEL (INCORRECT)**

```
MAIL_PASSWORD="jtog yhdt fvgh dqwu"
```

ou

```
MAIL_PASSWORD='jtog yhdt fvgh dqwu'
```

**✅ CORRECT**

```
MAIL_PASSWORD=jtogyhdtfvghdqwu
```

**IMPORTANT** :

-   ❌ PAS de guillemets doubles `"`
-   ❌ PAS de guillemets simples `'`
-   ❌ PAS d'espaces entre les 4 groupes
-   ✅ Seulement les 16 caractères collés

### 2. Redéployer le service

Après modification de `MAIL_PASSWORD`, Render redéploiera automatiquement.

### 3. Vérifier les logs

Surveillez les logs Render pour confirmer :

```
[QUEUE] Processing: App\Jobs\SendWelcomeOtpJob
[QUEUE]   ✅ OTP envoyé à xxx@gmail.com
```

## 🔍 Vérification technique

### Logs à chercher

**✅ Succès** :

```
GmailNotificationService: Email envoyé avec succès via SMTP
```

**❌ Échec** (si encore présent) :

```
GmailNotificationService: Erreur SMTP Transport
Expected response code "235" but got code "535"
Username and Password not accepted
```

### Test manuel

Après redéploiement, créez un nouvel utilisateur via l'API pour générer un job OTP :

```bash
curl -X POST https://tinkin-transfer.onrender.com/api/v2/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Test",
    "prenom": "User",
    "email": "votre-email@gmail.com",
    "telephone": "779999999",
    "password": "Test1234@"
  }'
```

Vérifiez que l'email OTP arrive dans la boîte mail.

## 📋 Checklist de déploiement

-   [ ] Variable `MAIL_PASSWORD` corrigée sur Render (sans guillemets, sans espaces)
-   [ ] Service redéployé automatiquement
-   [ ] Logs montrent "Email envoyé avec succès via SMTP"
-   [ ] Email de test reçu dans la boîte mail
-   [ ] Pas d'erreur 535 dans les logs

## 🔧 Code corrigé

Le code `GmailNotificationService.php` a été mis à jour pour :

1. **Utiliser Symfony Mailer** au lieu de `Mail::raw()`

    - Envoi SMTP direct synchrone (pas de mise en queue)

2. **Nettoyer le mot de passe** automatiquement

    ```php
    $passwordClean = str_replace(' ', '', $password);
    ```

    - Retire les espaces automatiquement si présents

3. **Logger les détails SMTP** pour debug
    - Host, port, username, longueur du mot de passe

Cette correction est déjà déployée. Il suffit de corriger `MAIL_PASSWORD` sur Render.

## ⏰ Temps estimé

-   Modification variable : 30 secondes
-   Redéploiement automatique : 2-3 minutes
-   Test complet : 1 minute

**Total : ~5 minutes**

---

**Date** : 11 novembre 2025  
**Status** : Code corrigé, en attente de correction variable Render
