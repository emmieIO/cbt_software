import { reactive } from 'vue';

export interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
}

let nextToastId = 0;

export const toastStore = reactive({
    items: [] as Toast[],

    add(message: string, type: Toast['type'] = 'success') {
        if (this.items.some((item) => item.message === message && item.type === type)) {
            return;
        }

        const id = ++nextToastId;
        this.items.unshift({ id, message, type });
        this.items.splice(3);

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
