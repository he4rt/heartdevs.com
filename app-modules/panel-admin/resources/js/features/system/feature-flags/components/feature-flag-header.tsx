import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { ToneBadge } from '@/components/tone-badge';
import { buttonVariants } from '@/components/ui/button';
import { index } from '@/wayfinder/routes/namespaced/panel-admin/feature-flags';
import { HelpLink } from '@admin/components/help-link';
import { cn } from 'cn';

type Flag = He4rt.PanelAdmin.Features.System.FeatureFlags.Data.FeatureFlagData;

export function FeatureFlagHeader({ flag }: { flag: Flag }) {
    return (
        <>
            <Link
                href={index().url}
                prefetch
                className={cn(buttonVariants({ variant: 'ghost', size: 'sm' }), '-ml-2 mb-2 self-start')}
            >
                <ArrowLeft /> Feature flags
            </Link>
            <PageHeader
                heading={
                    <span className="flex flex-wrap items-center gap-2">
                        {flag.label}
                        <ToneBadge tone={flag.scope.tone}>{flag.scope.label}</ToneBadge>
                        <ToneBadge tone={flag.gateOpen ? 'success' : 'neutral'}>
                            {flag.gateOpen ? 'Gate open' : 'Gate closed'}
                        </ToneBadge>
                    </span>
                }
                description={flag.description}
                actions={<HelpLink path="system/feature-flags" />}
            />
        </>
    );
}
