import { ThemeProvider as NextThemesProvider } from 'next-themes';
import type { ReactNode } from 'react';

export const THEME_STORAGE_KEY = 'he4rt.appearance';

/**
 * The root view applies the theme before React loads. The data block type keeps the next-themes copy of
 * that script inert, so React does not warn about a script tag.
 */
const INERT_SCRIPT_PROPS = { type: 'application/json' };

export function ThemeProvider({ children }: { children: ReactNode }) {
    return (
        <NextThemesProvider
            attribute="class"
            storageKey={THEME_STORAGE_KEY}
            defaultTheme="dark"
            enableSystem
            disableTransitionOnChange
            scriptProps={INERT_SCRIPT_PROPS}
        >
            {children}
        </NextThemesProvider>
    );
}
