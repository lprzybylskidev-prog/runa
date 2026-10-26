# Python development dependency review

Reviewed on 2026-10-26 for RM-2026-012 point 2. The accepted roadmap mandates
pytest, Ruff, strict Pyright and private setuptools builds. No Composer/pnpm
manifest addition is involved. Runtime packages remain stdlib-only.

| Python component | Exact version | License                    | Selected Linux x86_64 wheel bytes |
| ---------------- | ------------- | -------------------------- | --------------------------------: |
| pytest           | 9.1.1         | MIT                        |                           386,536 |
| ruff             | 0.17.2        | MIT                        |                        10,528,000 |
| setuptools       | 84.2.1        | MIT                        |                           876,263 |
| pip              | 26.2.2        | MIT                        |                         1,819,850 |
| wheel            | 0.48.0        | MIT                        |                            33,320 |
| iniconfig        | 2.3.1         | MIT                        |                             7,552 |
| packaging        | 26.3          | Apache-2.0 OR BSD-2-Clause |                           129,956 |
| pluggy           | 1.7.0         | MIT                        |                            27,082 |
| pygments         | 2.21.0        | BSD-2-Clause               |                         1,250,147 |

The Python graph contains nine locked distributions. pytest uses iniconfig,
packaging, pluggy and Pygments on Linux CPython 3.11/3.12. wheel uses packaging;
pip, Ruff and default setuptools add no resolved runtime dependencies. Setuptools
vendors its own build internals; optional upstream test/docs/core extras are not
installed. Windows/Python <3.11 conditionals and all optional pytest plugins are
outside this supported graph. The lock includes reviewed manylinux x86_64/aarch64
Ruff wheels plus universal wheels; aarch64 availability is not acceptance evidence.

Pyright 1.1.414 is Microsoft's MIT-licensed official npm distribution, Node >=14,
19,457,120 unpacked bytes. Existing Node 24.22.0 satisfies that requirement. Its
optional fsevents ~2.3.3 is macOS-only and omitted rather than introducing another
Linux dependency. The committed artifact integrity covers the complete tarball.
No Python Pyright wrapper/Node downloader, mypy, Black, isort, xdist, build frontend
or extra fixture plugin is installed. Ruff covers lint/import ordering/format;
Pyright covers types; pytest covers tests; setuptools/wheel/pip handle private
build/install. These responsibilities do not duplicate the existing PHP/JS tools.

Upstream registries show active maintained releases for all selected tools.
pytest/Ruff/setuptools/Pyright upstream documentation was reviewed for explicit
packages/resources, importlib collection, Python 3.11 targets and strict config.
PyPI release metadata for all nine selected versions lists no affected advisories;
an exact-version OSV batch query for all nine plus npm Pyright returned ten empty
results. Local raw responses are preserved in
`.runtime/python/reports/dependency-{metadata,advisories}.json`. This is a bounded
published-advisory review, not final filesystem/image/security clearance.

Setuptools has a real advisory history, including PackageIndex traversal
GHSA-5rjg-fvgr-3xxf and macOS Unicode sdist exclusion GHSA-h35f-9h28-mq5c.
The selected wheel version is not flagged by the reviewed version queries;
Runa builds explicitly staged wheels without PackageIndex or sdist discovery.
pytest and Ruff upstream security pages list no published advisories at review.
Absence of a published advisory does not prove absence of vulnerabilities.
Final selected security gates remain mandatory and strict.

Primary review sources:

- [pytest release metadata](https://pypi.org/pypi/pytest/9.1.1/json),
  [integration guide](https://docs.pytest.org/en/stable/explanation/goodpractices.html),
  [security](https://github.com/pytest-dev/pytest/security).
- [Ruff release metadata](https://pypi.org/pypi/ruff/0.17.2/json),
  [configuration](https://docs.astral.sh/ruff/configuration/),
  [security](https://github.com/astral-sh/ruff/security).
- [setuptools release metadata](https://pypi.org/pypi/setuptools/84.2.1/json),
  [resources](https://setuptools.pypa.io/en/latest/userguide/datafiles.html),
  [security history](https://github.com/pypa/setuptools/security).
- [Pyright exact npm metadata](https://registry.npmjs.org/pyright/1.1.414),
  [configuration](https://github.com/microsoft/pyright/blob/main/docs/configuration.md),
  [source/security reporting](https://github.com/microsoft/pyright).
- [pip](https://pypi.org/pypi/pip/26.2.2/json),
  [wheel](https://pypi.org/pypi/wheel/0.48.0/json),
  [iniconfig](https://pypi.org/pypi/iniconfig/2.3.1/json),
  [packaging](https://pypi.org/pypi/packaging/26.3/json),
  [pluggy](https://pypi.org/pypi/pluggy/1.7.0/json),
  [Pygments](https://pypi.org/pypi/pygments/2.21.0/json),
  [OSV API](https://google.github.io/osv.dev/api/).
