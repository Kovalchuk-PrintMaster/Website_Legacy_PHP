#!/usr/bin/env python3
from __future__ import annotations

import importlib.util
from pathlib import Path
import sys
import tarfile
import tempfile

ROOT = Path("/srv/software_development/forprint-project/forprint_website")
COMMON = ROOT / "scripts/maintenance/hosting_mirror_common.py"
BACKUP = ROOT / "scripts/maintenance/backup_hosting_to_local.py"
SYNC = ROOT / "scripts/maintenance/sync_local_to_hosting_full.py"
RESTORE = ROOT / "scripts/maintenance/restore_hosting_from_local_backup.py"


def load_common():
    spec = importlib.util.spec_from_file_location(
        "forprint_hosting_mirror_contract",
        COMMON,
    )
    if spec is None or spec.loader is None:
        raise RuntimeError("Could not load hosting_mirror_common.py")

    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    try:
        spec.loader.exec_module(module)
    except Exception:
        sys.modules.pop(spec.name, None)
        raise

    return module


def assert_text_contracts(common_text: str) -> None:
    exporter_start = common_text.find("DB_EXPORT_PHP =")
    importer_start = common_text.find("REMOTE_DB_IMPORT_PHP =")

    if exporter_start < 0 or importer_start < 0:
        raise RuntimeError("Canonical DB exporter/importer blocks are missing.")

    exporter = common_text[exporter_start:importer_start]

    forbidden = [
        "fwrite(STDOUT",
        "fwrite(STDERR",
        "fgets(STDIN",
    ]
    found = [token for token in forbidden if token in exporter]

    if found:
        raise RuntimeError(
            "DB exporter regressed to CLI stdio constants: "
            + ", ".join(found)
        )

    required = [
        "php://stderr",
        "echo $json",
        "format' => 2",
    ]
    missing = [token for token in required if token not in exporter]

    if missing:
        raise RuntimeError(
            "DB exporter canonical signals missing: "
            + ", ".join(missing)
        )

    print("[OK] DB exporter is compatible with PHP Standard input code")


def assert_tar_validator(common) -> None:
    with tempfile.TemporaryDirectory() as tmp_dir:
        tmp = Path(tmp_dir)
        payload = tmp / "probe.txt"
        payload.write_text("forprint\n", encoding="utf-8")

        valid = tmp / "valid.tar.gz"
        with tarfile.open(valid, "w:gz") as archive:
            root = tarfile.TarInfo(".")
            root.type = tarfile.DIRTYPE
            root.mode = 0o755
            archive.addfile(root)

            directory = tarfile.TarInfo("./templates")
            directory.type = tarfile.DIRTYPE
            directory.mode = 0o755
            archive.addfile(directory)

            archive.add(
                payload,
                arcname="./templates/probe.txt",
            )

        result = common.validate_webroot_tar(valid)
        if result != ["templates/probe.txt"]:
            raise RuntimeError(
                f"Tar root-member regression: {result}"
            )

        protected = tmp / "protected.tar.gz"
        with tarfile.open(protected, "w:gz") as archive:
            archive.add(
                payload,
                arcname="./config.php",
            )

        try:
            common.validate_webroot_tar(protected)
        except RuntimeError as exc:
            if "protected path" not in str(exc):
                raise
        else:
            raise RuntimeError(
                "Tar validator failed open for config.php."
            )

    print("[OK] tar validator accepts root '.' and rejects protected runtime")


def assert_operator_scripts() -> None:
    for path in [BACKUP, SYNC, RESTORE]:
        if not path.is_file():
            raise RuntimeError(
                f"Missing canonical operator script: {path}"
            )

    sync_text = SYNC.read_text(encoding="utf-8")
    required_sync = [
        "hosting-storage-prepare",
        "hosting-backup-local",
        "hosting-communication-check",
        "exact_sync_scope",
        "import_database_package",
        "automatic_restore",
        "hosting-clean-release-storage",
    ]
    missing = [
        token
        for token in required_sync
        if token not in sync_text
    ]

    if missing:
        raise RuntimeError(
            "Full-sync safety contract is incomplete: "
            + ", ".join(missing)
        )

    print("[OK] full-sync safety sequence is present")


def main() -> int:
    if not COMMON.is_file():
        raise RuntimeError("hosting_mirror_common.py is missing.")

    common_text = COMMON.read_text(encoding="utf-8")
    common = load_common()

    assert_text_contracts(common_text)
    assert_tar_validator(common)
    assert_operator_scripts()

    print()
    print("HOSTING_FULL_SYNC_CONTRACT_OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
