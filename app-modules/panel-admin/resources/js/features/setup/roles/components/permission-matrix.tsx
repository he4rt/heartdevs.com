import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Toggle, toggleVariants } from '@/components/ui/toggle';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from 'cn';

type Matrix = He4rt.PanelAdmin.Features.Setup.Roles.Data.PermissionMatrixData;
type MatrixResource = He4rt.PanelAdmin.Features.Setup.Roles.Data.PermissionResourceData;
type LevelKey = 'none' | 'read' | 'edit' | 'full';
type Level = { key: LevelKey; label: string; summary: string; actions: string[] | 'all' };

type PermissionMatrixProps = {
    matrix: Matrix;
    value: string[];
    onChange: (permissionIds: string[]) => void;
    readOnly?: boolean;
};

const LEVELS = {
    none: { key: 'none', label: 'None', summary: 'No access', actions: [] },
    read: { key: 'read', label: 'Read', summary: 'List and open records', actions: ['view_any', 'view'] },
    edit: {
        key: 'edit',
        label: 'Edit',
        summary: 'Read, create and update',
        actions: ['view_any', 'view', 'create', 'update'],
    },
    full: { key: 'full', label: 'Full', summary: 'Everything, including permanent delete', actions: 'all' },
} satisfies Record<LevelKey, Level>;

const PRESSED = 'data-pressed:border-primary/40 data-pressed:bg-primary/10 data-pressed:text-primary';

function resourceIds(resource: MatrixResource): string[] {
    return Object.values(resource.permissions);
}

function levelIds(resource: MatrixResource, level: Level): string[] {
    const actions = level.actions === 'all' ? Object.keys(resource.permissions) : level.actions;

    return actions.map((action) => resource.permissions[action]).filter((id): id is string => id !== undefined);
}

/**
 * The level whose permissions match the granted ones exactly, or null when the resource has a custom set.
 */
function currentLevel(resource: MatrixResource, selected: Set<string>): LevelKey | null {
    const grantedCount = resourceIds(resource).filter((id) => selected.has(id)).length;

    const match = Object.values(LEVELS).find((level) => {
        const ids = levelIds(resource, level);

        return ids.length === grantedCount && ids.every((id) => selected.has(id));
    });

    return match?.key ?? null;
}

function grantedSummary(matrix: Matrix, resource: MatrixResource, selected: Set<string>): string {
    return matrix.actions
        .filter((action) => {
            const id = resource.permissions[action.key];

            return id !== undefined && selected.has(id);
        })
        .map((action) => action.label)
        .join(', ');
}

function replaceIds(value: string[], removed: string[], added: string[]): string[] {
    const next = new Set(value);

    removed.forEach((id) => next.delete(id));
    added.forEach((id) => next.add(id));

    return [...next];
}

function LevelPicker({
    value,
    onSelect,
    label,
}: {
    value: LevelKey | null;
    onSelect: (level: LevelKey) => void;
    label: string;
}) {
    return (
        <ToggleGroup
            aria-label={label}
            variant="outline"
            size="sm"
            spacing={0}
            value={value ? [value] : []}
            onValueChange={(next) => {
                const [picked] = next as LevelKey[];

                if (picked) {
                    onSelect(picked);
                }
            }}
        >
            {Object.values(LEVELS).map((level) => (
                <ToggleGroupItem key={level.key} value={level.key} className={cn('w-14', PRESSED)}>
                    {level.label}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}

function ResourceRow({
    matrix,
    resource,
    selected,
    value,
    onChange,
    readOnly,
}: PermissionMatrixProps & { resource: MatrixResource; selected: Set<string> }) {
    const level = currentLevel(resource, selected);
    const [expanded, setExpanded] = useState(level === null);
    const summary = level === null ? grantedSummary(matrix, resource, selected) : LEVELS[level].summary;

    const applyLevel = (key: LevelKey) =>
        onChange(replaceIds(value, resourceIds(resource), levelIds(resource, LEVELS[key])));

    return (
        <li className="flex flex-col gap-3 px-4 py-3">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex min-w-0 flex-col">
                    <span className="flex items-center gap-2 font-medium">
                        {resource.label}
                        {level === null && <Badge variant="secondary">Custom</Badge>}
                    </span>
                    <span className="truncate text-sm text-muted-foreground">{summary}</span>
                </div>
                <div className="flex items-center gap-2">
                    {readOnly ? (
                        level !== null && <Badge variant="outline">{LEVELS[level].label}</Badge>
                    ) : (
                        <LevelPicker value={level} onSelect={applyLevel} label={`${resource.label} access level`} />
                    )}
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`${resource.label} permissions`}
                        aria-expanded={expanded}
                        onClick={() => setExpanded((open) => !open)}
                    >
                        <ChevronDown
                            className={cn(
                                'transition-transform duration-200 ease-out motion-reduce:transition-none',
                                expanded && 'rotate-180',
                            )}
                        />
                    </Button>
                </div>
            </div>
            {expanded && (
                <div className="flex flex-wrap gap-1.5 duration-150 ease-out animate-in fade-in-0 slide-in-from-top-1 motion-reduce:animate-none">
                    {matrix.actions.map((action) => {
                        const id = resource.permissions[action.key];

                        if (id === undefined) {
                            return null;
                        }

                        const granted = selected.has(id);

                        if (readOnly) {
                            return (
                                <span
                                    key={action.key}
                                    data-pressed={granted || undefined}
                                    className={cn(
                                        toggleVariants({ variant: 'outline', size: 'sm' }),
                                        'cursor-default hover:bg-transparent not-data-pressed:border-dashed not-data-pressed:text-muted-foreground',
                                        PRESSED,
                                    )}
                                >
                                    {action.label}
                                    <span className="sr-only">{granted ? ', granted' : ', not granted'}</span>
                                </span>
                            );
                        }

                        return (
                            <Toggle
                                key={action.key}
                                variant="outline"
                                size="sm"
                                pressed={granted}
                                onPressedChange={(pressed) =>
                                    onChange(replaceIds(value, pressed ? [] : [id], pressed ? [id] : []))
                                }
                                className={PRESSED}
                            >
                                {action.label}
                            </Toggle>
                        );
                    })}
                </div>
            )}
        </li>
    );
}

export function PermissionMatrix({ matrix, value, onChange, readOnly }: PermissionMatrixProps) {
    const selected = new Set(value);

    return (
        <div className="flex flex-col gap-4">
            {matrix.groups.map((group) => {
                const groupLevels = new Set(group.resources.map((resource) => currentLevel(resource, selected)));
                const sharedLevel = groupLevels.size === 1 ? ([...groupLevels][0] ?? null) : null;

                const applyToGroup = (key: LevelKey) =>
                    onChange(
                        replaceIds(
                            value,
                            group.resources.flatMap(resourceIds),
                            group.resources.flatMap((resource) => levelIds(resource, LEVELS[key])),
                        ),
                    );

                return (
                    <section key={group.key} className="overflow-hidden rounded-xl border bg-card">
                        <header className="flex min-h-11 flex-wrap items-center justify-between gap-3 border-b bg-muted/50 px-4 py-2">
                            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {group.label}
                            </span>
                            {!readOnly && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    Set all
                                    <LevelPicker
                                        value={sharedLevel}
                                        onSelect={applyToGroup}
                                        label={`${group.label} access level`}
                                    />
                                    <span className="w-7" aria-hidden="true" />
                                </div>
                            )}
                        </header>
                        <ul className="divide-y">
                            {group.resources.map((resource) => (
                                <ResourceRow
                                    key={resource.key}
                                    matrix={matrix}
                                    resource={resource}
                                    selected={selected}
                                    value={value}
                                    onChange={onChange}
                                    readOnly={readOnly}
                                />
                            ))}
                        </ul>
                    </section>
                );
            })}
        </div>
    );
}
