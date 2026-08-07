#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import os
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
BASE = ROOT / "base"

DEPLOY_TOOL = ROOT / "scripts/maintenance/deploy_website_to_hosting.py"
DEPLOY_ENV = ROOT / ".runtime/env/website.deploy"
FULL_TOOL = ROOT / "scripts/operations/hosting_mirror_operator.py"
DB_TOOL = ROOT / "scripts/maintenance/sync_hosting_database_from_local.py"
COMMUNICATION_CHECK = ROOT / "scripts/inspection/check_website_communication_runtime.py"

TMP_ROOT = ROOT / "tmp/hosting-release-profiles"

PRESERVE_EXACT = {
    ".htaccess",
    ".user.ini",
    "config.php",
    "mail.php",
    "php.ini",
    "error_log",
}

FRONTEND_ROOTS = (
    "templates/default/assets/css",
    "templates/default/assets/js",
    "templates/default/assets/images",
    "templates/default/surfaces",
    "templates/default/include",
)

BACKEND_ROOTS = (
    "core",
    "libraries",
)

DEPENDENCY_ROOTS = (
    "vendor",
)

MEDIA_ROOTS = (
    "userfiles/frontend",
)

FRONTEND_ROOT_FILES = (
    "templates/default/404.php",
    "templates/default/about.php",
    "templates/default/catalog.php",
    "templates/default/contacts.php",
    "templates/default/index.php",
    "templates/default/news.php",
    "templates/default/product.php",
    "templates/default/search.php",
)

BACKEND_ROOT_FILES = (
    "communication-request.php",
    "index.php",
)

DEPENDENCY_ROOT_FILES = (
    "composer.json",
    "composer.lock",
)


class ReleaseError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise ReleaseError(message)


def run(args: list[str], *, env: dict[str, str] | None = None) -> None:
    print("$ " + " ".join(args))
    result = subprocess.run(
        args,
        cwd=ROOT,
        env=env,
        text=True,
        check=False,
    )
    if result.returncode != 0:
        fail(
            f"command failed ({result.returncode}): "
            + " ".join(args)
        )


def communication_check() -> None:
    if not COMMUNICATION_CHECK.is_file():
        fail(
            "communication readiness checker is missing: "
            + str(COMMUNICATION_CHECK)
        )
    run([sys.executable, str(COMMUNICATION_CHECK)])


def add_tree(paths: set[str], relative_root: str) -> None:
    root = BASE / relative_root
    if not root.exists():
        return
    if root.is_symlink():
        fail(f"symlinked release root is not accepted: {relative_root}")

    for path in root.rglob("*"):
        if path.is_symlink():
            fail(
                "symlinked release path is not accepted: "
                + path.relative_to(BASE).as_posix()
            )
        if path.is_file():
            relative = path.relative_to(BASE).as_posix()
            if relative.split("/", 1)[0] in PRESERVE_EXACT:
                fail(f"preserved environment file entered scope: {relative}")
            paths.add(relative)


def add_files(paths: set[str], names: tuple[str, ...]) -> None:
    for name in names:
        path = BASE / name
        if path.is_file():
            paths.add(name)


def build_scope(profile: str) -> list[str]:
    paths: set[str] = set()

    if profile in {"frontend", "code"}:
        for root in FRONTEND_ROOTS:
            add_tree(paths, root)
        add_files(paths, FRONTEND_ROOT_FILES)

    if profile in {"backend", "code"}:
        for root in BACKEND_ROOTS:
            add_tree(paths, root)
        add_files(paths, BACKEND_ROOT_FILES)

    if profile in {"dependencies", "code"}:
        for root in DEPENDENCY_ROOTS:
            add_tree(paths, root)
        add_files(paths, DEPENDENCY_ROOT_FILES)

    if profile == "media":
        for root in MEDIA_ROOTS:
            add_tree(paths, root)

    if not paths:
        fail(
            f"profile {profile!r} resolved to an empty file scope"
        )

    forbidden = [
        path
        for path in paths
        if path in PRESERVE_EXACT
        or path.startswith("cache/")
        or path.startswith("sessions/")
        or path.startswith("temp/")
        or path.startswith("log/")
        or path.startswith("logs/")
    ]
    if forbidden:
        fail(
            "release scope contains hosting-owned runtime paths: "
            + ", ".join(sorted(forbidden)[:20])
        )

    return sorted(paths)


def write_manifest(profile: str, paths: list[str]) -> Path:
    stamp = dt.datetime.now().strftime("%Y%m%d_%H%M%S_%f")
    directory = TMP_ROOT / f"{stamp}-{profile}"
    directory.mkdir(parents=True, exist_ok=False)
    manifest = directory / f"{profile}.manifest"
    manifest.write_text(
        "\n".join(paths) + "\n",
        encoding="utf-8",
    )
    return manifest


def run_manifest_release(
    profile: str,
    action: str,
    manifest: Path,
) -> None:
    if not DEPLOY_TOOL.is_file():
        fail(f"deployment tool is missing: {DEPLOY_TOOL}")
    if not DEPLOY_ENV.is_file():
        fail(f"deployment env is missing: {DEPLOY_ENV}")

    flag = {
        "check": "--check",
        "dry-run": "--dry-run",
        "deploy": "--deploy",
    }[action]

    if action == "deploy":
        communication_check()

    run([
        sys.executable,
        str(DEPLOY_TOOL),
        flag,
        "--env",
        str(DEPLOY_ENV),
        "--manifest",
        str(manifest),
    ])

    if action == "deploy":
        communication_check()


def main() -> int:
    parser = argparse.ArgumentParser(
        description="ForPrint hosting deployment profile router"
    )
    parser.add_argument(
        "--profile",
        required=True,
        choices=(
            "full",
            "full-destructive",
            "code",
            "frontend",
            "backend",
            "dependencies",
            "database",
            "database-destructive",
            "media",
            "manifest",
        ),
    )
    parser.add_argument(
        "--action",
        default="deploy",
        choices=("check", "dry-run", "deploy"),
    )
    parser.add_argument("--manifest")
    args = parser.parse_args()

    profile = args.profile
    action = args.action

    print("ForPrint hosting deployment profile")
    print("=" * 80)
    print(f"profile={profile}")
    print(f"action={action}")

    if profile == "full-destructive":
        if action != "deploy":
            print("[DANGER PLAN] full replacement includes production operational rows")
            return 0

        communication_check()
        env = os.environ.copy()
        env["FP_HOSTING_RESET_ALLOWED"] = "1"
        env["FP_HOSTING_OPERATIONAL_DATA_REPLACE_ALLOWED"] = "1"
        run([sys.executable, str(FULL_TOOL), "reset"], env=env)
        communication_check()
        return 0

    if profile == "full":
        if action != "deploy":
            print(
                "[PLAN] full = application + vendor + all userfiles "
                "+ complete local database"
            )
            print(
                "[PLAN] hosting environment pack and external "
                "communication runtime remain preserved"
            )
            return 0

        communication_check()
        env = os.environ.copy()
        env["FP_HOSTING_RESET_ALLOWED"] = "1"
        run(
            [
                sys.executable,
                str(FULL_TOOL),
                "reset",
            ],
            env=env,
        )
        communication_check()
        return 0

    if profile == "database-destructive":
        command = [sys.executable, str(DB_TOOL), "--replace-operational"]
        if action != "deploy":
            command.append("--dry-run")
            run(command)
            return 0

        env = os.environ.copy()
        env["FP_HOSTING_DATABASE_SYNC_ALLOWED"] = "1"
        env["FP_HOSTING_OPERATIONAL_DATA_REPLACE_ALLOWED"] = "1"
        run(command, env=env)
        return 0

    if profile == "database":
        command = [
            sys.executable,
            str(DB_TOOL),
        ]
        if action != "deploy":
            command.append("--dry-run")
        else:
            env = os.environ.copy()
            env["FP_HOSTING_DATABASE_SYNC_ALLOWED"] = "1"
            run(command, env=env)
            return 0
        run(command)
        return 0

    if profile == "manifest":
        if not args.manifest:
            fail("--manifest is required for profile=manifest")
        manifest = Path(args.manifest)
        if not manifest.is_absolute():
            manifest = ROOT / manifest
        if not manifest.is_file():
            fail(f"manifest does not exist: {manifest}")
        run_manifest_release(profile, action, manifest)
        return 0

    paths = build_scope(profile)
    manifest = write_manifest(profile, paths)

    print(f"files={len(paths)}")
    print(f"manifest={manifest}")

    run_manifest_release(profile, action, manifest)
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except ReleaseError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
