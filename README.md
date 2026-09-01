# Trustindex Review App

Symfony-alapú cégértékelő minialkalmazás, amelyben a látogatók véleményt küldhetnek be, böngészhetik a beérkezett értékeléseket, és megtekinthetik a cégenkénti statisztikákat.

## Funkciók

A kötelező funkciók:

- új vélemény beküldése Symfony Form, Validator és CSRF-védelem használatával;
- vélemények listázása értékeléssel, csonkított szöveggel és dátummal;
- külön vélemény-részletező oldal;
- cégenkénti darabszám és átlagos értékelés adatbázisoldali `COUNT` és `AVG` aggregációval.

### Bónusz funkciók

- részleges, kis- és nagybetűtől független keresés cégnév alapján;
- cégenkénti saját adatlap statisztikával és véleménylistával;
- cégnévjavaslatok natív HTML `datalist` használatával;
- meglévő cégnév case-insensitive kanonizálása az elsőként tárolt írásmód alapján;
- reszponzív, billentyűzettel használható, szemantikus HTML-t és alapvető akadálymentességi megoldásokat alkalmazó felület.

## Technológiák

- PHP 8.3 és Apache 2.4;
- Symfony 7.4;
- MySQL 8.4;
- Doctrine ORM 3, DBAL 4 és Doctrine Migrations;
- Twig, Symfony Forms és Validator;
- PHPUnit 12 és DAMA Doctrine Test Bundle 8;
- PHP CS Fixer 3 `@Symfony` szabálykészlettel;
- Docker és Docker Compose.

## Követelmények

A host gépen csak az alábbiak szükségesek:

- Git;
- Docker;
- Docker Compose v2 (`docker compose`).

Host PHP, Composer vagy Symfony CLI nem szükséges. A repositoryban szereplő jelszavak kizárólag dokumentált helyi Docker-fejlesztői értékek, production környezetben nem használhatók.

## Telepítés friss clone után

A klónozott repository gyökérkönyvtárában futtasd:

```bash
docker compose up -d --build
docker compose exec app composer install --no-interaction
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -e APP_ENV=test app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console about
```

Az alkalmazás ezután a [http://localhost:8080](http://localhost:8080) címen érhető el.

A szolgáltatások állapota:

```bash
docker compose ps
```

A stack leállítása az adatbázis-volume megtartásával:

```bash
docker compose down
```

## Adatbázisok és migrációk

A fejlesztői adatbázis neve `app`. Migráció futtatása:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

A tesztek külön `app_test` adatbázist használnak. Friss Docker volume esetén ezt a MySQL init script automatikusan létrehozza. A verziókövetett migrációk test környezetben így futtathatók:

```bash
docker compose exec -e APP_ENV=test app php bin/console doctrine:migrations:migrate --no-interaction
```

## Automatizált tesztek

A teljes, izolált tesztcsomag:

```bash
docker compose exec app php bin/phpunit
```

TestDox kimenettel:

```bash
docker compose exec app php bin/phpunit --testdox
```

A unit tesztek adatbázis nélkül futnak. A repository- és funkcionális tesztek az `app_test` MySQL-adatbázist használják; a DAMA Doctrine Test Bundle minden teszt után automatikusan visszagörgeti a tranzakciót.

## Kódstílus

Ellenőrzés módosítás nélkül:

```bash
docker compose exec app php vendor/bin/php-cs-fixer fix --dry-run --diff
```

Automatikus javítás:

```bash
docker compose exec app php vendor/bin/php-cs-fixer fix
```

Ugyanezekhez Composer-scriptek is elérhetők a konténerben:

```bash
docker compose exec app composer test
docker compose exec app composer cs-check
docker compose exec app composer cs-fix
```

## Architekturális döntések

- A beküldési folyamatot a Symfony Form kezeli, az üzleti bemenetek validációja pedig az entitás Validator attribútumaiban marad.
- A Doctrine QueryBuilder-alapú keresési, rendezési és aggregációs logika a `ReviewRepository` osztályban található, nem a controllerekben.
- Sikeres beküldés után a controller Post/Redirect/Get folyamatot használ, így egy oldalfrissítés nem küldi be újra a formot.
- A fejlesztői és tesztadatok külön MySQL-adatbázisban vannak; a DAMA tranzakciós rollback biztosítja a tesztek izolációját.
- A főoldal és a cégoldal ugyanazt a Twig review-card partialt használja.

## Folyamatos integráció

A GitHub Actions workflow a `main` branchre történő push és minden pull request esetén ugyanazt a Docker Compose-környezetet építi fel, mint a helyi fejlesztés. Lefuttatja a migrációkat, a Composer-validációt, a kódstílus-ellenőrzést, a Symfony- és Doctrine-linteket, valamint a teljes PHPUnit tesztcsomagot. A workflow nem deployol és nem használ production credentialt.

## Fejlesztési folyamat

A feladat engedélyének megfelelően AI coding assistant segítette a megvalósítást. Minden módosítást manuálisan átnéztem, az alkalmazást és az automatizált teszteket manuálisan is lefuttattam.

## Ismert korlátok és továbbfejlesztések

- A cég nem külön entitás, ezért az elsőként tárolt írásmód válik kanonikussá.
- Egy későbbi adminisztrációs felület vagy külön Company entitás lehetővé tenné a hibás cégnév javítását.
- Valós publikus rendszerben moderáció, rate limiting vagy CAPTCHA bevezetése megfontolandó.
- Nagy adatmennyiségnél a véleménylistákhoz lapozás szükséges.

## Munkaidőnapló

| Feladat                                         |                               Idő |
| ----------------------------------------------- | --------------------------------: |
| Feladat elemzése és megvalósítási terv          |                           45 perc |
| Fejlesztői környezet és repository előkészítése |                           15 perc |
| Symfony- és Docker-környezet                    |                           30 perc |
| Adatmodell, validáció és migráció               |                           30 perc |
| Véleménybeküldés, lista és felület              |                           30 perc |
| Részletező, cégstatisztika és keresés           |                           60 perc |
| Bónuszfunkciók és felületi finomítások          |                           90 perc |
| Automatizált tesztek                            |                           30 perc |
| Minőségellenőrzés, CI és dokumentáció           |                           60 perc |
| Összesen                                        |                     6 óra 30 perc |
