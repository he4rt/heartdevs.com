import { useChatMessages } from '../../hooks/useChatMessages';
import { useOverlayFeed } from '../../hooks/useOverlayFeed';
import { usePubQuestions } from '../../hooks/usePubQuestions';
import { toChatBubble } from '../../lib/chat';
import { toChatMessage } from '../../lib/overlayEvents';
import { highlightedQuestion, pendingCount, queuedQuestions } from '../../lib/pubQuestions';
import { CameraWindow } from '../../ui/CameraWindow';
import { Stage } from '../../ui/Stage';
import TopBar from '../../ui/TopBar';
import ChatBubble from '../../ui/chat/ChatBubble';
import ChatPanel from '../../ui/chat/ChatPanel';
import QrCard from '../../ui/pub/QrCard';
import QuestionsPanel from '../../ui/pub/QuestionsPanel';
import type { PubPageProps } from '../../types';

const QUEUE_LIMIT = 3;
const CAMERA_BOTTOM = 262;

export default function PubOverlay({ handle, channel, authEndpoint, recentChat, pub }: PubPageProps) {
    const chat = useChatMessages(recentChat.map(toChatMessage));
    const questions = usePubQuestions(pub?.questions ?? []);

    useOverlayFeed(
        { channel, authEndpoint },
        {
            onChatMessage: chat.push,
            onChatDeleted: chat.remove,
            onChatterCleared: chat.removeChatter,
            onChatCleared: chat.clear,
            onPubQuestions: questions.onSync,
            onPubQuestion: questions.onUpsert,
            onPubQuestionPatch: questions.onPatch,
        },
    );

    const edition = pub?.event.name ? `HE4RT PUB · ${pub.event.name.toUpperCase()}` : 'HE4RT PUB';

    return (
        <Stage>
            <TopBar channel={handle} />

            <ChatPanel title="CHAT AO VIVO" fullHeight>
                {chat.messages.map((message) => (
                    <ChatBubble key={message.msgId} {...toChatBubble(message)} />
                ))}
            </ChatPanel>

            <CameraWindow label={edition} bottom={CAMERA_BOTTOM} />

            <QuestionsPanel
                highlighted={highlightedQuestion(questions.questions)}
                queue={queuedQuestions(questions.questions, QUEUE_LIMIT)}
                pending={pendingCount(questions.questions)}
                isConfigured={pub !== null}
            />

            <QrCard event={pub?.event ?? null} qrSvg={pub?.qrSvg ?? null} />
        </Stage>
    );
}
