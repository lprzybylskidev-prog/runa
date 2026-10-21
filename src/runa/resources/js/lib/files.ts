import type { FileConstraints, FileIssue } from '../runa/ui/forms';
/** UX checks only: MIME/name are user-controlled and never authorize a server upload. */
export function validateFiles(
  files: readonly globalThis.File[],
  constraints: FileConstraints = {},
): FileIssue[] {
  const issues: FileIssue[] = [];
  const limit = constraints.multiple ? constraints.maxFiles : 1;
  if (limit !== undefined && files.length > Math.max(0, limit))
    issues.push({ code: 'count' });
  const rules = (constraints.accept ?? '')
    .split(',')
    .map((rule) => rule.trim().toLowerCase())
    .filter(Boolean);
  for (const file of files) {
    if (constraints.maxBytes !== undefined && file.size > constraints.maxBytes)
      issues.push({ code: 'size', fileName: file.name });
    if (
      rules.length &&
      !rules.some((rule) =>
        rule.startsWith('.')
          ? file.name.toLowerCase().endsWith(rule)
          : rule.endsWith('/*')
            ? file.type.toLowerCase().startsWith(rule.slice(0, -1))
            : file.type.toLowerCase() === rule,
      )
    )
      issues.push({ code: 'type', fileName: file.name });
  }
  return issues;
}
