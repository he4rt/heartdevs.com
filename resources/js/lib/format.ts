const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });

export function formatDate(value: string | null | undefined): string {
    return value ? dateFormatter.format(new Date(value)) : '—';
}

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' });

export function formatDateTime(value: string | null | undefined): string {
    return value ? dateTimeFormatter.format(new Date(value)) : '—';
}
