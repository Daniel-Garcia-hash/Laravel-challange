# Laravel Challenge: beheeromgeving

Laravel 12 met Breeze-login en Spatie Permission 6. Vier beheeronderdelen voor bestaande gebruikers.

## Starten

PHP 8.2 of hoger, Composer en Node.js 22 of hoger zijn nodig. Standaard gebruikt dit nieuwe project SQLite; de tabellen staan in `database/database.sqlite`. Je bestaande game-project en MySQL-database zijn niet aangepast.

Bij een verse download:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite }
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve --port=8001
```

Maak een SQLite-bestand alleen als het nog niet bestaat. Voer geen `migrate:fresh` uit op een database die je wilt bewaren.

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

```powershell
php artisan test
php vendor/bin/pint --test
npm run build
```

De tests gebruiken een afzonderlijke SQLite-database in het geheugen. Ze controleren CRUD, ongeldige invoer, dubbele koppelingen, andere guards, alle beheer-routes en HTTP-methoden, echte toegang na een koppeling, beide navigatiemenu's en bescherming van de laatste admin.

## Bouwvolgorde en GitHub

De onderdelen zijn één voor één gebouwd en getest: permissies, rollen, rol-permissies, gebruiker-rollen. De geteste tussenversies zijn apart bewaard.

Git schrijven werd in de Codex-omgeving geweigerd, ook na verleende schrijftoegang. Daarom zijn commits en pushes vanuit deze omgeving nog niet uitgevoerd. In de bijgeleverde outputs staan `GitHub-synchroniseren.ps1` en `tussenversies.zip`. Voer het script vanuit je eigen PowerShell uit. Het maakt en pusht vier aparte commits op jouw repository, zonder force-push, en koppelt daarna de lokale projectmap. Controleer het resultaat op GitHub.

## Uitleg voor het eindgesprek

Een rol bundelt permissies. Een gebruiker krijgt rechten door een rol te krijgen die aan permissies is gekoppeld. guard_name web hoort bij browserlogin; model_type vertelt Spatie dat model_id naar een User verwijst. De middleware controleert toegang op de server, zodat ook een rechtstreeks ingevoerde URL of HTTP-request wordt tegengehouden.

Voor de code vind je de vier controllers in `app/Http/Controllers/Admin`, de Blade-views in `resources/views/admin`, de gezamenlijke layout in `resources/views/layouts/admin.blade.php` en de routes in `routes/web.php`.
