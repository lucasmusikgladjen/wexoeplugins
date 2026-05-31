import React from 'react';

/** Inline markdown renderer matching the PHP plugin's `wexoe_pa_md`:
 *  bold (**text**, __text__), italic (*text*, _text_), links ([text](url)),
 *  inline code (`code`), strikethrough (~~text~~). Returns React nodes so we
 *  never hand `dangerouslySetInnerHTML` raw user input. Newlines are preserved
 *  as <br/>; blank lines split paragraphs (the caller chooses the wrapping). */

type Node = React.ReactNode;

interface Token {
  type: 'bold' | 'italic' | 'code' | 'strike' | 'link';
  inner: string;
  href?: string;
  start: number;
  end: number;
}

const PATTERNS: { type: Token['type']; re: RegExp; capture: (m: RegExpExecArray) => Omit<Token, 'start' | 'end' | 'type'> }[] = [
  // Links first — must take precedence over bracket-only matches.
  {
    type: 'link',
    re: /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
    capture: (m) => ({ inner: m[1], href: m[2] }),
  },
  { type: 'bold', re: /\*\*(.+?)\*\*/g, capture: (m) => ({ inner: m[1] }) },
  { type: 'bold', re: /__(.+?)__/g, capture: (m) => ({ inner: m[1] }) },
  { type: 'strike', re: /~~(.+?)~~/g, capture: (m) => ({ inner: m[1] }) },
  { type: 'code', re: /`([^`]+)`/g, capture: (m) => ({ inner: m[1] }) },
  // Italic last so it doesn't eat bold markers.
  { type: 'italic', re: /(?<!\*)\*(?!\*)([^*\n]+?)\*(?!\*)/g, capture: (m) => ({ inner: m[1] }) },
  { type: 'italic', re: /(?<![\w_])_(?!_)([^_\n]+?)_(?![\w_])/g, capture: (m) => ({ inner: m[1] }) },
];

/** Find the leftmost non-overlapping match across all patterns. */
function nextToken(text: string, from: number): Token | null {
  let best: Token | null = null;
  for (const { type, re, capture } of PATTERNS) {
    re.lastIndex = from;
    const m = re.exec(text);
    if (!m) continue;
    if (best && m.index >= best.start) continue;
    const captured = capture(m);
    best = { type, inner: captured.inner, href: captured.href, start: m.index, end: m.index + m[0].length };
  }
  return best;
}

function renderTokenChildren(text: string, keyPrefix: string): Node[] {
  const out: Node[] = [];
  let cursor = 0;
  let i = 0;
  while (cursor < text.length) {
    const tok = nextToken(text, cursor);
    if (!tok) {
      out.push(text.slice(cursor));
      break;
    }
    if (tok.start > cursor) out.push(text.slice(cursor, tok.start));
    const inner = renderTokenChildren(tok.inner, `${keyPrefix}-${i}`);
    const key = `${keyPrefix}-${i++}`;
    switch (tok.type) {
      case 'bold':
        out.push(<strong key={key}>{inner}</strong>);
        break;
      case 'italic':
        out.push(<em key={key}>{inner}</em>);
        break;
      case 'strike':
        out.push(<del key={key}>{inner}</del>);
        break;
      case 'code':
        out.push(<code key={key}>{inner}</code>);
        break;
      case 'link':
        out.push(
          <a key={key} href={tok.href} target="_blank" rel="noopener noreferrer">
            {inner}
          </a>,
        );
        break;
    }
    cursor = tok.end;
  }
  return out;
}

/** Render inline markdown to React nodes. Single newlines become <br/> to
 *  match the PHP plugin's `wexoe_pa_md` (which ends with `nl2br`). Callers
 *  that pre-split on newlines (bullet lists) pass single lines and get the
 *  same result as before. */
export function renderInlineMarkdown(text: string): Node[] {
  if (text.indexOf('\n') === -1) return renderTokenChildren(text, 'm');
  const out: Node[] = [];
  const lines = text.split('\n');
  lines.forEach((line, i) => {
    if (i > 0) out.push(<br key={`br-${i}`} />);
    out.push(...renderTokenChildren(line, `m-${i}`));
  });
  return out;
}

/** Render a multi-line markdown string: blank lines split paragraphs, single
 *  newlines become <br/>. Returns React fragments — caller chooses the outer
 *  wrapper so styles compose with surrounding layout. */
export function renderMarkdown(text: string): Node {
  if (!text) return null;
  const paragraphs = text.split(/\n\s*\n/);
  return (
    <>
      {paragraphs.map((p, pi) => {
        const lines = p.split('\n');
        return (
          <p key={pi} style={{ margin: pi === 0 ? 0 : '1em 0 0 0' }}>
            {lines.map((line, li) => (
              <React.Fragment key={li}>
                {li > 0 && <br />}
                {renderInlineMarkdown(line)}
              </React.Fragment>
            ))}
          </p>
        );
      })}
    </>
  );
}
