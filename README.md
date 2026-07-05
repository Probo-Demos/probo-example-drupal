# Probo Example: Drupal

A **Drupal 11** site whose demo page displays the environment variables a Probo
CI build injects into a container. Delivered as a small custom module
([`web/modules/custom/probo_environment`](web/modules/custom/probo_environment))
that adds a route at **`/probo-environment`** rendering two tables:

- **Build variables** — the documented Probo catalog (`PROBO_ENVIRONMENT`,
  `BUILD_ID`, `BRANCH_NAME`, …) with live values where present.
- **Secrets & other injected variables** — everything else Probo made visible to
  the process (organization/project secrets and any custom variables),
  discovered dynamically.

Unlike the standalone Node/Python/.NET examples (which run a single app process),
this is a full Drupal codebase built by Probo's **LAMP + Drupal** plugin from a
database dump. The `probo_environment` controller reads the container
environment with `getenv()` and applies the same filter the container's
`envvars-swap.sh` uses
(`compgen -e | grep -Ev '^APACHE_|^(HOME|LANG|PWD|PATH|OLDPWD|SHLVL|_)$'`), so
only the injected variables remain.


## Example Database

The example database for this example can be downloaded (here)[https://probosupportfiles.blob.core.windows.net/utils/drupal-example.sql.gz] - be sure to place the .sql.gz file in your assets if you are trying to build it on your own Probo.CI account.

## Run locally

```bash
docker compose up -d
docker exec dapp bash -lc 'cd /var/www/html && vendor/bin/drush en probo_environment -y && vendor/bin/drush cr'
# then open http://localhost:8007/probo-environment
```

Simulate a Probo build environment (variables must reach the web server's PHP
process, so set them on the container, not just a shell):

```bash
docker exec -e PROBO_ENVIRONMENT=TRUE -e BUILD_ID=abc123 -e BRANCH_NAME=main \
  -e DEMO_CUSTOM_VAR=hello-from-probo dapp \
  curl -s http://localhost/probo-environment
```

The page reads the environment live per request (`#cache: { max-age: 0 }`), so
the tables reflect whatever the container currently exposes.

## How it runs in a Probo Drupal container

`.probo.yaml` uses `type: lamp` with `php: 8.4` and the built-in **Drupal**
plugin, which installs the site from a Composer build and a database dump:

```yaml
type: lamp
php: 8.4
database: mariadb:11.4
assets:
  - drupal-example.sql.gz

steps:
  - name: Install Drupal Based On Composer File
    plugin: Drupal
    drupalVersion: 11
    database: drupal-example.sql.gz
    databaseGzipped: true
    composer: true
    subDirectory: web
```

The Drupal plugin runs `composer install`, imports the gzipped database, and
points Apache/PHP at the `web/` docroot; nginx reverse-proxies `*.probo.build`
to it. Because the module ships in the codebase, enabling it (`drush en
probo_environment`) exposes `/probo-environment`, where standard Probo build
variables populate the first table and any injected secrets or custom variables
appear in the second.
