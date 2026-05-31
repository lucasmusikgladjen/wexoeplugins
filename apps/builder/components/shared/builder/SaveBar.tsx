'use client';

interface Props {
  dirty: boolean;
  saving: boolean;
  canSave: boolean;
  justSaved: boolean;
  isCreate: boolean;
  onSave: () => void;
  hint?: string;
}

export default function SaveBar({ dirty, saving, canSave, justSaved, isCreate, onSave, hint }: Props) {
  return (
    <div className="flex items-center gap-2">
      <span className="text-xs text-gray-400">
        {saving ? 'Sparar…' : justSaved ? 'Sparat ✓' : dirty ? 'Osparade ändringar' : 'Inga ändringar'}
      </span>
      {!canSave && hint && <span className="text-xs text-gray-300">{hint}</span>}
      <button
        type="button"
        onClick={onSave}
        disabled={saving || !canSave || (!dirty && !isCreate)}
        className="px-4 py-1.5 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
        style={{ background: '#11325D' }}
      >
        {saving ? (isCreate ? 'Skapar…' : 'Sparar…') : isCreate ? 'Skapa' : 'Spara'}
      </button>
    </div>
  );
}
