# Spécifications fonctionnelles — SplitPay

> Document rédigé par analyse du code source (modèles, contrôleurs, routes, jobs, migrations).
> Il décrit **ce que l'application fait réellement**. Les éléments présents dans le code mais
> non branchés, ainsi que les écarts constatés, sont signalés explicitement (§ 6).

## 1. Objet du système

SplitPay est une application SaaS **multi-établissements** de gestion et d'encaissement des frais
de scolarité. Elle permet à un établissement scolaire (et à ses annexes) de :

- tenir le fichier de ses étudiants et leur inscription annuelle ;
- définir un barème de scolarité par niveau, filière et année scolaire ;
- émettre des **liens de paiement** et les envoyer par e-mail ;
- faire encaisser ces montants par **Mobile Money (MTN, Moov)** via la passerelle **PayPlus** ;
- suivre les encaissements, les soldes restant dus et relancer automatiquement les retardataires.

Devise d'encaissement Mobile Money : XOF (FCFA). Fuseau des tâches planifiées : `Africa/Abidjan`.

## 2. Organisation des données métier

```
Plateforme
└── Institution (établissement, tenant)
    └── Annexe (site / campus)
        ├── Utilisateurs (via une affectation annexe + rôle)
        ├── Catalogue académique : niveaux d'études, filières, barèmes
        └── Étudiants
            └── Inscription annuelle (1 par année scolaire)
            └── Liens de paiement → Échéances → Paiements
```

Chaque institution dispose de son propre calendrier d'**années scolaires** (brouillon → active → clôturée).

## 3. Acteurs

| Acteur | Comment il accède | Ce qu'il peut faire (d'après le code) |
|---|---|---|
| **Administrateur plateforme** | Connexion admin (`scope = platform` ou rôle `platform_admin`) | Accès à toutes les institutions et annexes ; tableaux de bord plateforme (`/platform/*`) ; possède toutes les permissions. |
| **Super administrateur institution** | Connexion admin (`scope = institution` + rôle `super_admin_institution`). Créé automatiquement lors de l'inscription d'une institution. | Toutes les permissions, limitées à **son institution et à toutes ses annexes**. Seul habilité à clôturer une année scolaire et à attribuer le rôle de super admin institution. |
| **Super administrateur annexe** | Connexion admin, rôle `super_admin_annexe` | Toutes les permissions sauf `annexe.create`, `annexe.delete`, `dashboard.multi_annexe`, limitées à ses annexes. |
| **Gestionnaire** | Connexion admin, rôle `gestionnaire` | Étudiants (voir, créer, modifier, importer — pas supprimer), paiements (voir, statistiques), liens (voir, créer, envoyer, annuler), rappels, notifications, tableau de bord. |
| **Comptable** | Connexion admin, rôle `comptable` | Consultation : étudiants, paiements (voir, exporter, statistiques), liens (voir), notifications, tableau de bord. |
| **Étudiant** | Espace étudiant : matricule + code OTP reçu par e-mail | Consulter son profil, sa situation financière, ses liens de paiement et l'historique de ses paiements. |
| **Payeur** | Toute personne détenant l'URL d'un lien de paiement (`/payment/{token}`) — sans compte | Payer tout ou partie du montant restant dû par Mobile Money. Ses nom, e-mail et téléphone sont enregistrés sur le paiement. |
| **PayPlus** (système externe) | Appels API sortants + webhook / URL de retour entrants | Exécute le paiement Mobile Money et confirme son statut. |
| **Planificateur** (système) | Tâches `schedule:run` | Envoie les relances et crée les notifications d'échéance. |

> **Remarque sur les parents** : le code ne contient **aucun compte ni entité « parent »**.
> Un parent peut payer en tant que *payeur* s'il reçoit le lien de paiement. Le job de relance
> lit un champ `parent_email` qui n'existe pas en base : les relances partent donc toujours
> vers l'e-mail de l'étudiant (voir § 6).

Les rôles et permissions sont attribués **par annexe** (un même utilisateur peut avoir un rôle
différent selon l'annexe). Liste des permissions : `student.*` (view, create, edit, delete, import),
`payment.*` (view, manage, export, statistics), `link.*` (view, create, send, cancel),
`notification.*` (view, manage), `reminder.*` (view, create, edit, delete),
`user.*` (view, create, edit, delete, manage_roles, assign_annexe),
`annexe.*` (view, create, edit, delete), `dashboard.*` (view, statistics, multi_annexe).

## 4. Cas d'usage

### UC-01 — Inscrire une institution
- **Acteur** : visiteur (futur super admin institution).
- **Déroulé** : saisie du nom, e-mail et téléphone de l'institution, logo optionnel, nom de l'annexe principale, identité et mot de passe du responsable (`POST /api/register`).
- **Résultat** : création en une transaction de l'institution, de son annexe principale, du compte super admin institution et de l'année scolaire courante active.
- **Règles** : nom d'institution unique (insensible à la casse) ; e-mail d'institution unique ; limité à 5 tentatives par minute.

### UC-02 — Se connecter (administration)
- **Par mot de passe** : `POST /api/admin/login`.
- **Par code OTP** : `POST /api/admin/request-otp` envoie un code à 6 chiffres par e-mail, valable 10 minutes ; `POST /api/admin/verify-otp` le valide.
- **Résultat** : jeton d'accès (Sanctum) valable 12 h par défaut, et liste des annexes actives de l'utilisateur.
- **Règles** : refus si le compte, toutes ses annexes ou son institution sont désactivés ; un code OTP est invalidé après 5 essais erronés ; limitation du nombre de tentatives par minute.

### UC-03 — Gérer les utilisateurs et leurs rôles
- Lister, créer, modifier et supprimer des utilisateurs de son institution ; affecter ou retirer un rôle sur une annexe.
- **Règles** :
  - le rôle `platform_admin` ne peut jamais être attribué via l'API ;
  - le rôle `super_admin_institution` ne peut être attribué que par un super admin institution ;
  - le périmètre (`scope`) d'un utilisateur créé par une institution découle de son rôle ;
  - un utilisateur appartenant à une autre institution ne peut pas être affecté ;
  - un mot de passe aléatoire est généré si aucun n'est fourni.

### UC-04 — Gérer les annexes
- Créer, modifier, désactiver et supprimer des annexes de son institution.
- À la création, le catalogue académique (niveaux, filières, barèmes) de l'annexe courante est **copié** dans la nouvelle annexe.

### UC-05 — Gérer le catalogue académique
- **Niveaux d'études** (code, libellé, ordre de progression) et **filières** : propres à chaque annexe ; code unique par annexe.
- **Barème de scolarité** (`level_fees`) : montant par niveau, filière (optionnelle) et année scolaire, propre à chaque annexe.
  - **Résolution du tarif** : tarif spécifique « niveau + filière + année » s'il existe, sinon tarif générique du niveau (sans filière).
  - **Copie d'une année vers une autre** : les barèmes déjà présents dans l'année cible ne sont pas écrasés.
  - Un barème utilisé par une inscription ne peut pas être supprimé.

### UC-06 — Gérer les étudiants
- Créer, consulter, modifier et supprimer un étudiant (matricule unique, nom, prénom, e-mail, téléphone, filière, photo).
- Si un niveau et une année scolaire sont fournis, une **inscription annuelle** est créée avec le montant de scolarité issu du barème.
- **Import en masse** (Excel/CSV, 5 Mo maximum) : téléchargement d'un modèle, **prévisualisation** avec erreurs par ligne (matricule et e-mail uniques, champs obligatoires), puis import dans l'annexe active.
- **Export** de la liste des étudiants.
- Consultation de la **fiche financière** : montant dû, payé, restant, taux de recouvrement, 10 derniers paiements, historique des inscriptions.
- Ajustement manuel d'une inscription : montant de scolarité (réduction), montant payé, statut, notes.

### UC-07 — Émettre des liens de paiement
- **Lien individuel** (`POST /api/admin/payment-links`) : type (`tuition`, `registration`, `other`…), montant, devise, description, date d'échéance, date d'expiration.
  - Pour la scolarité, le montant est **plafonné au reste dû** de l'inscription de l'année ; refus si tout est déjà payé.
  - Une échéance (tranche 1) du même montant est créée automatiquement.
- **Diffusion groupée** (`POST /api/admin/payment-links/broadcast`) : un lien par étudiant ciblé (tous, une annexe ou une filière, pour une année donnée), avec envoi d'e-mail optionnel.
- **Envoi par e-mail** d'un lien existant, selon quatre types de message : `initial`, `reminder`, `urgent`, `final`. Les trois derniers incrémentent le compteur de relances des échéances.
- Consultation, modification, suppression d'un lien ; historique de communication par étudiant.

### UC-08 — Payer en ligne par Mobile Money
- **Acteur** : payeur, sans compte.
1. Ouverture de `/payment/{token}` : affichage de l'étudiant (nom, matricule), de l'établissement, du montant, des paiements déjà confirmés et du reste dû.
2. Saisie du montant (≤ reste dû), de l'opérateur (MTN ou Moov) et du numéro de téléphone ; identité et e-mail optionnels.
3. `POST /api/payments/public/checkout` : création d'un paiement *en attente* rattaché à la première échéance non soldée, puis déclenchement par PayPlus d'une demande de confirmation sur le téléphone du payeur.
4. **Confirmation** par trois voies :
   - interrogation périodique (`GET /api/payments/check/{reference}`, toutes les 5 s pendant 2 min) ;
   - webhook PayPlus (`POST /api/payplus/webhook`) ;
   - URL de retour (`GET /api/payplus/return`).
- **Règles** :
  - le statut n'est jamais pris dans la requête reçue : il est **re-vérifié auprès de l'API PayPlus** (`completed` → réussi, `notcompleted` → échoué, sinon en attente) ;
  - un paiement réussi n'est plus modifié par ces voies ;
  - lien inactif, expiré ou utilisé → refus ;
  - si PayPlus refuse l'initiation, le paiement en attente est supprimé.
- **Paiement en plusieurs fois** : plusieurs paiements partiels successifs sont possibles sur un même lien jusqu'à son solde ; plusieurs liens de scolarité d'une même année se cumulent sur l'inscription.
- **Effets d'un paiement réussi** :
  - recalcul du montant payé de l'échéance, qui passe en `used` si elle est soldée ;
  - statut du lien (`used` quand tout est soldé) ;
  - montant payé de l'inscription (somme des paiements réussis des liens `tuition` de l'année ; passe en `completed` si soldée) ;
  - notification « Paiement reçu » pour l'annexe.

### UC-09 — Suivre les paiements et les impayés
- Liste des paiements avec filtres : statut, étudiant, lien, période, annexe, année scolaire, recherche (référence, payeur, téléphone, transaction, nom, matricule).
- Widget « derniers paiements ».
- **Tableau de bord** :
  - indicateurs de l'année : montant total, encaissé, impayé, nombre d'étudiants et d'annexes, taux de recouvrement ;
  - encaissements mensuels (septembre → août) ;
  - statistiques par annexe ;
  - vue plateforme (liens créés et utilisés, montants payés et restants).
- **Correction manuelle** d'un statut de paiement (permission `payment.manage`), tracée dans les métadonnées (auteur, date, note) et suivie du recalcul des soldes.

### UC-10 — Relancer automatiquement les retardataires
- Configuration par annexe de rappels « N jours avant l'échéance » (0 à 30) avec un message personnalisable. Variables : `{student_name}`, `{amount}`, `{due_date}`, `{payment_link}`.
- Chaque jour à 8 h, `reminders:send` envoie un e-mail pour chaque échéance active, non soldée, arrivant à échéance dans exactement N jours. Il y a **3 relances au maximum** par échéance, et l'option `--dry-run` permet de simuler l'envoi.
- Actions manuelles : prévisualiser les destinataires, envoyer immédiatement, activer ou désactiver un rappel. Un seul rappel par couple annexe / délai.

### UC-11 — Recevoir des notifications internes
- Notifications par annexe :
  - paiement reçu ;
  - paiement échoué ;
  - échéance dans 3 jours ;
  - échéance dépassée (au plus une par 24 h et par étudiant).
- Les notifications d'échéance sont générées à 9 h et 15 h par `payments:check-due-dates`.
- Consultation, compteur de non-lues, marquage comme lu (une ou toutes), suppression, purge des notifications lues.

### UC-12 — Consulter son espace étudiant
- Connexion : matricule, puis code OTP à 6 chiffres envoyé à l'e-mail de l'étudiant (valable 10 min, 5 essais) ; session de 60 minutes.
- Consultation :
  - profil ;
  - synthèse financière par année (scolarité due, payée, restante ; autres frais) ;
  - liens de paiement avec montant déjà payé et URL de paiement ;
  - historique des paiements confirmés, filtrable par type, période et opérateur.

### UC-13 — Clôturer une année scolaire (promotion)
- **Prévisualisation** : pour chaque inscription active de l'année source, résultat prévu (`promote` vers le niveau suivant, `terminal` pour le dernier niveau, `no_level` sans niveau) et tarif résolu pour l'année cible.
- **Exécution** (super admin institution uniquement) :
  - les inscriptions sources passent en `completed` ;
  - une nouvelle inscription est créée au niveau N+1 pour l'année cible ;
  - l'année source est clôturée et l'année cible activée, **pour l'institution concernée uniquement**.
- **Effet sur les données** : une année clôturée reste consultable en lecture seule (toute écriture est refusée avec le code 423) ; une année en brouillon n'expose pas de données.

### UC-14 — Superviser la plateforme
- Réservé à l'administrateur plateforme :
  - liste et détail des institutions (statistiques par annexe : étudiants, montant attendu, encaissé, liens, taux de recouvrement) ;
  - annexes, utilisateurs et tableau de bord consolidé ;
  - activation et désactivation d'une institution.

## 5. Règles transverses

| # | Règle |
|---|---|
| RG-01 | Isolation des données : un utilisateur ne voit que les données des annexes auxquelles il a accès. Un super admin institution voit toutes les annexes de **son** institution et jamais celles d'une autre. |
| RG-02 | L'annexe active est transmise par l'en-tête `X-Active-Annexe-Id`. Une annexe hors du périmètre de l'utilisateur est refusée (403). |
| RG-03 | L'année scolaire active est transmise par l'en-tête `X-Active-School-Year` (format `AAAA-AAAA`). Une écriture sur une année inconnue ou clôturée est refusée (423). |
| RG-04 | Un compte, une annexe ou une institution désactivés bloquent l'accès (403). |
| RG-05 | Chaque action d'administration est protégée par une permission du rôle de l'utilisateur. |
| RG-06 | Un étudiant a au plus une inscription par année scolaire. |
| RG-07 | Le montant d'un paiement ne peut dépasser le reste dû sur le lien. |
| RG-08 | Seul le statut confirmé par l'API PayPlus fait passer un paiement en réussi ou en échec. |
| RG-09 | Les montants payés (échéance, lien, inscription) sont **recalculés** à partir des paiements réussis, et non incrémentés. |

## 6. Éléments non branchés et écarts constatés

| Constat | Détail |
|---|---|
| Échéancier multi-tranches non exposé | Le modèle supporte plusieurs échéances par lien, mais les routes ne créent qu'une tranche par lien. `InstallmentController` existe mais n'est relié à aucune route. |
| Pas d'e-mail parent | `SendReminderJob` lit `parent_email`, champ absent de la table `students` : les relances partent toujours à l'e-mail de l'étudiant. |
| Mail de retard inutilisé | `OverduePaymentMail` n'est envoyé nulle part (les retards génèrent seulement une notification interne). |
| Inscription sans barème | La création d'étudiant et la promotion tentent d'enregistrer une inscription sans barème (`level_fee_id` nul), alors que la colonne est obligatoire en base : l'opération échoue si aucun barème n'est configuré. |
| Lien de paiement « basique » | `POST /api/admin/students/{id}/payment-link` renvoie une URL `/pay/student/...` qui ne correspond à aucune route (fonction provisoire). |
| Devise par défaut | Un lien individuel sans devise est créé en `USD` ; la diffusion groupée utilise `XOF` ; PayPlus encaisse toujours en `xof`. |
