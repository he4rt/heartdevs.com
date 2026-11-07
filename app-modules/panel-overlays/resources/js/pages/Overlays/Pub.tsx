import { useChatMessages } from '../../hooks/useChatMessages';
import { useOverlayFeed } from '../../hooks/useOverlayFeed';
import { usePubQuestions } from '../../hooks/usePubQuestions';
import { toChatMessage } from '../../lib/overlayEvents';
import { editionLabel } from '../../lib/pubEdition';
import { highlightedQuestion, queuedQuestions } from '../../lib/pubQuestions';
import { Stage } from '../../ui/Stage';
import PubBrand from '../../ui/pub/PubBrand';
import PubChat from '../../ui/pub/PubChat';
import PubDock from '../../ui/pub/PubDock';
import type { PubPageProps } from '../../types';

const QUEUE_LIMIT = 3;

export default function PubOverlay({ scene, channel, authEndpoint, sources, recentChat, pub }: PubPageProps) {
    const chat = useChatMessages(recentChat.map(toChatMessage));
    const questions = usePubQuestions(pub?.questions ?? []);

    useOverlayFeed(
        { channel, authEndpoint, scene, sources },
        {
            onChatMessage: chat.push,
            onChatDeleted: chat.remove,
            onChatterCleared: chat.removeChatter,
            onChatCleared: chat.clear,
            onPubQuestions: questions.onSync,
            onPubQuestion: questions.onUpsert,
            onPubQuestionPatch: questions.onPatch,
            onOverlayConfig: (dto) => {
                const isPubSettingsChange = scene in dto.overlays;

                if (isPubSettingsChange) {
                    window.location.reload();
                }
            },
            onChannelReconnected: () => window.location.reload(),
        },
    );

    return (
        <Stage fillViewport>
            <PubBrand />
            <PubChat edition={pub ? editionLabel(pub.event.name) : null} messages={chat.messages} />
            <PubDock
                event={pub?.event ?? null}
                qrSvg={pub?.qrSvg ?? null}
                highlighted={highlightedQuestion(questions.questions)}
                queue={queuedQuestions(questions.questions, QUEUE_LIMIT)}
            />
        </Stage>
    );
}
