## Commande utils
```shell
docker system prune --all
docker volume prune 
```

### Réinitialise la base de données :
#### Accède au conteneur :
 ```shell
 docker exec -it flexistock_app bash
```

 ```shell
 docker exec -it flexistock_app bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create

php bin/console make:migration
php bin/console doctrine:migrations:migrate
```



## WEBPACK
```shell
An exception has been thrown during the rendering of a template ("Asset manifest file "/app/public/build/manifest.json" does not exist. Did you forget to build the assets with npm or yarn?").
```
> Fix it by
```shell
npm install
npm run build

```
1- Accès PhpMyAdmin (http://localhost:8082)
- Informations de connexion :

* Serveur (Host) : db
* Utilisateur (Username) : root
* Mot de passe (Password) : password

