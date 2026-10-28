import { shallowRef, reactive, ref } from 'vue';
import type { Ref } from 'vue';
import { authGet } from '../entry';
import { profileResponse } from '../account';
import type { Profile } from '../account';
import type { AccountLifetime } from './context';

export function createAccountProfile(
  lifetime: AccountLifetime,
  state: {
    fields: Record<string, string>;
    naming: Ref<string>;
    files: Ref<readonly File[]>;
    removeAvatar: Ref<boolean>;
    busy: Ref<boolean>;
    failure: Ref<string>;
  },
) {
  const { fields, naming, files, removeAvatar, busy, failure } = state;
  const profile = shallowRef<Profile>();
  const detailConflicts = reactive<Record<string, string>>({});
  const avatarConflict = ref(false);
  async function loadProfile(ownWrite = false) {
    const p = profileResponse(await authGet('profile', lifetime.signal));
    if (p.public_id !== lifetime.accountId)
      throw new TypeError('Account changed.');
    if (
      lifetime.active &&
      (!profile.value || p.version >= profile.value.version)
    ) {
      const before = profile.value;
      if (before) {
        if (
          (!ownWrite || p.version > before.version + 1) &&
          p.version > before.version &&
          (files.value.length || removeAvatar.value)
        )
          avatarConflict.value = true;
        const keys = [
          'name',
          'first_name',
          'surname',
          'login',
          'email',
        ] as const;
        const oldNaming = before.name === null ? 'full' : 'single';
        const nextNaming = p.name === null ? 'full' : 'single';
        const nameKeys = ['name', 'first_name', 'surname'] as const;
        const dirtyName =
          naming.value !== oldNaming ||
          nameKeys.some((key) => (fields[key] ?? '') !== (before[key] ?? ''));
        const changedName = nameKeys.some((key) => p[key] !== before[key]);
        if (!dirtyName) naming.value = nextNaming;
        else if (
          changedName &&
          (naming.value !== nextNaming ||
            nameKeys.some((key) => (fields[key] ?? '') !== (p[key] ?? '')))
        ) {
          detailConflicts.naming = nextNaming;
          for (const key of nameKeys) detailConflicts[key] = p[key] ?? '';
        }
        for (const key of keys) {
          const groupedName = (nameKeys as readonly string[]).includes(key);
          if (
            groupedName
              ? !dirtyName
              : (fields[key] ?? '') === (before[key] ?? '')
          )
            fields[key] = p[key] ?? '';
          else if (
            !groupedName &&
            p[key] !== before[key] &&
            (fields[key] ?? '') !== (p[key] ?? '')
          )
            detailConflicts[key] = p[key] ?? '';
        }
      }
      if (
        naming.value === (p.name === null ? 'full' : 'single') &&
        ['name', 'first_name', 'surname'].every(
          (key) => (fields[key] ?? '') === (p[key as 'name'] ?? ''),
        )
      )
        for (const key of ['naming', 'name', 'first_name', 'surname'])
          delete detailConflicts[key];
      for (const key of ['login', 'email'] as const)
        if ((fields[key] ?? '') === (p[key] ?? '')) delete detailConflicts[key];
      profile.value = p;
    }
  }
  function resolveDetails(keepLocal = false) {
    if (busy.value || !profile.value) return;
    if (!keepLocal) {
      for (const key of Object.keys(detailConflicts)) {
        if (key === 'naming')
          naming.value = profile.value.name === null ? 'full' : 'single';
        else fields[key] = detailConflicts[key] ?? '';
      }
    }
    Object.keys(detailConflicts).forEach((key) => delete detailConflicts[key]);
    if (failure.value === 'conflict') failure.value = '';
  }
  function resolveAvatar(keepLocal = false) {
    if (busy.value) return;
    if (!keepLocal) {
      files.value = [];
      removeAvatar.value = false;
    }
    avatarConflict.value = false;
    if (failure.value === 'conflict') failure.value = '';
  }
  return {
    profile,
    detailConflicts,
    avatarConflict,
    loadProfile,
    resolveDetails,
    resolveAvatar,
  };
}
