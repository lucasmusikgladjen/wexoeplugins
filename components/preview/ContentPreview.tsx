'use client';

import { PageState } from '@/lib/types';
import { renderInlineMarkdown, renderMarkdown } from '@/lib/markdown';
import SidebarPreview from './SidebarPreview';

interface Props {
  state: PageState;
  onClickSidebar: () => void;
  sidebarActive: boolean;
}

export default function ContentPreview({ state, onClickSidebar, sidebarActive }: Props) {
  if (!state.showContent && !state.showSidebar) return null;

  const hasSidebar = state.showSidebar && state.sidebarType !== '';
  const benefits = state.contentBenefits
    .split('\n')
    .map((b) => b.trim())
    .filter(Boolean);

  return (
    <div className="px-8 py-10 bg-white">
      <div className={`flex gap-8 ${hasSidebar ? '' : ''}`}>
        {/* Main content */}
        {state.showContent && (
          <div className={hasSidebar ? 'flex-1 min-w-0' : 'w-full'}>
            {state.contentH2 && (
              <h2
                className="text-2xl font-bold mb-4"
                style={{ color: state.colorMain }}
              >
                {state.contentH2}
              </h2>
            )}
            {state.contentText && (
              <div className="text-sm leading-relaxed text-lp-text mb-5">
                {renderMarkdown(state.contentText)}
              </div>
            )}
            {benefits.length > 0 && (
              <ul className="space-y-2">
                {benefits.map((b, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm">
                    <span
                      className="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold"
                      style={{ background: state.colorSecondary }}
                    >
                      ✓
                    </span>
                    <span>{renderInlineMarkdown(b)}</span>
                  </li>
                ))}
              </ul>
            )}
            {!state.contentH2 && !state.contentText && benefits.length === 0 && (
              <div className="text-sm text-gray-400 italic">Innehåll visas här...</div>
            )}
          </div>
        )}

        {/* Sidebar */}
        {hasSidebar && (
          <div
            className={`w-[320px] flex-shrink-0 preview-section rounded-lg ${sidebarActive ? 'active' : ''}`}
            onClick={(e) => {
              e.stopPropagation();
              onClickSidebar();
            }}
          >
            <SidebarPreview state={state} />
          </div>
        )}
      </div>
    </div>
  );
}
