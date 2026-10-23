# Discotek
Site permettant de Discotek-Animation

# Installation Locale
- Ajouter un hostname dans le fichier hosts
- Vhost Xampp
```bash
<VirtualHost *:80>
    DocumentRoot "C:/Travail/www/discotek-new/public/"
	ServerName discotek-new.local
	
    <Directory "C:/Travail/www/discotek-new">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
- Téléchager symfony .exe puis installer apache pack
```bash
symfony new --webapp discotek-new
composer require symfony/apache-pack
```
Le site est hébergé en local sur http://discotek-new.local/

Installer ensuite les composants :

Extensions PHP nécessaires (à activer dans `php.ini`, puis redémarrer Apache) : `gd` (redimensionnement des photos),
`zip` (import des archives de photos), `intl` (dates en français), `pdo_mysql`.

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
php bin/console app:create-admin fabien.lierville27@gmail.com --name=Fabien   # mot de passe demandé
php bin/console tailwind:build
php bin/console tailwind:build assets/styles/admin.css
```

Pendant le développement : `php bin/console tailwind:build --watch` (et la même commande avec `assets/styles/admin.css`).

### Administrateurs
Pas de création de compte ni de "mot de passe oublié" sur le site : uniquement la commande
`php bin/console app:create-admin email [--name=Nom] [--password=...]`. Relancée sur un email existant, elle réinitialise le mot de passe.

### Reprise des données de l'ancien site
La base `discotek-ancien` (fichier `discotek_ancien.sql` de l'ancien site) doit être importée dans MySQL, puis
(`LEGACY_DATABASE_URL` dans `.env`) :
```bash
php -d memory_limit=1G bin/console app:import-legacy --photos-dir="C:/Travail/www/discotek-old/medias/images/photos_soirees"
```
Import idempotent : paramètres, pages (avec redirections 301 des anciennes URL `?pageid=`), villes de l'autocomplétion,
abonnés newsletter, soirées (code soirée = ancien répertoire) et leurs photos.

Les photos de soirées sont stockées hors du dossier public dans `storage/parties/{raccourci}/` (à sauvegarder et à
rendre accessible en écriture sur le serveur), les images insérées dans les pages dans `public/uploads/pages/`.

### Tests
La base `discotek-new_test` est recréée et remplie par les fixtures à chaque lancement.
```bash
php vendor/bin/phpunit --testsuite unit,functional   # tests unitaires et fonctionnels
vendor/bin/bdi detect drivers                        # une fois : télécharge ChromeDriver
php vendor/bin/phpunit --testsuite e2e               # tests end-to-end dans Chrome (Panther)
```


# Installation Production

- Créer la clef SSH et la mettre sur le compte Github :
```bash
ssh-keygen -t ed25519 -C "deploy-marcantoineimage" -f ~/.ssh/id_ed25519_github -N "MotDePasse"

Mettre le contenu de la clef public dans les settings du compte GitHub (pas dans les settings du repo)


nano ~/.ssh/config
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519_github
    IdentitiesOnly yes



Mettre les permisions :
chmod 600 ~/.ssh/config
chmod 700 ~/.ssh
chmod 600 ~/.ssh/id_ed25519_github
chmod 644 ~/.ssh/id_ed25519_github.pub

Tester la connexion :
ssh -T git@github.com

Récupérer le projet :
git clone git@github.com:MarcAntoineKmd/generateimage.git ./
```

- Générer un hash pour APP_SECRET :
```shell
php -r "echo bin2hex(random_bytes(16));"
```

- Renseigner le fichier .env.local (créer si nécessaire)


```bash
APP_ENV=PROD

APP_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxx

DATABASE_URL="mysql://discotek_*************:****************@127.0.0.1:3306/marcantoine_***********?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

APP_MAIL_FROM_ADDRESS=no_reply@discotekanimation.fr
MAILER_DSN="smtp://*****************:***************@sandbox.smtp.mailtrap.io:2525"

DEFAULT_URI=https://www.discotekanimation.fr/
```

Installer ensuite les composants

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
php bin/console app:create-user vous@exemple.com --admin   # mot de passe demandé
php bin/console tailwind:build
```

Pour la dernière commande (build tailwind) sur un serveur mutualisé il est fort possible que le /tmp (qui accueille l'exécutable tailwind) ne soit pas accessible en exécution
Alors il faudra faire :
```bash
mkdir -p ~/tmp
chmod 700 ~/tmp
TMPDIR=~/tmp php bin/console tailwind:build
```

Voici les dernière commande pour la mise en production (`asset-map:compile` copie CSS/JS versionnés dans `public/assets`, indispensable en prod ; il se lance **après** `tailwind:build`) :
```bash
composer install --no-dev --optimize-autoloader
TMPDIR=~/tmp php bin/console tailwind:build --minify
TMPDIR=~/tmp php bin/console tailwind:build --minify assets/styles/admin.css
php bin/console asset-map:compile
php bin/console cache:clear
php bin/console cache:warmup
```

Vérification de l'envoi Email :

```bash
php bin/console debug:config framework mailer
php bin/console mailer:test fabien.lierville27@gmail.com --from=no-reply@classement-magicien.mycloud.lf10198.ma.fr -vvv
```


Update du projet
### 2.1 Update du projet
```shell
cd /chemin/vers/classement-magicien
git pull origin main
```
- Mettre à jour les dépendances (si composer.json/composer.lock a changé), update des migrations, rebuild tailwind
```shell
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
TMPDIR=~/tmp php bin/console tailwind:build --minify
TMPDIR=~/tmp php bin/console tailwind:build --minify assets/styles/admin.css
php bin/console asset-map:compile
php bin/console cache:clear
php bin/console cache:warmup

```
