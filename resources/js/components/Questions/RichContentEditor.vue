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
        editor.value.commands.setContent(value || '', false);
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
                    B
                </button>
                <button type="button" title="Italic" :class="{ active: isActive('italic') }" @click="editor?.chain().focus().toggleItalic().run()">
                    I
                </button>
                <button
                    type="button"
                    title="Underline"
                    :class="{ active: isActive('underline') }"
                    @click="editor?.chain().focus().toggleUnderline().run()"
                >
                    U
                </button>
                <button
                    type="button"
                    title="Highlight"
                    :class="{ active: isActive('highlight') }"
                    @click="editor?.chain().focus().toggleHighlight().run()"
                >
                    HL
                </button>
            </div>

            <div class="rich-content-toolbar-group">
                <button
                    type="button"
                    title="Superscript"
                    :class="{ active: isActive('superscript') }"
                    @click="editor?.chain().focus().toggleSuperscript().run()"
                >
                    x²
                </button>
                <button
                    type="button"
                    title="Subscript"
                    :class="{ active: isActive('subscript') }"
                    @click="editor?.chain().focus().toggleSubscript().run()"
                >
                    x₂
                </button>
                <button
                    v-if="!compact"
                    type="button"
                    title="Inline code"
                    :class="{ active: isActive('code') }"
                    @click="editor?.chain().focus().toggleCode().run()"
                >
                    Code
                </button>
            </div>

            <div v-if="!compact" class="rich-content-toolbar-group">
                <button
                    type="button"
                    title="Bullet list"
                    :class="{ active: isActive('bulletList') }"
                    @click="editor?.chain().focus().toggleBulletList().run()"
                >
                    • List
                </button>
                <button
                    type="button"
                    title="Numbered list"
                    :class="{ active: isActive('orderedList') }"
                    @click="editor?.chain().focus().toggleOrderedList().run()"
                >
                    1. List
                </button>
                <button
                    type="button"
                    title="Quote"
                    :class="{ active: isActive('blockquote') }"
                    @click="editor?.chain().focus().toggleBlockquote().run()"
                >
                    Quote
                </button>
            </div>

            <div v-if="!compact" class="rich-content-toolbar-group">
                <button
                    type="button"
                    title="Align left"
                    :class="{ active: isAligned('left') }"
                    @click="editor?.chain().focus().setTextAlign('left').run()"
                >
                    Left
                </button>
                <button
                    type="button"
                    title="Align center"
                    :class="{ active: isAligned('center') }"
                    @click="editor?.chain().focus().setTextAlign('center').run()"
                >
                    Center
                </button>
                <button
                    type="button"
                    title="Align right"
                    :class="{ active: isAligned('right') }"
                    @click="editor?.chain().focus().setTextAlign('right').run()"
                >
                    Right
                </button>
            </div>

            <div class="rich-content-toolbar-group">
                <button type="button" title="Link" :class="{ active: activePanel === 'link' || isActive('link') }" @click="togglePanel('link')">
                    Link
                </button>
                <button
                    type="button"
                    title="Inline formula"
                    :class="{ active: activePanel === 'formula' && formulaMode === 'inline' }"
                    @click="openFormulaPanel('inline')"
                >
                    f(x)
                </button>
                <button
                    v-if="!compact"
                    type="button"
                    title="Formula block"
                    :class="{ active: activePanel === 'formula' && formulaMode === 'block' }"
                    @click="openFormulaPanel('block')"
                >
                    Formula
                </button>
            </div>

            <div v-if="!compact" class="rich-content-toolbar-group">
                <button type="button" title="Undo" :disabled="!canUndo" @click="editor?.chain().focus().undo().run()">Undo</button>
                <button type="button" title="Redo" :disabled="!canRedo" @click="editor?.chain().focus().redo().run()">Redo</button>
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
