import katex from 'katex';

let input = '';

process.stdin.setEncoding('utf8');
process.stdin.on('data', (chunk) => {
    input += chunk;
});

process.stdin.on('end', () => {
    const expressions = JSON.parse(input || '[]');
    const rendered = expressions.map((expression) => {
        try {
            return katex.renderToString(expression.latex || '', {
                displayMode: Boolean(expression.displayMode),
                output: 'html',
                throwOnError: false,
            });
        } catch {
            return expression.latex || '';
        }
    });

    process.stdout.write(JSON.stringify(rendered));
});
