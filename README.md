# Laravel Challenge: beheeromgeving

Laravel 12 met Breeze-login en Spatie Permission 6. Vier beheeronderdelen voor bestaande gebruikers.

## Starten

PHP 8.2 of hoger, Composer en Node.js 22 of hoger zijn nodig. Dit project gebruikt MySQL/MariaDB in XAMPP. De app-database heet `laravel_challange` en is zichtbaar in [phpMyAdmin](http://localhost/phpmyadmin/index.php?route=/database/structure&db=laravel_challange). Start Apache en MySQL in XAMPP. Je bestaande game-project en andere databases zijn niet aangepast.

De lokale database is al aangemaakt en bevat drie rollen, vier permissies en zeven rol-permissiekoppelingen. De bestaande IDs zijn behouden. Op het moment van omzetten waren er geen geregistreerde gebruikers. Het project en de tests gebruiken nu beide MySQL/MariaDB.

Bij een verse download maak je via phpMyAdmin eerst de lege database `laravel_challange` aan met collation `utf8mb4_unicode_ci`. Voer daarna uit:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve --port=8001
```

De `.env.example` bevat de MySQL-instellingen voor XAMPP: host `127.0.0.1`, poort `3306`, database `laravel_challange`, gebruiker `root`, leeg wachtwoord. Pas je lokale `.env` aan als jouw MySQL-account andere instellingen gebruikt. Het bestand `.env` wordt niet gecommit.

Voer migraties en de seeder uit bij de eerste installatie. Op de al voorbereide lokale database hoef je die stappen niet te herhalen. `migrate:fresh` is uitsluitend voor een database waarvan de inhoud mag verdwijnen.

Als alternatief voor migreren en seeden is `database/sql/laravel_challange.sql` beschikbaar. Deze SQL-export bevat het schema en de oorspronkelijke voorbeeldrollen/permissies, zonder gebruikers, wachtwoorden of sessies. Importeer hem via phpMyAdmin uitsluitend in een lege database. Gebruik voor een installatie óf de migraties met seeder óf deze export.

In de lokale projectmap zijn installatie, migraties, voorbeeldrollen en frontend al voorbereid. Je kunt daar beginnen met:

```powershell
php artisan serve --port=8001
```

Open http://127.0.0.1:8001/register en registreer een account met je eigen wachtwoord. Open daarna een tweede terminal in de projectmap en maak dat bestaande account admin:

```powershell
php artisan app:make-admin jouw-email@example.com
```

Gebruik je eigen geregistreerde e-mailadres. Het command maakt geen nieuwe gebruiker en faalt bij een onbekend adres. Na verversen staat Beheer in je menu.

## De vier CRUD's

| Onderdeel | Overzicht | Opslag |
| --- | --- | --- |
| Permissies | /admin/permissions | permissions: name en automatisch guard_name=web |
| Rollen | /admin/roles | roles: name en automatisch guard_name=web |
| Rol-permissies | /admin/role-permissions | role_has_permissions: role_id en permission_id |
| Gebruiker-rollen | /admin/user-roles | model_has_roles: role_id, model_id en automatisch model_type=App\\Models\\User |

Elk onderdeel heeft een overzicht, een toevoegformulier, een bewerkformulier en een verwijderactie. Bij de koppelingen heet verwijderen **Ontkoppelen**: de oorspronkelijke rol, permissie en gebruiker blijven bestaan.

De selecties tonen namen en e-mailadressen; het formulier verstuurt de bijbehorende IDs. Een gebruiker kan meerdere rollen krijgen en een rol meerdere permissies. Een bewerking vervangt alleen het geselecteerde paar.

## Beveiliging en validatie

Alle beheer-routes, inclusief opslaan, wijzigen en verwijderen, staan in één routegroep met `auth` en `role:admin`. De middleware-alias staat in `bootstrap/app.php`; User gebruikt Spatie's `HasRoles`.

Namen zijn verplicht, maximaal 255 tekens en uniek binnen de guard web. Keuzelijsten accepteren alleen bestaande records en rollen/permissies met guard web. Dubbele koppelingen worden geweigerd. Formulieren gebruiken CSRF-beveiliging en tonen fouten en succesmeldingen.

De koppeltabellen hebben geen apart ID. Daarom bevatten hun bewerk- en verwijderroutes beide IDs. Updates verlopen in een transactie. De Spatie-methoden `givePermissionTo`, `revokePermissionTo`, `assignRole` en `removeRole` houden de rechten en cache bij.

De vaste beheerrol admin kan niet worden hernoemd of verwijderd. De laatste admin kan zijn adminrol of account niet verwijderen. Geef eerst een andere gebruiker de adminrol.

## Het gevolg van een koppeling aantonen

De pagina **Mijn toegang** toont je rollen en permissies. De demonstratiepagina `/rechten/product-aanpassen` gebruikt `permission:product aanpassen`.

1. Registreer een tweede gebruiker.
2. Geef deze gebruiker via Gebruiker-rollen de rol klant.
3. Log als die gebruiker in. De beheeromgeving geeft 403 en Product aanpassen is niet beschikbaar.
4. Log als admin in en koppel product aanpassen aan klant.
5. Log opnieuw als de tweede gebruiker in. Product aanpassen is nu beschikbaar.
6. Ontkoppel die permissie als admin en controleer dat de pagina weer wordt geweigerd.

De seeder geeft klant standaard product bekijken, editor bekijken en aanpassen, en admin de vier voorbeeldpermissies. Voer de seeder bij de eerste installatie uit; opnieuw seeden herstelt deze voorbeeldrechten.

## Tests

Gebruik de afzonderlijke database `laravel_challange_test`, eveneens met `utf8mb4_unicode_ci`. Maak deze via phpMyAdmin aan voordat je de tests voor het eerst draait. Voor een verse download:

```powershell
Copy-Item .env.testing.example .env.testing
php artisan key:generate --env=testing
```

Pas indien nodig het MySQL-account in `.env.testing` aan. Dit lokale bestand wordt niet gecommit. Beide databases en de lokale testinstellingen zijn op jouw laptop al voorbereid.

```powershell
php artisan test
php vendor/bin/pint --test
npm run build
```

De tests gebruiken MySQL/MariaDB met uitsluitend `laravel_challange_test`. De testbasis controleert vóór het opnieuw aanmaken van tabellen dat de actieve database werkelijk deze testdatabase is; anders stopt de test. Je app-database wordt daardoor niet gebruikt voor de test-reset. Draai de tests achtereenvolgens, zonder `--parallel`.

De tests controleren CRUD, ongeldige invoer, dubbele koppelingen, andere guards, alle beheer-routes en HTTP-methoden, echte toegang na een koppeling, beide navigatiemenu's en bescherming van de laatste admin.

## Bouwvolgorde en GitHub

De onderdelen zijn één voor één gebouwd en getest: permissies, rollen, rol-permissies, gebruiker-rollen. De geteste tussenversies zijn apart bewaard.

Alle vier tussenversies zijn afzonderlijk gecommit en naar [GitHub](https://github.com/Daniel-Garcia-hash/Laravel-challange) gepusht:

1. `3860c18` — Bouw CRUD 1: permissies beheren.
2. `788978a` — Bouw CRUD 2: rollen beheren.
3. `ac87dd7` — Bouw CRUD 3: permissies aan rollen koppelen.
4. `4f0323d` — Bouw CRUD 4: rollen aan gebruikers koppelen.

De lokale projectmap is daarna aan deze geschiedenis gekoppeld. De omschakeling naar MySQL/MariaDB staat in een aanvullende commit met de titel `Gebruik MySQL/MariaDB voor app en tests`. Er is geen force-push gebruikt.

Na de omzetting naar MySQL/MariaDB zijn alle 50 tests opnieuw geslaagd (447 assertions). De codecontrole slaagde eveneens. De actieve app-verbinding, overgezette gegevens, phpMyAdmin en registratiepagina zijn gecontroleerd. De frontend-build en browsercontrole van de beheerpagina's waren eerder geslaagd; de schermen zijn bij deze omzetting niet aangepast.

De meegeleverde synchronisatiescripts en tussenversies zijn bewaard als bouwarchief. Voer `GitHub-synchroniseren.ps1` niet opnieuw uit: de vier commits en de GitHub-sync zijn al afgerond. Het lokale herstelscript gebruikte vertrouwen voor alleen deze projectmap per Git-aanroep; het heeft geen globale Git-instellingen gewijzigd.

## Uitleg voor het eindgesprek

Een rol bundelt permissies. Een gebruiker krijgt rechten door een rol te krijgen die aan permissies is gekoppeld. guard_name web hoort bij browserlogin; model_type vertelt Spatie dat model_id naar een User verwijst. De middleware controleert toegang op de server, zodat ook een rechtstreeks ingevoerde URL of HTTP-request wordt tegengehouden.

Voor de code vind je de vier controllers in `app/Http/Controllers/Admin`, de Blade-views in `resources/views/admin`, de gezamenlijke layout in `resources/views/layouts/admin.blade.php` en de routes in `routes/web.php`.
