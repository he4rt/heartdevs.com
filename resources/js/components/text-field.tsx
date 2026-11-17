import type { ComponentProps, ReactNode } from 'react';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

type Props = Omit<ComponentProps<typeof Input>, 'onChange' | 'value'> & {
    id: string;
    label: string;
    value: string;
    error?: string;
    labelAction?: ReactNode;
    onValueChange: (value: string) => void;
};

/**
 * A labelled input with its validation error, the shape every account form repeats.
 */
export function TextField({ id, label, value, error, labelAction, onValueChange, ...input }: Props) {
    return (
        <Field data-invalid={!!error || undefined}>
            <div className="flex items-center">
                <FieldLabel htmlFor={id}>{label}</FieldLabel>
                {labelAction && <span className="ml-auto text-sm">{labelAction}</span>}
            </div>
            <Input
                id={id}
                name={id}
                value={value}
                aria-invalid={!!error || undefined}
                onChange={(event) => onValueChange(event.target.value)}
                {...input}
            />
            <FieldError>{error}</FieldError>
        </Field>
    );
}
