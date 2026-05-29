# Diagnostic page blanche Claroline

## Résumé court

Le problème venait d’un chargement cassé du bootstrap JavaScript de Claroline.

Deux symptômes se mélangeaient :

1. Le front chargeait encore des assets ou des endpoints obsolètes.
2. Le routeur Symfony exposé au navigateur n’était pas chargé de façon fiable en local.

Résultat :

- la page affichait “merci de patienter”
- des erreurs JavaScript bloquaient l’initialisation de l’application
- certains navigateurs renvoyaient une page blanche
- des requêtes `404` ou des réponses HTML à la place de JS provoquaient `Unexpected token '<'`

## Cause racine

### 1. Routeur JavaScript mal chargé

Le template principal chargeait :

- `bundles/fosjsrouting/js/router.js`
- puis l’endpoint dynamique `fos_js_routing_js`

En local, l’URL `http://localhost/js/routing?callback=fos.Router.setData` renvoyait `404`.

Or le front avait besoin de ces routes exposées pour générer des URLs comme `apiv2_home`.

### 2. Assets injectés incohérents

Le rendu HTML pouvait encore contenir :

- des chemins de bundles hérités d’une ancienne génération
- ou un `script src="/"` généré par une valeur vide dans la liste des javascripts configurables

Ce dernier point provoquait aussi un chargement HTML à la place d’un fichier JS.

### 3. Dev server / public path

En local, certains chemins pointaient encore vers `localhost:8080` ou vers des chunks dont les hashes ne correspondaient plus au build courant.

Cela provoquait :

- `404` sur les scripts
- `ERR_BLOCKED_BY_ORB`
- `Unexpected token '<'`

## Ce qui a été corrigé

### Bootstrap HTML

Le template principal a été corrigé pour :

- charger le dump statique `public/js/fos_js_routes.js`
- ne plus dépendre de `/js/routing?callback=fos.Router.setData` en local
- ignorer les entrées vides dans `stylesheets` et `javascripts`

### Webpack local

Le calcul des assets a été rendu plus robuste :

- `WEBPACK_DEV_SERVER` est lu de manière fiable
- `__webpack_public_path__` est calculé à partir du script réellement chargé
- on évite de forcer `localhost:8080` quand ce n’est pas le bon serveur

### Traduction

La récupération du translator a été sécurisée :

- si `window.Translator` existe mais ne possède pas `.trans`, on retombe sur le translator interne
- cela évite `o(...).trans is not a function`

## Vérifications effectuées

- la route Symfony `apiv2_home` existe bien
- le fichier `public/js/fos_js_routes.js` répond `200`
- la page HTML locale charge maintenant :
  - `router.js`
  - `js/fos_js_routes.js`
  - les bundles `/dist/...` corrects
- la page ne génère plus de `script src="/"` vide

## Symptômes qui devaient alerter

- `GET /js/routing?callback=fos.Router.setData 404`
- `Uncaught SyntaxError: Unexpected token '<'`
- `Error: The route "apiv2_home" does not exist`
- `o(...).trans is not a function`
- page bloquée sur “merci de patienter”

## Lecture métier

Le problème n’était pas une panne Symfony du contrôleur principal.

Le serveur répondait correctement, mais le navigateur n’arrivait pas à initialiser l’application React à cause d’un bootstrap JS incohérent.

## Risque résiduel

En production, il faut encore vérifier :

- le cache proxy/CDN
- le cache navigateur
- la génération et la publication des assets au déploiement
- l’absence de mélange entre vieux HTML et nouveaux bundles

## Recommandation de déploiement

Après un déploiement :

1. purger le cache applicatif
2. vérifier les assets publics générés
3. faire un hard refresh côté navigateur
4. confirmer qu’aucun script ne pointe vers une URL vide ou obsolète

