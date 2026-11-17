import { usePage } from '@inertiajs/react';

/**
 * Whether a shared feature flag is on for the signed-in user. A flag that the server does not share reads as off.
 * Show a component with it; keep the permission check (`can` props) for what the user may do.
 */
export function useFeature(key: string): boolean {
    return usePage<{ features: Record<string, boolean> }>().props.features[key] ?? false;
}
