import { usePage } from '@inertiajs/react';

type Account = He4rt.PanelApp.Shared.Data.AccountData;

/**
 * The signed-in user of the panel, or null for a guest.
 */
export function useAccount(): Account | null {
    return usePage<{ account: Account | null }>().props.account;
}
