# Probo Environment Demo (Drupal module)

A Drupal port of the single-file PHP demo (`probo_test`). It adds a public route
at **`/probo-environment`** that displays the environment variables Probo CI
injects into the build container, as two tables:

- **Build variables** — the documented Probo catalog (`PROBO_ENVIRONMENT`,
  `BUILD_ID`, `BRANCH_NAME`, …) with live values where present.
- **Secrets & other injected variables** — everything else Probo made visible to
  the process (organization/project secrets and any custom variables),
  discovered dynamically.

Where the PHP version read `getenv()` directly, the Drupal controller does the
same and applies the identical filter used by
`probo-ubuntu/<image>/files/envvars-swap.sh`
(`compgen -e | grep -Ev '^APACHE_|^(HOME|LANG|PWD|PATH|OLDPWD|SHLVL|_)$'`) so
only the injected variables remain.

## Layout

```
probo_environment.info.yml          Module definition
probo_environment.routing.yml       Route: /probo-environment (public)
probo_environment.libraries.yml     CSS library
probo_environment.module            hook_theme()
src/Controller/ProboEnvironmentController.php  Env read/filter/partition + render array
templates/probo-environment.html.twig          Page markup (hero, version card, two tables)
css/probo-environment.css           Scoped page styles
```

## Enable

```bash
drush en probo_environment -y
drush cr
```

Then visit `/probo-environment`. The page reads the environment live per request
(`#cache: { max-age: 0 }`), so the tables reflect whatever Probo injected into
the container's process environment.
