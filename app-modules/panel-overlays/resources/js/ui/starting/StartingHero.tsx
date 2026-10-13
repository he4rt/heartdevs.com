import CountdownDisplay from './CountdownDisplay';

export interface StartingHeroProps {
    title: string;
    remainingMs: number;
    totalMs: number;
    done: boolean;
}

export function StartingHero({ title, remainingMs, totalMs, done }: StartingHeroProps) {
    return (
        <div className="absolute left-[800px] top-[150px] flex items-stretch gap-[30px]">
            <div className="w-[10px] flex-none rounded-full bg-yellow shadow-[0_0_22px_rgba(255,203,5,.55)]" />

            <div className="flex flex-col">
                <div className="flex w-fit items-center gap-[10px] rounded-full border border-brand-light/35 bg-ink-900/70 px-[18px] py-[8px] backdrop-blur-[4px]">
                    <span
                        className={`h-[10px] w-[10px] rounded-full ${done ? 'bg-yellow shadow-[0_0_12px_#ffcb05]' : 'bg-brand shadow-[0_0_12px_#8b2fe8]'}`}
                        style={{ animation: 'ssBlink 1.4s ease-in-out infinite' }}
                    />
                    <span className="font-saira-cond text-[18px] font-bold tracking-[.22em] text-brand-pale">
                        {done ? 'AO VIVO' : 'EM BREVE'}
                    </span>
                    <span className="font-saira-cond text-[18px] font-bold tracking-[.22em] text-brand-faint">
                        · TWITCH.TV/DANIELHE4RT
                    </span>
                </div>

                <h1 className="mt-[22px] font-saira-semi text-[64px] font-extrabold italic uppercase leading-[.95] tracking-[-.01em] text-white">
                    {done ? (
                        <>
                            É <span className="text-yellow">agora</span>!
                        </>
                    ) : (
                        <>
                            A live vai <span className="text-brand-light">começar</span> em
                        </>
                    )}
                </h1>

                <div className="mt-[14px]">
                    <CountdownDisplay remainingMs={remainingMs} totalMs={totalMs} done={done} />
                </div>

                <div className="mt-[34px] flex items-center gap-[22px]">
                    <div className="h-0 w-0 border-y-[16px] border-l-[22px] border-y-transparent border-l-yellow" />
                    <div className="flex flex-col">
                        <span className="font-saira-cond text-[20px] font-bold tracking-[.32em] text-yellow">
                            PAUTA DE HOJE
                        </span>
                        <span className="font-saira-semi text-[42px] font-extrabold italic leading-[1.05] text-brand-pale">
                            {title}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default StartingHero;
