import '@inertiajs/core';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            appName: string;
            app: He4rt.PanelAdmin.Shared.Data.AdminSharedPropsData;
        };
    }
}
