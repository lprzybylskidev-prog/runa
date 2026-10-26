# Application tailwind guidance

Check installed Tailwind version and existing theme before editing. Use semantic light/dark tokens and approved spacing/color scales, mobile-first base styles and explicit complete class maps. Never interpolate utility names. Keep hover/focus/disabled/busy states with the owning application component. Public Runa styles are consumed without editing protected source; add application styles/tokens in App source. Custom CSS is allowed where clearer than utilities; avoid important overrides and arbitrary z-index escalation. Preserve wide-table access and inspect overflow/stacking contexts. No plugin/helper/animation dependency without approval. Prepare rendered states and a real build for final verification; source syntax alone cannot prove generated utilities or acceptance.

For all current/future Runa module views use
docs/runa/handbook/development/frontend/customization.md. Runa supplies usable
defaults plus documented public presentation replacement. Customer screens,
wrappers and CSS belong in src/app and survive reviewed updates/clones. Use typed
public facades/controllers, preserve identity/page cleanup, CSRF, restrictions,
reauthentication and explicit retries; never copy private security orchestration.
Native errors and generated emergency 503 share one static, CSP-constrained
presentation build. Keep the application scaffold minimal; domain pages are added
only for real customer requirements.
