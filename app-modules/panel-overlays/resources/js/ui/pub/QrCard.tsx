import type { SlidoEventPayload } from '../../types';

export interface QrCardProps {
    event: SlidoEventPayload | null;
    qrSvg: string | null;
}

export function QrCard({ event, qrSvg }: QrCardProps) {
    return (
        <div className="absolute right-[12px] bottom-[12px] flex h-[238px] w-[420px] items-center gap-5 overflow-hidden rounded-[22px] border border-yellow/40 bg-[linear-gradient(150deg,var(--color-brand-deep)_0%,var(--color-ink-950)_100%)] p-4">
            <div
                className="h-[196px] w-[196px] flex-none overflow-hidden rounded-[14px] bg-white p-2 [&_svg]:h-full [&_svg]:w-full"
                dangerouslySetInnerHTML={qrSvg ? { __html: qrSvg } : undefined}
            />
            <div className="flex min-w-0 flex-col gap-2">
                <span className="font-saira-cond text-[15px] font-bold tracking-[.16em] text-yellow">MANDE A SUA</span>
                <span className="font-saira-semi text-[30px] font-extrabold leading-none text-brand-pale">
                    PERGUNTA
                </span>
                {event ? (
                    <div className="mt-2 flex flex-col font-saira-cond leading-tight">
                        <span className="text-[16px] tracking-[.08em] text-brand-light">ou em slido.com</span>
                        <span className="text-[26px] font-bold tracking-[.04em] text-yellow">#{event.code}</span>
                    </div>
                ) : null}
            </div>
        </div>
    );
}

export default QrCard;
