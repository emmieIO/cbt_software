export interface NavItem {
    name: string;
    href: string;
    icon?: any;
    active?: boolean;
    permission?: string;
    external?: boolean;
}

export interface NavSection {
    section: string;
    items: NavItem[];
}
