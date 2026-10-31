import type { SlidoEventPayload, SlidoQuestionPayload } from '../../types';

export interface PubDockProps {
    event: SlidoEventPayload | null;
    qrSvg: string | null;
    highlighted: SlidoQuestionPayload | null;
    queue: SlidoQuestionPayload[];
}

function authorOf(question: SlidoQuestionPayload): string {
    return question.authorName ?? 'Anônimo';
}

function QuestionOnAir({ question }: { question: SlidoQuestionPayload }) {
    return (
        <>
            <div className="px-6 pt-[10px] pb-2 font-pub-sans text-[20px] font-semibold text-white">
                {authorOf(question)} pergunta
                <span className="ml-[10px] text-pub-yellow">▲ {question.score}</span>
            </div>
            <div className="bg-pub-yellow px-6 pt-[10px] pb-3">
                <p className="line-clamp-3 font-pub-cond text-[46px] leading-[1.02] font-extrabold text-pub-asphalt uppercase">
                    {question.text}
                </p>
            </div>
        </>
    );
}

function QuestionQueue({ queue }: { queue: SlidoQuestionPayload[] }) {
    return (
        <div className="px-[26px] pt-[14px] pb-4">
            <h3 className="mb-1 font-pub-cond text-[26px] font-extrabold text-pub-yellow">Próximas perguntas</h3>
            {queue.map((question) => (
                <div
                    key={question.id}
                    className="grid grid-cols-[64px_minmax(0,1fr)] items-baseline font-pub-sans text-[22px] leading-[1.55] font-medium text-white"
                >
                    <b className="font-pub-cond text-[26px] font-extrabold text-pub-yellow">▲ {question.score}</b>
                    <span className="truncate">{question.text}</span>
                </div>
            ))}
        </div>
    );
}

function EmptyDock({ isConfigured }: { isConfigured: boolean }) {
    return (
        <div className="px-7 py-[18px]">
            <strong className="block font-pub-cond text-[46px] leading-none font-extrabold text-pub-yellow uppercase">
                {isConfigured ? 'Manda tua pergunta' : 'Slido não configurado'}
            </strong>
            <span className="mt-2 block font-pub-sans text-[20px] font-medium text-white">
                {isConfigured
                    ? 'Aponta a câmera do celular pro QR ao lado.'
                    : 'Cole o link do evento na cena He4rt Pub do painel.'}
            </span>
        </div>
    );
}

export function PubDock({ event, qrSvg, highlighted, queue }: PubDockProps) {
    const contentKey = highlighted ? `live-${highlighted.id}` : queue.length > 0 ? 'queue' : 'empty';

    return (
        <div className="absolute bottom-0 left-0 flex items-end">
            {event && qrSvg ? (
                <div className="flex w-[196px] flex-none flex-col items-center gap-2 border-t-4 border-pub-yellow bg-pub-asphalt/[.93] px-[14px] pt-[14px] pb-3">
                    <div
                        className="h-[160px] w-[160px] bg-white p-[7px] [&_svg]:h-full [&_svg]:w-full"
                        dangerouslySetInnerHTML={{ __html: qrSvg }}
                    />
                    <strong className="font-pub-cond text-[24px] leading-none font-extrabold text-pub-yellow">
                        Pergunte aqui
                    </strong>
                    <span className="-mt-1 max-w-full text-center font-pub-sans text-[14px] font-medium break-all text-white">
                        slido.com #{event.code}
                    </span>
                </div>
            ) : null}

            <div
                key={contentKey}
                className="flex max-w-[1004px] min-w-[420px] flex-col border-t-4 border-l-2 border-pub-yellow border-l-pub-yellow/30 bg-pub-asphalt/[.93] animate-[pubWipe_.5s_cubic-bezier(.2,.8,.2,1)_both]"
            >
                {highlighted ? (
                    <QuestionOnAir question={highlighted} />
                ) : queue.length > 0 ? (
                    <QuestionQueue queue={queue} />
                ) : (
                    <EmptyDock isConfigured={event !== null} />
                )}
            </div>
        </div>
    );
}

export default PubDock;
