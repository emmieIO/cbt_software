import { reactive } from 'vue';

export interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
}

export const toastStore = reactive({
    items: [] as Toast[],

    add(message: string, type: Toast['type'] = 'success') {
        const id = Date.now();
        this.items.unshift({ id, message, type });

        setTimeout(() => {
            this.remove(id);
        }, 5000);
    },

    remove(id: number) {
        const index = this.items.findIndex((item) => item.id === id);
        if (index !== -1) {
            this.items.splice(index, 1);
        }
    },
});
