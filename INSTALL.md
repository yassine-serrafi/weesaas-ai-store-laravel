# Installation de WeeSaaS sur cPanel

Guide pas-à-pas pour installer la boutique WeeSaaS (Laravel) sur un hébergement
mutualisé **cPanel**, à l'aide de l'assistant web `public/install.php`.

> ⏱️ Temps estimé : 10–15 min. Aucune ligne de commande requise.

---

## 0. Pré-requis

- Un hébergement cPanel avec **PHP ≥ 8.2**
- Extensions PHP : `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `curl`, `zip`, `intl`
- Une base de données **MySQL / MariaDB**
- Un accès **Gestionnaire de fichiers** cPanel (ou FTP)

---

## 1. Préparer le paquet (ZIP de release)

L'hébergement mutualisé n'a souvent **pas** Composer. On livre donc un ZIP qui
contient déjà le dossier `vendor/`.

Sur ta machine, dans le dossier `wesaas-laravel/` :

```bash
composer install --no-dev --optimize-autoloader
```

Puis crée un ZIP **incluant** ces éléments à la racine :

```
app/  bootstrap/  config/  database/  public/  resources/  routes/
storage/  vendor/  artisan  composer.json  .env.example
```

> ❌ N'inclus pas : `.env`, `node_modules/`, `.git/`, `storage/installed.lock`.
> ✅ `vendor/` doit être présent (c'est ce qui évite Composer sur le serveur).

---

## 2. Créer la base de données dans cPanel

1. cPanel → **Bases de données MySQL®**
2. **Créer une base** : ex. `monsite_wee` → noter le **nom complet**
3. **Créer un utilisateur** + mot de passe → noter les deux
4. **Ajouter l'utilisateur à la base** → cocher **TOUS LES PRIVILÈGES**

Garde sous la main : `nom_base`, `utilisateur`, `mot_de_passe`, `hôte` (souvent `localhost`).

---

## 3. Choisir la version de PHP

cPanel → **Select PHP Version** (ou *MultiPHP Manager*) :

- Régler sur **8.2** (ou plus)
- Cocher les extensions listées au point 0
- Enregistrer

---

## 4. Téléverser et extraire

Deux configurations possibles selon ton domaine.

### Cas A — Domaine/sous-domaine pointant sur `public/` (recommandé)

C'est le plus propre et le plus sûr (le code reste hors du web).

1. Crée un dossier hors web, ex. `/home/monuser/wee/`
2. Téléverse le ZIP dedans → **Extraire**
3. cPanel → **Domaines** (ou *Subdomains*) → règle le **Document Root** du
   domaine sur `/home/monuser/wee/public`

### Cas B — Hébergement classique `public_html` (pas de changement de docroot)

1. Téléverse le ZIP dans le dossier home, extrais-le dans `/home/monuser/wee/`
2. Déplace **le contenu** de `wee/public/` dans `public_html/`
3. Déplace tout le reste de `wee/` dans un dossier frère, ex. `/home/monuser/wee_app/`
4. Édite `public_html/index.php` et corrige les 2 chemins :

   ```php
   require __DIR__.'/../wee_app/vendor/autoload.php';
   $app = require_once __DIR__.'/../wee_app/bootstrap/app.php';
   ```

5. Place aussi `install.php` dans `public_html/` (il était dans `public/`).

> Dans les deux cas, l'URL `https://tondomaine.com/install.php` doit être accessible.

---

## 5. Lancer l'assistant d'installation

1. Ouvre dans le navigateur : **`https://tondomaine.com/install.php`**
2. **Vérifications système** : tout doit être vert (sinon, corrige PHP/extensions/droits)
3. Remplis :
   - **Base de données** : valeurs du point 2 (hôte = `localhost` en général)
   - **Site** : URL (`https://tondomaine.com`), nom boutique, langue, devise
   - **Clé API Gemini** (optionnel, pour la génération IA)
   - **Compte admin** : utilisateur + mot de passe (≥ 8 caractères)
4. Clique **🚀 Lancer l'installation**

L'assistant écrit le `.env`, génère `APP_KEY`, lance les migrations, crée l'admin
et les réglages, puis affiche le récapitulatif.

---

## 6. Sécuriser (obligatoire)

Sur l'écran de succès, clique **« 🗑️ Supprimer l'installateur »**.

- Cela supprime `install.php` et t'emmène sur `/weeadmin`.
- Si la suppression échoue (droits), **supprime `install.php` à la main** via le
  Gestionnaire de fichiers. Ne JAMAIS laisser `install.php` en ligne.

> L'assistant se verrouille aussi tout seul (`storage/installed.lock`) : il refuse
> de se relancer tant que ce fichier existe.

---

## 7. Droits des dossiers

Si tu as des erreurs d'écriture, applique (Gestionnaire de fichiers → *Permissions*) :

| Dossier | Permissions |
|---|---|
| `storage/` (et sous-dossiers) | `755` (ou `775`) |
| `bootstrap/cache/` | `755` (ou `775`) |

---

## 8. File d'attente / génération IA (cron)

La génération de produits utilise la file d'attente Laravel. Sur mutualisé, le
worker automatique peut être bloqué (`exec`/`popen` désactivés). Ajoute alors un
**cron job** :

cPanel → **Cron Jobs** → toutes les minutes :

```
* * * * * /usr/local/bin/php /home/monuser/wee_app/artisan queue:work --stop-when-empty --max-time=55 >/dev/null 2>&1
```

> Adapte le chemin de `php` (cPanel → *Cron* affiche souvent le bon binaire, ex.
> `/opt/cpanel/ea-php82/root/usr/bin/php`) et le chemin vers `artisan`.

---

## 9. Connexion à l'administration

- URL : **`https://tondomaine.com/weeadmin`**
- Identifiants : ceux saisis à l'étape 5.

---

## Dépannage

| Problème | Solution |
|---|---|
| « vendor/ manquant » | Le ZIP n'incluait pas `vendor/`. Refais le point 1. |
| Erreur connexion DB | Vérifie nom base / user / mot de passe ; hôte = `localhost`. |
| Page blanche après install | Mets `APP_DEBUG=true` temporairement dans `.env`, recharge, lis l'erreur, puis remets `false`. |
| Images/storage cassés | Le `storage:link` a échoué : crée le lien manuellement ou copie `storage/app/public` vers `public/storage`. |
| « Déjà installé » | Supprime `storage/installed.lock` pour réinstaller (puis re-supprime `install.php` après). |
| Génération produit bloquée | Mets en place le cron du point 8. |

---

🤖 Généré pour WeeSaaS — pense à supprimer `install.php` après chaque installation.
