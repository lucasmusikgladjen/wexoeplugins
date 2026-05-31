'use client';

interface Props {
  onClick: () => void;
  saving: boolean;
  canSave: boolean;
  isCreate: boolean;
  /** Eget label-set, t.ex. ["Skapa", "Skapar…", "Spara", "Sparar…"]. Default
   *  är svenska "Skapa/Skapar…/Spara/Sparar…" — räcker för alla nuvarande sidtyper. */
  labels?: {
    create: string;
    creating: string;
    save: string;
    saving: string;
  };
}

const DEFAULT_LABELS = {
  create: 'Skapa',
  creating: 'Skapar…',
  save: 'Spara',
  saving: 'Sparar…',
};

/**
 * Standardiserad spara-knapp som alla page builders använder. Färg och
 * avstängd-stil är hårdkodad så knappen ser identisk ut över sidtyper.
 */
export default function SaveButton({ onClick, saving, canSave, isCreate, labels }: Props) {
  const L = labels ?? DEFAULT_LABELS;
  const text = saving
    ? (isCreate ? L.creating : L.saving)
    : (isCreate ? L.create : L.save);
  return (
    <button
      onClick={onClick}
      disabled={saving || !canSave}
      className="px-4 py-1.5 rounded-md text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
      style={{ background: '#11325D' }}
    >
      {text}
    </button>
  );
}
