export type Subject = {
    id: string;
    name: string;
    slug: string;
    topics: Topic[];
    level: string;
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
    branch: string;
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
    type: 'multiple_choice' | 'short_answer' | 'true_false' | 'theory';
    difficulty: 'easy' | 'medium' | 'hard';
    image_path: string | null;
    image_url: string | null;
    version: number;
    topic: Topic;
    school_class: SchoolClass;
    creator?: {
        id: string;
        name: string;
        branch: string;
    };
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
