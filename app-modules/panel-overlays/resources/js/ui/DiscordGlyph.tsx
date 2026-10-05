export interface DiscordGlyphProps {
    size?: number;
    fill?: string;
    className?: string;
}

export function DiscordGlyph({ size = 26, fill = '#8b2fe8', className }: DiscordGlyphProps) {
    return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill={fill} className={className}>
            <path d="M20 4.4A17 17 0 0 0 15.7 3l-.2.4a13 13 0 0 1 3.7 1.2 12.7 12.7 0 0 0-11 0A12.6 12.6 0 0 1 12 3.4L11.8 3A17 17 0 0 0 7.5 4.4 18 18 0 0 0 4 17a17 17 0 0 0 5.2 2.6l.6-1a11 11 0 0 1-1.8-.9l.4-.3a12 12 0 0 0 10.2 0l.4.3a11 11 0 0 1-1.8.9l.6 1A17 17 0 0 0 23.5 17 18 18 0 0 0 20 4.4ZM9.3 14.7c-.8 0-1.5-.8-1.5-1.7s.7-1.7 1.5-1.7 1.5.8 1.5 1.7-.7 1.7-1.5 1.7Zm5.4 0c-.8 0-1.5-.8-1.5-1.7s.7-1.7 1.5-1.7 1.5.8 1.5 1.7-.7 1.7-1.5 1.7Z" />
        </svg>
    );
}

export default DiscordGlyph;
