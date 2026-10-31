import { useCallback, useReducer } from 'react';
import type { PubQuestionDto, PubQuestionPatchDto, PubQuestionsDto } from '../feed';
import { pubQuestionsReducer } from '../lib/pubQuestions';
import type { SlidoQuestionPayload } from '../types';

export function usePubQuestions(initial: SlidoQuestionPayload[]) {
    const [questions, dispatch] = useReducer(pubQuestionsReducer, initial, (seed) =>
        pubQuestionsReducer([], { type: 'sync', questions: seed }),
    );

    const onSync = useCallback((dto: PubQuestionsDto) => dispatch({ type: 'sync', questions: dto.questions }), []);
    const onUpsert = useCallback((dto: PubQuestionDto) => dispatch({ type: 'upsert', question: dto.question }), []);
    const onPatch = useCallback(
        (dto: PubQuestionPatchDto) => dispatch({ type: 'patch', id: dto.id, changes: dto.changes }),
        [],
    );

    return { questions, onSync, onUpsert, onPatch };
}
