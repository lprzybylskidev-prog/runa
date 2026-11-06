"""Current Core declaration/consumer contracts; no rendered PASS implied."""

from __future__ import annotations

import json
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[4]
FIXTURES = Path(__file__).parent / "fixtures/admin"
BASELINE = ROOT / "docs/runa/maintainer/architecture/admin-contract-baseline.json"


def signatures(source: str) -> dict[str, str]:
    """Normalize existing simple declarations, including promoted DTO constructors."""
    result: dict[str, str] = {}
    for match in re.finditer(
        r"public function (\w+)\((.*?)\)\s*(?::\s*([^\n{;]+))?\s*[{;]", source, re.S
    ):
        name, arguments, returns = match.groups()
        arguments = re.sub(r"\s+", " ", arguments).strip().rstrip(",")
        result[name] = f"{name}({arguments})" + (f": {returns.strip()}" if returns else "")
    return result


def php(code: str, *arguments: str) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        [
            "php",
            "-r",
            "require $argv[1].'/src/vendor/autoload.php';" + code,
            str(ROOT),
            *arguments,
        ],
        cwd=ROOT,
        text=True,
        capture_output=True,
        check=False,
    )


def test_current_core_methods_declare_versions_permissions_and_audiences() -> None:
    baseline = json.loads(BASELINE.read_text())
    for api in baseline["apis"]:
        definition = (ROOT / api["definition"]).read_text()
        source = (ROOT / api["source"]).read_text()
        actual = signatures(source)
        assert set(api["imports"]) <= set(re.findall(r"^use ([^;]+);", source, re.M))
        declaration = re.search(
            r"new ApiDefinition\((\w+)::class,\s*\w+::class,\s*\w+::class,\s*'([^']+)'",
            definition,
        )
        assert declaration is not None
        assert declaration[1] == api["contract"].rsplit("\\", 1)[1]
        # The activation ledger is historical; current source-owned API versions
        # include the additive and pre-1.0 revisions delivered by this roadmap.
        expected_version = {
            "AuthLifecycle": "2.1.0",
            "AuthSecurity": "1.2.0",
            "AuthStepUp": "2.0.0",
            "AuthProfiles": "2.1.0",
            "AuthPreferences": "1.1.0",
            "AuthPolicies": "3.0.0",
            "AuthBulkSessions": "2.0.0",
            "PermissionsAccess": "2.2.0",
            "AuthAdministration": "2.1.0",
            "AuditReads": "1.1.0",
        }.get(api["contract"].rsplit("\\", 1)[1], api["api_version"])
        assert declaration[2] == expected_version
        assert f"use {api['contract']};" in definition
        manifest = json.loads((ROOT / api["source"]).parents[2].joinpath("module.json").read_text())
        assert api["provider"] in manifest["declarations"]["apis"]
        # The dated ledger locates owning declarations; it does not freeze methods
        # or signatures after the owner approved direct pre-1.0 refactoring.
        for method in actual:
            operation = re.search(
                rf"'{method}'\s*=>\s*new ApiOperation\('([^']+)'(?:,\s*'([^']+)')?\)",
                definition,
            )
            assert operation is not None, (api["contract"], method)
            assert re.fullmatch(r"[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*", operation[1])
            if operation[2] is not None:
                assert re.fullmatch(r"[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*", operation[2])


def test_optional_extension_does_not_change_existing_provider_and_value_signatures() -> None:
    for contract in json.loads(BASELINE.read_text())["legacy"]:
        actual = signatures((ROOT / contract["source"]).read_text())
        for expected in contract["signatures"]:
            assert actual[expected.split("(", 1)[0]] == expected, contract["source"]
        if contract["source"].endswith(("ManagementProvider.php", "ManagementEntityProvider.php")):
            # Adding required methods to a current provider breaks customer implementations.
            assert set(actual.values()) == set(contract["signatures"])


def test_ordinary_consumer_fixtures_use_real_public_boundary_without_foreign_access() -> None:
    result = php(
        r"""
$boundary = new Runa\ModuleFramework\Development\ApiBoundary;
$cases = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR)['cases'];
foreach ($cases as $case) {
    $actual = $boundary->php($case['source'], '', 'src/app/AdminFixture.php');
    if ($actual !== $case['expected']) {
        fwrite(STDERR, json_encode(['expected' => $case['expected'], 'actual' => $actual]));
        exit(1);
    }
}
""",
        str(FIXTURES / "consumers.json"),
    )
    assert result.returncode == 0, result.stderr


def test_legacy_provider_and_schema_bounds_execute_against_existing_framework() -> None:
    result = php(
        r"""
require $argv[2];
$provider = new App\AdminContractFixture\LegacyProvider;
$entity = $provider->entities()[0];
if ($entity->schemaVersion !== 1 || $entity->fields[0]->maxLength !== 160
    || $entity->operations[0]->inputs !== ['title']) exit(2);
$names = ['pl' => 'Pole', 'en' => 'Field'];
$field = $entity->fields[0];
$rejected = [
    fn () => new Runa\Contracts\Modules\ManagementEntity('record', 'app.fixture.manage', $names, $names, array_fill(0, 65, $field), []),
    fn () => new Runa\Contracts\Modules\ManagementField('secret', 'text', $names, $names, filterable: true, sensitive: true),
    fn () => new Runa\Contracts\Modules\ManagementField('title', 'text', $names, $names, maxLength: 65537),
    fn () => new Runa\Contracts\Modules\ManagementEntity('record', 'app.fixture.manage', $names, $names, [$field], [new Runa\Contracts\Modules\ManagementOperation(new Runa\Contracts\Modules\ApiOperation('save', 'app.fixture.manage'), ['unknown'])]),
    fn () => new Runa\Contracts\Modules\ManagementComponent('panel', '/arbitrary/import.vue', 'app.fixture.manage'),
    fn () => new Runa\Contracts\Modules\SlotHost('host', 'host', 1, ['subject' => 'object']),
];
foreach ($rejected as $case) {
    try { $case(); exit(3); } catch (InvalidArgumentException) {}
}
$slot = new Runa\Contracts\Modules\SlotHost('host', 'host', 1, ['publicId' => 'string']);
if ($slot->jsonSerialize()['schemaVersion'] !== 1) exit(4);
""",
        str(FIXTURES / "legacy-consumer.php"),
    )
    assert result.returncode == 0, result.stderr


def test_application_settings_container_adapters_do_not_export_internal_constructor_dependencies() -> (
    None
):
    result = php(
        r"""
$check = new Runa\Development\ApiSourceCheck;
foreach (['ApplicationSettings', 'ApplicationPresentation'] as $adapter) {
    $path = 'src/runa/Support/Settings/'.$adapter.'.php';
    if ($check->signatures(file_get_contents($argv[1].'/'.$path), $path) !== []) exit(1);
}
"""
    )
    assert result.returncode == 0, result.stderr


def test_named_settings_snapshot_adapters_hide_container_only_constructor_types() -> None:
    result = php(
        r"""
$check = new Runa\Development\ApiSourceCheck;
foreach (['SettingsSnapshots', 'SettingsSnapshotSection'] as $adapter) {
    $path = 'src/runa/Support/Modules/Settings/'.$adapter.'.php';
    if ($check->signatures(file_get_contents($argv[1].'/'.$path), $path) !== []) exit(1);
}
"""
    )
    assert result.returncode == 0, result.stderr
