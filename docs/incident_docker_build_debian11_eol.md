# Incident : échec de publication de l'image Docker Claroline

## Résumé

La publication de l'image Docker `ghcr.io/konsept-ch/claroline` échoue durant
l'installation des paquets système, avant l'installation de Composer et avant
la compilation des assets front-end.

La cause principale est l'obsolescence de la plate-forme de construction : le
Dockerfile part de `php:8.0-apache`, une image basée sur Debian 11 (Bullseye).
Debian 11 a atteint sa fin de support le **31 août 2026**. Les métadonnées APT
encore accessibles et les archives de sécurité ne sont plus cohérentes : APT
demande donc des versions retirées du dépôt `debian-security` et reçoit des
réponses HTTP 404.

Les avertissements GitHub Actions sur Node 20 et `save-state` sont à traiter,
mais ils ne sont pas la cause de l'échec actuel.

## Symptômes observés

Le workflow `Docker Publish` appelle
`.docker/web/Dockerfile`. La commande en erreur est :

```dockerfile
RUN apt-get update && apt-get install -y ... && rm -rf /var/lib/apt/lists/*
```

Elle s'arrête avec le code 100 et, notamment :

```text
E: Failed to fetch .../debian-security/.../icu-devtools_67.1-7+deb11u1... 404 Not Found
E: Failed to fetch .../debian-security/.../libcurl4-gnutls-dev_7.74.0-1.3+deb11u16... 404 Not Found
E: Unable to fetch some archives
```

Un `404` dans ce contexte signifie que l'index APT sélectionne une version qui
n'est plus présente à l'URL de téléchargement. Relancer un build peut masquer
le problème si les miroirs se réalignent, mais ne remet ni les paquets ni leur
support de sécurité sous garantie.

## Ce qui n'est pas la cause immédiate

| Message | Interprétation | Action |
| --- | --- | --- |
| `Node 20 is being deprecated` | Les actions JavaScript du workflow sont désormais exécutées par le runner avec Node 24. | Mettre à jour les actions GitHub ; ne pas définir la dérogation Node 20 sauf diagnostic ponctuel. |
| ``save-state` command is deprecated` | Une action utilisée par le workflow (ou une dépendance transitive) emploie une API GitHub Actions obsolète. | Mettre à niveau les actions concernées, en conservant un SHA immuable. |
| `apt-get ... exit code: 100` | C'est l'erreur bloquante. | Faire évoluer l'image et les dépendances de build. |

Le changement Node 24 ne change pas le `nodejs` installé *dans* l'image : ce
dernier est installé par APT durant `docker build`.

## Facteurs techniques à corriger

Le Dockerfile contient plusieurs composants arrivés en fin de vie ou très
anciens :

- PHP 8.0 (`php:8.0-apache`), plus maintenu en amont ;
- Debian 11, fin de support au 31 août 2026 ;
- Node.js 16, configuré via `https://deb.nodesource.com/setup_16.x`, lui aussi
  en fin de vie ;
- Composer 2.3 ;
- des actions GitHub anciennes (`actions/checkout@v2`, actions Docker épinglées
  sur d'anciens commits).

Le tag Docker non épinglé par digest rend en outre le build moins reproductible
et masque le moment où la base change.

## Correctif recommandé

Faire une mise à niveau contrôlée de la chaîne complète, sur une branche dédiée.
L'ordre proposé limite les variables :

1. Faire évoluer Claroline et ses dépendances vers une version de PHP encore
   supportée (cible à valider : PHP 8.2 ou 8.3), puis passer à une image Apache
   correspondante basée sur une distribution supportée.
2. Remplacer Node.js 16 par une version LTS compatible avec les outils de build
   du projet. Tester `npm ci`/`npm install`, puis `npm run webpack` avec le lock
   file existant avant toute mise à jour majeure des dépendances JavaScript.
3. Remplacer l'installation par script `setup_16.x` par une installation Node
   explicite et maintenue, ou par une étape de build dédiée `node:<version>`.
   Une construction multi-stage sépare proprement les assets du runtime PHP.
4. Mettre à jour Composer dans la même fenêtre de validation et remplacer
   `npm install` par `npm ci` si un `package-lock.json` fiable est présent.
5. Mettre à niveau `checkout`, `login-action`, `metadata-action` et
   `build-push-action` vers leurs versions actuelles, épinglées par SHA, puis
   activer Dependabot ou Renovate pour maintenir ces épingles.
6. Tester l'image localement et dans un environnement de validation : démarrage
   Apache, migrations, connexion MariaDB, parcours métier critique et rendu des
   assets compilés.
7. Épingler l'image de base par digest après validation pour des builds
   reproductibles ; planifier explicitement ses futures mises à jour.

## Mesure de continuité (temporaire)

Si une image doit absolument être produite avant la migration, utiliser un
snapshot Debian daté et vérifié ou un registre interne contenant les paquets
Bullseye nécessaires. Cette mesure doit être limitée dans le temps : elle ne
fournit pas de correctifs de sécurité pour PHP 8.0, Node 16 ou Debian 11.

### Mise en oeuvre retenue

Le Dockerfile utilise `snapshot.debian.org` au point
`20260831T235959Z`, soit le dernier jour de support de Bullseye. Cette source
est définie avant toute commande APT et remplace les sources de l'image de base.
Elle évite les 404 dus aux paquets déplacés, tout en gardant exactement les
versions de paquets historiques. Le paramètre de build
`DEBIAN_SNAPSHOT` permet d'auditer ou de changer explicitement ce point dans le
temps.

Cette modification n'altère ni le code PHP, ni la base de données, ni les
flux de connexion. Elle doit être retirée lors du passage à une image PHP et
Debian maintenue.

Ne pas considérer les solutions suivantes comme un correctif :

- ajouter `--fix-missing` ;
- relancer le workflow jusqu'à ce qu'il passe ;
- conserver Node 20 par `ACTIONS_ALLOW_USE_UNSECURE_NODE_VERSION=true` ;
- désactiver les contrôles APT ou ignorer les erreurs de téléchargement.

## Vérification après correction

Le workflow doit :

- achever l'étape `apt-get install` sans 404 ni code 100 ;
- compiler les assets sans dépendre de Node 16 ;
- construire et publier les tags de release et `latest` ;
- ne plus afficher les avertissements de compatibilité Node 20 ou `save-state`
  après la mise à jour des actions ;
- pouvoir être reconstruit à partir des mêmes digests et fichiers de lock.

## Références

- [Fin de support de Debian 11 (Bullseye)](https://www.debian.org/News/2026/20260831)
- [Dépréciation de Node 20 dans GitHub Actions](https://github.blog/changelog/2025-09-19-deprecation-of-node-20-on-github-actions-runners/)
