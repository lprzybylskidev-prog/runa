"""Pure manifest composition shared across acquisition/version boundaries."""

import copy

SECTIONS = {"composer": ("require", "require-dev"), "package": ("dependencies", "devDependencies")}


def compose(platform, application, kind):
    if not isinstance(platform, dict) or not isinstance(application, dict):
        raise ValueError("Dependency manifests must be JSON objects.")
    if set(application) - set(SECTIONS[kind]):
        raise ValueError("Unsupported application dependency declaration field.")
    result = copy.deepcopy(platform)
    owned = {}
    for section in SECTIONS[kind]:
        for source in (platform, application):
            values = source.get(section, {})
            if not isinstance(values, dict):
                raise ValueError("Dependency declarations must be objects.")
            for name, constraint in values.items():
                if not isinstance(constraint, str) or not constraint.strip():
                    raise ValueError("Dependency constraints must be non-empty strings.")
                if name in owned and owned[name] != (section, constraint):
                    raise ValueError("Conflicting dependency declaration: " + name)
                owned[name] = (section, constraint)
                result.setdefault(section, {})[name] = constraint
    return result
