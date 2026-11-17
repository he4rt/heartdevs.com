import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/wayfinder/routes/namespaced/panel-admin/login';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(store(), { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title="Sign in" />
            <Card>
                <CardHeader>
                    <CardTitle>Sign in</CardTitle>
                    <CardDescription>Super admins only.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} noValidate>
                        <FieldGroup>
                            <Field data-invalid={!!form.errors.email || undefined}>
                                <FieldLabel htmlFor="email">Email</FieldLabel>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="username"
                                    autoFocus
                                    required
                                    value={form.data.email}
                                    aria-invalid={!!form.errors.email || undefined}
                                    onChange={(event) => form.setData('email', event.target.value)}
                                />
                                <FieldError>{form.errors.email}</FieldError>
                            </Field>
                            <Field data-invalid={!!form.errors.password || undefined}>
                                <FieldLabel htmlFor="password">Password</FieldLabel>
                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    value={form.data.password}
                                    aria-invalid={!!form.errors.password || undefined}
                                    onChange={(event) => form.setData('password', event.target.value)}
                                />
                                <FieldError>{form.errors.password}</FieldError>
                            </Field>
                            <Field orientation="horizontal">
                                <Checkbox
                                    id="remember"
                                    checked={form.data.remember}
                                    onCheckedChange={(checked) => form.setData('remember', checked === true)}
                                />
                                <FieldLabel htmlFor="remember" className="font-normal">
                                    Remember me
                                </FieldLabel>
                            </Field>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                Sign in
                            </Button>
                        </FieldGroup>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
