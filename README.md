# Trustindex Review App

The credentials in `compose.yaml` and `.env` are local development values only.

Start a fresh clone without host PHP, Composer, or Symfony CLI:

```bash
docker compose up -d --build
docker compose exec app composer install --no-interaction
docker compose exec app php bin/console about
```

The application is available at <http://localhost:8080>. A Symfony 404 response is expected until the first route is added.

Check the running services with `docker compose ps`.

Stop the stack with:

```bash
docker compose down
```
