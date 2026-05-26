/**
 * Field-primitiver — delad uppsättning input-komponenter som alla editors
 * använder så redigeringsupplevelsen är identisk över sidtyper.
 *
 * Importera via namespace för konsekvent stil i nya editors:
 *
 *   import { Field } from '@/components/shared/fields';
 *   <Field.Text label="…" value={…} onChange={…} />
 *   <Field.Textarea ... />
 *   <Field.RichText ... />
 *   <Field.Select<MyType> ... />
 *   <Field.Checkbox ... />
 *   <Field.Color ... />
 *
 * De namngivna exporterna nedan re-exporteras från `components/editors/FieldInput.tsx`
 * och `components/editors/ButtonFieldset.tsx` så befintliga importer från de
 * filerna fortsätter att fungera. På sikt migreras alla editors till
 * namespace-import:en så fält-implementationerna kan flyttas till denna mapp.
 */

import {
  FieldInput,
  FieldTextarea,
  RichTextarea,
  FieldSelect,
  FieldCheckbox,
  FieldColor,
  FieldGroup,
} from '@/components/editors/FieldInput';
import ButtonFieldset from '@/components/editors/ButtonFieldset';
import LinkedRecords from './LinkedRecords';
import StringListField from './StringListField';

export const Field = {
  Text: FieldInput,
  Textarea: FieldTextarea,
  RichText: RichTextarea,
  Select: FieldSelect,
  Checkbox: FieldCheckbox,
  Color: FieldColor,
  Buttons: ButtonFieldset,
  Group: FieldGroup,
  LinkedRecords: LinkedRecords,
  StringList: StringListField,
} as const;

export {
  FieldInput,
  FieldTextarea,
  RichTextarea,
  FieldSelect,
  FieldCheckbox,
  FieldColor,
  FieldGroup,
  ButtonFieldset,
  LinkedRecords,
  StringListField,
};
