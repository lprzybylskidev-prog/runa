"""Reviewed source declarations: applicability is distinct from index freshness."""

import fnmatch
import json
import re
from datetime import date
from typing import cast

from tools.application.knowledge.configuration import Applicability, KnowledgeConfig, source_paths
from tools.application.knowledge.corpus import digest
from tools.core.state import json_value

PURPOSES = ("current_guidance", "historical_evidence", "proposal", "unknown")
MARKER = re.compile(r"<!-- runa-knowledge (\{.*\}) -->")


def extract(text: str) -> tuple[str, list[Applicability]]:
    """Only standalone single-line comments outside fenced examples are declarations."""
    output: list[str] = []
    declarations: list[Applicability] = []
    fence: tuple[str, int] | None = None
    for line in text.splitlines():
        marker = re.match(r"^\s{0,3}(`{3,}|~{3,})", line)
        if marker:
            if fence is None:
                fence = (marker[1][0], len(marker[1]))
            elif (
                fence[0] == marker[1][0]
                and len(marker[1]) >= fence[1]
                and not line.strip().strip(fence[0])
            ):
                fence = None
            output.append(line)
            continue
        match = MARKER.fullmatch(line) if fence is None else None
        if match:
            declarations.append(validate_metadata(json_value(json.loads(match[1]))))
        else:
            output.append(line)
    return "\n".join(output), declarations


def validate_date(value: str) -> None:
    try:
        date.fromisoformat(value)
    except ValueError:
        raise ValueError("Knowledge annotation date must be an ISO calendar date.") from None


def validate_metadata(value: object) -> Applicability:
    if not isinstance(value, dict):
        raise ValueError("Knowledge annotation must be an object.")
    data = cast(dict[str, object], value)
    if (
        set(data) != {"purpose", "effective_date", "applicable_version", "decisions"}
        or data.get("purpose") not in PURPOSES
    ):
        raise ValueError("Invalid knowledge applicability declaration.")
    for key in ("effective_date", "applicable_version"):
        if data[key] is not None and not isinstance(data[key], str):
            raise ValueError("Applicability scope must be a string or null.")
    if isinstance(data["effective_date"], str):
        validate_date(data["effective_date"])
    decisions = data["decisions"]
    if not isinstance(decisions, list):
        raise ValueError("Decision annotations must be a list.")
    seen: set[str] = set()
    for item in cast(list[object], decisions):
        if not isinstance(item, dict):
            raise ValueError("Invalid decision annotation.")
        decision = cast(dict[str, object], item)
        if set(decision) != {
            "id",
            "problem",
            "solution",
            "rationale",
            "status",
            "date",
            "version",
            "supersedes",
        }:
            raise ValueError("Invalid decision annotation fields.")
        for key in ("id", "problem", "solution"):
            if not isinstance(decision[key], str) or not decision[key]:
                raise ValueError("Decision identity/problem/solution must be explicit.")
        identifier = cast(str, decision["id"])
        if not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9_.:-]{0,127}", identifier) or identifier in seen:
            raise ValueError("Invalid or duplicate decision ID.")
        seen.add(identifier)
        if decision["status"] not in ("accepted", "superseded", "proposed", "unknown"):
            raise ValueError("Invalid decision status.")
        for key in ("rationale", "date", "version"):
            if decision[key] is not None and not isinstance(decision[key], str):
                raise ValueError("Unknown decision rationale/scope must remain null.")
        if isinstance(decision["date"], str):
            validate_date(decision["date"])
        links = decision["supersedes"]
        if not isinstance(links, list) or any(
            not isinstance(link, str) or not link or link == identifier
            for link in cast(list[object], links)
        ):
            raise ValueError("Invalid decision supersession links.")
    return cast(Applicability, data)


def validate_policy(config: dict[str, object]) -> None:
    if (
        type(config.get("payload_schema_version", 2)) is not int
        or config.get("payload_schema_version", 2) != 2
    ):
        raise ValueError("Unsupported knowledge payload schema version.")
    rules = config.get("metadata_rules", [])
    annotations = config.get("annotations", [])
    if not isinstance(rules, list) or not isinstance(annotations, list):
        raise ValueError("Knowledge metadata policy must contain lists.")
    for item in cast(list[object], rules):
        if not isinstance(item, dict):
            raise ValueError("Invalid reviewed knowledge metadata rule.")
        rule = cast(dict[str, object], item)
        if set(rule) != {"pattern", "purpose"} or rule.get("purpose") not in PURPOSES:
            raise ValueError("Invalid reviewed knowledge metadata rule.")
        source_paths([rule.get("pattern")])
    for item in cast(list[object], annotations):
        if not isinstance(item, dict):
            raise ValueError("Invalid external knowledge annotation.")
        annotation = cast(dict[str, object], item)
        if set(annotation) != {
            "source_path",
            "source_hash",
            "section",
            "metadata",
        }:
            raise ValueError("Invalid external knowledge annotation.")
        source_paths([annotation.get("source_path")])
        if (
            not isinstance(annotation["section"], str)
            or not isinstance(annotation["source_hash"], str)
            or not re.fullmatch(r"[a-f0-9]{64}", annotation["source_hash"])
        ):
            raise ValueError("External annotation needs an exact source hash and section.")
        validate_metadata(annotation["metadata"])


def policy_hash(config: KnowledgeConfig) -> str:
    return digest(
        json.dumps(
            {
                "version": 2,
                "rules": config.get("metadata_rules", []),
                "annotations": config.get("annotations", []),
            },
            sort_keys=True,
        )
    )


def declared(
    config: KnowledgeConfig, name: str, text: str, section: str, body: str
) -> Applicability:
    result: Applicability = {
        "purpose": "unknown",
        "effective_date": None,
        "applicable_version": None,
        "decisions": [],
    }
    for rule in config.get("metadata_rules", []):
        if fnmatch.fnmatchcase(name, rule["pattern"]):
            result["purpose"] = rule["purpose"]
    # Source-level declarations precede the first Markdown heading; section markers
    # override them. Closed history is annotated externally, never rewritten.
    preamble = re.split(r"^#{1,6}\s", text, maxsplit=1, flags=re.M)[0]
    for content in (preamble, body):
        for declaration in extract(content)[1]:
            result = declaration
    for annotation in config.get("annotations", []):
        if annotation["source_path"] != name:
            continue
        if annotation["source_hash"] != digest(text):
            raise ValueError("External annotation source hash changed; review the annotation.")
        if annotation["section"] == section:
            result = annotation["metadata"]
    return result
