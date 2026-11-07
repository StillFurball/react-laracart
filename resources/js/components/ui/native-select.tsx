import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Plain <select> styled like Input, so it submits with <Form> by name.
 */
function NativeSelect({ className, ...props }: React.ComponentProps<'select'>) {
    return (
        <select
            data-slot="native-select"
            className={cn(
                'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                'aria-invalid:border-destructive',
                className,
            )}
            {...props}
        />
    );
}

export { NativeSelect };
