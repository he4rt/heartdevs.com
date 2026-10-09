export interface CameraWindowProps {
    label?: string;
}

export function CameraWindow({ label }: CameraWindowProps) {
    return (
        <>
            <div className="absolute left-[710px] top-[835px] bottom-0 w-[46px] bg-yellow" />
            <div className="absolute left-[710px] top-[868px] h-[170px] w-[46px] bg-black/[.18]" />

            <div
                className="absolute left-[756px] top-[12px] right-[12px] bottom-[130px] overflow-hidden rounded-[22px]"
                style={{
                    boxShadow:
                        '0 0 0 2px rgba(255,255,255,.22),0 0 0 6px rgba(20,10,31,.55),0 18px 60px rgba(0,0,0,.5)',
                }}
            >
                {label ? (
                    <div className="absolute right-[18px] top-[16px] flex items-center gap-2 rounded-full border border-brand-light/35 bg-ink-900/[.62] px-[14px] py-[7px] backdrop-blur-[4px]">
                        <span className="h-2 w-2 rounded-full bg-brand shadow-[0_0_10px_#8b2fe8]" />
                        <span className="font-saira-cond text-[14px] font-bold tracking-[.14em] text-brand-pale">
                            {label}
                        </span>
                    </div>
                ) : null}
            </div>
        </>
    );
}
