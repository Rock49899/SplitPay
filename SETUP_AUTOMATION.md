# Configuration de l'Automation - Phase 1

Ce guide vous permet d'activer le système de rappels automatiques et de notifications.

---

## ✅ **Ce qui est déjà fait**

- ✅ Backend complet (Controllers, Jobs, Commands, Mails)
- ✅ Frontend complet (Services, Views, Sidebar)
- ✅ Scheduler configuré (routes/console.php)
- ✅ Permissions seedées dans la base de données
- ✅ Toutes les routes API fonctionnelles

---

## **Configuration Requise**

### **1. Système Cron (Scheduler Laravel)**

Le scheduler Laravel doit tourner en permanence pour exécuter les tâches programmées.

#### **Sur votre serveur de production:**

```bash
# Ouvrir le crontab
crontab -e

# Ajouter cette ligne (remplacer le chemin par le vôtre):
* * * * * cd /home/rock/PIEUVRE/Saas-schooling-project && php artisan schedule:run >> /dev/null 2>&1
```

#### **Vérification:**

```bash
# Tester manuellement
php artisan schedule:list

# Voir les tâches enregistrées:
# ✓ reminders:send ............ Daily at 08:00
# ✓ payments:check-due-dates .. Twice daily at 9:00 and 15:00
```

#### **Fonctionnement:**

Une fois le cron configuré, Laravel exécutera automatiquement:
- **8h00 chaque jour** → Envoi des rappels programmés
- **9h00 et 15h00** → Vérification des échéances et création de notifications

---

### **2. Queue Worker (Jobs Asynchrones)**

Les jobs d'envoi d'emails tournent en arrière-plan via le système de files d'attente.

#### **Configuration de la queue dans `.env`:**

```env
# Option 1: Database (simple, recommandé pour démarrage)
QUEUE_CONNECTION=database

# Option 2: Redis (performant, pour production)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### **Créer la table jobs (si database queue):**

```bash
php artisan queue:table
php artisan migrate
```

#### **Lancer le worker:**

**En développement (manuel):**
```bash
php artisan queue:work --verbose
```

**En production (avec Supervisor):**

Créer le fichier `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/rock/PIEUVRE/Saas-schooling-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/home/rock/PIEUVRE/Saas-schooling-project/storage/logs/worker.log
stopwaitsecs=3600
```

Puis:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

### **3. Configuration Email (SMTP)**

Les rappels sont envoyés par email, donc vous devez configurer SMTP.

#### **Dans `.env`:**

**Option 1: Gmail (développement)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-ecole.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Option 2: Mailtrap (test)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre-username-mailtrap
MAIL_PASSWORD=votre-password-mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-ecole.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Option 3: SendGrid (production)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre-api-key-sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-ecole.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### **Tester l'envoi:**

```bash
php artisan tinker

# Dans tinker:
Mail::raw('Test email', function($msg) {
    $msg->to('votre-email@test.com')->subject('Test');
});
```

---

## **Tests Manuels**

### **1. Tester les rappels (dry-run):**

```bash
# Voir quels rappels seraient envoyés (sans envoyer)
php artisan reminders:send --dry-run
```

### **2. Envoyer les rappels maintenant:**

```bash
# Envoyer tous les rappels actifs
php artisan reminders:send
```

### **3. Vérifier les échéances:**

```bash
# Créer les notifications pour échéances proches/retards
php artisan payments:check-due-dates
```

### **4. Voir les jobs en attente:**

```bash
# Lister les jobs dans la queue
php artisan queue:monitor
```

### **5. Vider les logs:**

```bash
# Voir les logs Laravel
tail -f storage/logs/laravel.log

# Voir les logs du scheduler
tail -f storage/logs/scheduler.log
```

---

## **Monitoring & Logs**

### **Emplacements des logs:**

- **Scheduler:** `storage/logs/laravel.log` (rechercher "Scheduled reminders sent")
- **Queue Worker:** `storage/logs/worker.log` (si Supervisor)
- **Emails:** `storage/logs/laravel.log` (rechercher "Payment reminder sent")

### **Commandes de monitoring:**

```bash
# Nombre de jobs en attente
php artisan queue:monitor

# Statistiques du scheduler
php artisan schedule:list

# Voir les failed jobs
php artisan queue:failed

# Réessayer un failed job
php artisan queue:retry {id}

# Réessayer tous les failed jobs
php artisan queue:retry all
```

---

## Permissions Utilisateurs**

Les permissions suivantes ont été seedées:

- `notification.view` → Voir les notifications
- `notification.manage` → Marquer comme lu/supprimer
- `reminder.view` → Voir les rappels
- `reminder.create` → Créer des rappels
- `reminder.edit` → Modifier des rappels
- `reminder.delete` → Supprimer des rappels

**Rôles avec accès:**
- ✅ Super Admin Institution (toutes permissions)
- ✅ Super Admin Annexe (toutes permissions)
- ✅ Gestionnaire (view + manage notifications, tous rappels)
- ✅ Comptable (view + manage notifications uniquement)

**Pour ajouter les permissions à un utilisateur existant:**

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RolePermissionSeeder

# Ensuite, DÉCONNECTEZ-VOUS et RECONNECTEZ-VOUS
# Ou videz le cache:
php artisan cache:clear
```

---

## **Fonctionnalités Disponibles**

### **Notifications (Automatiques)**

Créées automatiquement lors:
- ✅ Paiement reçu (webhook PayPlus)
- ✅ Paiement échoué (webhook PayPlus)
- ✅ Échéance proche (3 jours avant)
- ✅ Paiement en retard (après échéance)

**Accès:** Icône cloche dans le header ou `/admin/notifications`

### **Rappels (Configurables)**

**Page:** `/admin/reminders`

**Configuration:**
1. Choisir une annexe
2. Définir le nombre de jours avant l'échéance (0-30)
3. Personnaliser le message avec variables:
   - `{student_name}` → Nom de l'étudiant
   - `{amount}` → Montant dû
   - `{due_date}` → Date d'échéance
4. Activer le rappel

**Exemples de configurations:**
- Rappel à J-7 : "Bonjour, l'échéance de paiement de {amount} pour {student_name} arrive dans 7 jours..."
- Rappel à J-0 : "URGENT: L'échéance de {amount} est aujourd'hui ({due_date})..."

### **Envoi Manuel**

**Page:** `/admin/students/{id}` → Section "Communication History"

**Utilisation:**
1. Ouvrir le profil d'un étudiant
2. Cliquer sur "Envoyer un rappel"
3. Personnaliser le message (optionnel)
4. Confirmer l'envoi

---

## **Troubleshooting**

### **Les rappels ne s'envoient pas:**

1. Vérifier que le cron tourne: `crontab -l`
2. Vérifier que le scheduler est actif: `php artisan schedule:list`
3. Tester manuellement: `php artisan reminders:send`
4. Vérifier les logs: `tail -f storage/logs/laravel.log`

### **Les jobs restent bloqués:**

1. Vérifier que le worker tourne: `ps aux | grep queue:work`
2. Redémarrer le worker: `php artisan queue:restart`
3. Vérifier les failed jobs: `php artisan queue:failed`

### **Les emails ne partent pas:**

1. Tester la config SMTP: `php artisan tinker` → envoyer un test
2. Vérifier `.env`: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
3. Vérifier les logs: `storage/logs/laravel.log`

### **Permissions 403:**

1. Déconnectez-vous et reconnectez-vous
2. Videz le cache: `php artisan cache:clear`
3. Vérifiez les seeders: `php artisan db:seed --class=PermissionSeeder`

---

## **Quick Start Checklist**

- [ ] 1. Configurer cron système (`crontab -e`)
- [ ] 2. Configurer queue dans `.env` (database ou redis)
- [ ] 3. Lancer `php artisan queue:table && php artisan migrate` (si database)
- [ ] 4. Configurer SMTP dans `.env`
- [ ] 5. Tester email: `php artisan tinker` → envoyer un test
- [ ] 6. Lancer worker: `php artisan queue:work --verbose` (dev) ou Supervisor (prod)
- [ ] 7. Tester rappel dry-run: `php artisan reminders:send --dry-run`
- [ ] 8. Créer un rappel via `/admin/reminders`
- [ ] 9. Se déconnecter et reconnecter (permissions)
- [ ] 10. Vérifier notifications via icône cloche

---

## **Support**

Si vous rencontrez des problèmes:

1. Vérifier les logs: `storage/logs/laravel.log`
2. Vérifier la configuration: `.env`
3. Tester manuellement: commandes `php artisan reminders:send` et `php artisan payments:check-due-dates`

---

**Une fois cette configuration complète, votre système d'automation sera 100% opérationnel!**
