import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/wayfinder/routes/namespaced/panel-app/settings/preferences';
import { SettingsLayout, SettingsSection } from '../components/settings-layout';

type Props = {
    locale: string;
    themeColor: string | null;
    languages: App.Data.Support.EnumOptionData[];
};

const DEFAULT_ACCENT = '#6b3baa';

/**
 * The pages have fixed English text, so the language changes the messages the server writes: validation
 * errors and emails.
 */
export default function Preferences({ locale, themeColor, languages }: Props) {
    const form = useForm({ locale, theme_color: themeColor ?? '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(update(), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Preferences" />
            <SettingsLayout>
                <SettingsSection title="Preferences" description="Your language and the accent color of He4rt.">
                    <form onSubmit={submit} noValidate>
                        <FieldGroup>
                            <Field data-invalid={!!form.errors.locale || undefined}>
                                <FieldLabel htmlFor="locale">Language</FieldLabel>
                                <NativeSelect
                                    id="locale"
                                    value={form.data.locale}
                                    onChange={(event) => form.setData('locale', event.target.value)}
                                >
                                    {languages.map((language) => (
                                        <NativeSelectOption key={language.value} value={language.value}>
                                            {language.label}
                                        </NativeSelectOption>
                                    ))}
                                </NativeSelect>
                                <FieldDescription>Used for messages such as errors and emails.</FieldDescription>
                                <FieldError>{form.errors.locale}</FieldError>
                            </Field>
                            <Field data-invalid={!!form.errors.theme_color || undefined}>
                                <FieldLabel htmlFor="theme_color">Accent color</FieldLabel>
                                <div className="flex items-center gap-2">
                                    <Input
                                        id="theme_color"
                                        type="color"
                                        className="w-14 p-1"
                                        value={form.data.theme_color || DEFAULT_ACCENT}
                                        onChange={(event) => form.setData('theme_color', event.target.value)}
                                    />
                                    <span className="font-mono text-sm text-muted-foreground">
                                        {form.data.theme_color || 'Theme default'}
                                    </span>
                                    {form.data.theme_color && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => form.setData('theme_color', '')}
                                        >
                                            Reset
                                        </Button>
                                    )}
                                </div>
                                {!form.errors.theme_color && (
                                    <FieldDescription>Applies after you save.</FieldDescription>
                                )}
                                <FieldError>{form.errors.theme_color}</FieldError>
                            </Field>
                            <div>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <Spinner />}
                                    Save preferences
                                </Button>
                            </div>
                        </FieldGroup>
                    </form>
                </SettingsSection>
            </SettingsLayout>
        </>
    );
}
