# Contribuer au projet

Merci de contribuer au site d'Ina Zaoui ! Ce guide décrit comment signaler un problème, proposer une fonctionnalité et faire évoluer le code, les tests et la documentation.

## Sommaire

1. [Signaler un problème](#1-signaler-un-problème)
2. [Proposer une fonctionnalité](#2-proposer-une-fonctionnalité)
3. [Workflow Git](#3-workflow-git)
4. [Conventions de commit](#4-conventions-de-commit)
5. [Pull Requests et revue de code](#5-pull-requests-et-revue-de-code)
6. [Bonnes pratiques de code](#6-bonnes-pratiques-de-code)
7. [Tests](#7-tests)
8. [Analyse statique](#8-analyse-statique)
9. [Documentation](#9-documentation)
10. [Maintenance](#10-maintenance)

## 1. Signaler un problème

Avant d'ouvrir une **Issue**, vérifiez qu'elle n'existe pas déjà. Indiquez ensuite :

- **Titre** court et précis (ex. « L'upload d'un PNG de 1,5 Mo est refusé ») ;
- **Étapes pour reproduire** : URL, compte utilisé (admin ou invité), actions effectuées ;
- **Résultat attendu** et **résultat obtenu** (message d'erreur, capture, extrait de `var/log/dev.log`) ;
- **Environnement** : navigateur, version de PHP, branche ou commit.

Ajoutez le label `bug`. Une faille de sécurité ne doit **pas** être publiée dans une Issue publique : prévenez directement Ina, qui transmettra au développeur.

## 2. Proposer une fonctionnalité

Ouvrez une Issue avec le label `enhancement` qui décrit :

- le **besoin** (« En tant qu'Ina, je veux… afin de… ») ;
- les **critères d'acceptation** (ce qui doit être vrai pour considérer la fonctionnalité terminée) ;
- l'impact éventuel sur la base de données ou les performances.

Attendez la validation d'Ina avant de commencer le développement.

## 3. Workflow Git

- `main` est **toujours déployable** : on ne pousse jamais directement dessus, tout passe par une Pull Request.
- Créez une branche depuis `main`, à jour, nommée `type/description-courte` en kebab-case :

| Préfixe | Usage | Exemple |
|---|---|---|
| `feature/` | nouvelle fonctionnalité | `feature/gestion-invites` |
| `fix/` | correction de bug | `fix/upload-taille-max` |
| `perf/` | optimisation | `perf/page-invites` |
| `test/` | ajout ou amélioration de tests | `test/fixtures-et-tests` |
| `docs/` | documentation | `docs/rapport-performance` |
| `chore/` | dépendances, configuration, outillage | `chore/migration-symfony` |
| `ci/` | intégration continue | `ci/github-actions` |

```bash
git switch main && git pull
git switch -c fix/upload-taille-max
# ... commits ...
git push -u origin fix/upload-taille-max
```

Toute modification du schéma passe par une **migration Doctrine**. Ne modifiez jamais une migration déjà fusionnée.

```bash
php bin/console make:migration      # puis relire le SQL généré
php bin/console doctrine:migrations:migrate
```

## 4. Conventions de commit

Les messages suivent [Conventional Commits](https://www.conventionalcommits.org/fr/) et sont rédigés en français, à l'impératif :

```
<type>(<portée optionnelle>): <résumé en 72 caractères max>

<corps optionnel : le pourquoi, pas le comment>
```

Types : `feat`, `fix`, `perf`, `test`, `docs`, `refactor`, `chore`, `ci`.

Exemples :

```
feat(admin): ajoute le blocage d'un invité
fix(upload): vérifie le type MIME réel du fichier
perf: corrige la lenteur de la page « Invités » (N+1 requêtes)
```

Un commit doit correspondre à **une** modification cohérente, et chaque commit doit laisser les tests au vert.

## 5. Pull Requests et revue de code

1. Ouvrez une PR vers `main` et reliez l'Issue (`Closes #12`).
2. Décrivez **ce qui change**, **pourquoi**, et **comment tester** (captures si l'interface change).
3. La **CI doit être verte** : installation, audit, migrations, lints, PHPStan, tests et couverture d'au moins 70 %.
4. Au moins **une relecture approuvée** est nécessaire avant la fusion. Le relecteur vérifie :
   - que le besoin est couvert et que les critères d'acceptation sont remplis ;
   - la sécurité : droits (`ROLE_ADMIN`, `MediaVoter`), CSRF sur les actions `POST`, validation des données ;
   - les performances : pas de requête dans une boucle ni de N+1 (vérifier le profiler) ;
   - la présence de tests pour le nouveau comportement ;
   - la lisibilité et le respect des conventions.
5. L'auteur traite chaque commentaire (correction ou réponse argumentée), puis fusionne en **merge commit** (`--no-ff`) et supprime la branche.

## 6. Bonnes pratiques de code

- Respecter les [bonnes pratiques Symfony](https://symfony.com/doc/current/best_practices.html) et le style **PSR-12**.
- **Contrôleurs fins** : la logique va dans des services (`Service/`) ou des repositories, et les dépendances sont injectées (jamais `getDoctrine()`).
- Pas de requête dans les templates Twig : préparez les données (comptages, jointures) dans le repository.
- Toute action qui modifie des données passe en `POST` avec un jeton CSRF.
- Validation déclarée avec des contraintes (`#[Assert\...]`), messages en français.
- Typage strict : types de paramètres et de retour, PHPDoc pour les collections (`Collection<int, Media>`).
- Aucun secret dans le dépôt : utilisez `.env.local` ou les secrets Symfony.

## 7. Tests

- **Tout correctif de bug** est accompagné d'un test qui échouait avant le correctif.
- **Toute nouvelle fonctionnalité** est couverte par des tests fonctionnels (`tests/Functional`) et, pour la logique isolée, par des tests unitaires (`tests/Unit`).
- Si un scénario manque, enrichissez les fixtures (`src/DataFixtures/AppFixtures.php`).
- La couverture globale doit rester **au-dessus de 70 %** (actuellement 97 %).

```bash
vendor/bin/phpunit                    # avant chaque push
vendor/bin/phpunit --filter Guest     # cibler un test
```

## 8. Analyse statique

Avant d'ouvrir une PR, lancez :

```bash
vendor/bin/phpstan analyse            # niveau 8, doit afficher « No errors »
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console lint:container
composer audit
```

N'ajoutez pas d'erreur PHPStan à ignorer sans l'avoir justifiée dans la PR.

## 9. Documentation

- Mettez à jour le **README** si l'installation, les commandes ou l'usage changent.
- Documentez les choix non évidents par un commentaire **qui explique le pourquoi**.
- Après une optimisation, complétez le rapport de performance (`docs/performance/`) avec les mesures avant/après.

## 10. Maintenance

- **Dépendances** : lancez `composer outdated` puis `composer update` une fois par mois, et `composer audit` à chaque PR (déjà fait par la CI).
- **Symfony** : suivez la [roadmap](https://symfony.com/releases). Prévoyez le passage en 8.2 (fin novembre 2026), puis en **8.4 LTS** (novembre 2027), avec la même méthode qu'au cours de la migration : corriger les dépréciations, puis changer de version.
- **Base de données** : sauvegardez avant chaque migration en production.
- **Uploads** : `public/uploads` n'est pas versionné. Il doit être sauvegardé avec la base.
