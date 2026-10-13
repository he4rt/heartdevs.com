export interface ChatBadgeProps {
    url: string;
    label: string;
}

export function ChatBadge({ url, label }: ChatBadgeProps) {
    return (
        <img src={url} alt={label} title={label} className="h-[23px] w-[23px] flex-none rounded-[7px] object-contain" />
    );
}

export default ChatBadge;
