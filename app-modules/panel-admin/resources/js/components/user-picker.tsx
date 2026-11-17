import { Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useState, type ReactElement } from 'react';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { lookup } from '@/wayfinder/routes/namespaced/panel-admin/users';
import { cn } from 'cn';

export type UserOption = He4rt.PanelAdmin.Features.Setup.Users.Data.UserOptionData;

const SEARCH_DEBOUNCE_MS = 300;

/**
 * Searches users on the server as the admin types. With `excludeTeam`, members of that team are left out.
 */
function useUserLookup(open: boolean, search: string, excludeTeam?: string) {
    const [users, setUsers] = useState<UserOption[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setLoading(true);

            try {
                const query = { search, ...(excludeTeam ? { team: excludeTeam } : {}) };
                const response = await fetch(lookup({ query }).url, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const body: { data: UserOption[] } = await response.json();

                setUsers(body.data);
            } catch {
                if (!controller.signal.aborted) {
                    setUsers([]);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }, SEARCH_DEBOUNCE_MS);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [open, search, excludeTeam]);

    return { users, loading };
}

type UserPickerProps = {
    value: UserOption | null;
    onSelect: (user: UserOption) => void;
    excludeTeam?: string;
    disabled?: boolean;
    invalid?: boolean;
    id?: string;
    trigger?: ReactElement;
    placeholder?: string;
};

export function UserPicker({
    value,
    onSelect,
    excludeTeam,
    disabled,
    invalid,
    id,
    trigger,
    placeholder = 'Select a user',
}: UserPickerProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const { users, loading } = useUserLookup(open, search, excludeTeam);

    const select = (user: UserOption) => {
        onSelect(user);
        setOpen(false);
        setSearch('');
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                disabled={disabled}
                render={
                    trigger ?? (
                        <Button
                            id={id}
                            type="button"
                            variant="outline"
                            role="combobox"
                            aria-expanded={open}
                            aria-invalid={invalid || undefined}
                            className="w-full justify-between font-normal"
                        >
                            <span className={cn('truncate', !value && 'text-muted-foreground')}>
                                {value ? `${value.name} · ${value.email}` : placeholder}
                            </span>
                            <ChevronsUpDown className="opacity-50" />
                        </Button>
                    )
                }
            />
            <PopoverContent className="w-80 p-0" align="start">
                <Command shouldFilter={false}>
                    <CommandInput placeholder="Search by name or email..." value={search} onValueChange={setSearch} />
                    <CommandList>
                        {loading && (
                            <div className="flex justify-center py-6">
                                <Spinner />
                            </div>
                        )}
                        {!loading && <CommandEmpty>No users found</CommandEmpty>}
                        {!loading && users.length > 0 && (
                            <CommandGroup>
                                {users.map((user) => (
                                    <CommandItem key={user.id} value={user.id} onSelect={() => select(user)}>
                                        <div className="flex min-w-0 flex-col">
                                            <span className="truncate">{user.name}</span>
                                            <span className="truncate text-xs text-muted-foreground">{user.email}</span>
                                        </div>
                                        <Check
                                            className={cn(
                                                'ml-auto',
                                                value?.id === user.id ? 'opacity-100' : 'opacity-0',
                                            )}
                                        />
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
