/**
 * Delat FAQ-block (ARKITEKTURPLAN FAS 3).
 *
 * FAQ återanvänds av flera sidtyper men har lagrats på olika sätt i Airtable:
 *   - JSON-sträng `[{question, answer}, ...]`            (partner: `faqs`)
 *   - Q:/A:-rader, par separerade med tomrad             (cms-page/LP: `faq_items`)
 *
 * Den här modulen är den ENDA källan för FAQ-serialisering: alla sidtyper
 * läser/skriver via dessa funktioner istället för att duplicera parse/format-
 * logik (tidigare: `readFaqs`, `putFaqsJson`, `parseFaqItems`, `faqItemsToString`,
 * plus inline-parsning i previews).
 *
 * State-modellen är strukturerad `FaqItem[]` med ett lokalt `clientId` (stabilt
 * React-key som aldrig serialiseras till Airtable).
 *
 * Lagring (FAS 3, expand-contract):
 *   - JSON-spegeln (`*_json`) är den kanoniska delade formen.
 *   - Q:/A:-textformatet behålls som dual-write (PHP renderar fortfarande från
 *     det tills läsningen byts — gateat steg).
 *   - `faqItemsFromStored` är read-prefer: JSON om den finns, annars Q:/A:.
 *
 * `to*`-funktionerna tar en minimal strukturell typ så att alla sidtypers
 * item-arrayer (partner `clientId`, LP `id`, cms-page) kan skickas in oförändrat.
 */

import { asString } from './airtable-helpers';

export interface FaqItem {
  /** Stabilt React-key. Genereras lokalt — serialiseras aldrig till Airtable. */
  clientId: string;
  question: string;
  answer: string;
}

/** Minimal form som `to*`-serialiserarna behöver (kompatibel med alla FAQ-typer). */
type FaqLike = { question?: string; answer?: string };

export function newFaqClientId(): string {
  return `faq_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
}

export function emptyFaqItem(): FaqItem {
  return { clientId: newFaqClientId(), question: '', answer: '' };
}

/**
 * Normaliserar en lös array (parsad JSON e.d.) → `FaqItem[]`. Poster utan fråga
 * droppas; nya `clientId` tilldelas.
 */
export function coerceFaqItems(raw: unknown): FaqItem[] {
  if (!Array.isArray(raw)) return [];
  const items: FaqItem[] = [];
  for (const r of raw) {
    if (!r || typeof r !== 'object') continue;
    const o = r as Record<string, unknown>;
    const question = asString(o.question).trim();
    if (question === '') continue;
    items.push({ clientId: newFaqClientId(), question, answer: asString(o.answer) });
  }
  return items;
}

/**
 * `FaqItem[]` → JSON-spegeln (kanon): `[{question, answer}]`. Tom fråga filtreras,
 * `clientId` droppas. Stabil ordning = inmatningsordningen.
 */
export function faqItemsToJson(items: ReadonlyArray<FaqLike>): string {
  const filtered = (Array.isArray(items) ? items : [])
    .filter((f) => typeof f?.question === 'string' && f.question.trim() !== '')
    .map((f) => ({ question: f.question ?? '', answer: f.answer ?? '' }));
  return JSON.stringify(filtered);
}

/**
 * Parsar JSON-spegeln → `FaqItem[] | null`. `null` när värdet saknas eller inte
 * är giltig JSON-array, så callers kan falla tillbaka på Q:/A:-fältet.
 */
export function faqItemsFromJson(raw: unknown): FaqItem[] | null {
  if (typeof raw !== 'string' || raw.trim() === '') return null;
  let parsed: unknown;
  try {
    parsed = JSON.parse(raw);
  } catch {
    return null;
  }
  if (!Array.isArray(parsed)) return null;
  return coerceFaqItems(parsed);
}

/**
 * `FaqItem[]` → Q:/A:-text: `Q: fråga\nA: svar`, par separerade med tomrad.
 * Tom fråga droppas. Matchar wexoe-pages/sections/faq.php-parsern.
 */
export function faqItemsToLines(items: ReadonlyArray<FaqLike>): string {
  return (Array.isArray(items) ? items : [])
    .filter((f) => (f?.question ?? '').trim() !== '')
    .map((f) => `Q: ${f.question ?? ''}\nA: ${f.answer ?? ''}`)
    .join('\n\n');
}

/**
 * Parsar Q:/A:-text → `FaqItem[]`. Parar varje `Q:`-rad med följande `A:`-rad
 * (case-insensitive, valfritt mellanslag efter kolon). Poster utan fråga droppas.
 */
export function faqItemsFromLines(raw: unknown): FaqItem[] {
  const text = asString(raw);
  if (text.trim() === '') return [];
  const items: FaqItem[] = [];
  let pendingQ: string | null = null;
  for (const lineRaw of text.split(/\r\n|\r|\n/)) {
    const line = lineRaw.trim();
    if (line === '') continue;
    const qm = /^Q\s*:\s*(.*)$/i.exec(line);
    if (qm) {
      pendingQ = qm[1].trim();
      continue;
    }
    const am = /^A\s*:\s*(.*)$/i.exec(line);
    if (am) {
      if (pendingQ !== null && pendingQ !== '') {
        items.push({ clientId: newFaqClientId(), question: pendingQ, answer: am[1].trim() });
      }
      pendingQ = null;
    }
  }
  return items;
}

/**
 * Read-prefer: JSON-spegeln om den finns, annars Q:/A:-fältet. Den delade
 * läsvägen för alla sidtyper som har båda fälten.
 */
export function faqItemsFromStored(jsonValue: unknown, linesValue: unknown): FaqItem[] {
  const fromJson = faqItemsFromJson(jsonValue);
  if (fromJson) return fromJson;
  return faqItemsFromLines(linesValue);
}
