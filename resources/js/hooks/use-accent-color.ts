import { useEffect } from 'react';

const ACCENT_PROPERTIES = ['--primary', '--sidebar-primary', '--ring'];

/**
 * The user's accent color replaces the primary tokens. Without one, the theme tokens apply.
 */
export function useAccentColor(color: string | null) {
    useEffect(() => {
        const root = document.documentElement;

        ACCENT_PROPERTIES.forEach((property) =>
            color ? root.style.setProperty(property, color) : root.style.removeProperty(property),
        );
    }, [color]);
}
