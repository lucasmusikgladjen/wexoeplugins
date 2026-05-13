'use client';

import { AudienceState } from '@/lib/audience-types';
import { FieldCheckbox } from '@/components/editors/FieldInput';

interface Props {
  state: AudienceState;
  setField: <K extends keyof AudienceState>(key: K, value: AudienceState[K]) => void;
}

export default function SettingsEditor({ state, setField }: Props) {
  return (
    <div className="space-y-3">
      <h3 className="text-xl font-bold text-gray-900">Inställningar</h3>

      <FieldCheckbox
        label="Aktiv (publicerad)"
        checked={state.active}
        onChange={(v) => setField('active', v)}
      />
    </div>
  );
}
