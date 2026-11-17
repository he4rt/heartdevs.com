import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';

const OPTIONS = [
    { value: '', label: 'All' },
    { value: 'on', label: 'Enabled' },
    { value: 'off', label: 'Disabled' },
];

export function EnabledFilter({ value, onChange }: { value: string; onChange: (value: string) => void }) {
    return (
        <NativeSelect aria-label="Enabled" value={value} onChange={(event) => onChange(event.target.value)}>
            {OPTIONS.map((option) => (
                <NativeSelectOption key={option.value} value={option.value}>
                    {option.label}
                </NativeSelectOption>
            ))}
        </NativeSelect>
    );
}
