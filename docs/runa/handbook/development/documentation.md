# Documentation authoring

## Audience and authority

Use [documentation ownership](../../../README.md) to choose the reader and home
before writing. Application decisions/examples belong in docs/app, delivered Runa
procedures in the handbook and owner engineering/history in maintainer records.
Public documents and installed agent guidance must work without owner files.
Link one canonical procedure rather than creating independently maintained copies.

Current guides describe delivered behavior. Roadmaps describe agreed future work
and record decisions; release/changelog entries describe changes; dated evidence
records what actually ran and its limits. Preserve closed roadmaps and immutable
evidence, including historical wording and paths. Correct current prose around
those records rather than rewriting them to match newer behavior. Use the
applicable roadmap/release workflow for lifecycle and version decisions.

## Structure and metadata

Use one descriptive level-one title, followed by task-oriented level-two sections
and subordinate headings. Keep heading names stable when practical; update current
inbound links when renaming them. Do not add empty headings or sections merely to
imitate a template. Use tables for comparable properties and lists for ordered
steps or parallel items; prose explains purpose, consequences and tradeoffs.

Metadata follows the owning document format: roadmap frontmatter, skill name and
description, manifest schemas and optional reviewed knowledge annotations retain
their existing contracts. Ordinary guides do not need invented frontmatter.
Dates, versions, status and provenance must have evidence; unknown values stay
unknown. A current source hash or index timestamp does not establish factual truth.
Examples of metadata belong in fenced code, not an active leading record.

## Language and technical contracts

Write repository/backend/API explanations in English. Use consistent platform,
module and application terminology and distinguish their versions and owners.
Preserve Polish-default/English-secondary product copy in examples where relevant.
Use plain verbs and concrete subjects; remove promotional filler and redundant
introductions without removing security consequences, refusal/recovery conditions
or unusual operation constraints.

Document an API's owner, audience, entrypoint, complete parameter/return types,
defaults, implementation home and delivery/runtime status in its owning guide.
Source visibility is not public support. Mark abbreviated excerpts as excerpts
and link the complete contract; never present pseudo-signatures as callable code.
Synchronize current consumers/generators and examples when a contract changes.

Use fenced examples with the actual language and supported command transport.
Name prerequisites, execution location, effects, expected output and failure or
recovery behavior when needed. Use safe placeholders rather than real secrets;
never paste private payloads, environment values or credentials. Distinguish an
example command from evidence that it ran. Warnings identify a real condition,
consequence and action; do not repeat generic warnings on every page.

## Links and anchors

Use relative Markdown links for repository sources, with paths resolved from the
installed logical location for delivered instruction templates. Public links must
not depend on maintainer files or private tools. External sources use descriptive
HTTPS links; record exact revisions/licensing when adapting source material.
External availability/content is a source-review concern, not an offline PASS.

The local checker supports inline links, reference destinations, ATX/setext
headings and explicit HTML id/name anchors. Heading anchors use lowercase text,
preserve Unicode letters/numbers/marks, underscores and hyphens, omit formatting
and punctuation, and replace spaces with hyphens. Duplicate generated headings
receive the first unused numeric suffix. Prefer distinctive headings or an explicit
stable anchor for complicated markup. Percent-encoded paths/fragments are decoded
before checking ownership and existence. Asset fragments are not Markdown headings.

Leading metadata, comments and fenced examples do not create links
or headings. A fence closes only with the same character, at least its opening
length and no trailing nonspace text. Keep links/anchor markup out of inline code
unless they are examples. Literal comment delimiters inside fenced or inline code
remain examples; they cannot hide real prose between separate examples. Inline
code closes with a backtick run of exactly its opening length, including code
spans continued within a paragraph. Real comments exclude their content even when
it contains example-looking fences or links.
Use fenced rather than indented code examples: links in
indented prose/list items are still checked. This is an offline source checker, not a complete
Markdown renderer; for complex markup confirm the rendered target as well.

## Maintenance and evidence

Update current guidance in the owning implementation point and assess both agent
instruction products. Skills select topics; handbook guides own procedures;
AGENTS retains cross-task constraints. Follow [guidance editing](../ai/skills-policy.md#editing-reusable-guidance)
for provenance, selective loading and local customer extensions.

Supported documentation checks validate required sources, ownership/public
projection, local links/heading targets and one leading title. Format-specific
metadata, command/roadmap/current-state checks retain their existing owners.
They do not assess writing quality, security truth, external pages, full rendered
accessibility or native installation. Perform semantic source review separately.
Use the active roadmap for check timing; broad verification remains final and
mandatory commit hooks still apply.
