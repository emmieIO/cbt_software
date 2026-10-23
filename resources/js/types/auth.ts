export type User = {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: string[];
    permissions: string[];
    can_create_questions: boolean;
    can_edit_questions: boolean;
    can_generate_questions: boolean;
    can_review_curriculum: boolean;
    can_manage_knowledge_base: boolean;
    can_access_knowledge_base: boolean;
    can_approve_knowledge_documents: boolean;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    dashboard_url: string | null;
};
