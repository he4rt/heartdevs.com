const EDITION_PATTERNS = [/(\d{1,3})\s*[ªº°a]?\s*edi[cç][aã]o/i, /#\s*(\d{1,3})\b/, /\bpub\s+(\d{1,3})\b/i];

export function editionLabel(eventName: string): string {
    for (const pattern of EDITION_PATTERNS) {
        const match = eventName.match(pattern);

        if (match) {
            return `${Number(match[1])}ª edição`;
        }
    }

    return eventName;
}
