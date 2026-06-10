<script setup lang="ts">
import katex from 'katex';
import { computed, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        content?: string | null;
        truncate?: boolean;
    }>(),
    {
        content: '',
        truncate: false,
    },
);

const root = ref<HTMLElement | null>(null);
const allowedTags = new Set([
    'P',
    'BR',
    'STRONG',
    'B',
    'EM',
    'I',
    'U',
    'UL',
    'OL',
    'LI',
    'SPAN',
    'DIV',
    'SUP',
    'SUB',
    'H3',
    'H4',
    'BLOCKQUOTE',
    'CODE',
    'PRE',
    'MARK',
    'A',
]);
const allowedMathTypes = new Set(['inline-math', 'block-math']);
const alignableTags = new Set(['P', 'H3', 'H4']);

const sanitizeStyle = (child: Element) => {
    const match = child
        .getAttribute('style')
        ?.toLowerCase()
        .match(/text-align:\s*(left|center|right|justify)\s*;?/);
    if (match?.[1]) {
        child.setAttribute('style', `text-align: ${match[1]}`);
        return;
    }

    child.removeAttribute('style');
};

const sanitizeLink = (child: Element) => {
    const href = child.getAttribute('href')?.trim() || '';
    if (!/^(https?:\/\/|mailto:)/i.test(href)) {
        child.removeAttribute('href');
        child.removeAttribute('target');
        child.removeAttribute('rel');
        return;
    }

    child.setAttribute('href', href);
    child.setAttribute('target', '_blank');
    child.setAttribute('rel', 'noopener noreferrer');
};

const unwrap = (node: Element) => {
    const parent = node.parentNode;
    if (!parent) return;

    while (node.firstChild) {
        parent.insertBefore(node.firstChild, node);
    }

    parent.removeChild(node);
};

const sanitizeNode = (node: ParentNode) => {
    Array.from(node.children).forEach((child) => {
        if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED'].includes(child.tagName)) {
            child.remove();
            return;
        }

        if (!allowedTags.has(child.tagName)) {
            sanitizeNode(child);
            unwrap(child);
            return;
        }

        Array.from(child.attributes).forEach((attribute) => {
            const allowed = ['data-type', 'data-latex'];
            if (alignableTags.has(child.tagName)) allowed.push('style');
            if (child.tagName === 'A') allowed.push('href', 'target', 'rel');

            if (!allowed.includes(attribute.name)) {
                child.removeAttribute(attribute.name);
            }
        });

        if (child.hasAttribute('style')) sanitizeStyle(child);
        if (child.tagName === 'A') sanitizeLink(child);

        const type = child.getAttribute('data-type');
        if (type && !allowedMathTypes.has(type)) {
            child.removeAttribute('data-type');
            child.removeAttribute('data-latex');
        }

        sanitizeNode(child);
    });
};

const convertDelimitedMath = (rootNode: ParentNode) => {
    const walker = document.createTreeWalker(rootNode, NodeFilter.SHOW_TEXT);
    const textNodes: Text[] = [];

    while (walker.nextNode()) {
        const node = walker.currentNode as Text;
        const parent = node.parentElement;
        if (
            (!node.nodeValue?.includes('$') && !node.nodeValue?.includes('\\(') && !node.nodeValue?.includes('\\[')) ||
            parent?.closest('[data-type="inline-math"], [data-type="block-math"]')
        ) {
            continue;
        }
        textNodes.push(node);
    }

    textNodes.forEach((node) => {
        const text = node.nodeValue || '';
        const fragment = document.createDocumentFragment();
        let lastIndex = 0;
        const regex = /\\\[(.+?)\\\]|\\\((.+?)\\\)|\$(?!\d+\$)(.+?)\$(?!\d)/g;
        let match: RegExpExecArray | null;

        while ((match = regex.exec(text)) !== null) {
            fragment.append(document.createTextNode(text.slice(lastIndex, match.index)));

            const math = document.createElement(match[1] ? 'div' : 'span');
            math.dataset.type = match[1] ? 'block-math' : 'inline-math';
            math.dataset.latex = (match[1] || match[2] || match[3] || '').trim();
            fragment.append(math);

            lastIndex = match.index + match[0].length;
        }

        fragment.append(document.createTextNode(text.slice(lastIndex)));
        node.parentNode?.replaceChild(fragment, node);
    });
};

const safeContent = computed(() => {
    const template = document.createElement('template');
    template.innerHTML = props.content || '';
    convertDelimitedMath(template.content);
    sanitizeNode(template.content);

    return template.innerHTML;
});

const renderMath = () => {
    if (!root.value) return;

    root.value.querySelectorAll<HTMLElement>('[data-type="inline-math"], [data-type="block-math"]').forEach((node) => {
        const latex = node.dataset.latex || node.getAttribute('data-latex') || '';
        if (!latex) return;

        try {
            katex.render(latex, node, {
                displayMode: node.dataset.type === 'block-math',
                throwOnError: false,
            });
        } catch {
            node.textContent = latex;
        }
    });
};

onMounted(renderMath);
watch(safeContent, () => requestAnimationFrame(renderMath));
</script>

<template>
    <div ref="root" class="rich-content-viewer" :class="{ 'rich-content-viewer-truncate': truncate }" v-html="safeContent" />
</template>
