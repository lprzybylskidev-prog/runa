/** Public, shell-independent imports. Keep implementation paths private to the facade. */
export { default as Avatar } from '../../components/AppAvatar.vue';
export { default as Button } from '../../components/AppButton.vue';
export { default as Tooltip } from '../../components/AppTooltip.vue';
export { default as Icon } from '../../components/AppIcon.vue';
export { default as Form } from '../../components/AppForm.vue';
export { default as Select } from '../../components/AppSelect.vue';
export { default as LoadingOverlay } from '../../components/AppLoadingOverlay.vue';
export { default as Logo } from '../../components/RunaLogo.vue';
export { default as MinimalLayout } from '../../layouts/MinimalLayout.vue';
export { default as IdentityProvider } from '../../components/AppIdentityProvider.vue';
export { default as Footer } from '../../components/AppFooter.vue';
export { defaultIdentity, resolveIdentity, useIdentity } from './identity';
export type * from './identity';
export type * from './navigation';
export type * from './preferences';
export type * from './exports';

export { default as ApplicationLayout } from '../../layouts/ApplicationLayout.vue';
export { defaultPreferenceStore } from '../../../../modules/auth/frontend/preferences/session';
export { createPageResolver, usePageResolver } from '../../lib/pageResolver';
export {
  createPreferenceStore,
  preferenceKey,
} from '../../lib/preferenceStore';
export type {
  PreferenceStore,
  PreferenceDefinition,
} from '../../lib/preferenceStore';
export { usePreference } from '../../composables/usePreference';
export type { IconName } from './icons';
export { default as ButtonGroup } from '../../components/AppButtonGroup.vue';
export type * from './controls';
export { default as Alert } from '../../components/AppAlert.vue';
export { default as Badge } from '../../components/AppBadge.vue';
export { default as Status } from '../../components/AppStatus.vue';
export { default as Popover } from '../../components/AppPopover.vue';
export { default as FormConflict } from '../../components/AppFormConflict.vue';
export { default as FormNotifications } from '../../components/AppFormNotifications.vue';
export { default as ToastViewport } from '../../components/AppToastViewport.vue';
export { useToasts } from '../../composables/useToasts';
export type * from './feedback';

export type * from './containers';
export { default as Card } from '../../components/AppCard.vue';
export { default as Divider } from '../../components/AppDivider.vue';
export { default as Accordion } from '../../components/AppAccordion.vue';
export { default as Tabs } from '../../components/AppTabs.vue';
export { default as Breadcrumbs } from '../../components/AppBreadcrumbs.vue';
export { default as Pagination } from '../../components/AppPagination.vue';
export { default as Dropdown } from '../../components/AppDropdown.vue';

export type * from './loading';
export { useStatisticsMode } from '../../composables/useStatisticsMode';
export { default as Spinner } from '../../components/AppSpinner.vue';
export { default as Skeleton } from '../../components/AppSkeleton.vue';
export { default as EmptyState } from '../../components/AppEmptyState.vue';
export { default as Progress } from '../../components/AppProgress.vue';
export { default as AsyncContent } from '../../components/AppAsyncContent.vue';
export { default as Statistic } from '../../components/AppStatistic.vue';
export { default as Statistics } from '../../components/AppStatistics.vue';

export type * from './forms';
export { useForm, laravelFieldErrors } from '../../composables/useForm';
export {
  parseDecimal,
  formatDecimal,
  serializeDecimal,
} from '../../lib/decimal';
export { default as FormSection } from '../../components/AppFormSection.vue';
export { default as FormField } from '../../components/AppFormField.vue';
export { default as TextInput } from '../../components/AppTextInput.vue';
export { default as NumberInput } from '../../components/AppNumberInput.vue';

export {
  isDateOnly,
  isTimeOnly,
  validDateRange,
  isInstant,
  resolveLocalDateTime,
  toLocalDateTime,
  formatDateOnly,
} from '../../lib/temporal';
export { validateFiles } from '../../lib/files';
export { default as DateInput } from '../../components/AppDateInput.vue';
export { default as TimeInput } from '../../components/AppTimeInput.vue';
export { default as DateTimeInput } from '../../components/AppDateTimeInput.vue';
export { default as DateRangeInput } from '../../components/AppDateRangeInput.vue';
export { default as Checkbox } from '../../components/AppCheckbox.vue';
export { default as Switch } from '../../components/AppSwitch.vue';
export { default as RadioGroup } from '../../components/AppRadioGroup.vue';
export { default as FileInput } from '../../components/AppFileInput.vue';
export { default as ColorInput } from '../../components/AppColorInput.vue';
export { default as HiddenInput } from '../../components/AppHiddenInput.vue';
export { default as TagsInput } from '../../components/AppTagsInput.vue';

export type * from './selection';

export type * from './advancedForms';
export { formatMask, parseMaskedInput } from '../../lib/mask';
export { default as MaskedInput } from '../../components/AppMaskedInput.vue';
export { default as Wizard } from '../../components/AppWizard.vue';
export { useDependentFields } from '../../composables/useDependentFields';
export { useDirtyGuard } from '../../composables/useDirtyGuard';
export { useWizard } from '../../composables/useWizard';

export type * from './dialogs';
export { default as Dialog } from '../../components/AppDialog.vue';
export { default as AlertDialog } from '../../components/AppAlertDialog.vue';
export { default as ConfirmDialog } from '../../components/AppConfirmDialog.vue';

export type * from './tables';
export { default as Table } from '../../components/AppTable.vue';
export { useTable } from '../../composables/useTable';
export {
  clientTablePage,
  normalizeTableQuery,
  createApiTableProvider,
} from '../../lib/table';

export { default as TablePreferences } from '../../components/AppTablePreferences.vue';
export { useTableViews } from '../../composables/useTableViews';
export type { TableViewsController } from '../../composables/useTableViews';
export {
  normalizeTableColumns,
  normalizeTableSnapshot,
  normalizeTableViews,
} from '../../lib/tableViews';

export { default as TableActions } from '../../components/AppTableActions.vue';
export { default as OperationPanel } from '../../components/AppOperationPanel.vue';
export { useOperation } from '../../composables/useOperation';
export type { OperationController } from '../../composables/useOperation';
export {
  createClientExportProvider,
  clientExportRows,
} from '../../lib/clientExports';
export { createCsvAdapter, downloadExport } from '../../lib/exportFiles';
export { createXlsxAdapter, createPdfAdapter } from '../../lib/binaryExports';
export { createClientPrintAdapter } from '../../lib/printTable';
export {
  selectionCount,
  selectionContains,
  toggleSelection,
  tableQueryIdentity,
} from '../../lib/tableSelection';

export {
  createApiExportProvider,
  decodeOperationStatus,
} from '../../lib/serverExports';

export { loadApplicationIdentity } from './loadIdentity';

export { ModulePage, ModuleSlot } from '../modules';

export { accountPreferences } from '../../../../modules/auth/frontend/preferences/session';
export { createAccountPreferenceStore } from '../../../../modules/auth/frontend/preferences/accountPreferences';
export { default as PreferenceFeedback } from '../../components/AppPreferenceFeedback.vue';

export { default as LinkButton } from '../../components/AppLinkButton.vue';

export { createApplication } from './createApplication';
export { default as HttpErrorLayout } from '../../layouts/HttpErrorLayout.vue';
export { default as HttpErrorContent } from '../../layouts/MinimalErrorContent.vue';
export type { HttpErrorLayoutProps } from './errors';

export { default as TextLink } from '../../components/AppTextLink.vue';

export type * from './layouts';
export type { HttpErrorContent as HttpErrorContentData } from './errors';
export type { MessageKey } from '../../locales/messages';
