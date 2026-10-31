import type { SlidoQuestionPayload } from '../../types';

export interface QuestionsPanelProps {
    highlighted: SlidoQuestionPayload | null;
    queue: SlidoQuestionPayload[];
    pending: number;
    isConfigured: boolean;
}

function authorOf(question: SlidoQuestionPayload): string {
    return question.authorName ?? 'Anônimo';
}

function HighlightedQuestion({ question }: { question: SlidoQuestionPayload }) {
    const reply = question.replies.find((candidate) => candidate.isFromModerator) ?? question.replies[0] ?? null;

    return (
        <div className="flex h-full flex-col justify-center gap-3 animate-[chatIn_.4s_ease-out]">
            <p className="line-clamp-3 font-saira-semi text-[34px] font-bold leading-[1.15] text-brand-pale">
                {question.text}
            </p>
            <div className="flex items-center gap-3 font-saira-cond text-[18px] font-bold tracking-[.08em] text-brand-light">
                <span>{authorOf(question).toUpperCase()}</span>
                {question.score > 0 ? <span className="text-yellow">▲ {question.score}</span> : null}
            </div>
            {reply ? (
                <p className="line-clamp-2 border-l-4 border-yellow pl-3 font-saira text-[20px] leading-snug text-brand-muted">
                    {reply.text}
                </p>
            ) : null}
        </div>
    );
}

function QueuedQuestions({ queue }: { queue: SlidoQuestionPayload[] }) {
    return (
        <ul className="flex h-full flex-col justify-center gap-3">
            {queue.map((question) => (
                <li key={question.id} className="flex items-baseline gap-3 animate-[chatIn_.4s_ease-out]">
                    <span className="w-[54px] flex-none text-right font-saira-cond text-[20px] font-bold text-yellow">
                        ▲ {question.score}
                    </span>
                    <span className="line-clamp-1 font-saira-semi text-[24px] font-semibold text-brand-pale">
                        {question.text}
                    </span>
                    <span className="flex-none font-saira-cond text-[16px] tracking-[.08em] text-brand-faint">
                        {authorOf(question)}
                    </span>
                </li>
            ))}
        </ul>
    );
}

function EmptyState({ isConfigured }: { isConfigured: boolean }) {
    return (
        <div className="flex h-full items-center font-saira-semi text-[26px] font-semibold text-brand-muted">
            {isConfigured
                ? 'Nenhuma pergunta ainda. Aponta a câmera do celular pro QR Code ➜'
                : 'Configure o evento do Slido na cena He4rt Pub do painel.'}
        </div>
    );
}

export function QuestionsPanel({ highlighted, queue, pending, isConfigured }: QuestionsPanelProps) {
    const title = highlighted ? 'PERGUNTA NO AR' : 'PERGUNTAS DA GALERA';

    return (
        <div className="absolute left-[756px] right-[444px] bottom-[12px] h-[238px] overflow-hidden rounded-[22px] border border-brand/30 bg-[linear-gradient(180deg,var(--color-ink-850)_0%,var(--color-ink-950)_100%)] px-7 pt-[58px] pb-5">
            <div className="absolute left-5 top-4 flex items-center gap-2 rounded-full border border-brand-light/[.28] bg-[rgba(20,10,31,.55)] px-[14px] py-[7px]">
                <span className="h-2 w-2 rounded-full bg-yellow shadow-[0_0_10px_var(--color-yellow)]" />
                <span className="font-saira-cond text-[14px] font-bold tracking-[.14em] text-brand-pale">{title}</span>
            </div>
            {pending > 0 ? (
                <div className="absolute right-5 top-5 font-saira-cond text-[14px] font-bold tracking-[.14em] text-brand-faint">
                    {pending} NA FILA
                </div>
            ) : null}

            {highlighted ? (
                <HighlightedQuestion question={highlighted} />
            ) : queue.length > 0 ? (
                <QueuedQuestions queue={queue} />
            ) : (
                <EmptyState isConfigured={isConfigured} />
            )}
        </div>
    );
}

export default QuestionsPanel;
