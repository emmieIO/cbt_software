<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import Highlight from '@tiptap/extension-highlight';
import Link from '@tiptap/extension-link';
import { Mathematics } from '@tiptap/extension-mathematics';
import Placeholder from '@tiptap/extension-placeholder';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import katex from 'katex';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

type Panel = 'formula' | 'link' | null;
type FormulaMode = 'inline' | 'block';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
        compact?: boolean;
    }>(),
    {
        placeholder: 'Enter content...',
        compact: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const activePanel = ref<Panel>(null);
const formulaMode = ref<FormulaMode>('inline');
const formulaLatex = ref('');
const linkHref = ref('');
const editingFormula = ref<{ pos: number; mode: FormulaMode } | null>(null);
let migratingMath = false;

const migrateInlineMath = (instance: Editor) => {
    if (migratingMath || !instance.getText().includes('$')) return false;

    const replacements: Array<{ from: number; to: number; latex: string }> = [];
    const inlineMath = instance.schema.nodes.inlineMath;
    if (!inlineMath) return false;

    instance.state.doc.descendants((node, pos) => {
        if (!node.isText || !node.text?.includes('$')) return;

        const regex = /\$(?!\d+\$)(.+?)\$(?!\d)/g;
        let match: RegExpExecArray | null;

        while ((match = regex.exec(node.text)) !== null) {
            replacements.push({
                from: pos + match.index,
                to: pos + match.index + match[0].length,
                latex: match[1].trim(),
            });
        }
    });

    if (!replacements.length) return false;

    migratingMath = true;
    const tr = instance.state.tr;
    replacements.reverse().forEach((replacement) => {
        tr.replaceWith(replacement.from, replacement.to, inlineMath.create({ latex: replacement.latex }));
    });
    tr.setMeta('addToHistory', false);
    instance.view.dispatch(tr);
    migratingMath = false;

    return true;
};

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [3, 4],
            },
        }),
        Underline,
        Superscript,
        Subscript,
        Highlight.configure({ multicolor: false }),
        Link.configure({
            autolink: true,
            openOnClick: false,
            linkOnPaste: true,
            HTMLAttributes: {
                rel: 'noopener noreferrer',
                target: '_blank',
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        Mathematics.configure({
            inlineOptions: {
                onClick: (node, pos) => {
                    formulaMode.value = 'inline';
                    formulaLatex.value = node.attrs.latex || '';
                    editingFormula.value = { pos, mode: 'inline' };
                    activePanel.value = 'formula';
                },
            },
            blockOptions: {
                onClick: (node, pos) => {
                    formulaMode.value = 'block';
                    formulaLatex.value = node.attrs.latex || '';
                    editingFormula.value = { pos, mode: 'block' };
                    activePanel.value = 'formula';
                },
            },
            katexOptions: {
                throwOnError: false,
            },
        }),
    ],
    editorProps: {
        attributes: {
            class: props.compact ? 'rich-content-input rich-content-input-compact' : 'rich-content-input',
        },
    },
    onCreate: ({ editor }) => {
        migrateInlineMath(editor);
    },
    onUpdate: ({ editor }) => {
        if (migrateInlineMath(editor)) {
            emit('update:modelValue', editor.getHTML());
            return;
        }

        emit('update:modelValue', editor.getHTML());
    },
});

const isActive = (name: string, attrs?: Record<string, unknown>) => editor.value?.isActive(name, attrs) ?? false;
const isAligned = (alignment: string) => editor.value?.isActive({ textAlign: alignment }) ?? false;
const canUndo = computed(() => editor.value?.can().undo() ?? false);
const canRedo = computed(() => editor.value?.can().redo() ?? false);
const currentBlock = computed(() => {
    if (isActive('heading', { level: 3 })) return 'h3';
    if (isActive('heading', { level: 4 })) return 'h4';
    return 'paragraph';
});

const formulaPreview = computed(() => {
    if (!formulaLatex.value.trim()) return '';

    try {
        return katex.renderToString(formulaLatex.value.trim(), {
            displayMode: formulaMode.value === 'block',
            throwOnError: false,
        });
    } catch {
        return formulaLatex.value;
    }
});

const togglePanel = (panel: Panel) => {
    activePanel.value = activePanel.value === panel ? null : panel;
};

const openFormulaPanel = (mode: FormulaMode) => {
    formulaMode.value = mode;
    formulaLatex.value = '';
    editingFormula.value = null;
    activePanel.value = 'formula';
};

const applyFormula = () => {
    const latex = formulaLatex.value.trim();
    if (!latex || !editor.value) return;

    const chain = editor.value.chain().focus() as any;

    if (editingFormula.value) {
        if (editingFormula.value.mode === 'inline') {
            chain.updateInlineMath({ latex, pos: editingFormula.value.pos }).run();
        } else {
            chain.updateBlockMath({ latex, pos: editingFormula.value.pos }).run();
        }
    } else if (formulaMode.value === 'inline') {
        chain.insertInlineMath({ latex }).run();
    } else {
        chain.insertBlockMath({ latex }).run();
    }

    formulaLatex.value = '';
    editingFormula.value = null;
    activePanel.value = null;
};

const applyLink = () => {
    const href = linkHref.value.trim();
    if (!editor.value) return;

    if (!href) {
        editor.value.chain().focus().unsetLink().run();
        activePanel.value = null;
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href }).run();
    linkHref.value = '';
    activePanel.value = null;
};

const setBlock = (value: string) => {
    if (!editor.value) return;

    if (value === 'h3') {
        editor.value.chain().focus().toggleHeading({ level: 3 }).run();
    } else if (value === 'h4') {
        editor.value.chain().focus().toggleHeading({ level: 4 }).run();
    } else {
        editor.value.chain().focus().setParagraph().run();
    }
};

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value || editor.value.getHTML() === value) return;
        editor.value.commands.setContent(value || '', { emitUpdate: false });
        if (migrateInlineMath(editor.value)) {
            emit('update:modelValue', editor.value.getHTML());
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div class="rich-content-editor" :class="{ 'rich-content-editor-compact': compact }">
        <div class="rich-content-toolbar">
            <div v-if="!compact" class="rich-content-toolbar-group">
                <select
                    :value="currentBlock"
                    class="rich-content-select"
                    title="Text style"
                    @change="setBlock(($event.target as HTMLSelectElement).value)"
                >
                    <option value="paragraph">Paragraph</option>
                    <option value="h3">Heading</option>
                    <option value="h4">Subheading</option>
                </select>
            </div>

            <div class="rich-content-toolbar-group">
                <button type="button" title="Bold" :class="{ active: isActive('bold') }" @click="editor?.chain().focus().toggleBold().run()">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 4h7a4 4 0 010 8H7V4zm0 8h8a4 4 0 010 8H7v-8z" />
                    </svg>
                </button>
                <button type="button" title="Italic" :class="{ active: isActive('italic') }" @click="editor?.chain().focus().toggleItalic().run()">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 4h8M6 20h8M14 4L10 20" />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Underline"
                    :class="{ active: isActive('underline') }"
                    @click="editor?.chain().focus().toggleUnderline().run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3v7a6 6 0 0012 0V3M4 21h16" />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Highlight"
                    :class="{ active: isActive('highlight') }"
                    @click="editor?.chain().focus().toggleHighlight().run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 11-6 6v3h3l6-6m-3-3 5-7 6 6-7 5-4-4zM3 21h18" />
                    </svg>
                </button>
            </div>

            <div class="rich-content-toolbar-group">
                <button
                    type="button"
                    title="Superscript"
                    :class="{ active: isActive('superscript') }"
                    @click="editor?.chain().focus().toggleSuperscript().run()"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path
                            d="M3.7 8.2h2.5l2 3.1 2-3.1h2.5l-3.2 4.7 3.4 5h-2.6l-2.1-3.4L6 17.9H3.4l3.5-5-3.2-4.7ZM14 4.7c0-1.8 1.3-3 3.2-3 1.8 0 3.1 1 3.1 2.6 0 1.2-.6 2-2.1 3l-1.3.9h3.6V10h-6.6V8.5l3.1-2.2c.9-.6 1.2-1.1 1.2-1.7 0-.7-.5-1.1-1.2-1.1-.8 0-1.3.5-1.3 1.3L14 4.7Z"
                        />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Subscript"
                    :class="{ active: isActive('subscript') }"
                    @click="editor?.chain().focus().toggleSubscript().run()"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path
                            d="M3.7 5.2h2.5l2 3.1 2-3.1h2.5L9.5 9.9l3.4 5h-2.6l-2.1-3.4L6 14.9H3.4l3.5-5-3.2-4.7ZM14 15.7c0-1.8 1.3-3 3.2-3 1.8 0 3.1 1 3.1 2.6 0 1.2-.6 2-2.1 3l-1.3.9h3.6V21h-6.6v-1.5l3.1-2.2c.9-.6 1.2-1.1 1.2-1.7 0-.7-.5-1.1-1.2-1.1-.8 0-1.3.5-1.3 1.3l-1.7-.1Z"
                        />
                    </svg>
                </button>
                <button
                    v-if="!compact"
                    type="button"
                    title="Inline code"
                    :class="{ active: isActive('code') }"
                    @click="editor?.chain().focus().toggleCode().run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8 9-3 3 3 3m8-6 3 3-3 3m-2-10-4 14" />
                    </svg>
                </button>
            </div>

            <div v-if="!compact" class="rich-content-toolbar-group">
                <button
                    type="button"
                    title="Bullet list"
                    :class="{ active: isActive('bulletList') }"
                    @click="editor?.chain().focus().toggleBulletList().run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M9 6h11M9 12h11M9 18h11" />
                        <path stroke-linecap="round" d="M4 6h.01M4 12h.01M4 18h.01" stroke-width="3" />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Numbered list"
                    :class="{ active: isActive('orderedList') }"
                    @click="editor?.chain().focus().toggleOrderedList().run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M10 6h10M10 12h10M10 18h10" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h1v3M4 11h2l-2 3h2M4 17h2l-2 3h2" />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Quote"
                    :class="{ active: isActive('blockquote') }"
                    @click="editor?.chain().focus().toggleBlockquote().run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 11H4a1 1 0 00-1 1v5a1 1 0 001 1h3a1 1 0 001-1v-6Zm0 0c0-3 1-5 3-6M20 11h-4a1 1 0 00-1 1v5a1 1 0 001 1h3a1 1 0 001-1v-6Zm0 0c0-3 1-5 3-6"
                        />
                    </svg>
                </button>
            </div>

            <div v-if="!compact" class="rich-content-toolbar-group">
                <button
                    type="button"
                    title="Align left"
                    :class="{ active: isAligned('left') }"
                    @click="editor?.chain().focus().setTextAlign('left').run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M4 6h16M4 10h10M4 14h16M4 18h10" />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Align center"
                    :class="{ active: isAligned('center') }"
                    @click="editor?.chain().focus().setTextAlign('center').run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M4 6h16M7 10h10M4 14h16M7 18h10" />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Align right"
                    :class="{ active: isAligned('right') }"
                    @click="editor?.chain().focus().setTextAlign('right').run()"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M4 6h16M10 10h10M4 14h16M10 18h10" />
                    </svg>
                </button>
            </div>

            <div class="rich-content-toolbar-group">
                <button type="button" title="Link" :class="{ active: activePanel === 'link' || isActive('link') }" @click="togglePanel('link')">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10 13a5 5 0 007.07.07l2-2A5 5 0 0012 4l-1.15 1.15M14 11a5 5 0 00-7.07-.07l-2 2A5 5 0 0012 20l1.15-1.15"
                        />
                    </svg>
                </button>
                <button
                    type="button"
                    title="Inline formula"
                    :class="{ active: activePanel === 'formula' && formulaMode === 'inline' }"
                    @click="openFormulaPanel('inline')"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path
                            d="M7.2 19.5c1.2 0 2.1-.8 2.5-2.7l1.3-6.2H8.7l.4-1.9h2.3l.3-1.5c.6-2.8 2.1-4.4 4.5-4.4 1 0 1.9.3 2.5.7l-.8 1.7c-.4-.3-.9-.4-1.4-.4-1.1 0-1.8.8-2.1 2.5l-.3 1.4h3l-.4 1.9h-3l-1.3 6.3c-.7 3.2-2.4 4.6-5 4.6-1.1 0-2.1-.3-2.8-.8l.9-1.8c.5.4 1.1.6 1.7.6Z"
                        />
                    </svg>
                </button>
                <button
                    v-if="!compact"
                    type="button"
                    title="Formula block"
                    :class="{ active: activePanel === 'formula' && formulaMode === 'block' }"
                    @click="openFormulaPanel('block')"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8 8 3 4-3 4m5 0h4" />
                    </svg>
                </button>
            </div>

            <div v-if="!compact" class="rich-content-toolbar-group">
                <button type="button" title="Undo" :disabled="!canUndo" @click="editor?.chain().focus().undo().run()">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 7-5 5 5 5M4 12h9a7 7 0 017 7" />
                    </svg>
                </button>
                <button type="button" title="Redo" :disabled="!canRedo" @click="editor?.chain().focus().redo().run()">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 7 5 5-5 5m5-5h-9a7 7 0 00-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-if="activePanel === 'formula'" class="rich-content-panel">
            <div class="rich-content-panel-head">
                <div class="rich-content-segment">
                    <button type="button" :class="{ active: formulaMode === 'inline' }" @click="formulaMode = 'inline'">Inline</button>
                    <button type="button" :class="{ active: formulaMode === 'block' }" @click="formulaMode = 'block'">Block</button>
                </div>
                <button type="button" class="rich-content-panel-close" @click="activePanel = null">Close</button>
            </div>
            <div class="rich-content-formula-grid">
                <textarea v-model="formulaLatex" rows="3" class="rich-content-formula-input" placeholder="x^2 + y^2 = z^2"></textarea>
                <div class="rich-content-formula-preview" v-html="formulaPreview || 'Formula preview'" />
            </div>
            <div class="rich-content-panel-actions">
                <button type="button" class="rich-content-action-secondary" @click="formulaLatex = ''">Clear</button>
                <button type="button" class="rich-content-action-primary" :disabled="!formulaLatex.trim()" @click="applyFormula">
                    {{ editingFormula ? 'Update Formula' : 'Insert Formula' }}
                </button>
            </div>
        </div>

        <div v-if="activePanel === 'link'" class="rich-content-panel">
            <div class="rich-content-panel-head">
                <span class="rich-content-panel-title">Link</span>
                <button type="button" class="rich-content-panel-close" @click="activePanel = null">Close</button>
            </div>
            <div class="rich-content-link-row">
                <input v-model="linkHref" type="url" placeholder="https://example.com" />
                <button type="button" class="rich-content-action-primary" @click="applyLink">Apply</button>
                <button
                    type="button"
                    class="rich-content-action-secondary"
                    @click="
                        editor?.chain().focus().unsetLink().run();
                        activePanel = null;
                    "
                >
                    Remove
                </button>
            </div>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>
