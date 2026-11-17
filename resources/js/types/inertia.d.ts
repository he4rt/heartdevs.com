import '@inertiajs/core';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            toast?: { type: App.Enums.ToastType; message: string; description?: string };
        };
    }
}
