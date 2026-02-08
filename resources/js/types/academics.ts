export type Subject = {
    id: string;
    name: string;
    slug: string;
    topics: Topic[];
    [key: string]: unknown;
};

export type Topic = {
    id: string;
    name: string;
    slug: string;
    subject: Subject;
    school_class_id: string;
};

export type SchoolClass = {
    id: string;
    name: string;
    slug: string;
    level: string;
};

export type Option = {
    id?: string;
    question_id?: string;
    content: string;
    is_correct: boolean;
};

export type Question = {
    id: string;
    topic_id: string;
    school_class_id: string;
    content: string;
    explanation: string | null;
    type: 'multiple_choice' | 'true_false' | 'theory';
    difficulty: 'easy' | 'medium' | 'hard';
    version: number;
    is_active: boolean;
    topic: Topic;
    school_class: SchoolClass;
    options: Option[];
    created_at: string;
    updated_at: string;
};

export type PaginatedData<T> = {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
};
