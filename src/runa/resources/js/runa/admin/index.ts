/** Public Admin controllers and replaceable presentation; owning APIs retain business authority. */
export { default as AdminApplication } from '../../../../modules/admin/frontend/AdminApplication.vue';
export { default as AdminLayout } from '../../../../modules/admin/frontend/AdminLayout.vue';
export { default as AdminLoading } from '../../../../modules/admin/frontend/AdminLoading.vue';
export { default as AdminEntryProof } from '../../../../modules/admin/frontend/AdminEntryProof.vue';
export { default as AdminPresentationProvider } from '../../../../modules/admin/frontend/AdminPresentationProvider.vue';
export { useAdminPresentation } from '../../../../modules/admin/frontend/presentation';
export { useAdmin } from '../../../../modules/admin/frontend/useAdmin';
export { useAdminPage } from '../../../../modules/admin/frontend/useAdminPage';
export { useAdminResource } from '../../../../modules/admin/frontend/useAdminResource';
export { useAdminTable } from '../../../../modules/admin/frontend/useAdminTable';
export { default as AdminResourceTable } from '../../../../modules/admin/frontend/AdminResourceTable.vue';
export { default as AdminField } from '../../../../modules/admin/frontend/AdminField.vue';
export { default as AdminForm } from '../../../../modules/admin/frontend/AdminForm.vue';
export { default as AdminPage } from '../../../../modules/admin/frontend/AdminPage.vue';
export { default as AdminComponentBoundary } from '../../../../modules/admin/frontend/AdminComponentBoundary.vue';
export { default as AdminSectionContent } from '../../../../modules/admin/frontend/AdminSectionContent.vue';
export { default as AdminSection } from '../../../../modules/admin/frontend/AdminSection.vue';
export { default as AdminAction } from '../../../../modules/admin/frontend/AdminAction.vue';
export { useAdminForm } from '../../../../modules/admin/frontend/useAdminForm';
export { validateAdminField } from '../../../../modules/admin/frontend/fieldValidation';
export { resolveAdminComponent } from '../../../../modules/admin/frontend/components';
export type * from '../../../../modules/admin/frontend/contracts';

export { default as AdminRecordBrowser } from '../../../../modules/admin/frontend/AdminRecordBrowser.vue';

export { default as AdminOperations } from '../../../../modules/admin/frontend/AdminOperations.vue';
export { default as AdminOperationalEditor } from '../../../../modules/admin/frontend/AdminOperationalEditor.vue';
export { useAdminOperations } from '../../../../modules/admin/frontend/useAdminOperations';
export type {
  AdminOperationsController,
  AdminOperationView,
  AdminOperationCommand,
  AdminOperationData,
} from '../../../../modules/admin/frontend/useAdminOperations';

export { default as AdminOperationResult } from '../../../../modules/admin/frontend/AdminOperationResult.vue';

export { default as AdminApplicationSettings } from '../../../../modules/admin/frontend/AdminApplicationSettings.vue';
export { useAdminApplicationSettings } from '../../../../modules/admin/frontend/useAdminApplicationSettings';
export type {
  AdminApplicationSetting,
  AdminApplicationSettingsController,
} from '../../../../modules/admin/frontend/useAdminApplicationSettings';

export {
  useAdminLocale,
  adminText,
} from '../../../../modules/admin/frontend/localization';

export { default as AdminModuleCard } from '../../../../modules/admin/frontend/AdminModuleCard.vue';

export { default as AdminOperationHistory } from '../../../../modules/admin/frontend/AdminOperationHistory.vue';
