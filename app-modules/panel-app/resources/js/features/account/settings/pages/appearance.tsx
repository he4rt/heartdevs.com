import { Head } from '@inertiajs/react';
import { Monitor, Moon, Sun } from 'lucide-react';
import { useTheme } from 'next-themes';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { SettingsLayout, SettingsSection } from '../components/settings-layout';

const THEMES = [
    { value: 'light', label: 'Light', Icon: Sun },
    { value: 'dark', label: 'Dark', Icon: Moon },
    { value: 'system', label: 'System', Icon: Monitor },
] as const;

export default function Appearance() {
    const { theme, setTheme } = useTheme();

    return (
        <>
            <Head title="Appearance settings" />
            <SettingsLayout>
                <SettingsSection title="Appearance" description="Choose how He4rt looks on this device.">
                    <ToggleGroup
                        variant="outline"
                        value={theme ? [theme] : []}
                        onValueChange={(values) => values[0] && setTheme(values[0])}
                        aria-label="Theme"
                    >
                        {THEMES.map(({ value, label, Icon }) => (
                            <ToggleGroupItem key={value} value={value} className="px-4">
                                <Icon /> {label}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                </SettingsSection>
            </SettingsLayout>
        </>
    );
}
