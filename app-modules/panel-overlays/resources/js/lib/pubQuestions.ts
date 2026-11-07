import type { SlidoQuestionChanges, SlidoQuestionPayload } from '../types';

export type PubQuestionsAction =
    | { type: 'sync'; questions: SlidoQuestionPayload[] }
    | { type: 'upsert'; question: SlidoQuestionPayload }
    | { type: 'patch'; id: number; changes: SlidoQuestionChanges };

export function pubQuestionsReducer(state: SlidoQuestionPayload[], action: PubQuestionsAction): SlidoQuestionPayload[] {
    switch (action.type) {
        case 'sync':
            return action.questions.filter((question) => question.isVisible);
        case 'upsert': {
            const others = state.filter((question) => question.id !== action.question.id);
            return action.question.isVisible ? [...others, action.question] : others;
        }
        case 'patch':
            return state.map((question) => (question.id === action.id ? { ...question, ...action.changes } : question));
    }
}

export function highlightedQuestion(questions: SlidoQuestionPayload[]): SlidoQuestionPayload | null {
    return questions.find((question) => question.isHighlighted && !question.isAnswered) ?? null;
}

export function queuedQuestions(questions: SlidoQuestionPayload[], limit: number): SlidoQuestionPayload[] {
    return questions
        .filter((question) => !question.isHighlighted && !question.isAnswered)
        .sort((a, b) => b.score - a.score || a.createdAt.localeCompare(b.createdAt))
        .slice(0, limit);
}
