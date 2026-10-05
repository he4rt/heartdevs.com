import type React from 'react';

export interface FooterBarProps {
    children?: React.ReactNode;
}

export function FooterBar({ children }: FooterBarProps) {
    return (
        <div className="absolute right-0 bottom-0 left-0 h-[122px] border-t border-brand/22 bg-[linear-gradient(0deg,var(--color-ink-900)_0%,var(--color-ink-850)_100%)]">
            {children}
        </div>
    );
}

export default FooterBar;
