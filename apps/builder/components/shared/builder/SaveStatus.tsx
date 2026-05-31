'use client';

interface Props {
  /** Felmeddelande från senaste save-försök (eller validation-fel). */
  error?: string | null;
  /** True direkt efter lyckad save — visar "Sparat ✓" tills användaren ändrar något. */
  justSaved?: boolean;
  /** Liten gråtext som visas när inget annat visas och canSave är false. */
  hint?: string;
  /** Om saveknappen är aktiv. Används för att avgöra om hint ska visas. */
  canSave: boolean;
}

/**
 * Mellan-toolbar-status i builder-shellet: visar fel, "Sparat ✓",
 * eller en hint som "Slug + titel krävs". Endast ETT värde renderas åt
 * gången, prioritetsordning: error → justSaved → hint.
 */
export default function SaveStatus({ error, justSaved, hint, canSave }: Props) {
  if (error) {
    return <span className="text-xs text-red-500 truncate max-w-xs">{error}</span>;
  }
  if (justSaved) {
    return <span className="text-xs text-gray-400">Sparat ✓</span>;
  }
  if (!canSave && hint) {
    return <span className="text-xs text-gray-300">{hint}</span>;
  }
  return null;
}
