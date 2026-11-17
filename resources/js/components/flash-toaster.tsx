import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';

export function FlashToaster() {
    useEffect(
        () =>
            router.on('flash', (event) => {
                const flashed = event.detail.flash.toast;

                if (flashed) {
                    toast[flashed.type](flashed.message, { description: flashed.description });
                }
            }),
        [],
    );

    return <Toaster position="bottom-right" richColors />;
}
