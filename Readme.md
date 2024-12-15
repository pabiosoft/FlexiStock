# FlexiStock
## Prérequis
Avant de commencer, assurez-vous que les éléments suivants sont installés sur votre machine :
*  [Docker Desktop](https://www.docker.com/products/docker-desktop/)
### Verifier que docker c'est bien installe
- Commande dans un terminal:
```shell
docker pull dunglas/frankenphp:1.3-php8.3
```
- Vous deviez voir une réponse similaire:
```shell
1.3-php8.3: Pulling from dunglas/frankenphp
Digest: sha256:<hash>
Status: Downloaded newer image for dunglas/frankenphp:1.3-php8.3
docker.io/dunglas/frankenphp:1.3-php8.3
```

1- Construire et démarrer l'application avec Docker
```shell
docker compose up --build
```
2- Installation des dependence
- composer
```shell
composer install
```
- webpack
```shell
npm install
npm run build
```
3- Base de donnée
 ```shell
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create

php bin/console make:migration
php bin/console doctrine:migrations:migrate
```
4- Peuplement de la base de donnée
```shell
php bin/console doctrine:fixtures:load
```

4- Accéder à l'application :

* L'application : http://localhost:8000
* Un utilisateur admin a ete créer avec ces informations:
* * email : dev2@mo.com
* * password: flexiStock
* PhpMyAdmin : http://localhost:8082
* Identifiants PhpMyAdmin :
* Serveur : db
* Utilisateur : root
* Mot de passe : password
