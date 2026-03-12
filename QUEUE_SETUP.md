# Configuration de la Queue Laravel pour l'envoi des emails

## Problème
Les rappels ne sont pas envoyés immédiatement car ils utilisent un système de **queue** (file d'attente) Laravel.

## Solution rapide - Mode développement

### Option 1: Utiliser la queue `sync` (sans queue)
Dans votre fichier `.env`, changez:
```env
QUEUE_CONNECTION=database
```

En:
```env
QUEUE_CONNECTION=sync
```

**Avantage:** Les emails sont envoyés immédiatement  
**Inconvénient:** Bloque l'application pendant l'envoi (pas idéal en production)

### Option 2: Utiliser un worker de queue (RECOMMANDÉ)

1. **Vérifier la configuration actuelle:**
```bash
php artisan config:cache
```

2. **Démarrer un worker de queue:**
```bash
php artisan queue:work --tries=3
```

**Important:** Laissez ce terminal ouvert en arrière-plan. Les jobs seront traités au fur et à mesure.

3. **Pour un environnement de développement, utilisez plutôt:**
```bash
php artisan queue:listen
```
Cela recharge automatiquement le code à chaque job (utile pendant le développement).

## Solution production

### Avec Supervisor (Linux)

1. **Installer Supervisor:**
```bash
sudo apt-get install supervisor
```

2. **Créer un fichier de configuration:**
```bash
sudo nano /etc/supervisor/conf.d/saas-schooling-worker.conf
```

3. **Ajouter cette configuration:**
```ini
[program:saas-schooling-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/rock/PIEUVRE/Saas-schooling-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=rock
numprocs=2
redirect_stderr=true
stdout_logfile=/home/rock/PIEUVRE/Saas-schooling-project/storage/logs/worker.log
stopwaitsecs=3600
```

4. **Recharger Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start saas-schooling-worker:*
```

5. **Vérifier le statut:**
```bash
sudo supervisorctl status
```

## Vérifier que les jobs sont bien créés

```bash
# Voir les jobs en attente
php artisan queue:monitor

# Ou directement dans la base de données
php artisan tinker
>>> DB::table('jobs')->count()
```

## Tester l'envoi de rappels

1. **Créer un rappel** dans l'interface `/admin/reminders`
2. **Cliquer sur "Envoyer maintenant"**
3. **Vérifier les logs:**
```bash
tail -f storage/logs/laravel.log
```

Vous devriez voir:
```
Manual reminder send triggered
Payment reminder sent
```

4. **Vérifier la table jobs:**
```bash
php artisan tinker
>>> DB::table('jobs')->count()  // Devrait être > 0 si des jobs sont en attente
```

## Commandes utiles

```bash
# Démarrer le worker
php artisan queue:work

# Démarrer avec logs verbeux
php artisan queue:work --verbose

# Redémarrer tous les workers après un changement de code
php artisan queue:restart

# Supprimer tous les jobs en échec
php artisan queue:flush

# Voir les jobs en échec
php artisan queue:failed
```

## Configuration email recommandée

Dans `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # Pour les tests
MAIL_PORT=2525
MAIL_USERNAME=votre_username
MAIL_PASSWORD=votre_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votresite.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Troubleshooting

### Les emails ne sont pas envoyés
1. Vérifier que le worker est actif: `ps aux | grep queue:work`
2. Vérifier les logs: `tail -f storage/logs/laravel.log`
3. Vérifier la configuration email: `php artisan tinker` puis `Mail::to('test@test.com')->send(new TestMail())`

### Les jobs restent bloqués
```bash
php artisan queue:restart
```

### Trop de jobs en échec
```bash
# Voir les détails
php artisan queue:failed

# Réessayer tous les jobs en échec
php artisan queue:retry all

# Supprimer tous les jobs en échec
php artisan queue:flush
```
