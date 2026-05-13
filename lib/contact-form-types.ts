/**
 * Delade typer för ContactForm-editor.
 *
 * Används av UniquePageBuilder (Fas 7) och LP/PA/Audience-editorerna (Fas 9).
 */

export type ContactFormLayout = 'split' | 'centered';
export type ContactFormTheme = 'dark' | 'light';

export interface ContactFormState {
  eyebrow: string;
  title: string;
  subtitle: string;
  layout: ContactFormLayout;
  theme: ContactFormTheme;
  showCompany: boolean;
  showPhone: boolean;
  showDropdown: boolean;
  dropdownLabel: string;
  options: string; // multiline
  ctaText: string;
  messageLabel: string;
  trustSignals: string; // multiline, format **Bold** | Resten
  showContactPerson: boolean;
}

export function emptyContactFormState(): ContactFormState {
  return {
    eyebrow: '',
    title: '',
    subtitle: '',
    layout: 'split',
    theme: 'dark',
    showCompany: true,
    showPhone: true,
    showDropdown: true,
    dropdownLabel: '',
    options: '',
    ctaText: '',
    messageLabel: '',
    trustSignals: '',
    showContactPerson: true,
  };
}
