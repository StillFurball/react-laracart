import { Share2 } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

/**
 * Opens the device share sheet (WhatsApp, Telegram, …) where the Web Share API exists, otherwise
 * copies the link. Both outcomes are reported through the toast live region; the button is never
 * disabled, so a failed attempt can simply be retried.
 */
export default function ShareButton({
    title,
    text,
    className,
}: {
    title: string;
    text?: string;
    className?: string;
}) {
    const share = async () => {
        const url = window.location.href;

        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({ title, text, url });
            } catch (error) {
                if (
                    !(
                        error instanceof DOMException &&
                        error.name === 'AbortError'
                    )
                ) {
                    toast.error('Gagal membuka menu bagikan.');
                }
            }

            return;
        }

        try {
            await navigator.clipboard.writeText(url);
            toast.success('Tautan disalin. Tempel di chat untuk membagikan.');
        } catch {
            toast.error('Tautan gagal disalin; salin dari bilah alamat.');
        }
    };

    return (
        <Button
            type="button"
            variant="outline"
            onClick={() => void share()}
            className={className}
        >
            <Share2 aria-hidden />
            Bagikan
        </Button>
    );
}
