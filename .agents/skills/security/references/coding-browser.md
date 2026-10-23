# Secure Coding and Browser Boundary

1. Treat authentication, authorization, secrets, crypto, file handling, shell, external input, redirects, and outbound network code as security-sensitive review paths.
2. Input validation does not replace output encoding.
3. Escape/encode for the specific output context: HTML, attribute, URL, JavaScript, JSON.
4. Do not disable framework escaping without a controlled/sanitized content need.
5. Parameterize raw SQL values.
6. Dynamic SQL identifiers come from explicit whitelists.
7. Process execution uses separated validated arguments, not concatenated shell strings.
8. Prefer language/library APIs over shell execution.
9. User-influenced URL fetching must defend against SSRF, including schemes, private/internal targets, redirects, and localhost.
10. Validate/restrict redirect destinations to prevent open redirects.
11. Do not trust proxy headers without correct trusted-proxy configuration.
12. Do not trust Host as a sensitive absolute-URL authority without validation.
13. Avoid account-enumeration differences where authentication/recovery flow would expose existence unnecessarily.
14. Use timing-safe primitives where secret/signature comparison requires them.
15. Do not invent cryptography.
16. Generate security values with a CSPRNG, never Faker/`rand()`.
17. Apply least privilege to processes/services.
18. Runtime DB credentials are not PostgreSQL superuser credentials.
19. Separate runtime credentials from administrative/deploy credentials where practical.
20. Store a hash instead of a recoverable secret when only equality verification is needed.
21. Define a production browser security-header baseline: CSP, nosniff, Referrer-Policy, frame-ancestors, HSTS when HTTPS/domain state is correct.
22. CSP should be restrictive and intentional rather than `*` everywhere.
23. Avoid `unsafe-inline`/`unsafe-eval`; document a real dependency trade-off if one is unavoidable.
24. Do not enable HSTS blindly before production HTTPS/domain behavior is correct.
25. Prefer CSP `frame-ancestors` for clickjacking protection; legacy headers may be compatibility additions.
26. Send `X-Content-Type-Options: nosniff`.
27. Limit cookie Path/Domain scope to what is needed.
28. Choose SameSite from the actual flow; do not use `None` without a concrete reason.
29. Do not put secrets in URLs/query strings.
30. Use `Cache-Control: no-store` for truly sensitive responses where appropriate, not globally.
31. Security decisions are server-side.
32. Frontend permissions/route guards are never the authorization boundary.
33. Whitelist/validate sensitive writable fields; hidden UI does not prevent mass assignment.
34. Re-check authoritative state/authorization at sensitive writes.
35. Rate limiting supplements rather than replaces auth/authz/validation/idempotency.
36. Brute-force controls may need multiple dimensions rather than one naive global counter.
37. Do not permanently lock accounts using a simple attacker-triggerable failed-login counter.

Module account policy runs before controller/FormRequest resolution; Public/Authenticated/Restricted metadata never substitutes for current eligibility, permission or step-up checks. Never derive TrustedPrincipal from request fields or call a protected identity API recursively. Preparation contexts are trusted update-local capabilities, never request bypass flags. See [module foundations](../../../../docs/runa/handbook/architecture/module-foundations.md).

Meaningful business/security mutations require [Audit history](../../../../docs/runa/handbook/modules/audit/README.md). Use Auditable with source-owned approved fields inside the default PostgreSQL transaction; automatic capture supports single-model save/delete/restore and scoped suppression. Bulk SQL, relationship/pivot changes and aggregate operations require explicit manual events on that same transaction. Suppression never disables explicit records. Follow the guide for nested redaction, fail-closed rollback, independent refusal and permission-protected bounded reads; arbitrary Eloquent writes are not automatically captured.
