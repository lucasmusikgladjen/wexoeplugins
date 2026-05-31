'use client';

import { ContactFormState } from '@/lib/contact-form-types';
import EditorSection from '@/components/editors/EditorSection';
import ContactFormEditor from './ContactFormEditor';

interface Props {
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
  state: ContactFormState;
  onChange: (s: ContactFormState) => void;
}

/**
 * Top-level "Kontaktformulär"-sektion för alla page-typer. Använder samma
 * EditorSection-header som Hero, Innehåll osv. så formuläret syns lika
 * tydligt i sektion-listan som övriga editors.
 */
export default function ContactFormSection({ visible, onToggleVisible, state, onChange }: Props) {
  return (
    <EditorSection title="Kontaktformulär" visible={visible} onToggleVisible={onToggleVisible}>
      <ContactFormEditor state={state} onChange={onChange} />
    </EditorSection>
  );
}
