import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { redirect } from '@/wayfinder/routes/namespaced/panel-app/social-login';

type Props = {
    providers: App.Data.Support.EnumOptionData[];
    error?: string;
};

/**
 * A plain link, not an Inertia visit: the provider's page is outside the app.
 */
export function SocialSignIn({ providers, error }: Props) {
    return (
        <div className="flex flex-col gap-2">
            {providers.map((provider) => (
                <Button
                    key={provider.value}
                    variant="outline"
                    nativeButton={false}
                    render={<a href={redirect(provider.value).url} />}
                >
                    Continue with {provider.label}
                </Button>
            ))}
            {error && <FieldError className="text-center">{error}</FieldError>}
        </div>
    );
}
