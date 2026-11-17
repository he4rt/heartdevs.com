import { Head, Link } from '@inertiajs/react';
import { buttonVariants } from '@/components/ui/button';
import { useAccount } from '@app/hooks/use-account';

type Props = {
    dashboardUrl: string;
    loginUrl: string | null;
    registerUrl: string | null;
};

export default function Welcome({ dashboardUrl, loginUrl, registerUrl }: Props) {
    const account = useAccount();

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-svh flex-col bg-background">
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between p-6">
                    <span className="flex items-center gap-2 font-semibold">
                        <span className="size-3 rotate-45 rounded-sm bg-primary" />
                        He4rt
                    </span>
                    <nav className="flex items-center gap-2">
                        {account ? (
                            <Link href={dashboardUrl} prefetch className={buttonVariants({ variant: 'outline' })}>
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                {loginUrl && (
                                    <Link href={loginUrl} prefetch className={buttonVariants({ variant: 'ghost' })}>
                                        Log in
                                    </Link>
                                )}
                                {registerUrl && (
                                    <Link
                                        href={registerUrl}
                                        prefetch
                                        className={buttonVariants({ variant: 'outline' })}
                                    >
                                        Register
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>
                <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-6 p-6">
                    <h1 className="max-w-2xl text-4xl font-semibold tracking-tight sm:text-5xl">
                        One place for your teams and their data.
                    </h1>
                    <p className="max-w-xl text-lg text-muted-foreground">
                        He4rt connects your team to the tools it already uses and keeps the work in one place.
                    </p>
                    <div>
                        <Link
                            href={account ? dashboardUrl : (registerUrl ?? loginUrl ?? dashboardUrl)}
                            prefetch
                            className={buttonVariants({ size: 'lg' })}
                        >
                            {account ? 'Open the dashboard' : 'Get started'}
                        </Link>
                    </div>
                </main>
            </div>
        </>
    );
}
