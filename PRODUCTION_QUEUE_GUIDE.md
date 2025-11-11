# 🚀 Guide de gestion des jobs en production sur Render

## 🔍 Diagnostic du problème actuel

Les jobs sont créés dans MongoDB mais ne sont pas traités automatiquement.

### Pourquoi ?

Le worker Laravel standard (`queue:work`) ne fonctionne pas correctement avec MongoDB car :
- Il cherche un champ auto-incrémenté `id` de type integer
- MongoDB utilise des ObjectId
- La synchronisation n'est pas optimale

## ✅ Solutions pour la production

### Solution 1️⃣ : Worker personnalisé (DÉJÀ EN PLACE)

Le worker personnalisé `queue:process-mongodb` est déjà configuré dans `start-queue-worker.sh`.

**Vérification sur Render :**

1. **Vérifier dans les logs** que vous voyez :
   ```
   [QUEUE] 🔄 Lancement du worker de queue MongoDB...
   ```

2. **Si le worker tourne mais ne traite pas les jobs**, c'est probablement parce que :
   - La commande `queue:process-mongodb` n'est pas encore déployée
   - Le cache de Laravel utilise encore l'ancienne configuration

---

### Solution 2️⃣ : Job CRON sur Render (RECOMMANDÉ pour les petits volumes)

Sur Render.com, ajoutez un **Cron Job** :

#### Étapes sur Render :

1. **Dashboard** → Votre service → **Settings**
2. Cherchez **"Cron Jobs"** ou **"Background Workers"**
3. Ajoutez un nouveau job :

   ```bash
   * * * * * cd /opt/render/project/src && php artisan queue:process-mongodb --queue=notifications --once
   ```

   **Signification** : Toutes les minutes, traite un job de la queue notifications

4. **Alternative** : Chaque 2 minutes pour réduire la charge :
   ```bash
   */2 * * * * cd /opt/render/project/src && php artisan queue:process-mongodb --queue=notifications --once
   ```

---

### Solution 3️⃣ : Vérifier et forcer le redémarrage du worker

#### A. Sur Render, dans les logs :

Si vous voyez :
```
[QUEUE] Processing jobs from the [notifications] queue.
................................................... ~ 0s
```

Mais pas de jobs traités, alors :

#### B. Ajouter des logs dans la commande :

Modifiez `app/Console/Commands/ProcessQueueJobs.php` pour ajouter plus de debug.

#### C. Redéployer complètement :

1. **Git commit & push** tous les changements
2. Render va redéployer automatiquement
3. Le nouveau worker `queue:process-mongodb` sera utilisé

---

## 🧪 Test manuel immédiat

Pour traiter les jobs en attente **MAINTENANT** sur Render :

### Via le Shell Render :

1. Sur Render Dashboard → Votre service → **Shell**
2. Exécutez :
   ```bash
   php artisan queue:process-mongodb --queue=notifications --once
   ```

### Via SSH (si activé) :

```bash
ssh render
cd /opt/render/project/src
php artisan queue:process-mongodb --queue=notifications --once
```

---

## 📊 Surveillance des jobs

### Commande pour voir les jobs en attente :

```bash
php artisan tinker --execute="
echo 'Jobs en attente: ' . DB::connection('mongodb')->table('jobs')->whereNull('reserved_at')->count() . '\n';
"
```

### Commande pour voir les jobs échoués :

```bash
php artisan tinker --execute="
echo 'Jobs échoués: ' . DB::connection('mongodb')->table('failed_jobs')->count() . '\n';
"
```

---

## 🎯 Plan d'action immédiat

### Étape 1 : Traiter les jobs actuels

**Localement** (pour vérifier) :
```bash
php process_jobs_manually.php
```

**Sur Render** (via Shell) :
```bash
php artisan queue:process-mongodb --queue=notifications
```

### Étape 2 : Déployer les changements

```bash
git add .
git commit -m "feat: Add MongoDB queue worker and fix email configuration"
git push origin dev/v4.0.4
```

Render va redéployer automatiquement.

### Étape 3 : Vérifier après déploiement

Dans les logs Render, vous devriez voir :
```
[QUEUE] 🔄 Lancement du worker de queue MongoDB...
[QUEUE] Processing: App\Jobs\SendWelcomeOtpJob
[QUEUE]   ✅ OTP envoyé à xxx@gmail.com
```

### Étape 4 : Configurer un CRON (optionnel mais recommandé)

Si Render supporte les Cron Jobs, ajoutez :
```bash
*/1 * * * * cd /opt/render/project/src && php artisan queue:process-mongodb --once
```

---

## 🔧 Debugging

### Si les jobs ne sont toujours pas traités :

1. **Vérifier que la commande existe** :
   ```bash
   php artisan list | grep queue:process-mongodb
   ```

2. **Vérifier la configuration** :
   ```bash
   php artisan config:show queue
   ```

3. **Tester manuellement un job** :
   ```bash
   php artisan queue:process-mongodb --queue=notifications --once
   ```

4. **Voir les logs Laravel** :
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 📋 Checklist finale

- [ ] Code committé et pushé sur Git
- [ ] Render a redéployé l'application
- [ ] Worker visible dans les logs : `[QUEUE] 🔄 Lancement du worker de queue MongoDB...`
- [ ] Jobs traités : `[QUEUE] ✅ OTP envoyé`
- [ ] Emails reçus dans les boîtes mail
- [ ] CRON configuré (optionnel)
- [ ] Variables d'environnement vérifiées (MAIL_PASSWORD sans guillemets superflus)

---

## 🆘 En cas de problème persistant

### Option de secours : Traiter les jobs manuellement

Créez un endpoint API temporaire (à sécuriser !) :

```php
// routes/api.php
Route::post('/admin/process-queue', function() {
    Artisan::call('queue:process-mongodb', ['--once' => true]);
    return response()->json(['message' => 'Queue processed']);
})->middleware('auth:api'); // Protégé par auth
```

Puis appelez-le périodiquement avec un service externe (UptimeRobot, etc.)

---

## ✅ Résultat attendu

Une fois tout configuré :
1. ✅ Les utilisateurs s'inscrivent
2. ✅ Les jobs sont créés dans MongoDB
3. ✅ Le worker les traite automatiquement (ou via CRON)
4. ✅ Les emails OTP sont envoyés
5. ✅ L'inscription se termine avec succès
