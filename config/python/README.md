# ForPrint Python tooling configuration

- **Status:** canonical Python tooling configuration namespace
- **Repository runtime:** PHP website plus Python operational/automation tooling

`config/python/` owns declarative configuration for repository-local Python
tooling without turning the repository root into a Python package/project.

## Current structure

```text
config/python/
├── README.md
└── requirements/
    └── marketing.txt
```

## Runtime environment

The local Python runtime remains:

```text
.venv_website/
```

`.venv_website/` is local runtime state and is not committed.

Dependency manifests describe required packages; the virtual environment is
the installed realization of those manifests.

## Dependency manifests

Domain-specific dependency inputs live under:

```text
config/python/requirements/
```

Current marketing tooling uses:

```text
config/python/requirements/marketing.txt
```

Install into the existing repository-local environment with:

```bash
.venv_website/bin/python -m pip install \
  -r config/python/requirements/marketing.txt
```

A dependency manifest is source configuration. Installed packages inside
`.venv_website/` are not source configuration.

## Future packaging boundary

Do not add a root `pyproject.toml` merely to configure internal scripts.

If the Python tooling later becomes a cohesive reusable Python project with
packages, tests, build metadata, dependency groups, and a formal install
contract, define its project root deliberately under the Python tooling
namespace (expected candidate: `scripts/`) and adopt `pyproject.toml` there.

At that stage, standardized dependency groups and a lock workflow may replace
or generate compatibility requirements manifests.

Until that architecture exists, do not maintain two competing dependency
sources of truth.

## Locking

No lock file is canonical yet.

When reproducible transitive dependency locking becomes necessary, introduce
it as a versioned architecture/tooling decision and keep lock artifacts under a
dedicated Python configuration/tooling boundary rather than scattering them in
the repository root.

## Security

Never store Python package-index credentials, OAuth credentials, API tokens,
provider secrets, passwords, or private keys in this directory.

Provider credentials remain protected runtime/secret-store concerns.
