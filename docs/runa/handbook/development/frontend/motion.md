# Component motion

Use the accepted ApplicationLayout navigation as the reference: popup opacity with 4px travel over 140ms ease-out; disclosure/chevron/color changes over 160ms; shell geometry over 180ms. Existing root preference-cover timing (340ms and 500ms hold) stays unchanged. The owner requested this review in RM-2026-004 point 21; its rendered result was accepted on 2026-10-19.

For every component change, inspect opening **and closing**, selected/hover/focus/busy/error states, and existing consumers. Animate only useful changes. Prefer named opacity/translate/color properties; avoid blanket `transition: all`, delayed state updates, wholesale remounts, decorative entrance cascades and animated dense table sorting. Reuse shared component behavior rather than adding page-local copies.

Closed or exiting content becomes inert and hidden from accessibility immediately; focus return and logical dismissal must not wait for visual completion. Interrupted reopening restores interactivity. Teleported panels must keep their last visible position during exit even when positioning readiness resets. Disclosures keep drafts mounted, clip natural height and exclude collapsed children from keyboard access. Compose public Accordion/FormSection where they fit, or implement the pattern in application source. AppCollapse is a protected internal implementation, not a public import.

Use CSS for ordinary transitions. The internal useContentMotion helper gives retained Wizard/CalendarPicker/AsyncContent DOM a brief content change cue without replacing fields. Cancel superseded animation, respect live reduced-motion changes, printing and unmounting. CSS transitions/animations also stop under reduced motion and print. Loading/progress signals remain understandable without movement.

Verify real rendered normal/reduced behavior, rapid reversal, Escape, keyboard focus, preserved drafts, mobile/tablet/desktop and both themes/locales. Existing screenshots cannot prove timing; use interaction tests and inspect intermediate visibility/inert state where meaningful. No dependency or global animation service is needed.

## Established component motion inventory

Names below refer to `src/runa/resources/js/components/<name>.vue`. Every component is assigned once; inherited motion is deliberate and must stay in the shared source.

| Components                                                                                                      | Decision                                                                                                                                                          |
| --------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AppSelect                                                                                                       | Added 140ms popup entrance/exit, rotating chevron and option color transition; visible but inert exit.                                                            |
| AppCollapse, AppAccordion, AppFormSection                                                                       | Added natural-height/opacity disclosure; retained DOM, immediate inert/aria-hidden. Noncollapsible sections stay static.                                          |
| AppTabs, AppWizard, AppCalendarPicker, AppAsyncContent                                                          | Added brief retained-content reveal on tab/step/calendar/state change. No remount or deferred focus.                                                              |
| AppFormField, AppOperationPanel                                                                                 | Added feedback appearance/disappearance; error/status text and accessibility update immediately.                                                                  |
| AppFileInput, AppTagsInput                                                                                      | Added small item entrance/exit with stable identity; removed controls inert immediately, focus moves to surviving control/input. No positional reorder animation. |
| AppPopover, AppTooltip                                                                                          | Retained accepted 140ms fade/travel; repaired exit visibility after floating readiness resets. Popover also restores interactivity on interrupted reopen.         |
| AppDialog, AppToastViewport                                                                                     | Retained existing fade/scale/stack presentation; exiting roots inert immediately and interrupted enter restores interactivity.                                    |
| AppDropdown                                                                                                     | Inherits Popover/Button motion; added rotating indicator.                                                                                                         |
| AppTable                                                                                                        | Query changes show a centered spinner for at least 140ms; processing/requests start immediately. Widths/selection remain immediate, without moving rows.          |
| AppButton, AppLinkButton, AppBooleanInput, AppSwitch, AppProgress, AppSkeleton, AppSpinner                      | Retained existing interaction/progress/loading transitions. Boolean background/border/color easing is shared; reduced motion retains static state cues.           |
| AppCheckbox, AppRadioGroup                                                                                      | Inherit BooleanInput visual state easing.                                                                                                                         |
| AppAlertDialog, AppConfirmDialog                                                                                | Inherit Dialog and action-control motion; no second entrance.                                                                                                     |
| AppDateInput, AppDateRangeInput, AppDateTimeInput, AppTimeInput, AppTemporalInput, AppTimePicker, AppColorInput | Inherit field, Popover, CalendarPicker and control motion as composed; text/time/color input and pointer adjustment stay immediate.                               |
| AppPickerSlider                                                                                                 | Pointer/keyboard position and value stay immediate; inherits shared control hover/focus feedback. No easing behind the pointer.                                   |
| AppTextInput, AppNumberInput, AppMaskedInput                                                                    | Inherit shared field/control color and feedback motion. Typed values stay immediate.                                                                              |
| AppButtonGroup, AppPagination, AppTableActions, AppTablePreferences, AppPreferenceControls                      | Inherit shared buttons/selects/popovers/dialogs and fields. No extra group animation or delayed action.                                                           |
| AppToast, AppLoadingOverlay                                                                                     | Inherit viewport/spinner/feedback or established overlay behavior; retain existing loading delay and minimum-display contract.                                    |
| AppNavigation, AppTopNavigation                                                                                 | Retain accepted dedicated shell disclosure/dropdown/indicator motion. Do not extract into public controls.                                                        |
| AppAlert, AppBadge, AppStatus, AppEmptyState                                                                    | Static semantic content; the owning feedback/async container animates state changes when appropriate. Avoid duplicate entrances/announcements.                    |
| AppCard, AppDivider, AppBreadcrumbs, AppStatistic, AppStatistics, AppFooter                                     | Static structure/content; links/controls retain shared interaction feedback. No count tween or decorative mount animation.                                        |
| AppForm, AppHiddenInput, AppIdentityProvider                                                                    | Behavioral/identity/form contracts; no independent visual transition.                                                                                             |
| AppIcon, RunaLogo, AppConstellation                                                                             | Static assets/backgrounds; preserve accepted artwork and geometry without decorative movement.                                                                    |

Layouts: ApplicationLayout keeps its accepted 140/160/180ms navigation rhythm. MinimalLayout keeps the accepted preference cover and loading feedback, plus sparse randomized constellation-point opacity pulses in its backdrop. ApplicationLayout uses the same backdrop with the effect disabled. Both inherit content motion through shared components; neither receives a page entrance animation. Email and native error output remain static, suitable for email/print and independent of the application runtime.

AuthWelcome/AuthApplication/AuthAccount change page, restriction and form steps
immediately. Their links, controls, dropdown and preference cover inherit existing
motion and reduced-motion behavior; account tables do not animate rows.

MinimalLayout constellation glow uses one point at a time: a 3000ms gentle
opacity rise/fall across its three existing circle layers, then a random 7–15s
quiet interval. It chooses among viewport-visible points without repeating the
previous point. It never moves links, geometry, gradients or artwork. Reduced
motion, print, hidden tabs and static error rendering disable decorative glow;
opt-out/unmount cancels timers and animations. This exception is specific to
MinimalLayout and does not authorize animated ApplicationLayout backgrounds.

The MinimalLayout default brand illustration opts into one six-second lap of its
six points after each random 8–14 second quiet interval, with gentle opacity
pulsation during travel. Orbit borders/logo stay fixed; point positions are sampled
along their own ellipse and return exactly to the static start. No continuous spin.
AppBrandIllustration is static by default outside that layout. Both decorative
effects share a private scheduler that cancels timers/animations on reduced motion,
print, hidden documents, opt-out and disposal. Their public controls are
MinimalLayout constellationGlow/orbitMotion; static errors never start scheduling.
