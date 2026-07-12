/**
 * Chip-style tag input. Renders hidden `name[]` inputs per tag so a plain
 * FormData(form) capture picks the whole array up automatically.
 */
export default function tagInput({ initial = [] } = {}) {
    return {
        tags: Array.isArray(initial) ? [...initial] : [],
        draft: '',

        addFromDraft() {
            const value = this.draft.trim();
            if (value && !this.tags.includes(value)) {
                this.tags.push(value);
            }
            this.draft = '';
        },

        remove(index) {
            this.tags.splice(index, 1);
        },
    };
}
