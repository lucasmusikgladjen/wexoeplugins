import { NextResponse } from 'next/server';
import { readFileSync } from 'fs';
import { join } from 'path';
import { PageState } from '@/lib/types';
import { createRecord, createRecords, TABLE_IDS } from '@/lib/airtable';

const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;
const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;

// ── Load Airtable schema once at module level ───────────────────────────
const SCHEMA = readFileSync(
  join(process.cwd(), 'lib', 'airtable-schema.md'),
  'utf-8',
);

// ── Build the user-data payload that Claude will transform ──────────────
function buildDataPayload(state: PageState): string {
  const tabsData = state.tabs.map((tab, i) => {
    const base: Record<string, unknown> = {
      index: i + 1,
      name: tab.name,
      type: tab.type,
    };

    switch (tab.type) {
      case 'textimage':
        Object.assign(base, {
          tiH2: tab.tiH2,
          tiText: tab.tiText,
          tiBenefits: tab.tiBenefits,
          tiImage: tab.tiImage,
          tiInverted: tab.tiInverted,
        });
        break;
      case 'fullmedia':
        base.fmUrl = tab.fmUrl;
        break;
      case 'faq':
        base.faqItems = tab.faqItems
          .filter((f) => f.question || f.answer)
          .map((f) => ({ q: f.question, a: f.answer }));
        break;
      case 'calameo':
        Object.assign(base, {
          calTitle1: tab.calTitle1, calUrl1: tab.calUrl1,
          calTitle2: tab.calTitle2, calUrl2: tab.calUrl2,
          calTitle3: tab.calTitle3, calUrl3: tab.calUrl3,
        });
        break;
      case 'downloads':
        base.downloads = tab.downloads
          .filter((d) => d.name || d.fileUrl)
          .map((d) => ({
            name: d.name,
            description: d.description,
            fileUrl: d.fileUrl,
            fileType: d.fileType,
          }));
        break;
      case 'compare':
        Object.assign(base, {
          compareTitle: tab.compareTitle,
          compareColA: tab.compareColA,
          compareColB: tab.compareColB,
          compareRows: tab.compareRows
            .filter((r) => r.label || r.valueA || r.valueB)
            .map((r) => ({ label: r.label, a: r.valueA, b: r.valueB })),
        });
        break;
      case 'steps':
        Object.assign(base, {
          stepsTitle: tab.stepsTitle,
          stepsItems: tab.stepsItems
            .filter((s) => s.title || s.description)
            .map((s) => ({ title: s.title, description: s.description })),
        });
        break;
    }

    return base;
  });

  const data: Record<string, unknown> = {
    slug: state.slug,
    h1: state.h1,
    heroDescription: state.heroDescription,
    heroImage: state.heroImage,
    heroCta1Text: state.heroCta1Text,
    heroCta1Url: state.heroCta1Url,
    heroCta2Text: state.heroCta2Text,
    heroCta2Url: state.heroCta2Url,
    contentH2: state.contentH2,
    contentText: state.contentText,
    contentBenefits: state.contentBenefits,
    sidebarType: state.sidebarType,
    contactName: state.contactName,
    contactTitle: state.contactTitle,
    contactEmail: state.contactEmail,
    contactPhone: state.contactPhone,
    contactImage: state.contactImage,
    contactQuote: state.contactQuote,
    colorMain: state.colorMain,
    colorSecondary: state.colorSecondary,
    showContent: state.showContent,
    showSidebar: state.showSidebar,
    showTabs: state.showTabs,
    showContact: state.showContact,
  };

  // Include sidebar-specific fields
  if (state.sidebarType === 'case') {
    Object.assign(data, {
      caseTitle: state.caseTitle,
      caseDescription: state.caseDescription,
      caseImage: state.caseImage,
      caseOutcomes: state.caseOutcomes,
      caseCta: state.caseCta,
      caseCtaUrl: state.caseCtaUrl,
    });
  } else if (state.sidebarType === 'event') {
    Object.assign(data, {
      eventType: state.eventType,
      eventTitle: state.eventTitle,
      eventDescription: state.eventDescription,
      eventDate: state.eventDate,
      eventLocation: state.eventLocation,
      eventWebhook: state.eventWebhook,
    });
  } else if (state.sidebarType === 'leadmagnet') {
    Object.assign(data, {
      magnetTitle: state.magnetTitle,
      magnetFormat: state.magnetFormat,
      magnetDescription: state.magnetDescription,
      magnetFileUrl: state.magnetFileUrl,
      magnetWebhook: state.magnetWebhook,
    });
  } else if (state.sidebarType === 'calculator') {
    Object.assign(data, {
      calcTitle: state.calcTitle,
      calcHtml: state.calcHtml,
    });
  }

  if (tabsData.length > 0) {
    data.tabs = tabsData;
  }

  return JSON.stringify(data, null, 2);
}

// ── Expected JSON output shape from Claude ──────────────────────────────
interface TransformResult {
  landingPage: Record<string, unknown>;
  tabs: Array<Record<string, unknown>>;
  downloads: Array<{
    tabIndex: number;
    fields: Record<string, unknown>;
  }>;
}

// ── Main publish handler ────────────────────────────────────────────────
export async function POST(request: Request) {
  if (!ANTHROPIC_API_KEY) {
    return NextResponse.json(
      { error: 'ANTHROPIC_API_KEY ej konfigurerad.' },
      { status: 500 },
    );
  }
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json(
      { error: 'AIRTABLE_API_KEY ej konfigurerad.' },
      { status: 500 },
    );
  }

  try {
    const state: PageState = await request.json();

    if (!state.slug?.trim()) {
      return NextResponse.json({ error: 'Slug är obligatoriskt' }, { status: 400 });
    }
    if (!state.h1?.trim()) {
      return NextResponse.json({ error: 'H1 (rubrik) är obligatoriskt' }, { status: 400 });
    }

    const dataPayload = buildDataPayload(state);

    // ── Step 1: Claude transforms data to Airtable-ready JSON ───────
    const claudeResponse = await fetch('https://api.anthropic.com/v1/messages', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': ANTHROPIC_API_KEY,
        'anthropic-version': '2023-06-01',
      },
      body: JSON.stringify({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 4096,
        system: [
          {
            type: 'text',
            text: `Du är en datatransformerare. Du tar emot landing page-data i JSON-format och konverterar den till Airtable-redo JSON enligt schemat nedan.

${SCHEMA}

Svara med ENBART valid JSON (ingen markdown, ingen förklaring). Formatet:

{
  "landingPage": { <fältnamn>: <värde>, ... },
  "tabs": [ { <fältnamn>: <värde>, ... }, ... ],
  "downloads": [ { "tabIndex": <0-baserat index i tabs-arrayen>, "fields": { <fältnamn>: <värde>, ... } }, ... ]
}

Regler:
- Använd exakta Airtable-fältnamn från schemat ovan
- Utelämna fält med tomt värde (tomma strängar, null)
- Inkludera ALLTID boolean-fält (Show Content, Show Sidebar, etc.)
- Tabs: inkludera Name, Type, Order (1-baserat), Visa (alltid true), och typ-specifika fält
- Tabs: inkludera INTE "Landing Page"-fältet — det läggs till av backend
- Downloads: inkludera INTE "LP Tab"-fältet — det läggs till av backend
- Downloads: inkludera Visa (alltid true)
- Applicera formateringsreglerna (benefits-splitting, FAQ-format, pipe-format, etc.)`,
            cache_control: { type: 'ephemeral' },
          },
        ],
        messages: [
          {
            role: 'user',
            content: `Transformera denna data till Airtable-format:\n\n${dataPayload}`,
          },
        ],
      }),
    });

    if (!claudeResponse.ok) {
      const errData = await claudeResponse.json().catch(() => ({}));
      throw new Error(
        errData.error?.message || `Claude API error: ${claudeResponse.status}`,
      );
    }

    const claudeData = await claudeResponse.json();

    // ── Token tracking ──────────────────────────────────────────────
    const INPUT_PRICE_PER_M = 3.0;
    const OUTPUT_PRICE_PER_M = 15.0;

    const usage = claudeData.usage ?? {};
    const inputTokens = usage.input_tokens ?? 0;
    const outputTokens = usage.output_tokens ?? 0;
    const cacheWriteTokens = usage.cache_creation_input_tokens ?? 0;
    const cacheReadTokens = usage.cache_read_input_tokens ?? 0;
    const totalTokens = inputTokens + outputTokens;
    const estimatedCostUsd =
      (inputTokens / 1_000_000) * INPUT_PRICE_PER_M +
      (outputTokens / 1_000_000) * OUTPUT_PRICE_PER_M;

    // ── Parse Claude's JSON response ────────────────────────────────
    let responseText = '';
    if (claudeData.content && Array.isArray(claudeData.content)) {
      for (const block of claudeData.content) {
        if (block.type === 'text') responseText += block.text;
      }
    }

    // Strip markdown code fences if Claude added them
    responseText = responseText
      .replace(/^```(?:json)?\s*/i, '')
      .replace(/\s*```$/i, '')
      .trim();

    let transformed: TransformResult;
    try {
      transformed = JSON.parse(responseText);
    } catch {
      console.error('[publish] Claude returned invalid JSON:', responseText);
      throw new Error('Claude returnerade ogiltig JSON. Försök igen.');
    }

    // ── Step 2: Create Landing Page via Airtable REST API ───────────
    const lpRecord = await createRecord(
      AIRTABLE_API_KEY,
      TABLE_IDS.landingPages,
      transformed.landingPage,
    );

    // ── Step 3: Create Tabs (linked to Landing Page) ────────────────
    let tabRecords: Array<{ id: string; fields: Record<string, unknown> }> = [];
    if (transformed.tabs && transformed.tabs.length > 0) {
      const tabPayloads = transformed.tabs.map((tab) => ({
        fields: {
          ...tab,
          'Landing Page': [lpRecord.id],
        },
      }));
      tabRecords = await createRecords(
        AIRTABLE_API_KEY,
        TABLE_IDS.tabs,
        tabPayloads,
      );
    }

    // ── Step 4: Create Downloads (linked to Tabs) ───────────────────
    let downloadCount = 0;
    if (transformed.downloads && transformed.downloads.length > 0) {
      const dlPayloads = transformed.downloads.map((dl) => ({
        fields: {
          ...dl.fields,
          'LP Tab': [tabRecords[dl.tabIndex]?.id].filter(Boolean),
        },
      }));
      const dlRecords = await createRecords(
        AIRTABLE_API_KEY,
        TABLE_IDS.downloads,
        dlPayloads,
      );
      downloadCount = dlRecords.length;
    }

    const tokenUsage = {
      inputTokens,
      outputTokens,
      cacheWriteTokens,
      cacheReadTokens,
      totalTokens,
      estimatedCostUsd: parseFloat(estimatedCostUsd.toFixed(4)),
    };

    console.log('[publish] Token usage:', JSON.stringify(tokenUsage, null, 2));

    return NextResponse.json({
      success: true,
      recordId: lpRecord.id,
      slug: state.slug,
      tabCount: tabRecords.length,
      downloadCount,
      tokenUsage,
    });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[publish] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
