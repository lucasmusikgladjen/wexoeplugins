'use client';

import { useEffect } from 'react';

interface Props {
  active: boolean;
}

export default function UnsavedChangesGuard({ active }: Props) {
  useEffect(() => {
    if (!active) return;

    const handler = (event: BeforeUnloadEvent) => {
      event.preventDefault();
      event.returnValue = '';
    };

    window.addEventListener('beforeunload', handler);
    return () => window.removeEventListener('beforeunload', handler);
  }, [active]);

  return null;
}
