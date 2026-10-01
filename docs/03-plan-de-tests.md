# Plan de tests (recette) — SplitPay

> Liste des cas à valider avant de considérer une fonctionnalité comme **terminée**.
> Les numéros de cas d'usage (UC-xx) et de règles (RG-xx) renvoient à
> [01-specifications-fonctionnelles.md](01-specifications-fonctionnelles.md).

## Conditions d'exécution

- **Jeu de données** :
  - deux institutions A et B ; A possède deux annexes (A1, A2), B une annexe (B1) ;
  - un compte par rôle (admin plateforme, super admin institution A, super admin annexe A1, gestionnaire A1, comptable A1) ;
  - des étudiants dans chaque annexe et un barème configuré pour l'année active.
- **PayPlus** : mode test ou service simulé (mock) pour les tests automatisés.
- **Tests automatisés** : `php artisan test`, sur base MySQL (certaines migrations ne sont pas compatibles SQLite).
- Un cas est **validé** si le résultat observé correspond exactement au résultat attendu (code HTTP, données et effets en base).

## 1. Inscription et authentification

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Inscription institution (UC-01) | Inscription avec des données valides | 201 ; institution, annexe principale, compte super admin (scope `institution`) et année scolaire courante `active` créés |
| Inscription institution | Nom d'institution déjà utilisé (casse différente) | 422 « Ce nom d'institution existe déjà » |
| Inscription institution | E-mail d'institution déjà utilisé | 422 |
| Inscription institution | 6e tentative dans la même minute | 429 (limitation de débit) |
| Connexion admin (UC-02) | Bon e-mail et bon mot de passe | 200, jeton, date d'expiration (12 h), annexes actives |
| Connexion admin | Mauvais mot de passe / e-mail inconnu | 401 « Email ou mot de passe incorrect » (message identique dans les deux cas) |
| Connexion admin | Compte désactivé, ou toutes ses annexes / son institution désactivées | 403 |
| Connexion admin OTP | Demande de code pour un compte actif | 202, e-mail envoyé avec un code à 6 chiffres |
| Connexion admin OTP | Code correct dans les 10 minutes | 200 et jeton |
| Connexion admin OTP | Code erroné 5 fois, puis code correct | Les 5 essais → 401 ; le code correct est ensuite refusé (401), il faut redemander un code |
| Connexion admin OTP | Code utilisé une seconde fois | 401 (code à usage unique) |
| Session admin | Appel d'une route protégée sans jeton ou avec un jeton expiré | 401 |
| Déconnexion | `logout` puis réutilisation du jeton | 401 |

## 2. Isolation des données et droits (RG-01 à RG-05)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Périmètre institution | Super admin A liste les étudiants | Étudiants de A1 **et** A2 ; aucun étudiant de B |
| Périmètre institution | Super admin A ouvre la fiche d'un étudiant de B | 404 |
| Périmètre institution | Super admin A liste les paiements | Uniquement les paiements des étudiants de A |
| Périmètre institution | Super admin A consulte l'institution B | 404 |
| Annexe active | En-tête `X-Active-Annexe-Id` = A2 (super admin A) | 200 |
| Annexe active | En-tête `X-Active-Annexe-Id` = B1 (super admin A) | 403 `unauthorized_annexe_access` |
| Annexe active | Gestionnaire A1 avec en-tête = A2 (non affecté) | 403 |
| Périmètre annexe | Gestionnaire A1 liste les étudiants | Uniquement ceux de A1 |
| Permissions | Comptable tente de créer un étudiant | 403 « Permission manquante: student.create » |
| Permissions | Gestionnaire tente de supprimer un étudiant | 403 |
| Permissions | Comptable tente de forcer le statut d'un paiement | 403 (permission `payment.manage` requise) ; bouton masqué dans l'interface |
| Admin plateforme | Accès aux routes `/platform/*` et aux données de toutes les institutions | Autorisé |
| Admin plateforme | Un utilisateur non plateforme ouvre `/platform` | Redirigé vers le tableau de bord |

## 3. Utilisateurs et annexes

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Création utilisateur (UC-03) | Super admin A crée un gestionnaire sur A1 | 201 ; scope `annexe` ; affectation A1 / gestionnaire principale |
| Création utilisateur | Envoi de `scope = platform` par un super admin institution | Le scope est ignoré : l'utilisateur est créé en `annexe` (ou `institution` si le rôle est super admin institution) |
| Création utilisateur | Rôle `platform_admin` demandé | 403 |
| Création utilisateur | Super admin annexe attribue le rôle `super_admin_institution` | 403 |
| Création utilisateur | Annexe d'une autre institution | 403 « Annexe hors de votre institution » |
| Modification utilisateur | Changement d'`annexe_id` vers une annexe de B | 403 |
| Affectation de rôle | Affecter un utilisateur appartenant à l'institution B | 403 « Utilisateur hors de votre institution » |
| Affectation de rôle | Affecter un utilisateur sans aucune annexe | 200 |
| Annexes (UC-04) | Création d'une annexe | 201 ; niveaux, filières et barèmes de l'annexe courante copiés |
| Annexes | Désactivation d'une annexe unique d'un gestionnaire | Le gestionnaire ne peut plus se connecter (403) |

## 4. Catalogue académique et barèmes (UC-05)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Niveaux d'études | Création d'un code déjà existant dans la même annexe | 422 |
| Niveaux d'études | Même code dans une autre annexe | 201 (unicité par annexe) |
| Filières | Création, modification, suppression dans l'annexe active | Opérations limitées à l'annexe active |
| Barème | Création pour niveau + filière + année | 201 |
| Barème | Niveau appartenant à une autre annexe | 422 |
| Résolution de tarif | Tarif spécifique « niveau + filière » existant | Tarif spécifique retourné (`matched_on = specific`) |
| Résolution de tarif | Pas de tarif spécifique, tarif générique existant | Tarif générique retourné (`matched_on = generic`) |
| Résolution de tarif | Aucun tarif | 404 « Aucun tarif configuré » |
| Copie d'année | Copie 2025-2026 → 2026-2027 avec un barème déjà présent en cible | Barèmes manquants créés, existants ignorés (`created` / `skipped` corrects) |
| Suppression barème | Barème utilisé par une inscription | 422, barème conservé |

## 5. Étudiants et inscriptions (UC-06)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Création étudiant | Données valides avec niveau et année ayant un barème | 201 ; inscription `active` créée avec `tuition_amount` = montant du barème |
| Création étudiant | Matricule déjà existant (même dans une autre institution) | 422 (matricule unique sur la plateforme) |
| Création étudiant | Niveau et année **sans** barème configuré | **Écart connu** : l'inscription ne peut être créée (`level_fee_id` obligatoire en base) → erreur serveur. Attendu fonctionnellement : message explicite invitant à configurer le barème |
| Modification étudiant | Changement de niveau pour une année existante | Inscription mise à jour (barème, montant), `amount_paid` conservé |
| Fiche financière | Étudiant avec paiements réussis et en attente | Montant payé = somme des paiements **réussis** ; restant = dû − payé ; taux de recouvrement correct |
| Ajustement inscription | Réduction du `tuition_amount` | Montant restant recalculé |
| Import (prévisualisation) | Fichier avec une ligne sans e-mail et un matricule en doublon | Lignes en erreur signalées avec leur numéro ; aucune écriture en base |
| Import | Fichier valide (≤ 5 Mo, xlsx/xls/csv) | Étudiants créés dans l'annexe active |
| Import | Fichier > 5 Mo ou format non supporté | 422 |
| Export | Export depuis une annexe | Uniquement les étudiants du périmètre de l'utilisateur |

## 6. Liens de paiement (UC-07)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Création lien scolarité | Montant supérieur au reste dû de l'inscription | Lien créé avec un montant plafonné au reste dû |
| Création lien scolarité | Scolarité déjà entièrement payée | 422 « already fully paid » |
| Création lien | Étudiant d'une annexe hors périmètre | 403 ou 404 |
| Création lien | Création réussie | Lien `active`, token unique de 64 caractères, échéance n° 1 du même montant |
| Diffusion groupée | Cible « filière X » pour l'année active | Un lien par étudiant concerné ; compteur `created` = nombre d'étudiants |
| Diffusion groupée | Cible sans étudiant | 422 « No students found » |
| Envoi e-mail | Type `reminder` | E-mail « Rappel » envoyé ; `sent_at` renseigné ; `reminder_count` des échéances actives + 1 |
| Envoi e-mail | Étudiant sans e-mail et aucun e-mail fourni | 422 « No target email available » |
| Modification lien | Changement de description et de date d'expiration | Lien mis à jour et renvoyé |
| Page publique | Ouverture d'un token valide | Étudiant (nom, matricule), établissement, montant, paiements confirmés ; **aucun** token PayPlus ni métadonnée exposés |
| Page publique | Token inconnu ou lien `used` / `expired` | 404 |

## 7. Paiement Mobile Money (UC-08)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Checkout | Montant ≤ reste dû, MTN, numéro valide | 201 ; paiement `pending` rattaché à la première échéance non soldée ; référence `PAY-…` retournée |
| Checkout | Montant > reste dû | 422, aucun paiement créé |
| Checkout | Lien expiré (`expire_at` dépassé) ou déjà utilisé | 422 |
| Checkout | Opérateur autre que `mtn` / `moov` | 422 |
| Checkout | PayPlus refuse l'initiation | 422 avec le message PayPlus ; le paiement `pending` est supprimé |
| Checkout | 11e tentative dans la même minute | 429 |
| Confirmation (polling) | PayPlus répond `completed` | Statut `success`, `paid_at` renseigné, notification « Paiement reçu » |
| Confirmation (polling) | PayPlus répond `notcompleted` | Statut `failed`, notification « Paiement échoué » |
| Confirmation (polling) | PayPlus répond `pending` | Statut inchangé (`pending`) |
| Webhook | Webhook reçu, PayPlus confirme `completed` | 200 ; paiement `success` ; soldes recalculés |
| Webhook | **Webhook forgé** (`response_code = 00`) alors que PayPlus indique `pending` | 200 `status = pending` ; paiement **non** validé ; lien toujours `active` |
| Webhook | Token absent / inconnu | 400 / 404 |
| Webhook | API PayPlus injoignable | 502 (PayPlus pourra renvoyer le webhook) ; paiement inchangé |
| Idempotence | Webhook puis polling pour un paiement déjà `success` | Aucun doublon : montants et notifications inchangés |
| URL de retour | Retour avec un paiement confirmé | Redirection vers `FRONT_SUCCESS_URL?reference=…` |
| URL de retour | Retour alors que le paiement est encore en attente | Redirection vers `FRONT_FAILED_URL` avec `status=pending` ; paiement **non** marqué échoué |
| Paiement en plusieurs fois | Deux paiements partiels (300 puis 500) sur un lien de 800 | Après le 1er : échéance `amount_paid` = 300, lien `active` ; après le 2nd : échéance et lien `used` |
| Cumul inscription | Deux liens `tuition` de la même année payés | `enrollments.amount_paid` = somme des deux ; passage en `completed` si le total atteint la scolarité |
| Cumul inscription | Paiement sur un lien `other` ou d'une autre année | Sans effet sur l'inscription de l'année |

## 8. Suivi des paiements et impayés (UC-09)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Liste des paiements | Filtres statut + période + annexe | Seuls les paiements correspondants, dans le périmètre de l'utilisateur |
| Liste des paiements | Recherche par matricule ou téléphone du payeur | Paiements correspondants |
| Détail d'un paiement | `GET /api/admin/payments/{id}` | Paiement avec étudiant, lien et échéance |
| Correction manuelle | Passage `pending` → `success` avec une note | Statut modifié ; auteur, date et note enregistrés dans `metadata` ; soldes recalculés |
| Correction manuelle | Passage `success` → `failed` | Soldes diminués ; un lien `used` repasse `active` ; un lien `expired` reste `expired` |
| Tableau de bord | Indicateurs de l'année active | Montant attendu, encaissé, impayé et taux cohérents avec les inscriptions |
| Tableau de bord | Encaissements mensuels | 12 mois de septembre à août ; sommes par mois cohérentes avec `paid_at` |

## 9. Relances et notifications (UC-10, UC-11)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Création de rappel | Délai 7 j sur son annexe | 201 |
| Création de rappel | Même délai déjà existant pour l'annexe | 422 |
| Création de rappel | Annexe hors périmètre | 403 |
| Prévisualisation | Échéance non soldée due dans 7 jours | L'étudiant apparaît avec le message personnalisé (variables remplacées) |
| Envoi planifié | `php artisan reminders:send --dry-run` | Liste des destinataires, aucun e-mail envoyé |
| Envoi planifié | `php artisan reminders:send` | E-mail envoyé ; `reminder_count` + 1 ; `last_reminder_sent_at` renseigné |
| Plafond de relances | Échéance ayant déjà 3 relances | Non sélectionnée |
| Échéance soldée ou inactive | Échéance payée due dans 7 jours | Non sélectionnée |
| Notifications d'échéance | `payments:check-due-dates` avec une échéance à J+3 et une en retard | Notifications « Échéance proche » et « Échéance dépassée » créées pour l'annexe |
| Anti-doublon retard | Relance de la commande dans les 24 h | Pas de seconde notification de retard pour le même étudiant |
| Notifications | Marquer tout comme lu / purger les lues | Seules les notifications du périmètre sont affectées |

## 10. Espace étudiant (UC-12)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Demande OTP | Matricule existant avec e-mail | 202, code envoyé |
| Demande OTP | Matricule inconnu | 404 |
| Vérification OTP | Code correct | 200, jeton de session valable 60 min |
| Vérification OTP | 5 codes erronés puis code correct | Code invalidé : 401 |
| Accès espace | Jeton expiré ou absent | 401 |
| Profil | Étudiant avec scolarité partiellement payée | Scolarité due, payée, restante exactes ; autres frais calculés à partir des liens non `tuition` |
| Historique | Filtre opérateur `moov` | Uniquement les paiements réussis via Moov de l'étudiant |
| Cloisonnement | Jeton de l'étudiant X | Ne renvoie jamais les données d'un autre étudiant |

## 11. Années scolaires et promotion (UC-13)

| Fonctionnalité | Cas testé | Résultat attendu |
|---|---|---|
| Lecture seule | Écriture (POST/PUT/DELETE) sur une année `closed` | 423 `school_year_read_only` |
| Année inconnue | Écriture avec une année explicite inexistante | 423 `school_year_not_available` |
| Année obsolète en en-tête | Lecture avec une année d'en-tête inexistante | Bascule sur l'année active ; en-tête `X-Effective-School-Year` renvoyé |
| Prévisualisation promotion | Inscriptions L1, L3 (dernier niveau) et sans niveau | Résultats `promote`, `terminal`, `no_level` ; tarif N+1 indiqué, ou `fee_missing` |
| Exécution promotion | Par un gestionnaire ou un super admin annexe | 403 |
| Exécution promotion | Par le super admin A | Inscriptions sources `completed` ; inscriptions N+1 créées ; année source `closed`, année cible `active` **pour A uniquement** |
| Isolation calendrier | Après la clôture par A, écriture par un utilisateur de B sur son année active | Autorisée : le calendrier de B est inchangé |
| Promotion sans barème N+1 | Niveau suivant sans tarif pour l'année cible | **Écart connu** : l'inscription N+1 ne peut être créée (`level_fee_id` obligatoire) et l'étudiant apparaît dans `errors` |

## 12. Tests automatisés existants

| Fichier | Couverture |
|---|---|
| `tests/Feature/TenantIsolationTest.php` | Périmètre institution (étudiants, paiements, annexe active, institution), page publique sans données PayPlus |
| `tests/Feature/WebhookTest.php` | Recalcul de l'inscription, échec confirmé, webhook forgé, checkout puis solde de l'échéance et du lien, plafond du reste dû |
| `tests/Feature/PaymentFlowsTest.php` | Consultation publique d'un lien, checkout |
| `tests/Feature/ApiRoutesTest.php` | Ping, inscription d'institution, connexion admin |
| `tests/Feature/RolePolicyTest.php` | Affectation et retrait de rôle |
| `tests/Feature/DatabaseCoherenceTest.php` | Cohérence du jeu de données de développement (compteurs à mettre à jour selon les seeders actuels) |
