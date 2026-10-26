"""Pure quality selection, graph validation and declared scope contracts."""

import hashlib
import json
import re

from tools.application.quality_models import QualityStep


def select(definition, areas, final=False):
    nodes = {}
    for node in definition["base"] + [
        n for area in areas for n in definition["impact_areas"][area]
    ]:
        if node["id"] in nodes and nodes[node["id"]] != node:
            raise ValueError("Conflicting step: " + node["id"])
        nodes[node["id"]] = dict(node)
    preceding = list(nodes)
    if final:
        for node in definition["final"]:
            if node["id"] in nodes:
                raise ValueError("Duplicate final step: " + node["id"])
            nodes[node["id"]] = dict(
                node, depends_on=list(dict.fromkeys(preceding + node["depends_on"]))
            )
    for key, node in nodes.items():
        if not re.fullmatch(r"[a-z0-9-]+", key) or node["severity"] not in ("hard_fail", "warning"):
            raise ValueError("Invalid step: " + key)
        node["depends_on"] = list(
            dict.fromkeys(
                node["depends_on"]
                + [d for d in node.get("depends_on_if_selected", []) if d in nodes]
            )
        )
        if any(d not in nodes for d in node["depends_on"]):
            raise ValueError("Missing dependency: " + key)
        QualityStep.parse(node)
    done = set()
    while len(done) < len(nodes):
        ready = {k for k, n in nodes.items() if k not in done and set(n["depends_on"]) <= done}
        if not ready:
            raise ValueError("Dependency cycle.")
        done.update(ready)
    return nodes


def arguments(args):
    """Validate transport arguments before touching locks, logs or run evidence."""
    if not args or args[0] not in ("show", "roadmap", "final", "status"):
        raise ValueError("Invalid quality action.")
    groups = []
    all_groups = background = False
    for arg in args[1:]:
        if arg == "--all" and not all_groups:
            all_groups = True
        elif arg == "--background" and not background and args[0] in ("roadmap", "final"):
            background = True
        elif arg.startswith("--group=") and re.fullmatch(r"[a-z][a-z0-9-]*", arg[8:]):
            groups.append(arg[8:])
        else:
            raise ValueError("Invalid quality argument: " + arg)
    if (all_groups and groups) or (args[0] == "status" and args[1:]):
        raise ValueError("Use either --all or repeated --group=NAME; status takes no options.")
    return sorted(set(groups)) or None, all_groups, background


def resolve(definition, areas, final=False, groups=None, all_groups=False):
    if groups is None and not all_groups:
        return select(definition, areas, final), {
            "mode": "legacy",
            "impact_areas": areas,
            "final": final,
        }
    catalogue = definition["groups"]
    if (all_groups and groups) or (groups is not None and not groups):
        raise ValueError("Provide named groups or --all.")
    requested = sorted(catalogue if all_groups else set(groups))
    if any(group not in catalogue for group in requested):
        raise ValueError("Unknown or unavailable quality group. Available: " + ", ".join(catalogue))
    inventory = {}
    for node in (
        definition["base"]
        + [n for values in definition["impact_areas"].values() for n in values]
        + definition["final"]
        + definition.get("group_steps", [])
    ):
        key = node["id"]
        if key in inventory and inventory[key] != node:
            raise ValueError("Conflicting step: " + key)
        inventory[key] = node
    reasons = {}

    def include(key, reason):
        if key not in inventory:
            raise ValueError("Missing dependency or group step: " + key)
        if key in reasons:
            if reason not in reasons[key]:
                reasons[key].append(reason)
            return
        reasons[key] = [reason]
        for dependency in inventory[key]["depends_on"]:
            include(dependency, "prerequisite of " + key)

    for key in definition["mandatory"]:
        include(key, "mandatory baseline")
    for group in requested:
        for key in catalogue[group]:
            include(key, "group " + group)
    # Preserve definition order and validate the expanded graph through the same scheduler contract.
    chosen = [dict(node) for key, node in inventory.items() if key in reasons]
    final_ids = {node["id"] for node in definition["final"]}
    nodes = select(
        {
            "base": [node for node in chosen if node["id"] not in final_ids],
            "impact_areas": {},
            "final": [node for node in chosen if node["id"] in final_ids],
        },
        [],
        True,
    )
    scope = {
        "mode": "all" if all_groups else "groups",
        "requested_groups": requested,
        "excluded_groups": sorted(set(catalogue) - set(requested)),
        "mandatory": definition["mandatory"],
        "reasons": reasons,
        "graph_hash": graph_hash(nodes),
    }
    return nodes, scope


def graph_hash(nodes):
    return hashlib.sha256(
        json.dumps(nodes, sort_keys=True, separators=(",", ":")).encode()
    ).hexdigest()


def show_scope(scope):
    if scope.get("mode", "legacy") == "legacy":
        print("Scope: legacy impact-area selection (no explicit groups).")
        return
    print("Scope: " + scope["mode"] + "; requested groups: " + ", ".join(scope["requested_groups"]))
    print("Mandatory baseline: " + ", ".join(scope["mandatory"]))
    print("Excluded groups (not run): " + (", ".join(scope["excluded_groups"]) or "none"))
    for key, reasons in scope["reasons"].items():
        print("Included " + key + ": " + "; ".join(reasons))


def validate_declared(roadmap, scope):
    declared = roadmap.get("quality_groups")
    if declared is None:
        return
    if (
        not isinstance(declared, str)
        or not isinstance(roadmap.get("quality_rationale"), str)
        or not roadmap["quality_rationale"].strip()
    ):
        raise ValueError("Final selection requires quality_groups and quality_rationale strings.")
    if declared == "all":
        matches = scope.get("mode") == "all"
    else:
        groups = declared.split(",")
        matches = (
            all(re.fullmatch(r"[a-z][a-z0-9-]*", group) for group in groups)
            and scope.get("mode") == "groups"
            and sorted(set(groups)) == scope.get("requested_groups")
        )
    if not matches:
        raise ValueError(
            "Final quality selection differs from the roadmap quality_groups agreement."
        )
