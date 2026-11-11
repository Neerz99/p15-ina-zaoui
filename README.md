# Ina Zaoui — site de la photographe

Site vitrine de la photographe Ina Zaoui. Le site présente son portfolio par album et met en avant de jeunes photographes invités. Un espace d'administration permet à Ina de gérer ses albums, ses médias et les invités. Chaque invité peut gérer ses propres photos.

[![CI](../../actions/workflows/ci.yml/badge.svg)](../../actions/workflows/ci.yml)

## Sommaire

1. [Pré-requis](#pré-requis)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Tests et qualité](#tests-et-qualité)
5. [Fonctionnement du code](#fonctionnement-du-code)
6. [Choix techniques](#choix-techniques)
7. [Performance](#performance)
8. [Contribuer](#contribuer)

## Pré-requis

| Outil | Version | Rôle |
|---|---|---|
| PHP | **8.4** ou plus | extensions `ctype`, `iconv`, `intl`, `pdo_pgsql`, `gd` (gd sert aux tests d'upload) |
| Composer | 2.x | dépendances PHP |
| PostgreSQL | 16 | base de données (fournie par Docker) |
| Docker + Docker Compose | récent | lance PostgreSQL via `compose.yaml` |
| Symfony CLI | optionnel | serveur local (`symfony serve`) |
| Extension PCOV ou Xdebug | optionnel | rapport de couverture des tests |

## Installation

```bash
# 1. Récupérer le projet
git clone https://github.com/<votre-compte>/<repo>.git ina-zaoui
cd ina-zaoui

# 2. Installer les dépendances
composer install

# 3. Lancer PostgreSQL 16 (port 5432, utilisateur/mot de passe : postgres/postgres)
docker compose up -d

# 4. Créer la base et le schéma
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

Les variables d'environnement se trouvent dans `.env`. Pour les surcharger sur votre poste (autre port, autre mot de passe…), créez un fichier `.env.local`, qui n'est pas versionné :

```dotenv
DATABASE_URL="postgresql://postgres:postgres@127.0.0.1:5432/ina_zaoui?serverVersion=16&charset=utf8"
```

### Option A : données de démonstration (fixtures)

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

Comptes créés, tous avec le mot de passe `password` :

| Compte | E-mail | Rôle |
|---|---|---|
| Ina Zaoui | `ina@zaoui.com` | administratrice |
| Invité Actif | `actif@example.com` | invité |
| Invité Bloqué | `bloque@example.com` | invité bloqué (connexion refusée) |

### Option B : données réelles (backup)

Le fichier `backup.zip` (plus de 1 Go, non versionné) contient un dump SQL anonymisé (`album.sql`, `user.sql`, `media.sql`) et les images de `public/uploads`. Le dump date d'avant l'ajout des mots de passe. Il faut donc l'importer sur le schéma initial, puis appliquer les migrations suivantes :

```bash
php bin/console doctrine:database:drop --force --if-exists
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate 'DoctrineMigrations\Version20261111135632' --no-interaction

# import du dump (dans l'ordre : albums, utilisateurs, médias)
docker compose exec -T database psql -U postgres -d ina_zaoui < backup/album.sql
docker compose exec -T database psql -U postgres -d ina_zaoui < backup/user.sql
docker compose exec -T database psql -U postgres -d ina_zaoui < backup/media.sql

# migrations restantes, puis remise à niveau des compteurs d'identifiants
php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T database psql -U postgres -d ina_zaoui < docs/sql/reset-identities.sql
```

Copiez ensuite les images du backup dans `public/uploads/`. Ce dossier est ignoré par Git.

> Tous les comptes importés reçoivent le mot de passe provisoire `password`, avec l'e-mail comme identifiant (Ina : `ina@zaoui.com`). **Changez-les avant toute mise en production.**

### Lancer le serveur

```bash
symfony serve -d          # ou : php -S 127.0.0.1:8000 -t public
```

Le site est alors disponible sur http://127.0.0.1:8000.

## Usage

### Site public (Front Office)

| URL | Page |
|---|---|
| `/` | accueil |
| `/portfolio` et `/portfolio/{album}` | photos d'Ina, filtrables par album |
| `/guests` | liste des invités actifs avec leur nombre de photos |
| `/guest/{id}` | page d'un invité (404 s'il est bloqué) |
| `/about` | « Qui suis-je ? » |

### Administration (`/login`, puis `/admin`)

La connexion se fait avec l'**adresse e-mail** et le mot de passe.

| Fonction | Ina (`ROLE_ADMIN`) | Invité (`ROLE_USER`) |
|---|---|---|
| Médias : lister, ajouter, supprimer | tous les médias | uniquement les siens |
| Albums : créer, renommer, supprimer | ✅ | ❌ (403) |
| Invités : lister (statut), ajouter, bloquer/débloquer, supprimer | ✅ | ❌ (403) |

Ce qu'il faut savoir sur ces fonctions :

- **Upload** : seules les images JPEG, PNG, WebP et GIF de **2 Mo maximum** sont acceptées. Le type est vérifié par son type MIME réel, pas par l'extension.
- **Bloquer un invité** : il ne peut plus se connecter, sa session en cours est coupée et ses photos disparaissent du site. Le débloquer rétablit tout.
- **Supprimer un invité** : ses médias sont supprimés en base (cascade) et sur le disque.

## Tests et qualité

```bash
# base de test (une seule fois) : ina_zaoui_test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/console doctrine:fixtures:load --env=test --no-interaction

# lancer les tests
vendor/bin/phpunit                         # toute la suite
vendor/bin/phpunit --testsuite unit        # tests unitaires seulement
vendor/bin/phpunit --testsuite functional  # tests fonctionnels seulement
vendor/bin/phpunit --testdox               # sortie lisible

# couverture (nécessite PCOV ou Xdebug)
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html var/coverage

# analyse statique et lints
vendor/bin/phpstan analyse                 # PHPStan niveau 8 (symfony + doctrine)
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console lint:container
composer audit                             # failles connues dans les dépendances
```

- **74 tests** (unitaires et fonctionnels) et **97 % de lignes couvertes**. Les rapports sont dans [`docs/tests/`](docs/tests) : couverture texte et HTML, testdox, JUnit.
- Chaque test s'exécute dans une transaction annulée à la fin (DAMADoctrineTestBundle), donc les fixtures restent intactes.
- La CI GitHub Actions ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) rejoue à chaque push et pull request : installation, audit, migrations, lints, PHPStan, tests et seuil de couverture de 70 %.

## Fonctionnement du code

```
src/
├── Controller/
│   ├── HomeController.php          # pages publiques
│   └── Admin/
│       ├── SecurityController.php  # /login, /logout
│       ├── MediaController.php     # médias (Ina + invités)
│       ├── AlbumController.php     # albums (Ina)
│       └── GuestController.php     # gestion des invités (Ina)
├── Entity/          # User (Ina + invités), Media, Album
├── Repository/      # requêtes Doctrine optimisées
├── Form/            # MediaType, AlbumType, GuestType
├── Security/
│   ├── UserChecker.php             # refuse la connexion d'un invité bloqué
│   └── Voter/MediaVoter.php        # droit de gérer un média (propriétaire ou Ina)
├── Service/MediaUploader.php       # enregistrement / suppression des fichiers
└── DataFixtures/AppFixtures.php    # jeu de données de test
```

Le modèle de données :

```
User (id, name, email, password, admin, blocked, description)
  1 ──── * Media (id, title, path, user_id ON DELETE CASCADE, album_id ON DELETE SET NULL)
                     * ──── 0..1 Album (id, name)
```

Quelques points clés :

- **Authentification** : un provider `entity` charge les utilisateurs depuis la base (`security.yaml`, propriété `email`) et les mots de passe sont hashés (algorithme `auto`). Le rôle est déduit du booléen `admin`. `User` implémente `EquatableInterface` : si Ina bloque un invité connecté, sa session est invalidée dès la requête suivante.
- **Autorisations** : `access_control` réserve `/admin/album` et `/admin/guest` à `ROLE_ADMIN`. Le `MediaVoter` vérifie qu'un invité ne supprime que ses propres médias. Toutes les suppressions et tous les blocages passent par des formulaires `POST` protégés par jeton CSRF (`#[IsCsrfTokenValid]`).
- **Upload** : la contrainte `#[Assert\Image]` sur `Media::$file` vérifie le type MIME et la taille (2 Mo). `MediaUploader` range le fichier dans `public/uploads` sous un nom aléatoire. En test, le dossier est redirigé vers `var/test-public` (paramètre `app.public_dir`).
- **Page Invités** : `UserRepository::findGuestsWithMediaCount()` récupère les invités et leur nombre de photos en **une seule requête** (`COUNT` + `GROUP BY`).

## Choix techniques

- **Symfony 8.1** (dernière version stable) sur PHP 8.4. La migration depuis la 5.4 s'est faite par étapes, une version majeure à la fois (5.4 → 6.4 LTS → 7.4 LTS → 8.1), en corrigeant les dépréciations à chaque palier : routes en attributs, suppression de `getDoctrine()`, injection des repositories, DBAL 4 et DoctrineBundle 3. Symfony 8.1 est maintenue jusqu'en janvier 2027. La montée en 8.2, puis en 8.4 LTS (novembre 2027), sera une mise à jour mineure.
- **Migrations Doctrine** versionnées : le projet d'origine n'en avait aucune.
- **PostgreSQL via Docker** : même version en local et en CI.
- **PHPUnit 13 + DAMADoctrineTestBundle** : tests isolés et rapides (environ 10 s).
- **PHPStan niveau 8** avec les extensions Symfony et Doctrine.

## Performance

Le rapport complet, avec la méthode, les indicateurs, les mesures avant/après sur toutes les pages du Front Office et les recommandations, se trouve ici : [`docs/performance/Rapport_performance_Ina_Zaoui.pdf`](docs/performance/Rapport_performance_Ina_Zaoui.pdf).

En résumé, la page Invités passe de **101 requêtes SQL à 1**, et son temps de réponse baisse de 41 % en production.

## Contribuer

Les conventions (branches, commits, pull requests, revue, tests, analyse statique) sont décrites dans [CONTRIBUTING.md](CONTRIBUTING.md).
