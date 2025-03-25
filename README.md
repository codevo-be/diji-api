# Diji API

## Install project
- Copy .env.example and rename .env
- Install packages : ```composer install```
- Create migration : ```php artisan migrate```
- Create key for Laravel Passport : ```php artisan passport:keys```
- Create client secret Laravel Passport : ```php artisan passport:client --password```
- Create tenant : ```php artisan tenant:create Codevo```
- Create user : ```php artisan user:create info@co**.be ***** codevo```
- Run server : ```php artisan serve```
- Reload configuration : ```composer dump-autoload```
