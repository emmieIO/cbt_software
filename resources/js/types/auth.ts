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
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    dashboard_url: string | null;
};
