export interface Presentation {
    id: number;
    user_id: number;
    title: string;
    description?: string;
    admin_code: string;
    status: 'draft' | 'waiting' | 'active' | 'finished';
    created_at: string;
    updated_at: string;
    questions_count?: number;
}

export interface Question {
    id: number;
    presentation_id: number;
    content: {
        text: string;
        image_url?: string;
    };
    time_limit_seconds: number;
    order: number;
    group_name?: string;
    options: Option[];
}

export interface Option {
    id: number;
    question_id: number;
    text: string;
    is_correct?: boolean;
}

export interface Participant {
    id: string;
    display_name: string;
    presentation_id: number;
    score: number;
}

export interface LeaderboardEntry {
    rank: number;
    participant_id: string;
    display_name: string;
    score: number;
}

export interface QuizQuestionStartedEvent {
    question_id: number;
    content: {
        text: string;
        image_url?: string;
    };
    time_limit_seconds: number;
    options: Option[];
    started_at: string;
}

export interface QuizQuestionEndedEvent {
    question_id: number;
    correct_option_id: number;
    statistics: {
        total_answers: number;
        correct_answers: number;
        options: Array<{
            option_id: number;
            text: string;
            is_correct: boolean;
            count: number;
        }>;
    };
}

export interface LeaderboardUpdatedEvent {
    leaderboard: LeaderboardEntry[];
}

export interface QuestionGroup {
    name: string;
    questions: Question[];
}

export interface ReorderQuestionData {
    id: number;
    order: number;
    group_name?: string;
}
