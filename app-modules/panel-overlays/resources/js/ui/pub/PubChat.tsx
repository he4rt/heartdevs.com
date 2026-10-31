import type { ChatMessageDto } from '../../feed';

export interface PubChatProps {
    edition: string | null;
    messages: ChatMessageDto[];
}

const VISIBLE_MESSAGES = 8;

export function PubChat({ edition, messages }: PubChatProps) {
    return (
        <>
            <div className="absolute right-0 top-0 h-[560px] w-[560px] bg-[linear-gradient(to_left,rgba(11,11,9,.94)_0%,rgba(11,11,9,.82)_62%,transparent_100%)] [mask-image:linear-gradient(to_bottom,#000_72%,transparent)]" />

            {edition ? (
                <div className="absolute right-[34px] top-6 -rotate-[8deg] rounded-[50%] border-[3.5px] border-pub-yellow px-9 py-4 font-pub-sans text-[29px] font-bold leading-none whitespace-nowrap text-pub-yellow [text-shadow:0_2px_10px_rgba(0,0,0,.7)]">
                    {edition}
                </div>
            ) : null}

            <div className="absolute right-[34px] top-[142px] flex h-[340px] w-[420px] flex-col justify-end gap-3 overflow-hidden [mask-image:linear-gradient(to_bottom,transparent_0%,#000_22%)]">
                {messages.slice(-VISIBLE_MESSAGES).map((message) => (
                    <div
                        key={message.msgId}
                        className="font-pub-sans text-[23px] font-medium leading-[1.3] break-words text-pub-chalk [text-shadow:0_2px_10px_rgba(0,0,0,.85)] animate-[pubMessageIn_.4s_ease-out_both]"
                    >
                        <b className="block text-[18px] font-bold text-pub-yellow">{message.username}</b>
                        <span className="line-clamp-3">
                            {message.fragments.map((fragment, index) =>
                                fragment.kind === 'emote' ? (
                                    <img
                                        key={index}
                                        src={fragment.url}
                                        alt=""
                                        className="inline-block h-7 w-7 align-middle"
                                    />
                                ) : (
                                    <span key={index}>{fragment.text}</span>
                                ),
                            )}
                        </span>
                    </div>
                ))}
            </div>
        </>
    );
}

export default PubChat;
