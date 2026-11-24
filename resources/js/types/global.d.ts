import type { Auth } from '@/types/auth';
import type { StoreProfile } from '@/types/shop';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            store: StoreProfile;
            /** Payment simulator on: no real money changes hands. */
            demoMode: boolean;
            menuCategories: { name: string; slug: string }[];
            cartCount: number;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
